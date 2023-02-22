<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\DataProviders\Cronjobmanager\Config;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use EthanYehuda\CronjobManager\Helper\JobConfig;
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

    /**
     * @var Array
     */
    private $filterRegistry = [];

    /**
     * Used to point to current filter
     *
     * @var Array
     */
    private $currentFilter;

    /**
     * @var JobConfig
     */
    private $helper;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ManagerFactory $manager,
        JobConfig $helper,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->manager = $manager->create();
        $this->helper = $helper;
    }

    public function getData()
    {
        $this->prepareJobConfigRecords();

        if (!empty($this->sortDirection)) {
            $this->sortRecords();
        }

        if (!empty($this->filterRegistry)) {
            $this->filterRecords();
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

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $conditionType = $filter->getConditionType();
        $filterRegistry = [
            'field'         => $filter->getField(),
            'condition'     => $filter->getValue()
        ];
        switch ($conditionType) {
            case 'like':
                $filterRegistry['filter'] = function ($v) {
                    $reg = $this->currentFilter;
                    return strpos($v[$reg['field']], $reg['condition']) !== false;
                };
                $filterRegistry['condition'] = trim($filterRegistry['condition'], "%");
                $filterRegistry['condition'] = str_replace(['\%', '\_'], ['%', '_'], $filterRegistry['condition']);
                $this->filterRegistry[] = $filterRegistry;
                break;
            case 'eq':
                $filterRegistry['filter'] = function ($v) {
                    $reg = $this->currentFilter;
                    return $v[$reg['field']] === $reg['condition'];
                };
                $this->filterRegistry[] = $filterRegistry;
                break;
            default:
                break;
        }
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
                $job = $this->helper->sanitizeJobConfig($job);
                $this->records['totalRecords']++;
                $instance = $job['instance'];
                $method = $job['method'];
                $frequency = $job['schedule'];
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

        usort($items, function ($a, $b) use ($direction, $col) {
            if ($direction == 'asc') {
                return strcmp($a[$col], $b[$col]);
            } elseif ($direction == 'desc') {
                return (-1 * strcmp($a[$col], $b[$col]));
            }
        });

        $this->records['items'] = $items;
    }

    private function filterRecords()
    {
        foreach ($this->filterRegistry as $filter) {
            $this->currentFilter = $filter;
            $this->records['items'] = array_filter(
                $this->records['items'],
                $filter['filter'],
                ARRAY_FILTER_USE_BOTH
            );
        }

        $this->records['totalRecords'] = count($this->records['items']);
    }
}
