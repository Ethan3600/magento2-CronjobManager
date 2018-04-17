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
     * @var EthanYehuda\CronjobManager\Model\Manager
     */
    private $manager;
    
    private $jobs = null;
    
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        ManagerFactory $managerFactory
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->manager = $managerFactory->create();
    }
    
    public function getJobData($jobCode)
    {
        if(is_null($this->jobs)) {
            $this->jobs = $this->manager->getCronJobs();
        }
        
        foreach($this->jobs as $groupName => $group) {
            if (isset($group[$jobCode])) {
                $group[$jobCode]['group'] = $groupName;
                return $this->sanitizeJobConfig($group[$jobCode]);
            }
        }
        
        return false;
    }
    
    public function saveJobFrequencyConfig($path, $frequency)
    {
        $this->configWriter->save($path, $frequency);
    }
    
    public function restoreSystemDefault($path)
    {
        $this->configWriter->delete($path);
    }
    
    public function constructFrequencyPath($jobCode, $group = null)
    {
        $validGroupId = $this->manager->getGroupId($jobCode);
        if (!$validGroupId) {
            throw new ValidatorException("Job Code: $jobCode does not exist in the system");
        }
        if ($group) {
            if ($group != $validGroupId) {
                throw new ValidatorException("Invalid Group ID: $group for $jobCode");
            }
        } else {
            $group = $validGroupId;
        }
        return "crontab/$group/jobs/$jobCode/schedule/cron_expr";
    }

    public function sanitizeJobConfig(array $job)
    {
        $job['name'] = !empty($job['name']) ? $job['name'] : '';
        $job['group'] = !empty($job['group']) ? $job['group'] : '';
        $job['schedule'] = !empty($job['schedule']) ? $job['schedule'] : '';
        $job['instance'] = !empty($job['instance']) ? $job['instance'] : ''; 
        $job['method'] = !empty($job['method']) ? $job['method'] : '';
        return $job;
    }
}
