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
	
	public function saveCronJob($jobId, $jobCode = null, $status = null, $time = null)
	{
		$filteredTime = $this->filterTimeInput($time);
		
		$schedule = $this->_scheduleFactory->create();
		$scheduleResource = $schedule->getResource();
		$scheduleResource->load($schedule, $jobId);
		
		if(!is_null($jobCode))
			$schedule->setJobCode($jobCode);
		if(!is_null($status))
			$schedule->setStatus($status);
		if(!is_null($time))
			$schedule->setScheduledAt($filteredTime);
		
		$scheduleResource->save($schedule);
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
	
	public function dispatchCron($jobId, $jobCode)
	{
		$groups = $this->_config->getJobs();
		$groupId = $this->getGroupId($jobCode, $groups);
		$jobConfig = $groups[$groupId][$jobCode];
		$schedule = $this->loadSchedule($jobId);
		$scheduledTime = $this->timezone->scopeTimeStamp();
		
		/* We need to trick the method into thinking it should run now so we
		 *  set the scheduled and current time to be equal to one another */ 
		$this->_runJob($scheduledTime, $scheduledTime, $jobConfig, $schedule, $groupId);
		$schedule->getResource()->save($schedule);
	}
	
	protected function filterTimeInput($time) 
	{
		$matches = [];
		preg_match('/(\d+-\d+-\d+)T(\d+:\d+)/', $time, $matches);
		$yearMonthDate = $matches[1];
		$hourMinuets = " " . $matches[2];
		return $yearMonthDate . $hourMinuets;
	}
	
	protected function getGroupId($jobCode, $groups)
	{
		foreach($groups as $groupId => $crons) {
			if(isset($crons[$jobCode]))
				return $groupId;
		}
	}
	
	protected function loadSchedule($jobId)
	{
		/**
		 * @var $scheduleResource \Magento\Cron\Model\ResourceModel\Schedule
		 */
		$schedule = $this->_scheduleFactory->create();
		$scheduleResource = $schedule->getResource();
		$scheduleResource->load($schedule, $jobId);
		return $schedule;
	}
}