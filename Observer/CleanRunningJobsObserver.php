<?php

namespace EthanYehuda\CronjobManager\Observer;

use EthanYehuda\CronjobManager\Helper\Config;
use EthanYehuda\CronjobManager\Model\CleanRunningJobs;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CleanRunningJobsObserver implements ObserverInterface
{
    /**
     * @param CleanRunningJobs $cleanRunningJobs
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly CleanRunningJobs $cleanRunningJobs,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Mark jobs as 'went away' if configured to do so
     *
     * If this feature is active, Find all jobs in status "running" (according to db),
     * and check if the process is alive. If not, set status to error, with the message
     * "Process went away"
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->scopeConfig->getValue(Config::PATH_CLEAN_RUNNING)) {
            return;
        }

        $this->cleanRunningJobs->execute();
    }
}
