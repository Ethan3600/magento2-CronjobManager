<?php

namespace EthanYehuda\CronjobManager\Model\Schedule\Source;

use \Magento\Cron\Model\Config;

class Schedule implements \Magento\Framework\Data\OptionSourceInterface
{
	protected $cronConfig;
	
	public function __construct(
		Config $config		
	){
		$this->cronConfig = $config;
	}
	
	/**
	 * Return array of options as value-label pairs
	 *
	 * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
	 */
	public function toOptionArray() 
	{
		$cronJobs = $this->mergeCronArrays($this->cronConfig->getJobs());
		
		$options = [];
		foreach ($cronJobs as $cron) {
			$option = [
				'value' => $cron['name'],
				'label' => $cron['name']
			];
			array_push($options, $option);
		}
		
		return $options;
	}
	
	/**
	 * Returns array of all cron jobs
	 * 
	 * Magento separates index related crons and "default" crons
	 * This method merges them into one array
	 *  
	 * @param array $cronTypeArrays
	 * @return array
	 */
	private function mergeCronArrays($cronTypeArrays)
	{
		$merged = [];
		foreach ($cronTypeArrays as $cronArray) {
			$merged = array_merge($merged, $cronArray);
		}
		
		return $merged;
	}
}