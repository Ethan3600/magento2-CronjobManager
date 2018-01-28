<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\DataProviders\Cronjobmanager\Config;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Cache\StateInterface;

class Grid extends AbstractDataProvider
{
	const JOB_CONFIG_IDENTIFIER = 'ETHANYEHUDA_CRONJOBMANAGER_DATAPROVIDER_RECORDS';
	
	/**
	 * @var int cache lifetime in seconds
	 */
	const CACHE_LIFETIME = 180;
	
	/**
	 * Page size
	 * 
	 * @var int
	 */
	private $pageSize = 20;
	
	/**
	 * Pagination number
	 * 
	 * @var int
	 */
	private $pageNum = 1;
	
	/**
	 * @var string
	 */
	private $sortedColumn = 'job_code';
	
	/**
	 * @var string
	 */
	private $sortDirection = '';
	
	/**
	 * @var array
	 */
	private $records = [];
	
	/**
	 * @var CacheInterface
	 */
	private $cache;
	
	/**
	 * @var SerializerInterface
	 */
	private $serializer;
	
	/**
	 * @var StateInterface
	 */
	private $cacheState;
	
	/**
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
    	ManagerFactory $manager,
    	CacheInterface $cache,
    	SerializerInterface $serializer,
    	StateInterface $cacheState,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->manager = $manager->create();
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->cacheState = $cacheState;
    }
    
    public function getData()
    {
    	if($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)
    		&& $loadedData = $this->cache->load(self::JOB_CONFIG_IDENTIFIER)
    	) {
    		$this->records = $this->serializer->unserialize($loadedData);
    	} else {
	    	$this->prepareJobConfigRecords();
	    	
	    	$this->cache->save(
	    		$this->serializer->serialize($this->records),
	    		self::JOB_CONFIG_IDENTIFIER, 
	    		[Collection::CACHE_TAG],
	    		self::CACHE_LIFETIME
	    	);
    	}

    	if (!empty($this->sortDirection)) {
    	    $this->sortRecords();
    	}
    	$this->paginate();
    	return $this->records;
    }
    
    /**
     * Sets limits on pagination size
     * 
     * @param type $offset
     * @param type $size
     */
    public function setLimit($offset, $size)
    {
    	$this->pageSize = $size;
    	$this->pageNum = $offset;
    }
    
    /**
     * Set the sort order
     * 
     * @todo we need to override this method to avoid errors,
     * but this also means we need to implement our own sorting
     * mechanisim
     * 
     * @param type $field
     * @param type $direction
     */
    public function addOrder($col, $dir)
    {
    	$this->sortedColumn = $col;
    	$this->sortDirection = strtolower($dir);
    }
    
    private function prepareJobConfigRecords()
    {
    	$this->records = [
    			'totalRecords' => 0,
    			'items' => []
    	];
    	
    	$jobs = $this->manager->getCronJobs();
    	
    	foreach ($jobs as $group => $crons) {
    		foreach ($crons as $code => $job) {
    			$this->records['totalRecords']++;
    			$instance = $job['instance'];
    			$method = $job['method'];
    			$frequency= (isset($job['schedule']) ? $job['schedule'] : "");
    			$jobData = [
    					'job_code' => $code,
    					'group' => $group,
    					'frequency' => $frequency,
    					'class' => "$instance::$method()"
    			];
    			
    			array_push($this->records['items'], $jobData);
    		}
    	}
    }
    
    /**
     * Limits the amount of items provided to the UiComponent
     */
    private function paginate()
    {
    	$this->records['items'] = array_slice(
    		$this->records['items'],
    		(($this->pageNum - 1) * $this->pageSize),
    		$this->pageSize
    	);
    }
    
    /**
     * Sort records by the provided column and direction
     */
    private function sortRecords()
    {
        $items = $this->records['items'];
        $direction = $this->sortDirection;
        $col = $this->sortedColumn;
        
        usort($items, function($a, $b) use ($direction, $col) {
            if ($direction == 'asc') {
                return strcmp($a[$col], $b[$col]);
            } else if ($direction == 'desc') {
                return (-1 * strcmp($a[$col], $b[$col]));
            }       
        });
        
        $this->records['items'] = $items;
    }
}
