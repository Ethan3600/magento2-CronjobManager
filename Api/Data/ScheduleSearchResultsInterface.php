<?php

namespace EthanYehuda\CronjobManager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ScheduleSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items list
     *
     * @return \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface[]
     */
    public function getItems();

    /**
     * Set items list
     *
     * @param \EthanYehuda\CronjobManager\Api\Data\ScheduleInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items);
}
