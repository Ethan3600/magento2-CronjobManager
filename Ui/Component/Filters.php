<?php

namespace EthanYehuda\CronjobManager\Ui\Component;

use Magento\Ui\Component\Filters as MageFilters;

class Filters extends MageFilters
{
    protected $filterMap = [
        // Original list
        'dateRange' => 'filterDate',
        'select' => 'filterSelect',
        'text' => 'filterInput',
        'textRange' => 'filterRange',

        // Added
        'textWithDatalist' => 'filterInput',
    ];
}
