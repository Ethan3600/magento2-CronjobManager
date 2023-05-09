<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Observer;

use EthanYehuda\CronjobManager\Model\ProcessKillRequests;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class ProcessKillRequestsObserver implements ObserverInterface
{
    /**
     * @param ProcessKillRequests $processKillRequests
     */
    public function __construct(
        private readonly ProcessKillRequests $processKillRequests,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws CouldNotSaveException
     */
    public function execute(Observer $observer)
    {
        $this->processKillRequests->execute();
    }
}
