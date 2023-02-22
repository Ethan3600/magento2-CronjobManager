<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Model\Clock;
use EthanYehuda\CronjobManager\Model\ErrorNotification;
use EthanYehuda\CronjobManager\Test\Util\FakeClock;
use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManager\ObjectManager;
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

    /**
     * @var ObjectManager
     */
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
        $this->objectManager->configure(['preferences' => [Clock::class => FakeClock::class]]);
        $this->objectManager->addSharedInstance($this->createMock(ErrorNotification::class), ErrorNotification::class);
        $this->clock = $this->objectManager->get(Clock::class);
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
