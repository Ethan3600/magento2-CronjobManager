<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        $params = $this->getRequestParams();
        $cronId = $params['id'];

        $data = [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\''
                . __('Are you sure you want to delete this?')
                . '\', \''
                . $this->urlBuilder->getUrl('*/manage_job/delete', ['id' => $cronId])
                . '\')',
            'sort_order' => 20,
        ];
        return $data;
    }
}
