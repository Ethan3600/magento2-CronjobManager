<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Console\Command;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\ProcessManagement;
use EthanYehuda\CronjobManager\Model\Data\Schedule;

class KillJob extends Command
{
    protected const INPUT_KEY_JOB_CODE = 'job_code';
    protected const OPTION_KEY_PROC_KILL = 'process-kill';

    /** @var string[] */
    private $errors = [];

    /**
     * @param State $state
     * @param ScheduleRepositoryInterface $scheduleRepository
     * @param ScheduleManagementInterface $scheduleManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ProcessManagement $processManagement
     */
    public function __construct(
        private readonly State $state,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
        private readonly ScheduleManagementInterface $scheduleManagement,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly FilterGroupBuilder $filterGroupBuilder,
        private readonly ProcessManagement $processManagement,
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
                "Job code input (ex. 'sitemap_generate')" .
                    "\nSends \"kill request\" to all the cron jobs given a specified job_code." .
                    "\nKill requests will not kill jobs immediately; instead it will be scheduled" .
                    " to be killed and handled by Magento's cron scheduler"
            ),
            new InputOption(
                self::OPTION_KEY_PROC_KILL,
                "p",
                InputOption::VALUE_NONE,
                "Sends a kill request immediately to the job processes that are running the given job_code"
            )
        ];

        $this->setName("cronmanager:killjob");
        $this->setDescription("Kill cron jobs by code");
        $this->setDefinition($arguments);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException) {
            // Area code is already set
            $output->writeln(
                __(
                    'WARNING: cannot set area code. This is usually caused by a'
                    . ' command in a third party module calling'
                    . ' state->setAreaCode() in its "configure()" method.'
                )
            );
        }

        /** @var string $jobCode */
        $jobCode = $input->getArgument(self::INPUT_KEY_JOB_CODE);

        /** @var bool $optionProcKill */
        $optionProcKill = $input->getOption(self::OPTION_KEY_PROC_KILL);

        $runningJobs = $this->loadRunningJobsByCode($jobCode);

        if (!count($runningJobs)) {
            $output->writeln("No jobs for '$jobCode' are currently running.");
            return Cli::RETURN_SUCCESS;
        }

        $killCount = 0;

        foreach ($runningJobs as $job) {
            $id = (int) $job->getScheduleId();
            $pid = (int) $job->getPid();
            if ($id && $pid) {
                if ($optionProcKill) {
                    $killed = $this->processManagement->killPid($pid, $job->getHostname());
                    if ($killed) {
                        $job->setStatus(ScheduleInterface::STATUS_KILLED);
                        $this->scheduleRepository->save($job);
                    }
                } else {
                    $killed = $this->scheduleManagement->kill($id, \time());
                }

                if ($killed) {
                    $killCount++;
                } else {
                    $this->errors[] = "Unable to kill {$job->getJobCode()} with PID: $pid";
                }
            }
        }

        if (\count($this->errors) > 0) {
            foreach ($this->errors as $error) {
                $output->writeln($error);
            }

            return Cli::RETURN_FAILURE;
        }

        if ($optionProcKill) {
            $output->writeln("$jobCode successfully sent SIG_KILL to $killCount process(es)");
        } else {
            $output->writeln("$jobCode successfully marked $killCount jobs for termination");
        }
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Load running jobs by code
     *
     * @param string $jobCode
     *
     * @return \Magento\Cron\Model\Schedule[]
     */
    private function loadRunningJobsByCode(string $jobCode): array
    {
        $jobCodeFilter = $this->filterBuilder
            ->setField(Schedule::KEY_JOB_CODE)
            ->setConditionType('eq')
            ->setValue($jobCode)
            ->create();
        $jobCodeFilterGroup = $this->filterGroupBuilder
            ->addFilter($jobCodeFilter)
            ->create();

        $statusFilter = $this->filterBuilder
            ->setField(Schedule::KEY_STATUS)
            ->setConditionType('eq')
            ->setValue(Schedule::STATUS_RUNNING)
            ->create();
        $statusFilterGroup = $this->filterGroupBuilder
            ->addFilter($statusFilter)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups(
            [$jobCodeFilterGroup, $statusFilterGroup]
        )->create();

        $result = $this->scheduleRepository->getList($searchCriteria);
        return $result->getItems();
    }
}
