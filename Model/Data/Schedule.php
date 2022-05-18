<?php

namespace EthanYehuda\CronjobManager\Model\Data;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use Magento\Framework\DataObject;

/**
 * @codeCoverageIgnore
 */
class Schedule extends DataObject implements \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
{
    public const KEY_SCHEDULE_ID = 'schedule_id';
    public const KEY_JOB_CODE = 'job_code';
    public const KEY_STATUS = 'status';
    public const KEY_HOSTNAME = 'hostname';
    public const KEY_PID = 'pid';
    public const KEY_MESSAGES = 'messages';
    public const KEY_CREATED_AT = 'created_at';
    public const KEY_SCHEDULED_AT = 'scheduled_at';
    public const KEY_EXECUTED_AT = 'executed_at';
    public const KEY_FINISHED_AT = 'finished_at';
    public const KEY_KILL_REQUEST = 'kill_request';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getScheduleId(): int
    {
        return (int) $this->getData(self::KEY_SCHEDULE_ID);
    }

    public function getJobCode(): string
    {
        return $this->getData(self::KEY_JOB_CODE);
    }

    public function getStatus(): string
    {
        return $this->getData(self::KEY_STATUS);
    }

    public function getHostname(): string
    {
        return (string) $this->getData(self::KEY_HOSTNAME);
    }

    public function getPid()
    {
        return (int) $this->getData(self::KEY_PID);
    }

    public function getMessages()
    {
        return $this->getData(self::KEY_MESSAGES);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    public function getScheduledAt()
    {
        return $this->getData(self::KEY_SCHEDULED_AT);
    }

    public function getExecutedAt()
    {
        return $this->getData(self::KEY_EXECUTED_AT);
    }

    public function getFinishedAt()
    {
        return $this->getData(self::KEY_FINISHED_AT);
    }

    public function getKillRequest()
    {
        return $this->getData(self::KEY_KILL_REQUEST);
    }

    public function setScheduleId(int $scheduleId): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_SCHEDULE_ID, $scheduleId);
        return $this;
    }

    public function setJobCode(string $jobCode): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_JOB_CODE, $jobCode);
        return $this;
    }

    public function setStatus(string $status): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_STATUS, $status);
        return $this;
    }

    public function setPid(int $pid): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_PID, $pid);
        return $this;
    }

    public function setMessages(string $messages): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_MESSAGES, $messages);
        return $this;
    }

    public function setCreatedAt(string $createdAt): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_CREATED_AT, $createdAt);
        return $this;
    }

    public function setScheduledAt(string $scheduledAt): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_SCHEDULED_AT, $scheduledAt);
        return $this;
    }

    public function setKillRequest(string $killRequest): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_KILL_REQUEST, $killRequest);
        return $this;
    }

    public function setExecutedAt(string $executedAt): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_EXECUTED_AT, $executedAt);
        return $this;
    }

    public function setFinishedAt(string $finishedAt): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $this->setData(self::KEY_FINISHED_AT, $finishedAt);
        return $this;
    }
}
