<?php

namespace EthanYehuda\CronjobManager\Block\Adminhtml\Cronjob\Edit;

use EthanYehuda\CronjobManager\Model\RegistryConstants;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

class GenericButton
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        protected Registry $registry,
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Return suitable URL parameters for editing the current job
     */
    public function getRequestParams()
    {
        return $this->registry->registry(RegistryConstants::CURRENT_CRON_JOB) ?
            $this->registry->registry(RegistryConstants::CURRENT_CRON_JOB) :
            $this->registry->registry(RegistryConstants::CURRENT_CRON_CONFIG);
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     *
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
