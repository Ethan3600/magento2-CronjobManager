<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class MassScheduleNow extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * @var ManagerFactory
     */
    private $managerFactory;

    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        ManagerFactory $managerFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->managerFactory = $managerFactory;
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
