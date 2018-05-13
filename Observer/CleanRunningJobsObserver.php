<?php

namespace EthanYehuda\CronjobManager\Observer;

use EthanYehuda\CronjobManager\Helper\Config;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CleanRunningJobsObserver implements ObserverInterface
{
    /** @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory */
    private $collectionFactory;

    /** @var \Magento\Cron\Model\ResourceModel\Schedule */
    private $resourceModel;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(
        CollectionFactory $collectionFactory,
        ScheduleResource $resourceModel,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * If this feature is active, Find all jobs in status "running" (according to db),
     * and check if the process is alive. If not, set status to error, with the message
     * "Process went away"
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        if (!$this->scopeConfig->getValue(Config::PATH_CLEAN_RUNNING)) {
            return;
        }

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
                ->setMessages(join("\n", $messages));

            $this->resourceModel->save($schedule);
        }
    }

    /**
     * @param int $pid
     * @return boolean
     */
    private function isPidAlive($pid)
    {
        if (file_exists("/proc/" . intval($pid))) {
            return true;
        }

        return false;
    }
}