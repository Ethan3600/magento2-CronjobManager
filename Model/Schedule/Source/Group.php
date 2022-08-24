<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

use Magento\Cron\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class Group implements OptionSourceInterface
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
        $groups = \array_keys($this->cronConfig->getJobs());
        \natsort($groups);

        $options = [];
        foreach ($groups as $group) {
            $options[] = [
                'value' => $group,
                'label' => $group,
            ];
        }
        return $options;
    }
}
