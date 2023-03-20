<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Helper\JobConfig;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\App\CacheInterface;

class MassRestoreSystemDefault extends Action
{
    public const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";
    public const SYSTEM_DEFAULT_IDENTIFIER = 'system_default';

    /**
     * @param Context $context
     * @param JobConfig $helper
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
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/config/index');
            return;
        }

        try {
            foreach ($params as $jobCode) {
                $path = $this->helper->constructFrequencyPath($jobCode);
                $this->helper->restoreSystemDefault($path);
            }

            $this->cache->remove(self::SYSTEM_DEFAULT_IDENTIFIER);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->_redirect('*/config/index/');
            return;
        }

        $this->getMessageManager()->addSuccessMessage("Successfully restored system defaults");
        $this->_redirect("*/config/index/");
    }
}
