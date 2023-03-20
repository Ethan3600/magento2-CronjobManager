<?php

namespace EthanYehuda\CronjobManager\Api;

/**
 * Adapter used by REST API to work around the lack of data getters and setters in \Magento\Cron\Model\Schedule
 * making the core cron model incompatible with param and result resolvers.
 */
interface ScheduleManagementAdapterInterface
{
    /**
     * Schedule a given job code to be run now
     *
     * @param string $jobCode
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
     */
    public function scheduleNow(string $jobCode): Data\ScheduleInterface;

    /**
     * Schedule a given job code to be run at the specified time
     *
     * @param string $jobCode
     * @param int $time
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
     */
    public function schedule(string $jobCode, int $time): Data\ScheduleInterface;
}
