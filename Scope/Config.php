<?php
namespace EthanYehuda\CronjobManager\Scope;

use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    protected const RETRY_FAILED_JOBS = 'system/cron_job_manager/retry_failed_jobs';
    protected const RETRY_JOBS_GONE_AWAY = 'system/cron_job_manager/retry_jobs_gone_away';

    /** @var ConfigInterface */
    protected $magentoConfig;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    public function __construct(
        ConfigInterface $magentoConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->magentoConfig = $magentoConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function getJobs():array
    {
        return $this->magentoConfig->getJobs();
    }

    public function isRetryFailedJobs(): bool
    {
        return $this->scopeConfig->isSetFlag(self::RETRY_FAILED_JOBS);
    }

    public function isRetryJobsGoneAway(): bool
    {
        if ($this->isRetryFailedJobs()) {
            // If we are to retry all jobs, then we don't also want to retry
            // jobs which have been detected as missing/gone away. Doing so
            // would create duplicate entries in the schedule.
            return false;
        }

        return $this->scopeConfig->isSetFlag(self::RETRY_JOBS_GONE_AWAY);
    }
}
