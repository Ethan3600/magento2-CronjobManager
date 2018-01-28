<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Model\Manager;

class Run extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var Manager
     */
    protected $cronJobManager;
    
    /**
     * @var \Magento\Framework\Event\Observer
     */
    private $observer;
    
    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Event\ObserverFactory $observerFactory,
        Manager $cronJobManager
        ) {
            parent::__construct($context);
            $this->resultPageFactory = $resultPageFactory;
            $this->cronJobManager = $cronJobManager;
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
            $this->cronJobManager->execute($this->observer);
        } catch (\Magento\Framework\Exception\CronException $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/config/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Magento Cron Ran Successfully");
        $this->_redirect('*/config/');
    }
}
