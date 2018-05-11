<?php

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;

class SchedulePlugin
{
    /** @var \Magento\Cron\Model\ResourceModel\Schedule */
    private $resourceModel;

    public function __construct(
        ScheduleResource $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * If the return from @see \Magento\Cron\Model\Schedule::tryLockJob is
     * true, the job has started in THIS process, if it returns false, it has
     * not started, probably because it was already running.
     *
     * @param \Magento\Cron\Model\Schedule $subject
     * @param $return
     * @return boolean
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function afterTryLockJob(Schedule $subject, $return)
    {
        if ($return) {
            $subject->setData("pid", getmypid());
            $this->resourceModel->save($subject); // Save A.S.A.P, in case the process is killed
        }

        return $return;
    }
}