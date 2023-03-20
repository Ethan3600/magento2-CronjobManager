<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Helper\Processor;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ScheduleManagement implements ScheduleManagementInterface
{
    /**
     * @param Processor $processor
     * @param ScheduleRepositoryInterface $scheduleRepository
     * @param ConfigInterface $config
     * @param DateTime $dateTime
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        private readonly Processor $processor,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
        private readonly ConfigInterface $config,
        private readonly DateTime $dateTime,
        private readonly ScheduleFactory $scheduleFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(int $scheduleId): bool
    {
        $groups = $this->listJobs();
        $schedule = $this->scheduleRepository->get($scheduleId);
        $jobCode = $schedule->getJobCode();
        $groupId = $this->getGroupId($jobCode, $groups);
        $jobConfig = $groups[$groupId][$jobCode];

        $this->processor->runScheduledJob($jobConfig, $schedule);
        $this->scheduleRepository->save($schedule);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function listJobs(): array
    {
        $jobList = $this->config->getJobs();
        foreach ($jobList as &$jobs) {
            \ksort($jobs);
        }

        return $jobList;
    }

    /**
     * @inheritDoc
     */
    public function createSchedule(string $jobCode, $time = null): Schedule
    {
        $time = date(ScheduleManagementInterface::TIME_FORMAT, $time ?? $this->dateTime->gmtTimestamp());

        $schedule = $this->scheduleFactory->create()
            ->setJobCode($jobCode)
            ->setStatus(Schedule::STATUS_PENDING)
            ->setCreatedAt(
                date(ScheduleManagementInterface::TIME_FORMAT, $this->dateTime->gmtTimestamp())
            )->setScheduledAt($time);

        $this->scheduleRepository->save($schedule);

        return $schedule;
    }

    /**
     * @inheritDoc
     */
    public function scheduleNow(string $jobCode): Schedule
    {
        return $this->createSchedule($jobCode);
    }

    /**
     * @inheritDoc
     */
    public function schedule(string $jobCode, int $time): Schedule
    {
        return $this->createSchedule($jobCode, $time);
    }

    /**
     * @inheritDoc
     */
    public function getGroupId(string $jobCode, $groups = null): string
    {
        if ($groups === null) {
            $groups = $this->listJobs();
        }

        foreach ($groups as $groupId => $crons) {
            if (isset($crons[$jobCode])) {
                return $groupId;
            }
        }

        throw new LocalizedException(__(
            'No such job: %jobCode',
            ['jobCode' => $jobCode]
        ));
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
    {
        $jobGroups = $this->listJobs();
        foreach ($jobGroups as $groupId => $crons) {
            $this->processor->cleanupJobs($groupId);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function kill(int $jobId, int $timestamp): bool
    {
        $schedule = $this->scheduleRepository->get($jobId);
        if ($schedule->getStatus() !== Schedule::STATUS_RUNNING) {
            return false;
        }

        $schedule->setData(
            'kill_request',
            date(ScheduleManagementInterface::TIME_FORMAT, $this->dateTime->gmtTimestamp($timestamp))
        );
        $this->scheduleRepository->save($schedule);
        return true;
    }
}
