<?php

namespace EthanYehuda\CronjobManager\Helper;

use EthanYehuda\CronjobManager\Model\Cron\InstanceFactory as CronInstanceFactory;
use Psr\Log\LoggerInterface;
use Magento\Cron\Observer\ProcessCronQueueObserver;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Processor
{
    public function __construct(
        private readonly CronInstanceFactory $cronInstanceFactory,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly CacheInterface $cache,
        private readonly ConfigInterface $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Runs a scheduled job
     *
     * @param string $scheduledTime
     * @param string $currentTime
     * @param string $jobConfig
     * @param \Magento\Cron\Model\Schedule $schedule
     * @param int $groupId
     * @throws \Exception
     * @throws Ambigous <\Exception, \RuntimeException>
     * @deprecated
     */
    public function runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId)
    {
        return $this->runScheduledJob($jobConfig, $schedule);
    }

    /**
     * Runs a scheduled job
     *
     * @param string $scheduledTime
     * @param string $currentTime
     * @param string $jobConfig
     * @param \Magento\Cron\Model\Schedule $schedule
     * @param int $groupId
     * @throws \Exception
     * @throws Ambigous <\Exception, \RuntimeException>
     */
    public function runScheduledJob($jobConfig, $schedule)
    {
        $jobCode = $schedule->getJobCode();

        if (!isset($jobConfig['instance'], $jobConfig['method'])) {
            $e = new LocalizedException(__('No callbacks found'));
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
            $schedule->getResource()->save($schedule);
            throw $e;
        }

        // dynamically create cron instances
        $model = $this->cronInstanceFactory->create($jobConfig['instance']);
        $callback = [$model, $jobConfig['method']];
        if (!is_callable($callback)) {
            $e = new LocalizedException(__(
                'Invalid callback: %instance::%method can\'t be called',
                $jobConfig
            ));
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
            $schedule->getResource()->save($schedule);
            throw $e;
        }

        // Ensure we are the only process trying to run this job
        if (!$schedule->tryLockJob()) {
            throw new LocalizedException(__(
                'Unable to obtain lock for job: %jobCode',
                ['jobCode' => $jobCode]
            ));
        }

        $schedule->setExecutedAt(date('Y-m-d H:i:s', $this->dateTime->gmtTimestamp()));
        $schedule->getResource()->save($schedule);

        try {
            $this->logger->info(sprintf('Cron Job %s is run', $jobCode));
            call_user_func_array($callback, [$schedule]);
        } catch (\Throwable $e) {
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
            $schedule->getResource()->save($schedule);
            $this->logger->error(sprintf(
                'Cron Job %s has an error: %s.',
                $jobCode,
                $e->getMessage()
            ));
            if (!$e instanceof \Exception) {
                $e = new \RuntimeException(
                    'Error when running a cron job: ' . $e->getMessage(),
                    0,
                    $e
                );
            }
            throw $e;
        }

        $schedule->setStatus(Schedule::STATUS_SUCCESS)->setFinishedAt(date(
            'Y-m-d H:i:s',
            $this->dateTime->gmtTimestamp()
        ));
        $schedule->getResource()->save($schedule);
        $this->logger->info(sprintf(
            'Cron Job %s is successfully finished',
            $jobCode
        ));
    }

    public function cleanupJobs($groupId)
    {
        $currentTime = $this->dateTime->gmtTimestamp();

        $this->cache->save(
            $this->dateTime->gmtTimestamp(),
            ProcessCronQueueObserver::CACHE_KEY_LAST_HISTORY_CLEANUP_AT . $groupId,
            ['crontab'],
            null
        );

        $this->cleanupDisabledJobs($groupId);
        $historySuccess = (int)$this->getCronGroupConfigurationValue(
            $groupId,
            ProcessCronQueueObserver::XML_PATH_HISTORY_SUCCESS
        );
        $historyFailure = (int)$this->getCronGroupConfigurationValue(
            $groupId,
            ProcessCronQueueObserver::XML_PATH_HISTORY_FAILURE
        );
        $historyLifetimes = [
            Schedule::STATUS_SUCCESS =>
                $historySuccess * ProcessCronQueueObserver::SECONDS_IN_MINUTE,
            Schedule::STATUS_MISSED =>
                $historyFailure * ProcessCronQueueObserver::SECONDS_IN_MINUTE,
            Schedule::STATUS_ERROR =>
                $historyFailure * ProcessCronQueueObserver::SECONDS_IN_MINUTE,
            Schedule::STATUS_PENDING =>
                max($historyFailure, $historySuccess) * ProcessCronQueueObserver::SECONDS_IN_MINUTE,
        ];

        $jobs = $this->config->getJobs()[$groupId];
        $scheduleResource = $this->scheduleFactory->create()->getResource();
        $connection = $scheduleResource->getConnection();
        $count = 0;
        foreach ($historyLifetimes as $time) {
            $count += $connection->delete(
                $scheduleResource->getMainTable(),
                [
                    'job_code in (?)' => array_keys($jobs),
                    'created_at < ?' => $connection->formatDate($currentTime - $time)
                ]
            );
        }
        if ($count) {
            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    private function cleanupDisabledJobs($groupId)
    {
        $jobs = $this->config->getJobs();
        $jobsToCleanup = [];
        foreach ($jobs[$groupId] as $jobCode => $jobConfig) {
            if (!$this->getCronExpression($jobConfig)) {
                $jobsToCleanup[] = $jobCode;
            }
        }

        if (count($jobsToCleanup) > 0) {
            $scheduleResource = $this->scheduleFactory->create()->getResource();
            $count = $scheduleResource->getConnection()->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => Schedule::STATUS_PENDING,
                    'job_code in (?)' => $jobsToCleanup,
                ]
            );
            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    private function getCronExpression($jobConfig)
    {
        $cronExpression = null;
        if (isset($jobConfig['config_path'])) {
            $cronExpression = $this->getConfigSchedule($jobConfig) ?: null;
        }
        if (!$cronExpression) {
            if (isset($jobConfig['schedule'])) {
                $cronExpression = $jobConfig['schedule'];
            }
        }
        return $cronExpression;
    }

    private function getConfigSchedule($jobConfig)
    {
        $cronExpr = $this->scopeConfig->getValue(
            $jobConfig['config_path'],
            ScopeInterface::SCOPE_STORE
        );
        return $cronExpr;
    }

    private function getCronGroupConfigurationValue($groupId, $path)
    {
        return $this->scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
