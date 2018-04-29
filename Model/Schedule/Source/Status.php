<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

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
                'value' => Schedule::STATUS_ERROR,
                'label' => __('Error'),
            ],
            [
                'value' => Schedule::STATUS_MISSED,
                'label' => __('Missed'),
            ],
            [
                'value' => Schedule::STATUS_PENDING,
                'label' => __('Pending'),
            ],
            [
                'value' => Schedule::STATUS_RUNNING,
                'label' => __('Running'),
            ],
            [
                'value' => Schedule::STATUS_SUCCESS,
                'label' => __('Success'),
            ],
        ];
    }
}
