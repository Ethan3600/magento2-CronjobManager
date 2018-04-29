<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Flush extends Action
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
        try {
            $this->cronJobManager->flushCrons();
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/index/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully Flushed Cron Jobs");
        $this->_redirect('*/manage/index/');
    }
}
