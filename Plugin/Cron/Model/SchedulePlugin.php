<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use Magento\Cron\Model\Schedule;

class SchedulePlugin
{
    public function afterTryLockJob(Schedule $subject, bool $result)
    {
        if ($result) {
            $subject->setData('pid', \getmypid());
        }
        return $result;
    }
}
