<?php

namespace EthanYehuda\CronjobManager\Api;

use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ScheduleManagementInterface
{
    public const TIME_FORMAT = 'Y-m-d H:i:00';

    /**
     * Run a cron job by schedule identifier
     *
     * @param int $scheduleId
     *
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function execute(int $scheduleId): bool;

    /**
     * Return a list of jobs
     *
     * @return string[]
     */
    public function listJobs(): array;

    /**
     * Return cron group for a specified job
     *
     * @param string $jobCode
     * @param string[]|null $groups
     *
     * @return string
     * @throws LocalizedException
     */
    public function getGroupId(string $jobCode, $groups = null);

    /**
     * Create a new cronjob model object with a given job code
     *
     * @param string $jobCode
     * @param int|null $time
     *
     * @return Schedule
     */
    public function createSchedule(string $jobCode, $time = null): Schedule;

    /**
     * Schedule a given cronjob to be run now
     *
     * @param string $jobCode
     *
     * @return Schedule
     */
    public function scheduleNow(string $jobCode): Schedule;

    /**
     * Schedule a given cronjob for a specified date/time
     *
     * @param string $jobCode
     * @param int $time
     *
     * @return Schedule
     */
    public function schedule(string $jobCode, int $time): Schedule;

    /**
     * Clean up all jobs
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Mark a job to be killed
     *
     * @param int $jobId
     * @param int $timestamp
     *
     * @return bool
     */
    public function kill(int $jobId, int $timestamp): bool;
}
