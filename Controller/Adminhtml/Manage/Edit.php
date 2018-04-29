<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;

class Edit extends Action
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Product list page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        // Register cronjob information for later use
        $this->coreRegistry->register(
            RegistryConstants::CURRENT_CRON_JOB,
            $this->getRequest()->getParams()
        );

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('EthanYehuda_CronjobManager::cronjobmanager');
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Cron Job'));
        return $resultPage;
    }
}
