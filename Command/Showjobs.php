<?php
namespace EthanYehuda\CronjobManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EthanYehuda\CronjobManager\Model\Manager;
use \Magento\Framework\App\State;
use \Magento\Framework\Console\Cli;

class Showjobs extends Command
{
	/**
	 * @var EthanYehuda\CronjobManager\Model\Manager $manager
	 */
	private $manager;
	
	/**
	 * @var \Magento\Framework\App\State $state
	 */
	private $state;
	
	/**
	 * @var Array $headers
	 */
	private $headers = ['Job Code', 'Group', 'Frequency', 'Class'];
	
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
        $this->setName("cronmanager:showjobs");
        $this->setDescription("Show all cron job codes in Magento");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
        	$this->state->setAreaCode('adminhtml');
        	
        	$jobs = $this->manager->getCronJobs();
        	$table = $this->getHelperSet()->get('table')->setHeaders($this->headers);
        	
        	foreach ($jobs as $group => $crons) {
        		foreach ($crons as $code => $job) {
        			$instance = $job['instance'];
        			$method = $job['method'];
        			$schedule = (isset($job['schedule']) ? $job['schedule'] : "");
        			$jobData = [
        				$code,
        				$group,
        				$schedule,
        				"$instance::$method"
        			];
        			$table->addRow($jobData);
        		}
        	}
        	
        	$table->render($output);
        	return Cli::RETURN_SUCCESS;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        	$output->writeln($e->getMessage());
        	return Cli::RETURN_FAILURE;
        }
    }
} 
