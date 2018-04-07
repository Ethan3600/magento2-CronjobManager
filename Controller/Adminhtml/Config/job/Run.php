<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use Magento\Cron\Observer\ProcessCronQueueObserver;

class Run extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var ProcessCronQueueObserver
     */
    protected $cronQueue;
    
    /**
     * @var \Magento\Framework\Event\Observer
     */
    private $observer;
    
    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Event\ObserverFactory $observerFactory
     * @param Manager $cronJobManager
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Event\ObserverFactory $observerFactory,
        ProcessCronQueueObserver $cronQueue
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cronQueue= $cronQueue;
        $this->observer = $observerFactory->create('Magento\Framework\Event\Observer');
    }
    
    /**
     * {@inheritDoc}
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('EthanYehuda_CronjobManager::cronjobmanager');
    }
    
    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        try {
            $this->cronQueue->execute($this->observer);
        } catch (\Magento\Framework\Exception\CronException $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/config/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Magento Cron Ran Successfully");
        $this->_redirect('*/config/');
    }
}
