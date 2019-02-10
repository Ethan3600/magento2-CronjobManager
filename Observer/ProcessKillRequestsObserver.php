<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessKillRequestsObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // TODO: extract logic from CleanRunningJobsObserver to service
        // TODO: based on that, write service to process kill requests
        // TODO: call new service from ProcessKillRequestsObserver
    }
}
