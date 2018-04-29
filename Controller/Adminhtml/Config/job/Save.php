<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Helper\JobConfig;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;

class Save extends Action
{
    const ADMIN_RESOURCE = "EthanYehuda_CronjobManager::cronjobmanager";

    const SYSTEM_DEFAULT_IDENTIFIER = 'system_default';
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    
    /**
     * @var ManagerFactory
     */
    private $managerFactory;
    
    /**
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * @var JobConfig
     */
    private $helper;

    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        CacheInterface $cache,
        JobConfig $helper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->cache = $cache;
        $this->helper = $helper;
    }

    /**
     * Save cronjob
     *
     * @return Void
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $jobCode = $params['job_code'] ? $params['job_code'] : null;
        if (!$jobCode) {
            $this->getMessageManager()->addErrorMessage("Something went wrong when recieving the request");
            $this->_redirect('*/config/edit/');
            return;
        }
        $group = $params['group'] ? $params['group'] : null;
        $frequency = $params['frequency'] ? $params['frequency'] : null;
        try {
            $path = $this->helper->constructFrequencyPath($jobCode, $group);
            $this->helper->saveJobFrequencyConfig($path, $frequency);
            $this->cache->remove(self::SYSTEM_DEFAULT_IDENTIFIER);
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            unset($params['key'], $params['form_key']);
            $this->_redirect('*/config/edit/', $params);
            return;
        }
        $this->getMessageManager()->addSuccessMessage("Successfully saved Cron Job: {$jobCode}");
        if (!isset($params['back'])) {
            $this->_redirect("*/config/index/");
        } else {
            unset($params['key'], $params['form_key']);
            $this->_redirect("*/config/{$params['back']}/", $params);
        }
    }
}
