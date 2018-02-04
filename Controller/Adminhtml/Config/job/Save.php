<?php

namespace EthanYehuda\CronjobManager\Controller\Adminhtml\Config\Job;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\App\CacheInterface;

class Save extends \Magento\Backend\App\Action
{
    const SYSTEM_DEFAULT_IDENTIFIER = 'system_default';
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    
    /**
     * @var WriterInterface
     */    
    private $configWriter;
    
    /**
     * @var ManagerFactory
     */
    private $managerFactory;
    
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\App\Action\Context $context,
        WriterInterface $configWriter,
        ManagerFactory $managerFactory,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->configWriter = $configWriter;
        $this->managerFactory = $managerFactory;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('EthanYehuda_CronjobManager::cronjobmanager');
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
            $path = $this->constructPath($group, $jobCode);
            $this->configWriter->save($path, $frequency);
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
    
    private function constructPath($group, $jobCode)
    {  
        $validGroupId = $this->managerFactory->create()->getGroupId($jobCode);
        if (!$validGroupId) {
            throw new ValidatorException("Job Code: $jobCode does not exist in the system");
        }
        if ($group != $validGroupId) {
            throw new ValidatorException("Invalid Group ID: $group for $jobCode");
        }
        
        return "crontab/$group/jobs/$jobCode/schedule/cron_expr";
    }
}
