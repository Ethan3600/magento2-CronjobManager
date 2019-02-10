<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event;

/**
 * @magentoAppArea crontab
 */
class CleanRunningJobsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Event\ManagerInterface
     */
    private $eventManager;

    private const DEAD_PID = 99999999;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventManager = $this->objectManager->get(Event\ManagerInterface::class);
    }

    public function testDeadRunningJobsAreCleaned()
    {
        $this->givenRunningScheduleWithInactiveProcess($schedule);
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, Schedule::STATUS_ERROR);
        $this->andScheduleHasMessage($schedule, 'Process went away');
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
