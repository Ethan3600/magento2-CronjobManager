<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;

/**
 * Update jobs with dead processes from running to error
 */
class CleanRunningJobs
{
    /** @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory */
    private $collectionFactory;

    /** @var \Magento\Cron\Model\ResourceModel\Schedule */
    private $resourceModel;

    /**
     * @var ProcessManagement
     */
    private $processManagement;
    /**
     * @var ScheduleRepositoryAdapterInterface
     */
    private $scheduleRepository;

    public function __construct(
        CollectionFactory $collectionFactory,
        ScheduleResource $resourceModel,
        ScheduleRepositoryAdapterInterface $scheduleRepository,
        ProcessManagement $processManagement
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->processManagement = $processManagement;
        $this->scheduleRepository = $scheduleRepository;
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
            if ($this->processManagement->isPidAlive($schedule->getPid())) {
                continue;
            }

            $messages = [];
            if ($schedule->getMessages()) {
                $messages[] = $schedule->getMessages();
            }

            $messages[] = 'Process went away';

            $schedule
                ->setStatus(Schedule::STATUS_ERROR)
                ->setMessages(implode("\n", $messages));

            $this->scheduleRepository->save($schedule);
        }
    }
}
