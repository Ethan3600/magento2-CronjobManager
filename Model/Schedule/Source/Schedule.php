<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

use Magento\Cron\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class Schedule implements OptionSourceInterface
{
    /**
     * @param Config $cronConfig
     */
    public function __construct(
        private readonly Config $cronConfig,
    ) {
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $cronJobs = $this->mergeCronGroups($this->cronConfig->getJobs());

        $options = [];
        foreach ($cronJobs as $cron) {
            if (empty($cron['name'])) {
                continue;
            }

            $option = [
                'value' => $cron['name'],
                'label' => $cron['name']
            ];
            $options[] = $option;
        }

        \usort($options, function ($a, $b) {
            return \strnatcmp($a['label'], $b['label']);
        });

        return $options;
    }

    /**
     * Returns array of all cron jobs
     *
     * Magento separates crons into "groups"
     * This method merges them into one array
     *
     * @param array $groups
     *
     * @return array
     */
    private function mergeCronGroups($groups)
    {
        $merged = [];
        foreach ($groups as $group) {
            $merged += $group;
        }
        return $merged;
    }
}
