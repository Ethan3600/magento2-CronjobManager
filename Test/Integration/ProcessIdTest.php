<?php
declare(strict_types=1);
namespace EthanYehuda\CronjobManager\Test\Integration;

use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 */
class ProcessIdTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
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

    public function testProcessIdMaintainedAfterSuccesfulRun()
    {
        $this->givenPid($pid);
        $this->givenPendingSchedule($schedule);
        $this->whenTryLockJob($schedule);
        $this->andScheduleSavedWithSuccess($schedule);
        $this->thenScheduleIsSavedWithPid($schedule, $pid);
    }

    private function givenPendingSchedule(&$schedule)
    {
        /** @var Schedule $newSchedule */
        $newSchedule = $this->objectManager->create(Schedule::class);
        $newSchedule->setStatus(Schedule::STATUS_PENDING);
        $newSchedule->setJobCode('test_job_code');
        $newSchedule->save();
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->load($newSchedule->getId());
    }

    private function whenTryLockJob(Schedule $schedule)
    {
        $lock = $schedule->tryLockJob();
        $this->assertTrue($lock, 'Precondition: tryLockJob() should be successful');
    }

    private function andScheduleSavedWithSuccess(Schedule $schedule)
    {
        $schedule->setStatus(Schedule::STATUS_SUCCESS);
        $schedule->save();
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
