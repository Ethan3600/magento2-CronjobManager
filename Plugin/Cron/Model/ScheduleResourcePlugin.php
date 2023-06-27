<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use EthanYehuda\CronjobManager\Model\ErrorNotification;
use EthanYehuda\CronjobManager\Scope\Config;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\DataObject;

class ScheduleResourcePlugin
{
    /** @var Config */
    private $config;

    /**
     * @var ErrorNotification
     */
    private $errorNotification;

    /** @var ScheduleFactory */
    private $scheduleFactory;

    public function __construct(
        Config $config,
        ErrorNotification $errorNotification,
        ScheduleFactory $scheduleFactory
    ) {
        $this->config = $config;
        $this->errorNotification = $errorNotification;
        $this->scheduleFactory = $scheduleFactory;
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
     * Email notification and job retry if status has been set to ERROR
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

            // We check 'scheduled_at' to avoid scheduling jobs run via command line
            if ($object->getScheduledAt() && $this->config->isRetryFailedJobs()) {
                $this->scheduleFactory->create()
                    ->setJobCode($object->getJobCode())
                    ->setStatus(Schedule::STATUS_PENDING)
                    ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S'))
                    ->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S'))
                    ->save();
            }
        }
        return $result;
    }
}
