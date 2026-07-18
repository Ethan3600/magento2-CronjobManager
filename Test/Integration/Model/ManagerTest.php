<?php

namespace EthanYehuda\CronjobManager\Test\Integration\Model;

use Magento\Cron\Model\Schedule;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use EthanYehuda\CronjobManager\Model\Manager;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ManagerTest extends TestCase
{
    public const FIXTURE_CRON_JOB_CODE = 'ethanyehuda_cronjobmanager_fixture_job';

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->manager = $objectManager->create(Manager::class);
        $this->scheduleFactory = $objectManager->create(ScheduleFactory::class);
    }

    public function testCreateCronJob()
    {
        $cronJob = $this->manager->createCronJob(
            'expired_tokens_cleanup',
            date('Y-m-d\TH:i', strtotime('+5 minutes'))
        );

        $this->assertInstanceOf(Schedule::class, $cronJob);
    }

    /**
     * @magentoDataFixture loadDataFixtureCron
     */
    public function testSaveCronJob()
    {
        $cron = $this->loadCron(self::FIXTURE_CRON_JOB_CODE, 'job_code');
        $cronId = (int) $cron->getScheduleId();
        $this->assertGreaterThan(0, $cronId);

        $this->manager->saveCronJob($cronId, null, Schedule::STATUS_SUCCESS);
        $cron = $this->loadCron($cronId);

        $this->assertEquals(Schedule::STATUS_SUCCESS, $cron->getStatus());
    }

    public function testSaveCronInvalidId()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Schedule with the "99999" ID doesn\'t exist');
        $this->manager->saveCronJob(99999);
    }

    /**
     * @magentoDataFixture loadDataFixtureCron
     */
    public function testDeleteCronJob()
    {
        $cron = $this->loadCron(self::FIXTURE_CRON_JOB_CODE, 'job_code');
        $cronId = (int) $cron->getScheduleId();
        $this->assertGreaterThan(0, $cronId);

        $this->manager->deleteCronJob($cronId);
        $cron = $this->loadCron($cronId);

        $this->assertNull($cron->getScheduleId());
    }

    public function testDeleteInvalidId()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Schedule with the "99999" ID doesn\'t exist');
        $this->manager->deleteCronJob(99999);
    }

    public function testGetCronJobs()
    {
        $jobs = $this->manager->getCronJobs();

        $this->assertArrayHasKey('default', $jobs);
    }

    public static function loadDataFixtureCron()
    {
        include __DIR__ . '/../_files/cron.php';
    }

    /**
     * @param int|string $value
     * @param string|null $field
     *
     * @return \Magento\Cron\Model\Schedule
     */
    private function loadCron($value, $field = null)
    {
        $cron = $this->scheduleFactory->create();
        // phpcs:ignore Magento2.Methods.DeprecatedModelMethod.FoundDeprecatedModelMethod
        $cron->getResource()->load($cron, $value, $field);

        return $cron;
    }
}
