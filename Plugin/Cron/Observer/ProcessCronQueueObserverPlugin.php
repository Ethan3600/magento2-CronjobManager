<?php

namespace EthanYehuda\CronjobManager\Plugin\Cron\Observer;

use Magento\Cron\Observer\ProcessCronQueueObserver;
use Magento\Framework\Event\Manager;
use Magento\Framework\Event\Observer;

class ProcessCronQueueObserverPlugin
{
    /**
     * @param Manager $eventManager
     */
    public function __construct(
        private readonly Manager $eventManager,
    ) {
    }

    /**
     * Dispatch an event before cron processing begin
     *
     * @see ProcessCronQueueObserver::execute()
     *
     * @param ProcessCronQueueObserver $subject
     * @param Observer $observer
     *
     * @return array
     */
    public function beforeExecute(ProcessCronQueueObserver $subject, Observer $observer)
    {
        $this->eventManager->dispatch("process_cron_queue_before", ["queue" => $subject]);

        return [$observer];
    }
}
