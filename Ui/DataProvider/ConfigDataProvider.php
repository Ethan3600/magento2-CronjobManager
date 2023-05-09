<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use EthanYehuda\CronjobManager\Helper\JobConfig;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Registry;

class ConfigDataProvider extends AbstractDataProvider
{
    /** @var array */
    private $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param JobConfig $helper
     * @param Registry $coreRegistry
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly JobConfig $helper,
        private readonly Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
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
            'job_code'  => $params['job_code'] ?? $jobData['name'],
            'group'     => $params['group'] ?? $jobData['group'],
            'frequency' => $params['frequency'] ?? $jobData['schedule'],
            'class'     => $params['class'] ?? ($jobData["instance"] . '::' . $jobData['method'] . "()"),
        ];

        $this->loadedData[$jobCode] = $jobData;
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

    /**
     * Remove dependency to the collections
     *
     * @see AbstractDataProvider::addFilter()
     *
     * @param Filter $filter
     *
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
        return;
    }
}
