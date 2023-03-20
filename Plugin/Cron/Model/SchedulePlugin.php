<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\AlreadyExistsException;

class SchedulePlugin
{
    /**
     * @param ScheduleResource $scheduleResource
     */
    public function __construct(
        protected ScheduleResource $scheduleResource,
    ) {
    }

    /**
     * Set hostname and process ID on cronjob model
     *
     * @param Schedule $subject
     * @param bool $result
     *
     * @return bool
     * @throws AlreadyExistsException
     */
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
