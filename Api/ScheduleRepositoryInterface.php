<?php

namespace EthanYehuda\CronjobManager\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ScheduleRepositoryInterface
{
    /**
     * @param int $scheduleId
     * @return \Magento\Cron\Model\Schedule
     * @throws NoSuchEntityException
     */
    public function get(int $scheduleId): \Magento\Cron\Model\Schedule;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): \Magento\Framework\Api\SearchResultsInterface;

    /**
     * @param \Magento\Cron\Model\Schedule $schedule
     * @return \Magento\Cron\Model\Schedule
     * @throws CouldNotSaveException
     */
    public function save(\Magento\Cron\Model\Schedule $schedule): \Magento\Cron\Model\Schedule;

    /**
     * @param \Magento\Cron\Model\Schedule $schedule
     * @return bool True on success
     * @throws CouldNotDeleteException
     */
    public function delete(\Magento\Cron\Model\Schedule $schedule): bool;

    /**
     * @param int $scheduleId
     * @return bool True on success
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $scheduleId): bool;
}
