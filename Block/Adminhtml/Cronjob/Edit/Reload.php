<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Reload extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        $data = [
            'label' => __('Reload'),
            'class' => 'primary',
            'on_click' => "require('uiRegistry')
                .get('cronjobmanager_timeline.cronjobmanager_timeline.timeline_container')
                .reloader()",
            'sort_order' => 5,
        ];
        return $data;
    }
}
