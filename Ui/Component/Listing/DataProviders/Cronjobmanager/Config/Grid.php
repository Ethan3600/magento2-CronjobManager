<?php
namespace EthanYehuda\CronjobManager\Ui\Component\Listing\DataProviders\Cronjobmanager\Config;

use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;

class Grid extends \Magento\Ui\DataProvider\AbstractDataProvider
{
	private $loadedData;
	
	/**
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
    	CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = [],
    	Manager $manager
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->manager = $manager;
        $this->collection = $collectionFactory->create();
    }
    
    public function getData()
    {
    	if($this->loadedData) {
    		return $this->loadedData;
    	}
    	
    	$jobs = $this->manager->getCronJobs();
    	
    	foreach ($jobs as $group => $crons) {
    		foreach ($crons as $code => $job) {
    			$instance = $job['instance'];
    			$method = $job['method'];
    			$schedule = (isset($job['schedule']) ? $job['schedule'] : "");
    			$jobData = [
    				$code,
    				$group,
    				$schedule,
    				"$instance::$method"
    			];
    			
    			$this->loadedData[$code] = $jobData;
    		}
    	}
    	
    	return $this->loadedData;
    }
    
    public function getMeta()
    {
    	$meta = parent::getMeta();
    	return $meta;
    }
}
