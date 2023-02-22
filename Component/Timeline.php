<?php

namespace EthanYehuda\CronjobManager\Component;

use Magento\Ui\Component\AbstractComponent;

class Timeline extends AbstractComponent
{
    public const NAME = 'cronjobmanager_timeline';

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return static::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getDataSourceData()
    {
        return ['data' => $this->getContext()->getDataProvider()->getData()];
    }
}
