<?php

namespace EthanYehuda\CronjobManager\Console\Command;

use EthanYehuda\CronjobManager\Model\ManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Runjob extends Command
{
    const INPUT_KEY_JOB_CODE = 'job_code';

    /**
     * @var ManagerFactory $managerFactory
     */
    private $managerFactory;

    /**
     * @var \Magento\Framework\App\State $state
     */
    private $state;

    /**
     * @var DateTimeFactory $dateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        State $state,
        ManagerFactory $managerFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->managerFactory = $managerFactory;
        $this->state = $state;
        $this->dateTimeFactory = $dateTimeFactory;
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
        $manager = $this->managerFactory->create();
        $dateTime = $this->dateTimeFactory->create();

        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            // Area code is already set
        }

        try {
            // lets create a new cron job and dispatch it
            $jobCode = $input->getArgument(self::INPUT_KEY_JOB_CODE);
            $now = strftime('%Y-%m-%dT%H:%M:%S', $dateTime->gmtTimestamp());

            $schedule = $manager->createCronJob($jobCode, $now);
            $manager->dispatchCron(null, $jobCode, $schedule);
            $output->writeln("$jobCode successfully ran");
            return Cli::RETURN_SUCCESS;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }
    }
}
