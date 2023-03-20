<?php

namespace EthanYehuda\CronjobManager\Model\Cron;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;

class Runner
{
    public function __construct(
        private readonly State $state,
        private readonly ScheduleManagementInterfaceFactory $scheduleManagementFactory,
    ) {
    }

    public function runCron($jobCode)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $scheduleManager = $this->scheduleManagementFactory->create();
        try {
            // lets create a new cron job and dispatch it
            $schedule = $scheduleManager->scheduleNow($jobCode);
            $scheduleManager->execute($schedule->getId());
            return [Cli::RETURN_SUCCESS, "$jobCode successfully ran"];
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return [Cli::RETURN_FAILURE, $e->getMessage()];
        }
    }
}
