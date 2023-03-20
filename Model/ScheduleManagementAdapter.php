<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data;
use EthanYehuda\CronjobManager\Api\ScheduleManagementAdapterInterface;
use EthanYehuda\CronjobManager\Api\Data\ScheduleInterfaceFactory;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;

class ScheduleManagementAdapter implements ScheduleManagementAdapterInterface
{
    public function __construct(
        private readonly ScheduleInterfaceFactory $scheduleFactory,
        private readonly ScheduleManagementInterface $scheduleManagement,
    ) {
    }

    public function scheduleNow(string $jobCode): Data\ScheduleInterface
    {
        $coreSchedule = $this->scheduleManagement->scheduleNow($jobCode);

        return $this->scheduleFactory->create(['data' => $coreSchedule->getData()]);
    }

    public function schedule(string $jobCode, int $time): Data\ScheduleInterface
    {
        $coreSchedule = $this->scheduleManagement->schedule($jobCode, $time);

        return $this->scheduleFactory->create(['data' => $coreSchedule->getData()]);
    }
}
