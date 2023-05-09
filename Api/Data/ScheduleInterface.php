<?php

namespace EthanYehuda\CronjobManager\Api\Data;

use Magento\Cron\Model\Schedule;

interface ScheduleInterface
{
    public const STATUS_PENDING = Schedule::STATUS_PENDING;
    public const STATUS_RUNNING = Schedule::STATUS_RUNNING;
    public const STATUS_SUCCESS = Schedule::STATUS_SUCCESS;
    public const STATUS_MISSED = Schedule::STATUS_MISSED;
    public const STATUS_ERROR = Schedule::STATUS_ERROR;
    public const STATUS_KILLED = 'killed';

    /**
     * Return schedule identifier
     *
     * @return int
     */
    public function getScheduleId(): int;

    /**
     * Return job code
     *
     * @return string
     */
    public function getJobCode(): string;

    /**
     * Return job status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Return name of server (host) where this job is/was running
     *
     * @return string|null
     */
    public function getHostname();

    /**
     * Return the process identifier for this job
     *
     * @return int|null
     */
    public function getPid();

    /**
     * Return any messages saved for this job
     *
     * @return string|null
     */
    public function getMessages();

    /**
     * Return datetime string for when this job was created
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Return datetime string for when this job was scheduled
     *
     * @return string|null
     */
    public function getScheduledAt();

    /**
     * Return datetime string for when this job was executed
     *
     * @return string|null
     */
    public function getExecutedAt();

    /**
     * Return datetime string for when this job finished running
     *
     * @return string|null
     */
    public function getFinishedAt();

    /**
     * Return kill request
     *
     * @return string|null
     */
    public function getKillRequest();

    /**
     * Set internal job identifier
     *
     * @param int $scheduleId
     *
     * @return ScheduleInterface
     */
    public function setScheduleId(int $scheduleId): self;

    /**
     * Set job code
     *
     * @param string $jobCode
     *
     * @return ScheduleInterface
     */
    public function setJobCode(string $jobCode): self;

    /**
     * Set job status
     *
     * @param string $status
     *
     * @return ScheduleInterface
     */
    public function setStatus(string $status): self;

    /**
     * Set process identifier for this job
     *
     * @param int $pid
     *
     * @return ScheduleInterface
     */
    public function setPid(int $pid): self;

    /**
     * Set messages associated with this job
     *
     * @param string $messages
     *
     * @return ScheduleInterface
     */
    public function setMessages(string $messages): self;

    /**
     * Set datetime string for when this job was created
     *
     * @param string $createdAt
     *
     * @return ScheduleInterface
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Set datetime string for when this job was scheduled
     *
     * @param string $scheduledAt
     *
     * @return ScheduleInterface
     */
    public function setScheduledAt(string $scheduledAt): self;

    /**
     * Set datetime string for when this job was executed
     *
     * @param string $executedAt
     *
     * @return ScheduleInterface
     */
    public function setExecutedAt(string $executedAt): self;

    /**
     * Set datetime string for when this job finished running
     *
     * @param string $finishedAt
     *
     * @return ScheduleInterface
     */
    public function setFinishedAt(string $finishedAt): self;

    /**
     * Set kill request
     *
     * @param string $killRequest
     *
     * @return ScheduleInterface
     */
    public function setKillRequest(string $killRequest): self;
}
