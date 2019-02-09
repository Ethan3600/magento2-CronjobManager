<?php
declare(strict_types=1);
namespace EthanYehuda\CronjobManager\Test\Integration;

use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 */
class ProcessIdTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }
    public function testProcessIdSavedOnStart()
    {
        $this->givenPid($pid);
        $this->givenPendingSchedule($schedule);
        $this->whenTryLockJob($schedule);
        $this->thenScheduleIsSavedWithPid($schedule, $pid);
    }

    private function givenPendingSchedule(&$schedule)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_PENDING);
        $schedule->save();
    }

    private function whenTryLockJob(Schedule $schedule)
    {
        $schedule->tryLockJob();
    }

    private function thenScheduleIsSavedWithPid(Schedule $schedule, $pid)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
        $scheduleResource->load($schedule, $schedule->getId());
        $this->assertEquals($pid, $schedule->getData('pid'), 'PID should be saved in schedule');
    }

    private function givenPid(&$pid): void
    {
        $pid = \getmypid();
        $this->assertNotFalse($pid, 'Precondition: getmypid() should not return false');
    }
}