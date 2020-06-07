<?php

namespace EthanYehuda\CronjobManager\Api\Data;

use Magento\Cron\Model\Schedule;

interface ScheduleInterface
{
    const STATUS_PENDING = Schedule::STATUS_PENDING;

    const STATUS_RUNNING = Schedule::STATUS_RUNNING;

    const STATUS_SUCCESS = Schedule::STATUS_SUCCESS;

    const STATUS_MISSED  = Schedule::STATUS_MISSED;

    const STATUS_ERROR   = Schedule::STATUS_ERROR;

    const STATUS_KILLED  = 'killed';

    /**
     * @return int
     */
    public function getScheduleId(): int;

    /**
     * @return string
     */
    public function getJobCode(): string;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return string|null
     */
    public function getHostname();

    /**
     * @return int|null
     */
    public function getPid();

    /**
     * @return string|null
     */
    public function getMessages();

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @return string|null
     */
    public function getScheduledAt();

    /**
     * @return string|null
     */
    public function getExecutedAt();

    /**
     * @return string|null
     */
    public function getFinishedAt();

    /**
     * @return string|null
     */
    public function getKillRequest();

    /**
     * @param int $scheduleId
     * @return ScheduleInterface
     */
    public function setScheduleId(int $scheduleId): self;

    /**
     * @param string $jobCode
     * @return ScheduleInterface
     */
    public function setJobCode(string $jobCode): self;

    /**
     * @param string $status
     * @return ScheduleInterface
     */
    public function setStatus(string $status): self;

    /**
     * @param int $pid
     * @return ScheduleInterface
     */
    public function setPid(int $pid): self;

    /**
     * @param string $messages
     * @return ScheduleInterface
     */
    public function setMessages(string $messages): self;

    /**
     * @param string $createdAt
     * @return ScheduleInterface
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @param string $scheduledAt
     * @return ScheduleInterface
     */
    public function setScheduledAt(string $scheduledAt): self;

    /**
     * @param string $executedAt
     * @return ScheduleInterface
     */
    public function setExecutedAt(string $executedAt): self;

    /**
     * @param string $finishedAt
     * @return ScheduleInterface
     */
    public function setFinishedAt(string $finishedAt): self;

    /**
     * @param string $killRequest
     * @return ScheduleInterface
     */
    public function setKillRequest(string $killRequest): self;
}
