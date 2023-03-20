<?php

namespace EthanYehuda\CronjobManager\Ui\Component;

use Magento\Ui\Component\Filters as MageFilters;

class Filters extends MageFilters
{
    /** @var string[] */
    protected $filterMap = [
        // Original list
        'text' => 'filterInput',
        'textRange' => 'filterRange',
        'select' => 'filterSelect',
        'dateRange' => 'filterDate',
        'datetimeRange' => 'filterDate',

        // Added
        'textWithDatalist' => 'filterInput',
    ];
}
