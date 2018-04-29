<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Create extends Action
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Manager
     */
    private $cronJobManager;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        Manager $cronJobManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cronJobManager = $cronJobManager;
    }

    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        $jobCode = $this->getRequest()->getParam('job_code');
        $scheduledAt = $this->getRequest()->getParam('scheduled_at');
        try {
            $this->cronJobManager->createCronJob($jobCode, $scheduledAt);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/create/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully Created Cron Job: {$jobCode}");
        $this->_redirect('*/manage/index/');
    }
}
