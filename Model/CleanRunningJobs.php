<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Update jobs with dead processes from running to error
 */
class CleanRunningJobs
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
     * Find all jobs in status "running" (according to db),
     * and check if the process is alive. If not, set status to error, with the message
     * "Process went away"
     */
    public function execute()
    {
        $runningJobs = $this->scheduleRepository->getByStatus(ScheduleInterface::STATUS_RUNNING);

        foreach ($runningJobs as $schedule) {
            if ($schedule->getHostname() !== \gethostname()) {
                continue;
            }

            if ($this->processManagement->isPidAlive($schedule->getPid())) {
                continue;
            }

            $messages = [];
            if ($schedule->getMessages()) {
                $messages[] = $schedule->getMessages();
            }

            $messages[] = __('Process went away at %1', $this->dateTime->gmtDate(null, $this->clock->now()));

            $schedule
                ->setStatus(Schedule::STATUS_ERROR)
                ->setMessages(implode("\n", $messages));

            $this->scheduleRepository->save($schedule);
        }
    }
}
