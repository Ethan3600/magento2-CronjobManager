<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    /**
     * @var array
     */
    private $scheduleCache = [];

    /**
     * @param ScheduleFactory $scheduleFactory
     * @param ScheduleResource $scheduleResource
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly ScheduleFactory $scheduleFactory,
        private readonly ScheduleResource $scheduleResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(int $scheduleId): Schedule
    {
        if (isset($this->scheduleCache[$scheduleId])) {
            return $this->scheduleCache[$scheduleId];
        }

        $schedule = $this->scheduleFactory->create();
        $this->scheduleResource->load($schedule, $scheduleId);

        if (!$schedule->getId()) {
            throw new NoSuchEntityException(__('The Schedule with the "%1" ID doesn\'t exist.', $scheduleId));
        }

        $this->scheduleCache[$scheduleId] = $schedule;
        return $this->scheduleCache[$scheduleId];
    }

    /**
     * @inheritDoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var \EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function save(Schedule|Data\Schedule $schedule): Schedule
    {
        try {
            $this->scheduleResource->save($schedule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        unset($this->scheduleCache[$schedule->getId()]);

        return $schedule;
    }

    /**
     * @inheritDoc
     */
    public function delete(Schedule $schedule): bool
    {
        try {
            $this->scheduleResource->delete($schedule);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        unset($this->scheduleCache[$schedule->getId()]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $scheduleId): bool
    {
        return $this->delete($this->get($scheduleId));
    }
}
