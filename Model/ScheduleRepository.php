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
     * @var ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var ScheduleResource
     */
    private $scheduleResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var array
     */
    private $scheduleCache = [];

    public function __construct(
        ScheduleFactory $scheduleFactory,
        ScheduleResource $scheduleResource,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->scheduleResource = $scheduleResource;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
    }

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

    public function save(Schedule $schedule): Schedule
    {
        try {
            $this->scheduleResource->save($schedule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        unset($this->scheduleCache[$schedule->getId()]);

        return $schedule;
    }

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

    public function deleteById(int $scheduleId): bool
    {
        return $this->delete($this->get($scheduleId));
    }
}
