<?php

namespace EthanYehuda\CronjobManager\Model\Cron;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;

class Runner
{
    /**
     * @param State $state
     * @param ScheduleManagementInterfaceFactory $scheduleManagementFactory
     */
    public function __construct(
        private readonly State $state,
        private readonly ScheduleManagementInterfaceFactory $scheduleManagementFactory,
    ) {
    }

    /**
     * Run a specific job code inline
     *
     * @param string $jobCode
     *
     * @return array
     * @throws LocalizedException
     */
    public function runCron($jobCode)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $scheduleManager = $this->scheduleManagementFactory->create();
        try {
            // Let's create a new cron job and dispatch it
            $schedule = $scheduleManager->scheduleNow($jobCode);
            $scheduleManager->execute($schedule->getId());
            return [Cli::RETURN_SUCCESS, "$jobCode successfully ran"];
        } catch (LocalizedException $e) {
            return [Cli::RETURN_FAILURE, $e->getMessage()];
        }
    }
}
