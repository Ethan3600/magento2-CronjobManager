<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;

class ScheduleStatus extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                switch ($item[$this->getData('name')]) {
                    case ScheduleInterface::STATUS_ERROR:
                        $class = 'grid-severity-critical';
                        $text = __('Error');
                        break;
                    case ScheduleInterface::STATUS_MISSED:
                        $class = 'grid-severity-minor';
                        $text = __('Missed');
                        break;
                    case ScheduleInterface::STATUS_PENDING:
                        $class = 'grid-severity-pending';
                        $text = __('Pending');
                        break;
                    case ScheduleInterface::STATUS_RUNNING:
                        $class = 'grid-severity-running';
                        $text = __('Running');
                        break;
                    case ScheduleInterface::STATUS_SUCCESS:
                        $class = 'grid-severity-notice';
                        $text = __('Success');
                        break;
                    case ScheduleInterface::STATUS_KILLED:
                        $class = 'grid-severity-critical';
                        $text = __('Killed');
                        break;
                    default:
                        $class = 'grid-severity-minor';
                        $text = __('Unknown');
                        break;
                }

                $html = '<span class="' . $class . '"><span>' . $text . '</span></span>';
                $item[$this->getData('name')] = $html;
            }
        }

        return $dataSource;
    }
}
