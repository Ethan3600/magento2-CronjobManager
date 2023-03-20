<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Index extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
    ) {
        parent::__construct($context);
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
