<?php

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

class SchedulePlugin
{
    /**
     * If the return from @see \Magento\Cron\Model\Schedule::tryLockJob is
     * true, the job has started in THIS process, if it returns false, it has
     * not started, probably because it was already running.
     *
     * @param \Magento\Cron\Model\Schedule $subject
     * @param $return
     * @return boolean
     */
    public function afterTryLockJob(\Magento\Cron\Model\Schedule $subject, $return)
    {
        if ($return) {
            $subject->setData("pid", \getmypid());
        }

        return $return;
    }
}