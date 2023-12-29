<?php

namespace EthanYehuda\CronjobManager\Helper;

use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
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
    /**
     * @param CronInstanceFactory $cronInstanceFactory
     * @param ScheduleFactory $scheduleFactory
     * @param CacheInterface $cache
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        private readonly CronInstanceFactory $cronInstanceFactory,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly CacheInterface $cache,
        private readonly ConfigInterface $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {
    }

    /**
     * Runs a scheduled job
     *
     * @param string $jobConfig
     * @param Schedule $schedule
     *
     * @throws LocalizedException|\RuntimeException
     */
    public function runScheduledJob($jobConfig, $schedule)
    {
        $jobCode = $schedule->getJobCode();

        if (!isset($jobConfig['instance'], $jobConfig['method'])) {
            $e = new LocalizedException(__('No callbacks found'));
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
            $this->scheduleRepository->save($schedule);
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
            $this->scheduleRepository->save($schedule);
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
        $this->scheduleRepository->save($schedule);

        try {
            $this->logger->info(sprintf('Cron Job %s is run', $jobCode));
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            call_user_func_array($callback, [$schedule]);
        } catch (\Throwable $e) {
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
            $this->scheduleRepository->save($schedule);
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
        $this->scheduleRepository->save($schedule);
        $this->logger->info(sprintf(
            'Cron Job %s is successfully finished',
            $jobCode
        ));
    }

    /**
     * Clean up jobs for a given group
     *
     * @param string $groupId
     *
     * @return void
     */
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

    /**
     * Clean up disabled jobs for a given group
     *
     * @param string $groupId
     *
     * @return void
     */
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

    /**
     * Retrieve cron expression for a job code
     *
     * @param string $jobConfig
     *
     * @return mixed|null
     */
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

    /**
     * Get configuration for the schedule
     *
     * @param string $jobConfig
     *
     * @return mixed
     */
    private function getConfigSchedule($jobConfig)
    {
        $cronExpr = $this->scopeConfig->getValue(
            $jobConfig['config_path'],
            ScopeInterface::SCOPE_STORE
        );
        return $cronExpr;
    }

    /**
     * Get configuration value for the specified cron group and path
     *
     * @param string $groupId
     * @param string $path
     *
     * @return mixed
     */
    private function getCronGroupConfigurationValue($groupId, $path)
    {
        return $this->scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
