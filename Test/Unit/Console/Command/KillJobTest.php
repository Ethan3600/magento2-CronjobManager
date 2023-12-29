<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Unit\Console\Command;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Console\Command\KillJob;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Config\ScopeInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Model\Data\Schedule;
use EthanYehuda\CronjobManager\Model\ProcessManagement;

class KillJobTest extends TestCase
{
    /** @var KillJob */
    private $command;

    /** @var (State&MockObject)|MockObject */
    private $mockState;

    /** @var (ScheduleRepositoryInterface&MockObject)|MockObject */
    private $mockScheduleRepository;

    /** @var (ScheduleManagementInterface&MockObject)|MockObject */
    private $mockScheduleManagement;

    /** @var (SearchCriteriaBuilder&MockObject)|MockObject */
    private $mockSearchCriteriaBuilder;

    /** @var (FilterBuilder&MockObject)|MockObject */
    private $mockFilterBuilder;

    /** @var (FilterGroupBuilder&MockObject)|MockObject */
    private $mockFilterGroupBuilder;

    /** @var (ProcessManagement&MockObject)|MockObject */
    private $mockProcessManagement;

    protected function setUp(): void
    {
        $this->mockState = $this->getMockBuilder(State::class)->setConstructorArgs([
            $this->createMock(ScopeInterface::class),
            State::MODE_PRODUCTION
        ])->getMock();

        $this->mockScheduleRepository = $this->createMock(ScheduleRepositoryInterface::class);
        $this->mockScheduleManagement = $this->createMock(ScheduleManagementInterface::class);
        $this->mockProcessManagement = $this->createMock(ProcessManagement::class);

        $this->mockFilterBuilder = $this->createMock(FilterBuilder::class);
        $this->mockFilterBuilder->method('setField')->willReturnSelf();
        $this->mockFilterBuilder->method('setConditionType')->willReturnSelf();
        $this->mockFilterBuilder->method('setValue')->willReturnSelf();

        $this->mockFilterGroupBuilder = $this->createMock(FilterGroupBuilder::class);
        $this->mockFilterGroupBuilder->method('addFilter')->willReturnSelf();

        $this->mockSearchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->mockSearchCriteriaBuilder->method('setFilterGroups')->willReturnSelf();

        $this->command = new KillJob(
            $this->mockState,
            $this->mockScheduleRepository,
            $this->mockScheduleManagement,
            $this->mockSearchCriteriaBuilder,
            $this->mockFilterBuilder,
            $this->mockFilterGroupBuilder,
            $this->mockProcessManagement
        );
    }

    public function testExecute()
    {
        $mockSchedule = new Schedule([
            "schedule_id" => "2246",
            "job_code" => "long_running_cron",
            "status" => "running",
            "pid" => 999,
            "kill_request" => null
        ]);

        $this->mockQueryResults([$mockSchedule]);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockScheduleManagement->expects($this->once())
            ->method('kill')
            ->with(
                $this->equalTo("2246"),
                $this->isType('int')
            )->willReturn(true);

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute([
            'job_code' => 'long_running_cron'
        ]);

        $this->assertEquals(0, $resultCode);
    }

    public function testExecuteWithProcKillFlag()
    {
        $mockSchedule = new Schedule([
            "schedule_id" => "2246",
            "job_code" => "long_running_cron",
            "status" => "running",
            "pid" => 999,
            "kill_request" => null
        ]);

        $this->mockQueryResults([$mockSchedule]);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockProcessManagement->expects($this->once())
            ->method('killPid')
            ->with(
                $this->equalTo(999)
            )->willReturn(true);

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute(
            [
                'job_code' => 'long_running_cron',
                '--process-kill' => true
            ]
        );
        $this->assertEquals(0, $resultCode);
    }

    public function testExecuteWithKillFailures()
    {
        $numOfSchedules = 3;
        $mockedSchedules = $this->mockMultipleSchedules($numOfSchedules);

        $this->mockQueryResults($mockedSchedules);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockScheduleManagement->expects($this->exactly($numOfSchedules))
            ->method('kill')
            ->with(
                $this->isType('int'),
                $this->isType('int')
            )->willReturn(false);

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute([
            'job_code' => 'long_running_cron'
        ]);

        $expectedOutput = [
            "Unable to kill long_running_cron with PID: 1000",
            "Unable to kill long_running_cron with PID: 1001",
            "Unable to kill long_running_cron with PID: 1002\n" // writeln ends with a newline
        ];
        $expectedOutput = \implode("\n", $expectedOutput);

        $this->assertEquals(1, $resultCode);
        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    public function testExecuteUsingProcKillWithKillFailures()
    {
        $numOfSchedules = 3;
        $mockedSchedules = $this->mockMultipleSchedules($numOfSchedules);

        $this->mockQueryResults($mockedSchedules);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockProcessManagement->expects($this->exactly($numOfSchedules))
            ->method('killPid')
            ->with(
                $this->isType('int')
            )->willReturn(false);

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute(
            [
                'job_code' => 'long_running_cron',
                '--process-kill' => true
            ]
        );

        $expectedOutput = [
            "Unable to kill long_running_cron with PID: 1000",
            "Unable to kill long_running_cron with PID: 1001",
            "Unable to kill long_running_cron with PID: 1002\n" // writeln ends with a newline
        ];
        $expectedOutput = \implode("\n", $expectedOutput);

        $this->assertEquals(1, $resultCode);
        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    public function testExecuteWithPartialKillFailures()
    {
        $numOfSchedules = 2;
        $mockedSchedules = $this->mockMultipleSchedules($numOfSchedules);

        $this->mockQueryResults($mockedSchedules);

        $this->mockState->expects($this->once())
            ->method('setAreaCode')
            ->with(
                Area::AREA_ADMINHTML
            );

        $this->mockScheduleManagement->expects($this->exactly($numOfSchedules))
            ->method('kill')
            ->withConsecutive(
                [
                    $this->isType('int'),
                    $this->isType('int')
                ],
                [
                    $this->isType('int'),
                    $this->isType('int')
                ]
            )->willReturnOnConsecutiveCalls(false, true);

        $commandTester = new CommandTester($this->command);
        $resultCode = $commandTester->execute(
            [
                'job_code' => 'long_running_cron',
            ]
        );

        $expectedOutput = [
            "Unable to kill long_running_cron with PID: 1000\n" // writeln ends with a newline
        ];
        $expectedOutput = \implode("\n", $expectedOutput);

        $this->assertEquals(1, $resultCode);
        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    private function mockQueryResults($queryResults)
    {
        $this->mockFilterBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->createMock(Filter::class));
        $this->mockFilterGroupBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->createMock(FilterGroup::class));
        $this->mockSearchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(SearchCriteria::class));

        $searchResults = $this->createMock(SearchResultsInterface::class);

        $this->mockScheduleRepository->expects($this->once())
            ->method('getList')
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn($queryResults);
    }

    /**
     * @return Schedule[]
     */
    private function mockMultipleSchedules(int $numOfSchedules): array
    {
        $mockSchedules = [];
        for ($i = 0; $i < $numOfSchedules; $i++) {
            $mockSchedules[] = new Schedule([
                "schedule_id" => "3233" . $i,
                "job_code" => "long_running_cron",
                "status" => "running",
                "pid" => 1000 + $i,
                "kill_request" => null
            ]);
        }

        return $mockSchedules;
    }
}
