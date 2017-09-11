<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @todo Need to figure out how to get the ID
     * @return array
     */
    public function getButtonData()
    {
    	$cronId = '';

		$data = [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                'Are you sure you want to delete this?'
            ) . '\', \'' . $this->urlBuilder->getUrl('*/*/delete', ['id' => $cronId]) . '\')',
            'sort_order' => 20,
        ];
        return $data;
    }
}
