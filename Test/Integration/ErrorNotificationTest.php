<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Api\ScheduleRepositoryInterface;
use EthanYehuda\CronjobManager\Model\ErrorNotification;
use EthanYehuda\CronjobManager\Model\ErrorNotificationEmail;
use EthanYehuda\CronjobManager\Plugin\Cron\Model\ScheduleResourcePlugin;
use EthanYehuda\CronjobManager\Test\Util\FakeClock;
use EthanYehuda\CronjobManager\Test\Util\FakeJobConfig;
use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Observer\ProcessCronQueueObserver;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Event;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class ErrorNotificationTest extends TestCase
{
    protected const NOW = '2019-02-09 18:33:00';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /**
     * @var ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * @var FakeClock
     */
    private $clock;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var ErrorNotification|\PHPUnit_Framework_MockObject_MockObject
     */
    private $errorNotification;

    /**
     * @var ProcessCronQueueObserver
     */
    private $processCronQueueObserver;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->objectManager->configure(
            [
                'preferences' => [
                    ConfigInterface::class => FakeJobConfig::class,
                    Clock::class           => FakeClock::class,
                ],
            ]
        );
        $this->clock = $this->objectManager->get(Clock::class);
        $this->clock->setTimestamp(strtotime(self::NOW));
        $this->setUpMocks();
        $this->scheduleManagement = $this->objectManager->get(ScheduleManagementInterface::class);
        $this->scheduleRepository = $this->objectManager->get(ScheduleRepositoryInterface::class);
        $this->cache = $this->objectManager->get(\Magento\Framework\App\CacheInterface::class);
        $this->processCronQueueObserver = $this->objectManager->get(ProcessCronQueueObserver::class);
        $this->cleanSchedule();
    }

    private function cleanSchedule(): void
    {
        foreach ($this->scheduleRepository->getList(new SearchCriteria())->getItems() as $schedule) {
            $this->scheduleRepository->delete($schedule);
        }
        // Crontab cache contains last schedule generation timestamp
        $this->cache->clean(['crontab']);
    }

    private function setUpMocks(): void
    {
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturnCallback([$this->clock, 'now']);
        $this->objectManager->addSharedInstance($dateTime, DateTime::class);
        $this->errorNotification = $this->createMock(ErrorNotification::class);
        $this->objectManager->addSharedInstance($this->errorNotification, ErrorNotification::class);
        $this->objectManager->addSharedInstance($this->errorNotification, ErrorNotificationEmail::class);
    }

    public function testSentIfScheduleHasErrorStatusProcessedByScheduleManagement()
    {
        $this->markTestSkipped('Schedule management fails immediately instead of saving error status');
        $this->givenCronjobThrows(new \Exception('Fake error message'), $executed);
        $this->thenErrorNotificationShouldBeSentWithMessage('Fake error message');
        $this->whenCronjobsAreProcessedByScheduleManagement();
    }

    public function testSentIfScheduleHasErrorStatusProcessedByQueue()
    {
        $this->givenCronjobThrows(new \Exception('Fake error message'));
        $this->thenErrorNotificationShouldBeSentWithMessage('Fake error message');
        $this->whenCronjobsAreProcessedByQueue();
    }

    private function whenCronjobsAreProcessedByScheduleManagement(): void
    {
        $schedule = $this->scheduleManagement->schedule(FakeJobConfig::JOB_ID, $this->clock->now());
        $this->scheduleManagement->execute((int)$schedule->getId());
    }

    private function whenCronjobsAreProcessedByQueue(): void
    {
        $this->objectManager->get(\Magento\Framework\App\Console\Request::class)->setParams(
            [ProcessCronQueueObserver::STANDALONE_PROCESS_STARTED => '1']
        );
        $this->processCronQueueObserver->execute(new \Magento\Framework\Event\Observer);
    }

    private function givenCronjobThrows(\Exception $exception): void
    {
        FakeJobConfig::setCallback(
            function () use (&$executed, $exception) {
                throw $exception;
            }
        );
    }

    private function thenErrorNotificationShouldBeSentWithMessage(string $expectedMessage): void
    {
        $this->errorNotification->expects($this->once())->method('sendFor')->with(
            $this->callback(
                function (Schedule $schedule) use ($expectedMessage) {
                    $this->assertEquals(
                        Schedule::STATUS_ERROR,
                        $schedule->getStatus(),
                        'Schedule with status error should be sent as notification'
                    );
                    $this->assertEquals(
                        $expectedMessage,
                        $schedule->getMessages(),
                        'Schedule with messages from exception should be sent as notification'
                    );
                    return true;
                }
            )
        );
    }
}
