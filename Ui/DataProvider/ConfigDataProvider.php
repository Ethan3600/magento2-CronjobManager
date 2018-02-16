<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use EthanYehuda\CronjobManager\Helper\JobConfig;
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
     * @var JobConfig;
     */
    private $helper;

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
        JobConfig $helper,
        Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->helper = $helper;
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

        $params = $this->coreRegistry->registry(
            RegistryConstants::CURRENT_CRON_CONFIG
        );
        
        $jobCode = $params['job_code'];
        $jobData = $this->helper->getJobData($jobCode);
        
        $jobData = [
            'job_code'  => $jobData['name'],
            'group'     => $jobData['group'],
            'frequency' => $jobData['schedule'],
            'class'     => $jobData["instance"] 
                        . '::' . $jobData['method'] . "()"
        ];
        
        $this->loadedData[$jobCode] = $jobData;
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
    
    /**
     * Remove dependency to the collections
     * 
     * @see \Magento\Ui\DataProvider\AbstractDataProvider::addFilter()
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }
}
