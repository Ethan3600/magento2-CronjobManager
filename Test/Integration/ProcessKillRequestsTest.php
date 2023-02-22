<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\Clock;
use EthanYehuda\CronjobManager\Model\ProcessManagement;
use EthanYehuda\CronjobManager\Test\Util\FakeClock;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Event;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoAppIsolation enabled
 */
class ProcessKillRequestsTest extends TestCase
{
    protected const NOW = '2019-02-09 18:33:00';
    protected const REMOTE_HOSTNAME = 'hostname.example.net';

    /**
     * @var int
     */
    private $childPid = 0;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @var ProcessManagement
     */
    private $processManagement;

    /**
     * @var FakeClock
     */
    private $clock;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->objectManager->configure(['preferences' => [Clock::class => FakeClock::class]]);
        $this->clock = $this->objectManager->get(Clock::class);
        $this->clock->setTimestamp(strtotime(self::NOW));
        $this->eventManager = $this->objectManager->get(Event\ManagerInterface::class);
        $this->scheduleManagement = $this->objectManager->get(ScheduleManagementInterface::class);
        $this->processManagement = $this->objectManager->get(ProcessManagement::class);
    }

    protected function tearDown(): void
    {
        /*
         * Take care of children that we failed to kill
         */
        if ($this->childPid) {
            \posix_kill($this->childPid, SIGKILL);
        }
    }

    public function testDeadRunningJobsAreCleaned()
    {
        $this->givenRunningScheduleWithKillRequest($schedule, $this->timeStampInThePast());
        $this->givenScheduleIsRunningOnHost($schedule, \gethostname());
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, ScheduleInterface::STATUS_KILLED);
        $this->andScheduleHasMessage($schedule, 'Process was killed at ' . self::NOW);
        $this->andProcessIsKilled($schedule);
    }

    public function testDeadRunningJobsOnAnotherHostAreNotCleaned()
    {
        $this->givenRunningScheduleWithKillRequest($schedule, $this->timeStampInThePast());
        $this->givenScheduleIsRunningOnHost($schedule, self::REMOTE_HOSTNAME);
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_RUNNING);
    }

    private function givenRunningScheduleWithKillRequest(&$schedule, int $timestamp)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_RUNNING);
        $schedule->setData('pid', $this->createProcessToKill());
        $schedule->save();
        $this->scheduleManagement->kill((int)$schedule->getId(), $timestamp);
    }

    private function givenScheduleIsRunningOnHost(Schedule &$schedule, string $hostname): void
    {
        $schedule->setData('hostname', $hostname);
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

    private function timeStampInThePast(): int
    {
        return $this->clock->now() - 1;
    }

    private function createProcessToKill(): int
    {
        $pid = \pcntl_fork();
        if ($pid === -1) {
            $this->fail('Could not fork process to test killing');
        } elseif ($pid) {
            $this->assertTrue($this->processManagement->isPidAlive($pid), 'Precondition: child is alive');
            $this->childPid = $pid;
            return $pid;
        } else {
            // we are the child, waiting to be killed
            while (true) {
                sleep(1);
            }
        }
        return 0;
    }

    private function andProcessIsKilled(Schedule $schedule)
    {
        \pcntl_wait($status); // killed children are zombies until we wait for them
        $pid = (int)$schedule->getData('pid');
        $this->assertFalse($this->processManagement->isPidAlive($pid), "Child with PID {$pid} should be killed");
    }
}
