<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;

class Edit extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        Context $context,
        private readonly Registry $coreRegistry,
    ) {
        parent::__construct($context);
    }

    /**
     * Product list page
     *
     * @return Page
     */
    public function execute()
    {
        // Register cronjob information for later use
        $this->coreRegistry->register(
            RegistryConstants::CURRENT_CRON_JOB,
            $this->getRequest()->getParams()
        );

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('EthanYehuda_CronjobManager::cronjobmanager');
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Cron Job'));
        return $resultPage;
    }
}
