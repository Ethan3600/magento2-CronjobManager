<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;

class SchedulePlugin
{
    public function __construct(
        protected ScheduleResource $scheduleResource,
    ) {
    }

    public function afterTryLockJob(Schedule $subject, bool $result)
    {
        if ($result) {
            $subject->setData('hostname', \gethostname());
            $subject->setData('pid', \getmypid());
            $this->scheduleResource->save($subject);
        }
        return $result;
    }
}
