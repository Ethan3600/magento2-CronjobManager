<?php

namespace EthanYehuda\CronjobManager\Helper;

use EthanYehuda\CronjobManager\Model\Manager;
use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\CronException;
use Magento\Framework\Exception\ValidatorException;

class JobConfig extends AbstractHelper
{
    /** @var Manager */
    private $manager;

    /** @var string[] */
    private $jobs;

    /**
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param ManagerFactory $managerFactory
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        Context $context,
        private readonly WriterInterface $configWriter,
        ManagerFactory $managerFactory,
        private readonly ScheduleFactory $scheduleFactory,
    ) {
        parent::__construct($context);
        $this->manager = $managerFactory->create();
    }

    /**
     * Get job data
     *
     * @param string $jobCode
     *
     * @return array|false
     */
    public function getJobData($jobCode)
    {
        if (!isset($this->jobs)) {
            $this->jobs = $this->manager->getCronJobs();
        }

        foreach ($this->jobs as $groupName => $group) {
            if (isset($group[$jobCode])) {
                $group[$jobCode]['group'] = $groupName;
                return $this->sanitizeJobConfig($group[$jobCode]);
            }
        }

        return false;
    }

    /**
     * Store configuration at the specified path
     *
     * @param string $path
     * @param string $frequency
     *
     * @return void
     */
    public function saveJobFrequencyConfig($path, $frequency)
    {
        $this->configWriter->save($path, $frequency);
    }

    /**
     * Delete configuration for a specific path
     *
     * @param string $path
     *
     * @return void
     */
    public function restoreSystemDefault($path)
    {
        $this->configWriter->delete($path);
    }

    /**
     * Generate configuration path for job code (in a given group)
     *
     * @param string $jobCode
     * @param string $group
     *
     * @return string
     * @throws ValidatorException
     */
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

    /**
     * Sanitise job configuration
     *
     * @param array $job
     *
     * @return array
     */
    public function sanitizeJobConfig(array $job)
    {
        $job['name'] = !empty($job['name']) ? $job['name'] : '';
        $job['group'] = !empty($job['group']) ? $job['group'] : '';
        $job['schedule'] = !empty($job['schedule']) ? $job['schedule'] : '';
        $job['instance'] = !empty($job['instance']) ? $job['instance'] : '';
        $job['method'] = !empty($job['method']) ? $job['method'] : '';
        if (isset($job['config_path'])) {
            $job['schedule'] = $this->parseCronConfigPath($job);
        }
        return $job;
    }

    /**
     * Validate cronjob frequency string
     *
     * @param string|null $frequency
     *
     * @throws CronException
     * @see \Magento\Cron\Model\Schedule::trySchedule()
     */
    public function validateFrequency(?string $frequency): void
    {
        if ($frequency === null) {
            return;
        }

        $schedule = $this->scheduleFactory->create();
        $schedule->setCronExpr($frequency);

        foreach ($schedule->getCronExprArr() as $expression) {
            $schedule->matchCronExpression($expression, 0);
        }
    }

    public function parseCronConfigPath(array $job): string
    {
        return $this->scopeConfig->getValue($job['config_path']) ?? '';
    }
}
