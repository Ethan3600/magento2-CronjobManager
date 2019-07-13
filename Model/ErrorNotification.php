<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use Magento\Cron\Model\Schedule;

interface ErrorNotification
{
    public function sendFor(Schedule $schedule): void;
}