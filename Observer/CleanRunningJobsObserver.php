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
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var CleanRunningJobs */
    private $cleanRunningJobs;

    public function __construct(
        CleanRunningJobs $cleanRunningJobs,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cleanRunningJobs = $cleanRunningJobs;
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

        $this->cleanRunningJobs->execute();
    }
}
