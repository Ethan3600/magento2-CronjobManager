<?php

namespace EthanYehuda\CronjobManager\Model\ResourceModel\Schedule;

use Magento\Cron\Model\ResourceModel\Schedule\Collection as ScheduleResourceModelCollection;

class Collection extends ScheduleResourceModelCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = "schedule_id";
}
