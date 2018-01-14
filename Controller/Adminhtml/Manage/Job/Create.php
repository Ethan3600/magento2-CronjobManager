<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;

class Create extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Manager
     */
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
        $this->cronJobManager = $cronJobManager;
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
        $jobCode = $this->getRequest()->getParam('job_code');
        $scheduledAt = $this->getRequest()->getParam('scheduled_at');
        try {
            $this->cronJobManager->createCronJob($jobCode, $scheduledAt);
        } catch (\Magento\Framework\Exception\CronException $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/create/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully Created Cron Job: {$jobCode}");
        $this->_redirect('*/manage/index/');
    }
}
