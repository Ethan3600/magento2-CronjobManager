<?php

namespace EthanYehuda\CronjobManager\Observer;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CleanRunningJobsObserver implements ObserverInterface
{
    /** @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory */
    private $collectionFactory;

    /** @var \Magento\Cron\Model\ResourceModel\Schedule */
    private $resourceModel;

    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Cron\Model\ResourceModel\Schedule $resourceModel
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * Find all jobs in status "running" (according to db), and check if the process is alive.
     * If not, set status to error, with the message "Process went away"
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule\Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter("status", Schedule::STATUS_RUNNING);

        /** @var Schedule $schedule */
        foreach ($collection->getItems() as $schedule) {
            if ($this->isPidAlive($schedule->getData("pid"))) {
                continue;
            }

            $messages = [];
            if ($schedule->getMessages()) {
                $messages[] = $schedule->getMessages();
            }

            $messages[] = "Process went away";

            $schedule
                ->setStatus(Schedule::STATUS_ERROR)
                ->setMessages(join("\n", $messages))
            ;

            $this->resourceModel->save($schedule);
        }
    }

    /**
     * @param int $pid
     * @return boolean
     */
    private function isPidAlive($pid)
    {
        if (file_exists("/proc/" . \intval($pid))) {
            return true;
        }

        // todo: add support for other os than linux?
        // https://stackoverflow.com/questions/9874331/how-to-check-whether-specified-pid-is-currently-running-without-invoking-ps-from

        return false;
    }
}