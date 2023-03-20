<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use Magento\Cron\Model\Schedule;

interface ErrorNotificationInterface
{
    /**
     * Send an email notification for the given schedule model
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    public function sendFor(Schedule $schedule): void;
}
