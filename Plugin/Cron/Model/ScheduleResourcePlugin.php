<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use EthanYehuda\CronjobManager\Model\ErrorNotification;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\Schedule;
use Magento\Framework\DataObject;

class ScheduleResourcePlugin
{
    /**
     * @var ErrorNotification
     */
    private $errorNotification;

    public function __construct(
        ConfigInterface $config,
        ErrorNotification $errorNotification
    ) {
        $this->config = $config;
        $this->errorNotification = $errorNotification;
    }

    public function beforeSave(
        ScheduleResource $subject,
        DataObject $dataObject
    ) {
        $this->recordJobDuration($dataObject);
        $this->recordJobGroup($dataObject);
    }

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
