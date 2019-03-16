<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use Magento\Cron\Observer\ProcessCronQueueObserver;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Event\ObserverFactory;
use Magento\Framework\Controller\ResultFactory;

class Run extends Action
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    
    /**
     * @var ProcessCronQueueObserver
     */
    private $cronQueue;
    
    /**
     * @var Observer
     */
    private $observer;
    
    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Event\ObserverFactory $observerFactory
     * @param Manager $cronJobManager
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        ObserverFactory $observerFactory,
        ProcessCronQueueObserver $cronQueue
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cronQueue= $cronQueue;
        $this->observer = $observerFactory->create('Magento\Framework\Event\Observer');
    }
    
    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {
            $this->cronQueue->execute($this->observer);
        } catch (\Magento\Framework\Exception\CronException $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }
        $this->getMessageManager()->addSuccessMessage("Magento Cron Ran Successfully");
        return $resultRedirect;
    }
}
