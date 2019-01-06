<?php

namespace EthanYehuda\CronjobManager\Api;

use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ScheduleManagementInterface
{
    const TIME_FORMAT = '%Y-%m-%d %H:%M:00';

    /**
     * @param int $scheduleId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function execute(int $scheduleId): bool;

    /**
     * @return string[]
     */
    public function listJobs(): array;

    /**
     * @param string $jobCode
     * @param string[]|null $groups
     * @return string|null
     */
    public function getGroupId(string $jobCode, $groups = null);

    /**
     * @param string $jobCode
     * @param int|null $time
     * @return Schedule
     */
    public function createSchedule(string $jobCode, $time = null): Schedule;

    /**
     * @param string $jobCode
     * @return Schedule
     */
    public function scheduleNow(string $jobCode): Schedule;

    /**
     * @param string $jobCode
     * @param int $time
     * @return Schedule
     */
    public function schedule(string $jobCode, int $time): Schedule;

    /**
     * @return bool
     */
    public function flush(): bool;
}
