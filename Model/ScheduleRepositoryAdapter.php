<?php

namespace EthanYehuda\CronjobManager\Model;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\Data\ScheduleInterfaceFactory;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ScheduleRepositoryAdapter implements ScheduleRepositoryAdapterInterface
{
    /**
     * @var ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var ScheduleInterfaceFactory
     */
    private $scheduleFactory;

    /**
     * @var ScheduleFactory
     */
    private $coreScheduleFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        ScheduleRepositoryInterface $scheduleRepository,
        ScheduleInterfaceFactory $scheduleFactory,
        ScheduleFactory $coreScheduleFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->scheduleRepository = $scheduleRepository;
        $this->scheduleFactory = $scheduleFactory;
        $this->coreScheduleFactory = $coreScheduleFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function get(int $scheduleId): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        $entity = $this->scheduleRepository->get($scheduleId);

        return $this->scheduleFactory->create(['data' => $entity->getData()]);
    }

    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): \Magento\Framework\Api\SearchResultsInterface
    {
        $list = [];
        $result = $this->scheduleRepository->getList($searchCriteria);
        foreach ($result->getItems() as $key => $item) {
            $list[$key] = $this->scheduleFactory->create(['data' => $item->getData()]);
        }

        $result->setItems($list);

        return $result;
    }

    public function save(\EthanYehuda\CronjobManager\Api\Data\ScheduleInterface $schedule, $scheduleId = null): \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface
    {
        if ($scheduleId) {
            $schedule->setScheduleId($scheduleId);
        }

        $coreSchedule = $this->coreScheduleFactory->create(['data' => $schedule->getData()]);
        $coreSchedule->setHasDataChanges(true);
        $this->scheduleRepository->save($coreSchedule);

        return $this->scheduleFactory->create(['data' => $coreSchedule->getData()]);
    }

    public function getByStatus($status)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('status', $status)->create();
        return $this->getList($searchCriteria)->getItems();
    }

}
