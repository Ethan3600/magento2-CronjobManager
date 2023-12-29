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
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";
    protected const MAX_QUERY_SIZE = 10;

    /**
     * @param ManagerFactory $managerFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Context $context
     */
    public function __construct(
        private readonly ManagerFactory $managerFactory,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        Context $context
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
                    $manager->deleteCronJob($schedule->getId());
                } catch (\Exception $e) {
                    $this->getMessageManager()->addErrorMessage($e->getMessage());
                }
            }
        }

        $this->getMessageManager()->addSuccessMessage("Successfully Deleted Schedules");
        $this->_redirect("*/manage/index/");
    }
}
