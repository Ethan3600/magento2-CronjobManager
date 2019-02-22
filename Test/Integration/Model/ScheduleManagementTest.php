<?php
declare(strict_types=1);

namespace Test\Integration\Model;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use Magento\Cron\Model\Schedule;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ScheduleManagementTest extends TestCase
{
    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    const NOW = '2019-02-09 18:33:00';

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scheduleManagement = $this->objectManager->get(ScheduleManagementInterface::class);
    }
    public function testKillRequestForRunningJobSucceeds()
    {
        $this->givenRunningSchedule($schedule);
        $this->whenKillRequestedFor($schedule, strtotime(self::NOW));
        $this->thenScheduleHasKillRequest($schedule, self::NOW);
    }

    public function testKillRequestForFinishedJobFails()
    {
        $this->givenFinishedSchedule($schedule);
        $this->whenKillRequestedFor($schedule, strtotime(self::NOW));
        $this->thenScheduleDoesNotHaveKillRequest($schedule);
    }

    private function givenRunningSchedule(&$schedule)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_RUNNING);
        $schedule->save();
    }

    private function givenFinishedSchedule(&$schedule)
    {
        /** @var Schedule $schedule */
        $schedule = $this->objectManager->create(Schedule::class);
        $schedule->setStatus(Schedule::STATUS_SUCCESS);
        $schedule->save();
    }

    private function whenKillRequestedFor(Schedule $schedule, $timestamp)
    {
        $this->scheduleManagement->kill((int)$schedule->getId(), $timestamp);
    }

    private function thenScheduleHasKillRequest($schedule, $now)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
        $scheduleResource->load($schedule, $schedule->getId());
        $this->assertEquals($now, $schedule->getData('kill_request'), 'Kill request should be saved in schedule');
    }

    private function thenScheduleDoesNotHaveKillRequest($schedule)
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
        $scheduleResource->load($schedule, $schedule->getId());
        $this->assertNull($schedule->getData('kill_request'), 'Kill request should not be saved in schedule');
    }
}
