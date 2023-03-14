<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Dispatch extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

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
        $jobId = $this->getRequest()->getParam('id');
        $jobCode = $this->getRequest()->getParam('job_code');
        try {
            $this->cronJobManager->dispatchCron($jobId, $jobCode);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/index/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully Dispatched Cron Job: {$jobCode}");
        $this->_redirect('*/manage/index/');
    }
}
