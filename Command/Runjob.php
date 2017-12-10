<?php
namespace EthanYehuda\CronjobManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use EthanYehuda\CronjobManager\Model\Manager;
use \Magento\Framework\App\State;
use \Magento\Framework\Console\Cli;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class Runjob extends Command
{
	const INPUT_KEY_JOB_CODE = 'job_code';
	
	/**
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
	/**
	 * @var \Magento\Framework\App\State $state
	 */
	private $state;
	
	/**
	 * @var \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
	 */
	private $dateTime;
	
	public function __construct(
		State $state,
		Manager $manager,
		DateTime $dateTime
	) {
			$this->manager = $manager;
			$this->state = $state;
			$this->dateTime = $dateTime;
			parent::__construct();
	}
	protected function configure()
	{
		$arguments = [
			new InputArgument(
				self::INPUT_KEY_JOB_CODE,
				InputArgument::REQUIRED,
				'Job code input (ex. \'sitemap_generate\')'
			)
		];
		
		$this->setName("cronmanager:runjob");
		$this->setDescription("Run a specific cron job by its job_code ");
		$this->setDefinition($arguments);
		parent::configure();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$this->state->setAreaCode('adminhtml');
			
			// lets create a new cron job and dispatch it
			$jobCode = $input->getArgument(self::INPUT_KEY_JOB_CODE);
			$now = strftime('%Y-%m-%dT%H:%M:%S', $this->dateTime->gmtTimestamp());
			
			$schedule = $this->manager->createCronJob($jobCode, $now);
			$this->manager->dispatchCron(null, $jobCode, $schedule);
			
			return Cli::RETURN_SUCCESS;
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			$output->writeln($e->getMessage());
			return Cli::RETURN_FAILURE;
		}
	}
}
