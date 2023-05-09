<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @deprecated
 * @see \EthanYehuda\CronjobManager\Api\ScheduleManagementInterface
 * @see \EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface
 */
class Manager
{
    /**
     * @param ScheduleManagementInterface $scheduleManagement
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        private readonly ScheduleManagementInterface $scheduleManagement,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {
    }

    /**
     * Create a new schedule object for the given job code
     *
     * @param string $jobCode
     * @param string $time
     *
     * @return Schedule
     */
    public function createCronJob($jobCode, $time)
    {
        return $this->scheduleManagement->createSchedule($jobCode, strtotime($time));
    }

    /**
     * Save a schedule model and set some properties
     *
     * @param int $jobId
     * @param string $jobCode
     * @param string $status
     * @param string $time
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function saveCronJob(
        $jobId,
        $jobCode = null,
        $status = null,
        $time = null
    ) {
        $schedule = $this->scheduleRepository->get($jobId);

        if ($jobCode !== null) {
            $schedule->setJobCode($jobCode);
        }

        if ($status !== null) {
            $schedule->setStatus($status);
        }

        if ($time !== null) {
            $schedule->setScheduledAt(date(ScheduleManagementInterface::TIME_FORMAT, strtotime($time)));
        }

        $this->scheduleRepository->save($schedule);
    }

    /**
     * Delete cronjob run from the database
     *
     * @param int $jobId
     *
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteCronJob($jobId)
    {
        $this->scheduleRepository->deleteById($jobId);
    }

    /**
     * Clean up all jobs
     *
     * @return void
     */
    public function flushCrons()
    {
        $this->scheduleManagement->flush();
    }

    /**
     * Get a list of all cron jobs
     *
     * @return string[]
     */
    public function getCronJobs()
    {
        return $this->scheduleManagement->listJobs();
    }

    /**
     * Get cron group for the specified job code
     *
     * @param String $jobCode
     * @param array|null $groups
     *
     * @return String | Boolean $groupId
     */
    public function getGroupId($jobCode, $groups = null)
    {
        return $this->scheduleManagement->getGroupId($jobCode, $groups);
    }
}
