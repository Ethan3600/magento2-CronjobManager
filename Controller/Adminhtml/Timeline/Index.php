<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Timeline;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;

class Index extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Product list page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('EthanYehuda_CronjobManager::cronjobmanager');
        $resultPage->getConfig()->getTitle()->prepend(__('Timeline'));
        return $resultPage;
    }
}
