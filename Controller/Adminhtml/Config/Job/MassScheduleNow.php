<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Model\ScheduleManagement;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class MassScheduleNow extends Action
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
     * Save cronjob
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParam('selected');
        if (!isset($params)) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when receiving the request");
            $this->_redirect('*/config/index');
            return;
        }

        try {
            foreach ($params as $jobCode) {
                $this->scheduleManagement->scheduleNow($jobCode);
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/config/index/');
            return;
        }

        $this->getMessageManager()->addSuccessMessage("Successfully Ran Schedule Now Action");
        $this->_redirect("*/config/index/");
    }
}
