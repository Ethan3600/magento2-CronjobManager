<?php

namespace EthanYehuda\CronjobManager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ScheduleSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface[]
     */
    public function getItems();

    /**
     * @param \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface[]
     * @return $this
     */
    public function setItems(array $items);
}
