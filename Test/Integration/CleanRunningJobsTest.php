<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Api\ScheduleRepositoryAdapterInterface;
use EthanYehuda\CronjobManager\Model\CleanRunningJobs;
use EthanYehuda\CronjobManager\Model\ClockInterface;
use EthanYehuda\CronjobManager\Model\ErrorNotificationInterface;
use EthanYehuda\CronjobManager\Test\Util\FakeClock;
use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event;

/**
 * @magentoAppIsolation enabled
 * @magentoAppArea crontab
 */
class CleanRunningJobsTest extends TestCase
{
    protected const NOW = '2019-02-09 18:33:00';
    protected const REMOTE_HOSTNAME = 'hostname.example.net';
    protected const DEAD_PID = 99999999;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @var Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var FakeClock
     */
    private $clock;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->objectManager->configure(['preferences' => [ClockInterface::class => FakeClock::class]]);
        $this->objectManager->addSharedInstance(
            $this->createMock(ErrorNotificationInterface::class),
            ErrorNotificationInterface::class
        );
        $this->clock = $this->objectManager->get(ClockInterface::class);
        $this->clock->setTimestamp(strtotime(self::NOW));
        $this->eventManager = $this->objectManager->get(Event\ManagerInterface::class);
    }

    public function testDeadRunningJobsAreCleaned()
    {
        $this->givenRunningScheduleWithInactiveProcess($schedule);
        $this->givenScheduleIsRunningOnHost($schedule, \gethostname());
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_ERROR);
        $this->andScheduleHasMessage($schedule, 'Process went away at ' . self::NOW);
    }

    public function testDeadRunningJobsOnAnotherHostAreNotCleaned()
    {
        $this->givenRunningScheduleWithInactiveProcess($schedule);
        $this->givenScheduleIsRunningOnHost($schedule, self::REMOTE_HOSTNAME);
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_RUNNING);
    }

    public function testActiveRunningJobsAreNotCleaned()
    {
        $this->givenRunningScheduleWithActiveProcess($schedule);
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_RUNNING);
    }

    /**
     * A short-lived job can save its final status and exit between the watchdog
     * loading its list of "running" schedules and checking the job's PID. The
     * watchdog must not overwrite that final status with "Process went away".
     */
    public function testJobFinishedAfterSnapshotIsNotMarkedAsError()
    {
        $this->givenRunningScheduleWithInactiveProcess($schedule);
        $this->givenScheduleIsRunningOnHost($schedule, \gethostname());
        $staleSnapshot = $this->givenWatchdogSnapshotContaining($schedule);

        $schedule->setStatus(Schedule::STATUS_SUCCESS);
        $schedule->save();

        $this->whenCleanRunningJobsExecutesWithSnapshot($staleSnapshot);
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_SUCCESS);
    }

    private function givenWatchdogSnapshotContaining(Schedule $schedule): array
    {
        /** @var ScheduleRepositoryAdapterInterface $scheduleRepository */
        $scheduleRepository = $this->objectManager->get(ScheduleRepositoryAdapterInterface::class);

        return [$scheduleRepository->get((int)$schedule->getId())];
    }

    private function whenCleanRunningJobsExecutesWithSnapshot(array $staleSnapshot): void
    {
        $realRepository = $this->objectManager->get(ScheduleRepositoryAdapterInterface::class);
        $staleRepository = $this->createMock(ScheduleRepositoryAdapterInterface::class);
        $staleRepository->method('getByStatus')->willReturn($staleSnapshot);
        $staleRepository->method('save')->willReturnCallback(
            fn ($schedule, $scheduleId = null) => $realRepository->save($schedule, $scheduleId)
        );

        /** @var CleanRunningJobs $cleanRunningJobs */
        $cleanRunningJobs = $this->objectManager->create(
            CleanRunningJobs::class,
            ['scheduleRepository' => $staleRepository]
        );
        $cleanRunningJobs->execute();
    }

    private function givenRunningScheduleWithInactiveProcess(&$schedule)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_RUNNING);
        $schedule->setData('pid', self::DEAD_PID);
        $schedule->save();
    }

    private function givenScheduleIsRunningOnHost(Schedule &$schedule, string $hostname): void
    {
        $schedule->setData('hostname', $hostname);
        $schedule->save();
    }

    private function givenRunningScheduleWithActiveProcess(&$schedule)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_RUNNING);
        $schedule->setData('pid', \getmypid());
        $schedule->save();
    }

    private function whenEventIsDispatched($eventName)
    {
        $this->eventManager->dispatch($eventName);
    }

    private function thenScheduleHasStatus(Schedule $schedule, $expectedStatus)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
        $scheduleResource->load($schedule, $schedule->getId());
        $this->assertEquals($expectedStatus, $schedule->getStatus(), 'Schedule should have expected status');
    }

    private function andScheduleHasMessage(Schedule $schedule, $expectedMessage)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
        $scheduleResource->load($schedule, $schedule->getId());
        $this->assertEquals($expectedMessage, $schedule->getMessages(), 'Schedule should have expected message');
    }
}
