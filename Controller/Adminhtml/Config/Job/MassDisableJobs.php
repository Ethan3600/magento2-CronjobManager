<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Helper\JobConfig;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config as ConfigCache;

class MassDisableJobs extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    /**
     * @param Context        $context
     * @param JobConfig      $helper
     * @param CacheInterface $cache
     */
    public function __construct(
        Context $context,
        private readonly JobConfig $helper,
        private readonly CacheInterface $cache,
    ) {
        parent::__construct($context);
    }

    /**
     * Save cronjob
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParam('selected');
        if (!isset($params)) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when receiving the request");
            $this->_redirect('*/config/index');
            return;
        }

        try {
            foreach ($params as $jobCode) {
                $path = $this->helper->constructFrequencyPath($jobCode);
                // Empty frequency disables the job
                $this->helper->saveJobFrequencyConfig($path, '');
            }

            // Clear the config cache
            $this->cache->clean([ConfigCache::CACHE_TAG]);
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/config/index/');

            return;
        }

        $this->getMessageManager()->addSuccessMessage("Successfully disabled " . count($params) . " jobs");
        $this->_redirect("*/config/index/");
    }
}
