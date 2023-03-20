<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\Data\ScheduleInterfaceFactory;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

class ScheduleRepositoryAdapter implements ScheduleRepositoryAdapterInterface
{
    /**
     * @param ScheduleRepositoryInterface $scheduleRepository
     * @param ScheduleInterfaceFactory $scheduleFactory
     * @param ScheduleFactory $coreScheduleFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly ScheduleRepositoryInterface $scheduleRepository,
        private readonly ScheduleInterfaceFactory $scheduleFactory,
        private readonly ScheduleFactory $coreScheduleFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(int $scheduleId): ScheduleInterface
    {
        $entity = $this->scheduleRepository->get($scheduleId);

        return $this->scheduleFactory->create(['data' => $entity->getData()]);
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $list = [];
        $result = $this->scheduleRepository->getList($searchCriteria);
        foreach ($result->getItems() as $key => $item) {
            $list[$key] = $this->scheduleFactory->create(['data' => $item->getData()]);
        }

        $result->setItems($list);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save(ScheduleInterface $schedule, $scheduleId = null): ScheduleInterface
    {
        if ($scheduleId) {
            $schedule->setScheduleId($scheduleId);
        }

        $coreSchedule = $this->coreScheduleFactory->create(['data' => $schedule->getData()]);
        $coreSchedule->setHasDataChanges(true);
        $this->scheduleRepository->save($coreSchedule);

        return $this->scheduleFactory->create(['data' => $coreSchedule->getData()]);
    }

    /**
     * @inheritDoc
     */
    public function getByStatus($status)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('status', $status)->create();
        return $this->getList($searchCriteria)->getItems();
    }
}
