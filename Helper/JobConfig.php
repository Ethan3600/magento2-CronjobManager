<?php

namespace EthanYehuda\CronjobManager\Helper;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\ValidatorException;

class JobConfig extends AbstractHelper
{
    /**
     * @var WriterInterface
     */
    private $configWriter;
    
    /**
     * @var ManagerFactory
     */
    private $managerFactory;
    
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        ManagerFactory $managerFactory
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->managerFactory = $managerFactory;
    }
    
    public function saveJobFrequencyConfig($path, $frequency)
    {
        $this->configWriter->save($path, $frequency);
    }
    
    public function restoreSystemDefault($path)
    {
        $this->configWriter->delete($path);
    }
    
    public function constructFrequencyPath($group, $jobCode)
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