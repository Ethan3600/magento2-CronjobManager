<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;

/**
 * @deprecated
 * @see \EthanYehuda\CronjobManager\Api\ScheduleManagementInterface
 * @see \EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface
 */
class Manager
{
    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @var ScheduleRepositoryInterface
     */
    private $scheduleRepository;
    
    public function __construct(
        ScheduleManagementInterface $scheduleManagement,
        ScheduleRepositoryInterface $scheduleRepository
    ) {
        $this->scheduleManagement = $scheduleManagement;
        $this->scheduleRepository = $scheduleRepository;
    }
    
    public function createCronJob($jobCode, $time)
    {
        return $this->scheduleManagement->createSchedule($jobCode, strtotime($time));
    }

    public function saveCronJob(
        $jobId,
        $jobCode = null,
        $status = null,
        $time = null
    ) {
        $schedule = $this->scheduleRepository->get($jobId);

        if (!is_null($jobCode)) {
            $schedule->setJobCode($jobCode);
        }
        if (!is_null($status)) {
            $schedule->setStatus($status);
        }
        if (!is_null($time)) {
            $schedule->setScheduledAt(strftime(ScheduleManagementInterface::TIME_FORMAT, strtotime($time)));
        }

        $this->scheduleRepository->save($schedule);
    }

    public function deleteCronJob($jobId)
    {
        $this->scheduleRepository->deleteById($jobId);
    }

    public function flushCrons()
    {
        $this->scheduleManagement->flush();
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
        if (is_null($schedule)) {
            $schedule = $this->scheduleRepository->get($jobId);
        }

        $this->scheduleManagement->execute($schedule->getId());
    }
    
    /**
     * Dispatches cron schedule
     *
     * @param int $jobId
     * @param \Magento\Cron\Model\Schedule $schedule
     */
    public function dispatchSchedule($jobId, $schedule = null)
    {
        $this->scheduleManagement->execute($jobId);
    }

    public function getCronJobs()
    {
        return $this->scheduleManagement->listJobs();
    }
    
    /**
     * @param String $jobCode
     * @param array | null $groups
     * @return String | Boolean $groupId
     */
    public function getGroupId($jobCode, $groups = null)
    {
        return $this->scheduleManagement->getGroupId($jobCode, $groups);
    }
    
    public function scheduleNow($jobCode)
    {
        return $this->scheduleManagement->scheduleNow($jobCode);
    }
}
