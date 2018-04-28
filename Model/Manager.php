<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Helper\Processor;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Manager
{
    /**
     * @var Processor
     */
    private $processor;
    
    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;
    
    /**
     * @var ConfigInterface
     */
    private $config;
    
    /**
     * @var DateTime
     */
    private $dateTime;
    
    public function __construct(
        Processor $processor,
        ScheduleFactory $scheduleFactory,
        ConfigInterface $config,
        DateTime $dateTime
    ) {
        $this->processor = $processor;
        $this->scheduleFactory = $scheduleFactory;
        $this->config = $config;
        $this->dateTime = $dateTime;
    }
    
    public function createCronJob($jobCode, $time)
    {
        $filteredTime = $this->filterTimeInput($time);

        /**
         * @var $schedule \Magento\Cron\Model\Schedule
         */
        $schedule = $this->scheduleFactory->create()
            ->setJobCode($jobCode)
            ->setStatus(Schedule::STATUS_PENDING)
            ->setCreatedAt(
                strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp())
            )->setScheduledAt($filteredTime);

        $schedule->getResource()->save($schedule);

        return $schedule;
    }

    public function saveCronJob(
        $jobId,
        $jobCode = null,
        $status = null,
        $time = null
    ) {
        $schedule = $this->loadSchedule($jobId);

        if (!is_null($jobCode)) {
            $schedule->setJobCode($jobCode);
        }
        if (!is_null($status)) {
            $schedule->setStatus($status);
        }
        if (!is_null($time)) {
            $schedule->setScheduledAt($this->filterTimeInput($time));
        }

        $schedule->getResource()->save($schedule);
    }

    public function deleteCronJob($jobId)
    {
        /**
         * @var $schedule \Magento\Cron\Model\Schedule
         */
        $schedule = $this->loadSchedule($jobId);
        $schedule->getResource()->delete($schedule);
    }

    public function flushCrons()
    {
        $jobGroups = $this->config->getJobs();
        foreach ($jobGroups as $groupId => $crons) {
            $this->processor->cleanupJobs($groupId);
        }
    }

    /**
     * Dispatches cron schedule
     * 
     * @param int $jobId
     * @param string $jobCode
     * @param \Magento\Cron\Model\Schedule $schedule
     * @deprecated
     */
    public function dispatchCron($jobId = null, $jobCode, $schedule = null)
    {
        $groups = $this->config->getJobs();
        $groupId = $this->getGroupId($jobCode, $groups);
        $jobConfig = $groups[$groupId][$jobCode];
        if (is_null($schedule)) {
            $schedule = $this->loadSchedule($jobId);
        }
        $scheduledTime = $this->dateTime->gmtTimestamp();

        /* We need to trick the method into thinking it should run now so we
         * set the scheduled and current time to be equal to one another
         */
        $this->processor->runJob(
            $scheduledTime,
            $scheduledTime,
            $jobConfig,
            $schedule,
            $groupId
        );

        $schedule->getResource()->save($schedule);
    }
    
    /**
     * Dispatches cron schedule
     *
     * @param int $jobId
     * @param \Magento\Cron\Model\Schedule $schedule
     */
    public function dispatchSchedule($jobId, $schedule = null)
    {
        $groups = $this->config->getJobs();
        if (is_null($schedule)) {
            $schedule = $this->loadSchedule($jobId);
        }
        $jobCode = $schedule->getJobCode();
        $groupId = $this->getGroupId($jobCode, $groups);
        $jobConfig = $groups[$groupId][$jobCode];

        $this->processor->runScheduledJob($jobConfig, $schedule);
        $schedule->getResource()->save($schedule);
    }

    public function getCronJobs()
    {
        return $this->config->getJobs();
    }
    
    /**
     * @param String $jobCode
     * @param array | null $groups
     * @return String | Boolean $groupId
     */
    public function getGroupId($jobCode, $groups = null)
    {
        if (is_null($groups)) {
            $groups = $this->config->getJobs();
        }
        
        foreach ($groups as $groupId => $crons) {
            if (isset($crons[$jobCode])) {
                return $groupId;
            }
        }
        return false;
    }
    
    public function scheduleNow($jobCode)
    {
        $now = strftime('%Y-%m-%dT%H:%M:%S', $this->dateTime->gmtTimestamp());
        return $this->createCronJob($jobCode, $now);
    }

    // ========================= UTILITIES ========================= //

    /**
     * Generates filtered time input from user to formatted time (YYYY-MM-DD)
     *
     * @param mixed $time
     * @return string
     */
    protected function filterTimeInput($time)
    {
        $matches = [];
        preg_match('/(\d+-\d+-\d+)T(\d+:\d+)/', $time, $matches);
        $time = $matches[1] . " " . $matches[2];
        return strftime('%Y-%m-%d %H:%M:00', strtotime($time));
    }

    protected function loadSchedule($jobId)
    {
        /**
         * @var $scheduleResource \Magento\Cron\Model\ResourceModel\Schedule
         */
        $schedule = $this->scheduleFactory->create();
        $scheduleResource = $schedule->getResource();
        $scheduleResource->load($schedule, $jobId);

        if (!$schedule || !$schedule->getScheduleId()) {
            throw new NoSuchEntityException(__('No Schedule entry with ID %1.', $jobId));
        }

        return $schedule;
    }
}
