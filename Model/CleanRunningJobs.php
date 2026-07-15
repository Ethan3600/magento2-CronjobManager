<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
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
     * @param ScheduleResource $scheduleResource
     */
    public function __construct(
        private readonly ScheduleRepositoryAdapterInterface $scheduleRepository,
        private readonly ProcessManagement $processManagement,
        private readonly DateTime $dateTime,
        private readonly ClockInterface $clock,
        private readonly ScheduleResource $scheduleResource,
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

            /*
             * $runningJobs is a stale snapshot: with multiple cron groups running in
             * separate processes, a short-lived job can save its final status and exit
             * between loading the snapshot and the PID check above. A dead PID guarantees
             * the process can never write again, so lock the row and re-check: if it is
             * still "running" now, the process genuinely died mid-job.
             */
            $connection = $this->scheduleResource->getConnection();
            $connection->beginTransaction();
            try {
                $row = $connection->fetchRow(
                    $connection->select()
                        ->from($this->scheduleResource->getMainTable())
                        ->where('schedule_id = ?', $schedule->getScheduleId())
                        ->forUpdate(true)
                );

                if (!$row || $row['status'] !== ScheduleInterface::STATUS_RUNNING) {
                    $connection->commit();
                    continue;
                }

                $messages = [];
                if (!empty($row['messages'])) {
                    $messages[] = $row['messages'];
                }

                $messages[] = __('Process went away at %1', $this->dateTime->gmtDate(null, $this->clock->now()));

                $schedule
                    ->setStatus(Schedule::STATUS_ERROR)
                    ->setMessages(implode("\n", $messages));

                $this->scheduleRepository->save($schedule);
                $connection->commit();
            } catch (\Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }
        }
    }
}
