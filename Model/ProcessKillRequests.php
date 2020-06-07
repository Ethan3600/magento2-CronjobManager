<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ProcessKillRequests
{
    /**
     * @var ProcessManagement
     */
    private $processManagement;

    /**
     * @var ScheduleRepositoryAdapterInterface
     */
    private $scheduleRepository;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var Clock
     */
    private $clock;

    public function __construct(
        ScheduleRepositoryAdapterInterface $scheduleRepository,
        ProcessManagement $processManagement,
        DateTime $dateTime,
        Clock $clock
    ) {
        $this->processManagement = $processManagement;
        $this->scheduleRepository = $scheduleRepository;
        $this->dateTime = $dateTime;
        $this->clock = $clock;
    }

    public function execute()
    {
        $runningJobs = $this->scheduleRepository->getByStatus(ScheduleInterface::STATUS_RUNNING);
        foreach ($runningJobs as $schedule) {
            if ($schedule->getKillRequest() && $schedule->getKillRequest() <= \time() && $schedule->getPid()) {
                $this->killScheduleProcess($schedule);
            }
        }
    }

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
