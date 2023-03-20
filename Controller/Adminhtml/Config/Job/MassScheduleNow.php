<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class MassScheduleNow extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    public function __construct(
        private readonly PageFactory $resultPageFactory,
        Context $context,
        private readonly ManagerFactory $managerFactory,
    ) {
        parent::__construct($context);
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
            $this->_redirect('*/config/index');
            return;
        }
        try {
            foreach ($params as $jobCode) {
                $manager->scheduleNow($jobCode);
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
