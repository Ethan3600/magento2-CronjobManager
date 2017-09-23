<?php

Namespace EthanYehuda\CronjobManager\Model;

use Magento\Cron\Observer\ProcessCronQueueObserver;
use \Magento\Cron\Model\Schedule;

class Manager extends ProcessCronQueueObserver
{
	public function createCronJob($jobCode, $time)
	{
		$filteredTime = $this->filterTimeInput($time);
		
		/**
		 * @var $schedule \Magento\Cron\Model\Schedule
		 */
		$schedule = $this->_scheduleFactory->create()
			->setJobCode($jobCode)
			->setStatus(Schedule::STATUS_PENDING)
			->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp()))
			->setScheduledAt($filteredTime);

		$schedule->getResource()->save($schedule);
	}
	
	public function deleteCronJob($jobId)
	{
		/**
		 * @var $scheduleResource \Magento\Cron\Model\ResourceModel\Schedule
		 */
		$schedule = $this->_scheduleFactory->create();
		$scheduleResource = $schedule->getResource();
		$scheduleResource->load($schedule, $jobId);
		$scheduleResource->delete($schedule);
	}
	
	public function flushCrons() 
	{
		$jobGroups = $this->_config->getJobs();
		foreach ($jobGroups as $groupId => $crons) {
			$this->_cleanup($groupId);
		}
	}
	
	protected function filterTimeInput($time) 
	{
		$matches = [];
		preg_match('/(\d+-\d+-\d+)T(\d+:\d+)/', $time, $matches);
		$yearMonthDate = $matches[1];
		$hourMinuets = " " . $matches[2];
		return $yearMonthDate . $hourMinuets;
	}
}