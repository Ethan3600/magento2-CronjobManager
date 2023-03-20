<?php

namespace EthanYehuda\CronjobManager\Model\Data;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use Magento\Framework\DataObject;

/**
 * @codeCoverageIgnore
 */
class Schedule extends DataObject implements ScheduleInterface
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

    /**
     * @inheritDoc
     */
    public function getScheduleId(): int
    {
        return (int) $this->getData(self::KEY_SCHEDULE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getJobCode(): string
    {
        return $this->getData(self::KEY_JOB_CODE);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return $this->getData(self::KEY_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function getHostname(): string
    {
        return (string) $this->getData(self::KEY_HOSTNAME);
    }

    /**
     * @inheritDoc
     */
    public function getPid()
    {
        return (int) $this->getData(self::KEY_PID);
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return $this->getData(self::KEY_MESSAGES);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getScheduledAt()
    {
        return $this->getData(self::KEY_SCHEDULED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getExecutedAt()
    {
        return $this->getData(self::KEY_EXECUTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getFinishedAt()
    {
        return $this->getData(self::KEY_FINISHED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getKillRequest()
    {
        return $this->getData(self::KEY_KILL_REQUEST);
    }

    /**
     * @inheritDoc
     */
    public function setScheduleId(int $scheduleId): ScheduleInterface
    {
        $this->setData(self::KEY_SCHEDULE_ID, $scheduleId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setJobCode(string $jobCode): ScheduleInterface
    {
        $this->setData(self::KEY_JOB_CODE, $jobCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): ScheduleInterface
    {
        $this->setData(self::KEY_STATUS, $status);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPid(int $pid): ScheduleInterface
    {
        $this->setData(self::KEY_PID, $pid);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setMessages(string $messages): ScheduleInterface
    {
        $this->setData(self::KEY_MESSAGES, $messages);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): ScheduleInterface
    {
        $this->setData(self::KEY_CREATED_AT, $createdAt);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setScheduledAt(string $scheduledAt): ScheduleInterface
    {
        $this->setData(self::KEY_SCHEDULED_AT, $scheduledAt);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setKillRequest(string $killRequest): ScheduleInterface
    {
        $this->setData(self::KEY_KILL_REQUEST, $killRequest);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setExecutedAt(string $executedAt): ScheduleInterface
    {
        $this->setData(self::KEY_EXECUTED_AT, $executedAt);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFinishedAt(string $finishedAt): ScheduleInterface
    {
        $this->setData(self::KEY_FINISHED_AT, $finishedAt);
        return $this;
    }
}
