<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface\Proxy as ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\ProcessManagement;
use EthanYehuda\CronjobManager\Model\Data\Schedule;

class KillJob extends Command
{
    protected const INPUT_KEY_JOB_CODE = 'job_code';
    protected const OPTION_KEY_PROC_KILL = 'process-kill';

    /**
     * @var State
     */
    private $state;

    /**
     * @var ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var ProcessManagement
     */
    private $processManagement;

    /**
     * @var string[]
     */
    private $errors = [];

    public function __construct(
        State $state,
        ScheduleRepositoryInterface $scheduleRepository,
        ScheduleManagementInterface $scheduleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ProcessManagement $processManagement
    ) {
        $this->state = $state;
        $this->scheduleRepository = $scheduleRepository;
        $this->scheduleManagement = $scheduleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->processManagement = $processManagement;
        parent::__construct();
    }

    protected function configure()
    {
        $arguments = [
            new InputArgument(
                self::INPUT_KEY_JOB_CODE,
                InputArgument::REQUIRED,
                "Job code input (ex. 'sitemap_generate')
                \nSends \"kill request\" to all the cron jobs given a specified job_code.
                \nKill requests will not kill jobs immediately; instead it will be scheduled to be killed and handled by Magento's cron scheduler"
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $exception) {
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

        /** @var Schedule[] $runningJobs */
        $runningJobs = $this->loadRunningJobsByCode($jobCode);

        /** @var Schedule $job */
        foreach ($runningJobs as $job) {
            /** @var int $id */
            $id = (int)$job->getScheduleId();
            /** @var int $pid */
            $pid = (int)$job->getPid();
            if ($id !== null && $pid !== null) {
                /** @var bool $killed */
                $killed = false;
                if ($optionProcKill) {
                    $killed = $this->processManagement->killPid($pid, $job->getHostname());
                } else {
                    $killed = $this->scheduleManagement->kill($id, \time());
                }

                if (!$killed) {
                    $this->errors[] = "Unable to kill {$job->getJobCode()} with PID: $pid";
                }
            }
        }

        if (\count($this->errors) > 0) {
            foreach ($this->errors as $error) {
                /** @var string $error */
                $output->writeln($error);
            }
            return Cli::RETURN_FAILURE;
        }
        $output->writeln("$jobCode successfully killed");
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return Schedule[]
     */
    private function loadRunningJobsByCode(string $jobCode): array
    {
        /** @var AbstractSimpleObject $jobCode */
        $jobCodeFilter = $this->filterBuilder
            ->setField(Schedule::KEY_JOB_CODE)
            ->setConditionType('eq')
            ->setValue($jobCode)
            ->create();
        /** @var AbstractSimpleObject $jobCodeFilterGroup */
        $jobCodeFilterGroup = $this->filterGroupBuilder
            ->addFilter($jobCodeFilter)
            ->create();

        /** @var AbstractSimpleObject $jobCode */
        $statusFilter = $this->filterBuilder
            ->setField(Schedule::KEY_STATUS)
            ->setConditionType('eq')
            ->setValue(Schedule::STATUS_RUNNING)
            ->create();
        /** @var AbstractSimpleObject $statusFilterGroup */
        $statusFilterGroup = $this->filterGroupBuilder
            ->addFilter($statusFilter)
            ->create();

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups(
            [$jobCodeFilterGroup, $statusFilterGroup]
        )->create();

        /** @var \Magento\Framework\Api\SearchResultsInterface $result */
        $result = $this->scheduleRepository->getList($searchCriteria);
        return $result->getItems();
    }
}
