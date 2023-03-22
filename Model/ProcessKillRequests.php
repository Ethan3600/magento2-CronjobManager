<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ProcessKillRequests
{
    /**
     * @param ScheduleRepositoryAdapterInterface $scheduleRepository
     * @param ProcessManagement $processManagement
     * @param DateTime $dateTime
     * @param ClockInterface $clock
     */
    public function __construct(
        private readonly ScheduleRepositoryAdapterInterface $scheduleRepository,
        private readonly ProcessManagement $processManagement,
        private readonly DateTime $dateTime,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Kill any running jobs, which have been marked for termination
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function execute()
    {
        $runningJobs = $this->scheduleRepository->getByStatus(ScheduleInterface::STATUS_RUNNING);
        foreach ($runningJobs as $schedule) {
            if ($schedule->getKillRequest()
                && $schedule->getKillRequest() <= date(ScheduleManagementInterface::TIME_FORMAT)
                && $schedule->getPid()
            ) {
                $this->killScheduleProcess($schedule);
            }
        }
    }

    /**
     * Terminate the specified process
     *
     * @param ScheduleInterface $schedule
     *
     * @return void
     * @throws CouldNotSaveException
     */
    private function killScheduleProcess(ScheduleInterface $schedule): void
    {
        if ($this->processManagement->killPid($schedule->getPid(), $schedule->getHostname())) {
            $messages = [];
            if ($schedule->getMessages()) {
                $messages[] = $schedule->getMessages();
            }

            $messages[] = 'Process was killed at ' . $this->dateTime->gmtDate(null, $this->clock->now());
            $schedule
                ->setMessages(\implode("\n", $messages))
                ->setStatus(ScheduleInterface::STATUS_KILLED);

            $this->scheduleRepository->save($schedule);
        }
    }
}
