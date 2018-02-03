<?php

Namespace EthanYehuda\CronjobManager\Model;

use Magento\Cron\Observer\ProcessCronQueueObserver;
use \Magento\Cron\Model\Schedule;

class Manager extends ProcessCronQueueObserver
{
    public function createCronJob($jobCode, $time)
    {
        $filteredTime = $this->filterTimeInput($time);

        /**
         * @var $schedule \Magento\Cron\Model\Schedule
         */
        $schedule = $this->_scheduleFactory->create()
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
        $jobGroups = $this->_config->getJobs();
        foreach ($jobGroups as $groupId => $crons) {
            $this->_cleanup($groupId);
        }
    }

    public function dispatchCron($jobId = null, $jobCode, $schedule = null)
    {
        $groups = $this->_config->getJobs();
        $groupId = $this->getGroupId($jobCode, $groups);
        $jobConfig = $groups[$groupId][$jobCode];
        if (is_null($schedule)) {
            $schedule = $this->loadSchedule($jobId);
        }
        $scheduledTime = $this->dateTime->gmtTimestamp();

        /* We need to trick the method into thinking it should run now so we
         * set the scheduled and current time to be equal to one another
         */
        $this->_runJob(
            $scheduledTime,
            $scheduledTime,
            $jobConfig,
            $schedule,
            $groupId
        );

        $schedule->getResource()->save($schedule);
    }

    public function getCronJobs()
    {
        return $this->_config->getJobs();
    }
    
    /**
     * @param String $jobCode
     * @param array | null $groups
     * @return String | Boolean $groupId
     */
    public function getGroupId($jobCode, $groups = null)
    {
        if (is_null($groups)) {
            $groups = $this->_config->getJobs();
        }
        
        foreach ($groups as $groupId => $crons) {
            if (isset($crons[$jobCode])) {
                return $groupId;
            }
        }
        return false;
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
        $schedule = $this->_scheduleFactory->create();
        $scheduleResource = $schedule->getResource();
        $scheduleResource->load($schedule, $jobId);

        if (!$schedule || !$schedule->getScheduleId()) {
            throw new NoSuchEntityException(__('No Schedule entry with ID %1.', $jobId));
        }

        return $schedule;
    }
}
