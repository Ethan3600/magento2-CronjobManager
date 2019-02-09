<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;

class Kill extends AbstractAction
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        Context $context,
        ScheduleManagementInterface $scheduleManagement
    ) {
        parent::__construct($context);
        $this->scheduleManagement = $scheduleManagement;
    }

    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        $jobId = (int)$this->getRequest()->getParam('id');
        $jobCode = $this->getRequest()->getParam('job_code');
        try {
            if ($this->scheduleManagement->kill($jobId, \time())) {
                $this->getMessageManager()->addSuccessMessage("Job will be killed by next cron run: {$jobCode}");
            } else {
                $this->getMessageManager()->addNoticeMessage("Job cannot be killed.");
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/index/');
            return;
        }
        $this->_redirect('*/manage/index/');
    }
}
