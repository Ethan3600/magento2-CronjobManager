<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    protected $cronJobManager;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    	\Magento\Backend\App\Action\Context $context,
    	Manager $cronJobManager
    ) {
    	parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cronJobManager= $cronJobManager;
    }
    
    /**
     * {@inheritDoc}
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
    	return $this->_authorization->isAllowed('EthanYehuda_CronjobManager::cronjobmanager');
    }

    /**
     * Save cronjob
     *
     * @return Void
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
    		$this->_redirect('*/manage/create/');
    		return;
    	}
    	$this->getMessageManager()->addSuccessMessage("Successfully saved Cron Job: {$jobCode}");
    	if(!isset($params['back']))
    		$this->_redirect("*/manage/index/");
    	else
    		$this->_redirect("*/manage/{$params['back']}/", ['id' => $jobId]);
    }
}
