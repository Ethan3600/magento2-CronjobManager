<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'on_click' => '',
        	'data_attribute' => [
        		'mage-init' => [
        			'button' => ['event' => 'saveAndContinueEdit'],
        		]
        	],
            'sort_order' => 90,
        ];
        return $data;
    }
}
