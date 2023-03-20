<?php

namespace EthanYehuda\CronjobManager\Plugin\Cron\Observer;

use Magento\Cron\Observer\ProcessCronQueueObserver;
use Magento\Framework\Event\Observer;

class ProcessCronQueueObserverPlugin
{
    public function __construct(
        private readonly \Magento\Framework\Event\Manager $eventManager,
    ) {
    }

    /**
     * Dispatch an event before cron processing begin
     * @see \Magento\Cron\Observer\ProcessCronQueueObserver::execute()
     *
     * @param \Magento\Cron\Observer\ProcessCronQueueObserver $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return array
     */
    public function beforeExecute(ProcessCronQueueObserver $subject, Observer $observer)
    {
        $this->eventManager->dispatch("process_cron_queue_before", ["queue" => $subject]);

        return [$observer];
    }
}
