<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

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
    
    /**
     * @var Filter
     */
    private $filter;
    
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    
    public function __construct(
        ManagerFactory $managerFactory,
        PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->managerFactory = $managerFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        if ($collection->getSize() < 1) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/manage/index');
            return;
        }
        
        foreach ($collection->getItems() as $schedule) {
            try {
                $manager->dispatchSchedule($schedule->getId(), $schedule);
            } catch (\Exception $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
            }
        }
       
        $this->getMessageManager()->addSuccessMessage("Successfully Ran Schedules");
        $this->_redirect("*/manage/index/");
    }
}
