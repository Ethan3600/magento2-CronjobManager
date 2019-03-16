<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ScheduleInterface::STATUS_ERROR,
                'label' => __('Error'),
            ],
            [
                'value' => ScheduleInterface::STATUS_MISSED,
                'label' => __('Missed'),
            ],
            [
                'value' => ScheduleInterface::STATUS_PENDING,
                'label' => __('Pending'),
            ],
            [
                'value' => ScheduleInterface::STATUS_RUNNING,
                'label' => __('Running'),
            ],
            [
                'value' => ScheduleInterface::STATUS_SUCCESS,
                'label' => __('Success'),
            ],
            [
                'value' => ScheduleInterface::STATUS_KILLED,
                'label' => __('Killed'),
            ],
        ];
    }
}
