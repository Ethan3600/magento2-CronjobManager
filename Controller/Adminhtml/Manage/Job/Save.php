<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Save extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param \Magento\Backend\App\Action\Context $context
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
        $params = $this->getRequest()->getParams();
        $jobId = $params['schedule_id'] ? $params['schedule_id'] : null;
        if (!$jobId) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/manage/edit/');
            return;
        }

        $jobCode = $params['job_code'] ? $params['job_code'] : null;
        $status = $params['status'] ? $params['status'] : null;
        $scheduledAt = $params['scheduled_at'] ? $params['scheduled_at'] : null;
        try {
            $this->cronJobManager->saveCronJob($jobId, $jobCode, $status, $scheduledAt);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/edit/', ['id' => $jobId]);
            return;
        }

        $this->getMessageManager()->addSuccessMessage("Successfully saved Cron Job: {$jobCode}");
        if (!isset($params['back'])) {
            $this->_redirect("*/manage/index/");
        } else {
            $this->_redirect("*/manage/{$params['back']}/", ['id' => $jobId]);
        }
    }
}
