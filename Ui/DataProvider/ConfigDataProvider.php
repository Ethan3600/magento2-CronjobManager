<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Registry;

class ConfigDataProvider extends AbstractDataProvider
{
    private $loadedData = [];
    
    /**
     * @var Magento\Framework\Registry;
     */
    private $coreRegistry;

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
        Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        $cron = $this->coreRegistry->registry(
            RegistryConstants::CURRENT_CRON_CONFIG
        );
        
        $jobCode = $cron['job_code'];
        $this->loadedData[$jobCode] = $cron;
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
