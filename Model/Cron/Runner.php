<?php

namespace EthanYehuda\CronjobManager\Model\Cron;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;

class Runner
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ScheduleManagementInterfaceFactory
     */
    private $scheduleManagementFactory;

    public function __construct(
        State $state,
        ScheduleManagementInterfaceFactory $scheduleManagementFactory
    ) {
        $this->state = $state;
        $this->scheduleManagementFactory = $scheduleManagementFactory;
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
