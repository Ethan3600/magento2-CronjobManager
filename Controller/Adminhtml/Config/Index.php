<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config;

class Index extends \Magento\Backend\App\Action
{         
    protected $resultPageFactory;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        parent::__construct($context);
    }
    
    /**
     * {@inheritDoc}
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
    	return $this->_authorization->isAllowed('EthanYehuda_CronjobManager::cronjobmanager');
    }
    
    public function execute()
    {
    	/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
    	$resultPage = $this->resultPageFactory->create();
    	$resultPage->setActiveMenu('EthanYehuda_CronjobManager::cronjobmanager');
    	$resultPage->getConfig()->getTitle()->prepend(__('Job Configuration'));
    	return $resultPage;
    }
}
