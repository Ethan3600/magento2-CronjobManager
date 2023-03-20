<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Manage\Job;

use EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\CollectionFactory;
use EthanYehuda\CronjobManager\Model\ScheduleManagement;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

class MassDispatch extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ScheduleManagement $scheduleManagement
     * @param Context $context
     */
    public function __construct(
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ScheduleManagement $scheduleManagement,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Schedule a new run of each selected jobcode
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        if ($collection->getSize() < 1) {
            $this->getMessageManager()->addErrorMessage(__('Something went wrong when receiving the request'));
            $this->_redirect('*/manage/index');
            return;
        }

        foreach ($collection->getItems() as $schedule) {
            try {
                $this->scheduleManagement->scheduleNow($schedule->getJobCode());
            } catch (\Exception $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
            }
        }

        $this->getMessageManager()->addSuccessMessage(__('Successfully scheduled selected jobs'));
        $this->_redirect('*/manage/index');
    }
}
