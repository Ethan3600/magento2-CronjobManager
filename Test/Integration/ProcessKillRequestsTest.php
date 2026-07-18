<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Api\Data\ScheduleInterface;
use EthanYehuda\CronjobManager\Api\ScheduleManagementInterface;
use EthanYehuda\CronjobManager\Model\ClockInterface;
use EthanYehuda\CronjobManager\Model\ProcessManagement;
use EthanYehuda\CronjobManager\Test\Util\FakeClock;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Event;
use Magento\Framework\ObjectManagerInterface;
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
    protected const SIGKILL = 9;
    private const IPC_TIMEOUT_SECONDS = 5;
    private const IPC_MAX_PAYLOAD_LENGTH = 32;
    private const IPC_ACKNOWLEDGEMENT = "ACK\n";

    /**
     * @var int
     */
    private $childPid = 0;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @var Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var ScheduleManagementInterface
     */
    private $scheduleManagement;

    /** @var \Magento\Cron\Model\ResourceModel\Schedule */
    private $scheduleResource;

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
        $this->objectManager->configure(['preferences' => [ClockInterface::class => FakeClock::class]]);
        $this->clock = $this->objectManager->get(ClockInterface::class);
        $this->clock->setTimestamp(strtotime(self::NOW));
        $this->eventManager = $this->objectManager->get(Event\ManagerInterface::class);
        $this->scheduleManagement = $this->objectManager->get(ScheduleManagementInterface::class);
        $this->processManagement = $this->objectManager->get(ProcessManagement::class);
        $this->scheduleResource = $this->objectManager->get(\Magento\Cron\Model\ResourceModel\Schedule::class);
    }

    protected function tearDown(): void
    {
        /*
         * Take care of children that we failed to kill
         */
        if ($this->childPid) {
            \posix_kill($this->childPid, self::SIGKILL);
        }
    }

    public function testRunningJobsMarkedForDeadOnThisHostAreCleaned()
    {
        $this->givenRunningScheduleWithKillRequest($schedule, $this->timeStampInThePast());
        $this->givenScheduleIsRunningOnHost($schedule, \gethostname());
        $this->whenEventIsDispatched('process_cron_queue_before');
        $this->thenScheduleHasStatus($schedule, ScheduleInterface::STATUS_KILLED);
        $this->andScheduleHasMessage($schedule, 'Process was killed at ' . self::NOW);
        $this->andProcessIsKilled($schedule);
    }

    public function testRunningJobsMarkedForDeadOnAnotherHostAreNotCleaned()
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
        $schedule->save();
        $this->createProcessToKillForSchedule($schedule);
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
        $this->reloadScheduleFromDatabase($schedule);
        $this->assertEquals($expectedStatus, $schedule->getStatus(), 'Schedule should have expected status');
    }

    private function andScheduleHasMessage(Schedule $schedule, $expectedMessage)
    {
        $this->reloadScheduleFromDatabase($schedule);
        $this->assertEquals($expectedMessage, $schedule->getMessages(), 'Schedule should have expected message');
    }

    private function timeStampInThePast(): int
    {
        return $this->clock->now() - 1;
    }

    private function createProcessToKillForSchedule(Schedule $schedule): int
    {
        $ipcSockets = \stream_socket_pair(\STREAM_PF_UNIX, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);
        if ($ipcSockets === false) {
            $this->fail('Could not create process ID channel');
            return 0;
        }

        $pid = \pcntl_fork();
        if ($pid === -1) {
            \fclose($ipcSockets[0]);
            \fclose($ipcSockets[1]);
            $this->fail('Could not fork process to test killing');
            return 0;
        }

        if (!$pid) {
            \fclose($ipcSockets[0]);
            $this->runIntermediaryProcess($ipcSockets[1]);
            return 0;
        }

        // We are the main process, where the test is running.
        \fclose($ipcSockets[1]);

        try {
            $this->childPid = $this->readChildPid($ipcSockets[0]);
            $acknowledgementLength = \strlen(self::IPC_ACKNOWLEDGEMENT);
            if (\fwrite($ipcSockets[0], self::IPC_ACKNOWLEDGEMENT) !== $acknowledgementLength) {
                $this->fail('Could not acknowledge grandchild process ID');
            }
        } finally {
            \fclose($ipcSockets[0]);
            $this->reapIntermediaryProcess($pid);
        }

        $this->assertTrue(
            $this->processManagement->isPidAlive($this->childPid),
            'Precondition: child is alive'
        );

        $schedule->setData('pid', $this->childPid);
        $schedule->save();

        return $this->childPid;
    }

    /**
     * @param resource $ipcSocket
     */
    private function runIntermediaryProcess($ipcSocket): void
    {
        $childPid = \pcntl_fork();
        if ($childPid === -1) {
            \fclose($ipcSocket);
            $this->terminateIntermediaryProcess();
        }

        if (!$childPid) {
            \fclose($ipcSocket);
            while (true) {
                \sleep(1);
            }
        }

        $acknowledged = false;
        try {
            $payload = $childPid . "\n";
            $payloadWritten = \fwrite($ipcSocket, $payload) === \strlen($payload);
            $timeoutConfigured = \stream_set_timeout($ipcSocket, self::IPC_TIMEOUT_SECONDS);
            $acknowledgement = $payloadWritten && $timeoutConfigured
                ? \fgets($ipcSocket, \strlen(self::IPC_ACKNOWLEDGEMENT) + 1)
                : false;
            $acknowledged = $acknowledgement === self::IPC_ACKNOWLEDGEMENT;
        } finally {
            try {
                if (!$acknowledged) {
                    $this->killAndReapOwnedProcess($childPid);
                }
            } finally {
                try {
                    \fclose($ipcSocket);
                } finally {
                    $this->terminateIntermediaryProcess();
                }
            }
        }
    }

    /**
     * @param resource $ipcSocket
     */
    private function readChildPid($ipcSocket): int
    {
        if (!\stream_set_timeout($ipcSocket, self::IPC_TIMEOUT_SECONDS)) {
            $this->fail('Could not configure process ID channel timeout');
            return 0;
        }

        $payload = \fgets($ipcSocket, self::IPC_MAX_PAYLOAD_LENGTH);
        if ($payload === false) {
            $metadata = \stream_get_meta_data($ipcSocket);
            $message = $metadata['timed_out']
                ? 'Timed out waiting for grandchild process ID'
                : 'Process ID channel closed before receiving a payload';
            $this->fail($message);
            return 0;
        }

        if (\substr($payload, -1) !== "\n") {
            $this->fail('Grandchild process ID payload was not newline-terminated');
            return 0;
        }

        $pid = \substr($payload, 0, -1);
        if (!\ctype_digit($pid) || (int) $pid <= 0) {
            $this->fail('Grandchild process ID payload was not a positive integer');
            return 0;
        }

        return (int) $pid;
    }

    private function reapIntermediaryProcess(int $pid): void
    {
        $deadline = \microtime(true) + self::IPC_TIMEOUT_SECONDS;
        do {
            $waitResult = \pcntl_waitpid($pid, $status, \WNOHANG);
            if ($waitResult === $pid || $waitResult === -1) {
                return;
            }
            \usleep(10000);
        } while (\microtime(true) < $deadline);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        \posix_kill($pid, self::SIGKILL);
        \pcntl_waitpid($pid, $status);
        $this->fail('Timed out waiting for intermediary process to exit');
    }

    private function killAndReapOwnedProcess(int $pid): void
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        if (\posix_kill($pid, self::SIGKILL)) {
            \pcntl_waitpid($pid, $status);
        }
    }

    private function terminateIntermediaryProcess(): void
    {
        $this->processManagement->killPid(\getmypid(), \gethostname());

        // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
        exit(1);
    }

    private function andProcessIsKilled(Schedule $schedule)
    {
        $pid = (int)$schedule->getData('pid');
        $this->assertFalse($this->processManagement->isPidAlive($pid), "Child with PID {$pid} should be killed");
        $this->childPid = 0;
    }

    private function reloadScheduleFromDatabase($schedule): void
    {
        $this->scheduleResource->load($schedule, $schedule->getId());
    }
}
