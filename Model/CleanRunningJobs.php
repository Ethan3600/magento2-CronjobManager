<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use EthanYehuda\CronjobManager\Scope\Config;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Update jobs with dead processes from running to error
 */
class CleanRunningJobs
{
    /** @var Config */
    private $config;

    /**
     * @var ProcessManagement
     */
    private $processManagement;

    /** @var ScheduleFactory */
    private $scheduleFactory;

    /**
     * @var ScheduleRepositoryAdapterInterface
     */
    private $scheduleRepository;
    /**
     * @var Clock
     */
    private $clock;
    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        Config $config,
        ScheduleFactory $scheduleFactory,
        ScheduleRepositoryAdapterInterface $scheduleRepository,
        ProcessManagement $processManagement,
        DateTime $dateTime,
        Clock $clock
    ) {
        $this->config = $config;
        $this->scheduleFactory = $scheduleFactory;
        $this->processManagement = $processManagement;
        $this->scheduleRepository = $scheduleRepository;
        $this->dateTime = $dateTime;
        $this->clock = $clock;
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

            // We check 'scheduled_at' to avoid scheduling jobs run via command line
            if ($schedule->getScheduledAt() && $this->config->isRetryJobsGoneAway()) {
                $this->scheduleFactory->create()
                    ->setJobCode($schedule->getJobCode())
                    ->setStatus(Schedule::STATUS_PENDING)
                    ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S'))
                    ->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S'))
                    ->save();
            }
        }
    }
}
