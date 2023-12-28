<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;

class Frequency extends Column
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (empty($item['frequency'])) {
                    $item['frequency'] = __('Disabled');
                }
            }
        }

        return $dataSource;
    }
}
