<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\DataProviders\Cronjobmanager\Config;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Grid extends AbstractDataProvider
{
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
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
    	ManagerFactory $manager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->manager = $manager->create();
    }
    
    public function getData()
    {
	  	$this->prepareJobConfigRecords();

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
