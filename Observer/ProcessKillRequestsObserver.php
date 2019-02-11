<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Observer;

use EthanYehuda\CronjobManager\Model\ProcessKillRequests;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessKillRequestsObserver implements ObserverInterface
{
    /**
     * @var ProcessKillRequests
     */
    private $processKillRequests;

    public function __construct(ProcessKillRequests $processKillRequests)
    {
        $this->processKillRequests = $processKillRequests;
    }

    public function execute(Observer $observer)
    {
        $this->processKillRequests->execute();
    }
}
