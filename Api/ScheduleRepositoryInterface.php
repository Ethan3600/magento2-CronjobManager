<?php

namespace EthanYehuda\CronjobManager\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ScheduleRepositoryInterface
{
    /**
     * Get specified model from the database
     *
     * @param int $scheduleId
     *
     * @return \Magento\Cron\Model\Schedule
     * @throws NoSuchEntityException
     */
    public function get(int $scheduleId): \Magento\Cron\Model\Schedule;

    /**
     * Get a list of models matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Magento\Framework\Api\SearchResultsInterface;

    /**
     * Store the given model to the database
     *
     * @param \Magento\Cron\Model\Schedule|\EthanYehuda\CronjobManager\Model\Data\Schedule $schedule
     *
     * @return \Magento\Cron\Model\Schedule
     * @throws CouldNotSaveException
     */
    public function save(\Magento\Cron\Model\Schedule|\EthanYehuda\CronjobManager\Model\Data\Schedule $schedule): \Magento\Cron\Model\Schedule;

    /**
     * Delete the given model from the database
     *
     * @param \Magento\Cron\Model\Schedule $schedule
     *
     * @return bool True on success
     * @throws CouldNotDeleteException
     */
    public function delete(\Magento\Cron\Model\Schedule $schedule): bool;

    /**
     * Delete the given model (by ID) from the database
     *
     * @param int $scheduleId
     *
     * @return bool True on success
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $scheduleId): bool;
}
