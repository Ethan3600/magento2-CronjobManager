<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Config\ScopeInterface;

use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\Data\Schedule;
use EthanYehuda\CronjobManager\Model\ProcessManagement;

class KillJobTest extends TestCase
{
    private $command;
    private $mockState;
    private $mockScheduleRepository;
    private $mockScheduleManagement;
    private $mockSearchCriteriaBuilder;
    private $mockFilterBuilder;
    private $mockFilterGroupBuilder;

    protected function setUp(): void
    {
        $this->mockState = $this->getMockBuilder(State::class)->setConstructorArgs([
            $this->createMock(ScopeInterface::class),
            State::MODE_PRODUCTION
        ])->getMock();

        $this->mockScheduleRepository = $this->getMockBuilder(ScheduleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept([])
            ->getMock();

        $this->mockScheduleManagement = $this->getMockBuilder(ScheduleManagementInterface\Proxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['kill'])
            ->getMock();

        $this->mockSearchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockFilterBuilder = $this->getMockBuilder(FilterBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockFilterGroupBuilder = $this->getMockBuilder(FilterGroupBuilder::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->mockProcessManagement = $this->getMockBuilder(ProcessManagement::class)->disableOriginalConstructor()
            ->getMock();

        $this->command = new \EthanYehuda\CronjobManager\Console\Command\KillJob(
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
        /** @var int $numOfSchedules */
        $numOfSchedules = 3;
        /** @var Schedule[] $mockedSchedules */
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
        /** @var int $numOfSchedules */
        $numOfSchedules = 3;
        /** @var Schedule[] $mockedSchedules */
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
        /** @var int $numOfSchedules */
        $numOfSchedules = 2;
        /** @var Schedule[] $mockedSchedules */
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

        $searchResults = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);

        $this->mockScheduleRepository->expects($this->once())
            ->method('getList')
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn($queryResults);
    }

    private function mockMultipleSchedules(int $numOfSchedules): array
    {
        $mockSchedules = [];
        for ($i = 0; $i < $numOfSchedules; $i++) {
            $mockSchedules[] = new Schedule([
                "schedule_id" => "3233" + $i,
                "job_code" => "long_running_cron",
                "status" => "running",
                "pid" => 1000 + $i,
                "kill_request" => null
            ]);
        }
        return $mockSchedules;
    }
}
