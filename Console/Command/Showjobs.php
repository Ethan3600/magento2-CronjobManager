<?php

namespace EthanYehuda\CronjobManager\Console\Command;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;

class Showjobs extends Command
{
    /**
     * @var \EthanYehuda\CronjobManager\Model\ManagerFactory $managerFactory
     */
    private $managerFactory;

    /**
     * @var \Magento\Framework\App\State $state
     */
    private $state;

    /**
     * @var array $headers
     */
    private $headers = ['Job Code', 'Group', 'Frequency', 'Class'];

    public function __construct(
        State $state,
        ManagerFactory $managerFactory
    ) {
        $this->managerFactory = $managerFactory;
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
        $manager = $this->managerFactory->create();

        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            // Area code is already set
            $output->writeln(
                __(
                    'WARNING: cannot set area code. This is usually caused by a'
                    . ' command in a third party module calling'
                    . ' state->setAreaCode() in its "configure()" method.'
                )
            );
        }

        try {
            $jobs = $manager->getCronJobs();
            $table = new Table($output);
            $table->setHeaders($this->headers);

            foreach ($jobs as $group => $crons) {
                foreach ($crons as $code => $job) {
                    $instance   = (isset($job['instance']) ? $job['instance'] : "");
                    $method     = (isset($job['method']) ? $job['method'] : "");
                    $schedule   = (isset($job['schedule']) ? $job['schedule'] : "");
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
