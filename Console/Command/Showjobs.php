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
    /** @var array */
    private $headers = ['Job Code', 'Group', 'Frequency', 'Class'];

    /**
     * @param State $state
     * @param ManagerFactory $managerFactory
     */
    public function __construct(
        private readonly State $state,
        private readonly ManagerFactory $managerFactory
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName("cronmanager:showjobs");
        $this->setDescription("Show all cron job codes in Magento");
        parent::configure();
    }

    /**
     * @inheritDoc
     */
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

            $table->render();
            return Cli::RETURN_SUCCESS;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }
    }
}
