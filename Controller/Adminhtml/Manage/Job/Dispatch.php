<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\ScheduleManagement;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Dispatch extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param Context $context
     * @param ScheduleManagement $scheduleManagement
     */
    public function __construct(
        Context $context,
        private readonly ScheduleManagement $scheduleManagement,
    ) {
        parent::__construct($context);
    }

    /**
     * Schedule a new run of the selected jobcode
     *
     * @return void
     */
    public function execute()
    {
        $jobCode = $this->getRequest()->getParam('job_code');

        try {
            $this->scheduleManagement->scheduleNow($jobCode);
            $this->getMessageManager()->addSuccessMessage(__('Successfully scheduled selected job'));
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/manage/index');
    }
}
