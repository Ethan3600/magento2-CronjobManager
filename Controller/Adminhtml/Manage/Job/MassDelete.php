<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    const MAX_QUERY_SIZE = 10;
    
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
        $this->resultPageFactory = $resultPageFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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
        $size = $collection->getSize();
        if ($size < 1) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/manage/index');
            return;
        }
        
        if ($size > self::MAX_QUERY_SIZE) {
            $deleteQuery = $collection->getSelect()->deleteFromSelect('main_table');
            $collection->getConnection()->query($deleteQuery);
        } else {
            foreach ($collection->getItems() as $schedule) {
                try {
                    $manager->deleteCronJob($schedule->getId(), $schedule);
                } catch (\Exception $e) {
                    $this->getMessageManager()->addErrorMessage($e->getMessage());
                }
            }
        }
        
        $this->getMessageManager()->addSuccessMessage("Successfully Deleted Schedules");
        $this->_redirect("*/manage/index/");
    }
}
