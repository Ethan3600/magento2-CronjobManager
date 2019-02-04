<?php

namespace EthanYehuda\CronjobManager\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Cron
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ManagerFactory
     */
    private $managerFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        State $state,
        ManagerFactory $managerFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->state = $state;
        $this->managerFactory = $managerFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function runCron($jobCode)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $manager = $this->managerFactory->create();
        $dateTime = $this->dateTimeFactory->create();

        try {
            // lets create a new cron job and dispatch it
            $now = strftime('%Y-%m-%dT%H:%M:%S', $dateTime->gmtTimestamp());

            $schedule = $manager->createCronJob($jobCode, $now);
            $manager->dispatchCron(null, $jobCode, $schedule);
            return [Cli::RETURN_SUCCESS, "$jobCode successfully ran"];
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return [Cli::RETURN_FAILURE, $e->getMessage()];
        }
    }
}
