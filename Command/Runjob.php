<?php
namespace EthanYehuda\CronjobManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EthanYehuda\CronjobManager\Model\Manager;
use \Magento\Framework\App\State;
use \Magento\Framework\Console\Cli;

class Runjob extends Command
{
	/**
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
	/**
	 * @var \Magento\Framework\App\State $state
	 */
	private $state;
	
	public function __construct(
		State $state,
		Manager $manager
	) {
			$this->manager = $manager;
			$this->state = $state;
			parent::__construct();
	}
	protected function configure()
	{
		$this->setName("cronmanager:runjob");
		$this->setDescription("Run a specific cron job code ");
		parent::configure();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$this->state->setAreaCode('adminhtml');
			
			return Cli::RETURN_SUCCESS;
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			$output->writeln($e->getMessage());
			return Cli::RETURN_FAILURE;
		}
	}
}
