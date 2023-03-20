<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Delete extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param Context $context
     * @param Manager $cronJobManager
     */
    public function __construct(
        Context $context,
        private readonly Manager $cronJobManager,
    ) {
        parent::__construct($context);
    }

    /**
     * Save cronjob
     *
     * @return void
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getParam('id');
        try {
            $this->cronJobManager->deleteCronJob($jobId);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/index/');
            return;
        }

        $this->getMessageManager()->addSuccessMessage("Successfully Deleted Cron Job");
        $this->_redirect('*/manage/index/');
    }
}
