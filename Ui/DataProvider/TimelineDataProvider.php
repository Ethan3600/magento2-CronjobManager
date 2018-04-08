<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class TimelineDataProvider extends AbstractDataProvider
{
    private $loadedData;

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
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }
        $this->collection->addOrder('job_code');
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[$item->getJobCode()][] = $item->getData();
        }

        return $this->loadedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        return $meta;
    }
}
