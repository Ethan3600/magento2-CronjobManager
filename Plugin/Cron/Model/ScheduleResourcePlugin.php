<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use EthanYehuda\CronjobManager\Model\ErrorNotificationInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\Schedule;
use Magento\Framework\DataObject;

class ScheduleResourcePlugin
{
    /**
     * @param ConfigInterface $config
     * @param ErrorNotificationInterface $errorNotification
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ErrorNotificationInterface $errorNotification,
    ) {
    }

    /**
     * Store job duration and group on cronjob model
     *
     * @param ScheduleResource $subject
     * @param DataObject $dataObject
     *
     * @return void
     */
    public function beforeSave(
        ScheduleResource $subject,
        DataObject $dataObject
    ) {
        $this->recordJobDuration($dataObject);
        $this->recordJobGroup($dataObject);
    }

    /**
     * Store job duration on cronjob model
     *
     * @param DataObject $dataObject
     *
     * @return void
     */
    protected function recordJobDuration(DataObject $dataObject): void
    {
        if ($dataObject->dataHasChangedFor('duration')) {
            // avoid loops
            return;
        }

        $executedAt = $dataObject->getData('executed_at');
        $finishedAt = $dataObject->getData('finished_at');
        $executedTimestamp = \strtotime($executedAt ?: 'now') ?: \time();
        $finishedTimestamp = \strtotime($finishedAt ?: 'now') ?: \time();

        if (!$executedAt) {
            // Job has not yet started. Nothing to do.
            return;
        }

        $dataObject->setData('duration', $finishedTimestamp - $executedTimestamp);
    }

    /**
     * Store job group on cronjob model
     *
     * @param DataObject $dataObject
     *
     * @return void
     */
    protected function recordJobGroup(DataObject $dataObject): void
    {
        if ($dataObject->dataHasChangedFor('group')) {
            // avoid loops
            return;
        }

        if ($dataObject->getData('group')) {
            // already have recorded group. Nothing to do.
            return;
        }

        $jobCode = $dataObject->getData('job_code');
        foreach ($this->config->getJobs() as $group => $jobs) {
            if (\in_array($jobCode, \array_keys($jobs))) {
                $dataObject->setData('group', $group);
                return;
            }
        }
    }

    /**
     * Email notification if status has been set to ERROR
     *
     * @param ScheduleResource $subject
     * @param ScheduleResource $result
     * @param Schedule $object
     *
     * @return ScheduleResource
     */
    public function afterSave(
        ScheduleResource $subject,
        ScheduleResource $result,
        Schedule $object
    ) {
        if ($object->getOrigData('status') !== $object->getStatus()
            && $object->getStatus() === Schedule::STATUS_ERROR
        ) {
            $this->errorNotification->sendFor($object);
        }

        return $result;
    }
}
