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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\Data\Schedule;

class KillJob extends Command
{
    const INPUT_KEY_JOB_CODE = 'job_code';

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

    public function __construct(
        State $state,
        ScheduleRepositoryInterface $scheduleRepository,
        ScheduleManagementInterface $scheduleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->state = $state;
        $this->scheduleRepository = $scheduleRepository;
        $this->scheduleManagement = $scheduleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
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
        }

        /** @var string $jobCode */
        $jobCode = $input->getArgument(self::INPUT_KEY_JOB_CODE);

        /** @var Schedule[] $runningJobs */
        $runningJobs = $this->loadRunningJobsByCode($jobCode);

        /** @var Schedule $job */
        foreach ($runningJobs as $job) {
            /** @var int $id */
            $id = (int)$job->getScheduleId();
            if ($id !== null && $job->getPid() !== null) {
                $this->scheduleManagement->kill($id, \time());
            }
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
