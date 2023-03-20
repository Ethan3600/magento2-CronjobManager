<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Message\ManagerInterface;

class TimelineDataProvider extends AbstractDataProvider
{
    protected const MAX_PAGE_SIZE = 35000;

    /** @var array */
    private $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly ManagerInterface $messageManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        $firstHour = null;
        $lastHour = null;

        $this->collection
            ->addOrder('scheduled_at', 'DESC')
            ->addOrder('job_code', 'ASC')
            ->setPageSize(self::MAX_PAGE_SIZE)
            ->addFieldToFilter(
                'scheduled_at',
                [
                    'gt' => date(
                        'Y-m-d H:m:s',
                        strtotime(date('Y-m-d H:m:s') . ' -7 day')
                    )
                ]
            );

        $collectionSize = $this->collection->count();
        if ($collectionSize < 1) {
            $this->messageManager->addErrorMessage(
                "No cron jobs are currently in the queue. Please double check crontab configurations"
            );
            return [];
        }

        foreach ($this->collection->getItems() as $item) {
            /** \Magento\Framework\DataObject $item */
            $this->loadedData[$item->getJobCode()][] = $item->getData();

            if ($firstHour === null) {
                $firstHour = $this->getFirstHour($item);
                $lastHour = $this->getLastHour($item);
            } else {
                $firstHour = min($firstHour, $this->getFirstHour($item));
                $lastHour = max($lastHour, $this->getLastHour($item));
            }
        }

        array_unshift($this->loadedData, [
            'total' => $collectionSize,
            'range' => [
                'first' => $firstHour,
                'last' => $lastHour,
            ],
        ]);

        return $this->loadedData;
    }

    /**
     * Calculate the first time of a particular job
     *
     * @param DataObject $item
     *
     * @return int
     */
    private function getFirstHour(DataObject $item): int
    {
        $firstHour = $item->getScheduledAt();
        if (empty($firstHour)) {
            $firstHour = $item->getExecutedAt();
        }

        if (empty($firstHour)) {
            $firstHour = $item->getFinishedAt();
        }

        if (empty($firstHour)) {
            $firstHour = $item->getCreatedAt();
        }

        return (int) strtotime($firstHour);
    }

    /**
     * Calculate the last time of a particular job
     *
     * @param DataObject $item
     *
     * @return int
     */
    private function getLastHour(DataObject $item): int
    {
        $lastHour = $item->getFinishedAt();
        if (empty($lastHour)) {
            $lastHour = $item->getExecutedAt();
        }

        if (empty($lastHour)) {
            $lastHour = $item->getScheduledAt();
        }

        if (empty($lastHour)) {
            $lastHour = $item->getCreatedAt();
        }

        return (int) strtotime($lastHour);
    }
}
