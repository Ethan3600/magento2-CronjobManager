<?php
declare(strict_types=1);
namespace EthanYehuda\CronjobManager\Test\Integration;

use Magento\Cron\Model\ResourceModel\Schedule as ScheduleResource;
use Magento\Cron\Model\Schedule;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoDbIsolation enabled
 */
class HostnameTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ScheduleResource
     */
    protected $scheduleResource;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scheduleResource = $this->objectManager->get(ScheduleResource::class);
    }

    public function testProcessIdSavedOnStart(): void
    {
        $this->givenHostname($hostname);
        $this->givenPendingSchedule($schedule);
        $this->whenTryLockJob($schedule);
        $this->thenScheduleIsSavedWithHostname($schedule, $hostname);
    }

    public function testProcessIdMaintainedAfterSuccesfulRun(): void
    {
        $this->givenHostname($hostname);
        $this->givenPendingSchedule($schedule);
        $this->whenTryLockJob($schedule);
        $this->andScheduleSavedWithSuccess($schedule);
        $this->thenScheduleIsSavedWithHostname($schedule, $hostname);
    }

    private function givenPendingSchedule(&$schedule): void
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

    private function whenTryLockJob(Schedule $schedule): void
    {
        $lock = $schedule->tryLockJob();
        $this->assertTrue($lock, 'Precondition: tryLockJob() should be successful');
    }

    private function andScheduleSavedWithSuccess(Schedule $schedule): void
    {
        $schedule->setStatus(Schedule::STATUS_SUCCESS);
        $this->scheduleResource->save($schedule);
    }

    private function thenScheduleIsSavedWithHostname(Schedule $schedule, string $hostname): void
    {
        $this->scheduleResource->load($schedule, $schedule->getId());
        $this->assertEquals($hostname, $schedule->getData('hostname'), 'Hostname should be saved in schedule');
    }

    private function givenHostname(&$hostname): void
    {
        $hostname = \gethostname();
        $this->assertNotFalse($hostname, 'Precondition: gethostname() should not return false');
    }
}
