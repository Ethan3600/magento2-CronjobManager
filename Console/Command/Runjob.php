<?php

namespace EthanYehuda\CronjobManager\Console\Command;

use EthanYehuda\CronjobManager\Model\Cron\Runner;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Runjob extends Command
{
    protected const INPUT_KEY_JOB_CODE = 'job_code';

    /**
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(
        private readonly ObjectManagerFactory $objectManagerFactory,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @todo Find a way to avoid using `ObjectManager`
         */
        // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageWarning
        $omParams = $_SERVER;
        $omParams[StoreManager::PARAM_RUN_CODE] = Store::ADMIN_CODE;
        $omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        $objectManager = $this->objectManagerFactory->create($omParams);

        $jobCode = $input->getArgument(self::INPUT_KEY_JOB_CODE);
        $cron = $objectManager->create(Runner::class);
        list($resultCode, $resultMessage) = $cron->runCron($jobCode);
        $output->writeln($resultMessage);
        return $resultCode;
    }
}
