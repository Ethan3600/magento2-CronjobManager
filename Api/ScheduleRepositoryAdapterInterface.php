<?php

namespace EthanYehuda\CronjobManager\Api;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Adapter used by REST API to work around the lack of data getters and setters in \Magento\Cron\Model\Schedule
 * making the core cron model incompatible with param and result resolvers.
 */
interface ScheduleRepositoryAdapterInterface
{
    /**
     * Get schedule by ID
     *
     * @param int $scheduleId
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
     * @throws NoSuchEntityException
     */
    public function get(int $scheduleId): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;

    /**
     * Get a list of schedules matching the given criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Magento\Framework\Api\SearchResultsInterface;

    /**
     * Store a given schedule in the database
     *
     * @param \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface $schedule
     * @param int $scheduleId
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
     * @throws CouldNotSaveException
     */
    public function save(
        \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface $schedule,
        $scheduleId = null
    ): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;

    /**
     * Return all jobs with given status
     *
     * @param string $status
     *
     * @return ScheduleInterface[]
     */
    public function getByStatus($status);
}
