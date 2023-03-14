<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Message\ManagerInterface;

class TimelineDataProvider extends AbstractDataProvider
{
    protected const MAX_PAGE_SIZE = 35000;

    private $loadedData;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        ManagerInterface $messageManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->messageManager = $messageManager;
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
            $this->loadedData[$item->getJobCode()][] = $item->getData();

            $minimumTime = $this->getFirstHour($item);
            $firstHour = is_null($firstHour) ?
                $minimumTime: min($firstHour, $this->getFirstHour($item));

            $lastHour  = is_null($lastHour) ?
                $minimumTime: max($lastHour, $this->getLastHour($item));
        }

        array_unshift($this->loadedData, [
            'total' => $collectionSize,
            'range' => $this->getRange($firstHour, $lastHour)
        ]);

        return $this->loadedData;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        return $meta;
    }

    private function getRange($firstHour, $lastHour)
    {
        return [
            'first' => $firstHour,
            'last' => $lastHour
        ];
    }

    private function getFirstHour($item)
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
        return strtotime($firstHour);
    }

    private function getLastHour($item)
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
        return strtotime($lastHour);
    }
}
