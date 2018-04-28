<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class MassDispatch extends Action
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    
    /**
     * @var ManagerFactory
     */
    private $managerFactory;
    
    public function __construct(
        ManagerFactory $managerFactory,
        PageFactory $resultPageFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->managerFactory = $managerFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        $manager = $this->managerFactory->create();
        $params = $this->getRequest()->getParam('selected');
        if (!isset($params)) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/manage/index');
            return;
        }
        try {
            foreach ($params as $jobId) {
                $manager->dispatchSchedule($jobId);
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/manage/index/');
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully Ran Schedules");
        $this->_redirect("*/manage/index/");
    }
}
