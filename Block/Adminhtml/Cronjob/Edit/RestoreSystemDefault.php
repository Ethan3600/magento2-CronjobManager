<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class RestoreSystemDefault extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        $params = $this->getRequestParams();
        unset($params['key'], $params['form_key']);
        $data = [
            'label' => __('Restore System Default'),
            'class' => 'secondary',
            'on_click' => 'deleteConfirm(\''
            . __('Are you sure you want to restore this to system defaults?')
            . '\', \''
            . $this->urlBuilder->getUrl('*/config_job/restoreSystemDefault', $params)
            . '\')',
            'sort_order' => 5,
        ];
        return $data;
    }
}
