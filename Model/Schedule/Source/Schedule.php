<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

use Magento\Cron\Model\Config;

class Schedule implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $cronConfig;

    public function __construct(
        Config $config
    ) {
        $this->cronConfig = $config;
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
            $option = [
                'value' => $cron['name'],
                'label' => $cron['name']
            ];
            array_push($options, $option);
        }

        return $options;
    }

    /**
     * Returns array of all cron jobs
     *
     * Magento separates crons into "groups"
     * This method merges them into one array
     *
     * @param array $groups
     * @return array
     */
    private function mergeCronGroups($groups)
    {
        $merged = [];
        foreach ($groups as $group) {
            $merged = array_merge($merged, $group);
        }

        return $merged;
    }
}
