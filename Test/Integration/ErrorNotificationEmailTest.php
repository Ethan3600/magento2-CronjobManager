<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Integration;

use EthanYehuda\CronjobManager\Model\ErrorNotificationEmail;
use Magento\Cron\Model\Schedule;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 * @magentoAppIsolation enabled
 */
class ErrorNotificationEmailTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ErrorNotificationEmail
     */
    private $errorNotificationEmail;

    /**
     * @var \Magento\TestFramework\Mail\Template\TransportBuilderMock
     */
    private $transportBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
        $this->objectManager->addSharedInstance(
            $this->transportBuilder,
            TransportBuilder::class
        );
        $this->errorNotificationEmail = $this->objectManager->get(ErrorNotificationEmail::class);
    }

    /**
     * @magentoConfigFixture default_store system/cron_job_manager/email_notification 0
     * @magentoAdminConfigFixture system/cron_job_manager/email_recipients errors@example.com,other@example.com
     * @magentoAdminConfigFixture system/cron_job_manager/email_identity general
     * @magentoConfigFixture current_store trans_email/ident_general/name No-Reply
     * @magentoConfigFixture current_store trans_email/ident_general/email noreply@example.com
     */
    public function testDoNotSendIfConfigurationDisabled()
    {
        $this->givenScheduleWithData(
            [
                'job_code'    => 'dummy_job_code',
                'executed_at' => '1999-12-31 23:59:00',
                'finished_at' => '1900-01-01 00:00:00',
                'status'      => Schedule::STATUS_ERROR,
                'messages'    => "Hello, I am the <Y2K> Bug\n\nHere be stacktrace",
            ],
            $schedule
        );
        $this->whenNotificationIsSent($schedule, $sentMessage);
        $this->thenEmailShouldNotBeSent($sentMessage);
    }

    /**
     * @magentoAdminConfigFixture system/cron_job_manager/email_notification 1
     * @magentoAdminConfigFixture system/cron_job_manager/email_recipients errors@example.com,other@example.com
     * @magentoAdminConfigFixture system/cron_job_manager/email_identity general
     * @magentoConfigFixture current_store trans_email/ident_general/name No-Reply
     * @magentoConfigFixture current_store trans_email/ident_general/email noreply@example.com
     */
    public function testSentWithTemplateToConfiguredAddresses()
    {
        $this->givenScheduleWithData(
            [
                'job_code'    => 'dummy_job_code',
                'executed_at' => '1999-12-31 23:59:00',
                'finished_at' => '1900-01-01 00:00:00',
                'status'      => Schedule::STATUS_ERROR,
                'messages'    => "Hello, I am the <Y2K> Bug\n\nHere be stacktrace",
            ],
            $schedule
        );
        $this->whenNotificationIsSent($schedule, $sentMessage);
        $this->thenEmailShouldBeSent($sentMessage, 'noreply@example.com', ['errors@example.com', 'other@example.com']);
        $this->andEmailShouldHaveContents(
            $sentMessage,
            [
                'job_code'    => '<td>dummy_job_code</td>',
                'messages'    => "<td>Hello, I am the &lt;Y2K&gt; Bug<br />\n<br />\nHere be stacktrace</td>",
                'exeuted_at'  => '<td>1999-12-31 23:59:00</td>',
                'finished_at' => '<td>1900-01-01 00:00:00</td>',
            ]
        );
    }

    private function givenScheduleWithData(array $scheduleData, &$schedule): void
    {
        $schedule = $this->objectManager->create(
            Schedule::class,
            [
                'data' => $scheduleData,
            ]
        );
    }

    private function whenNotificationIsSent($schedule, &$sentMessage): void
    {
        $this->errorNotificationEmail->sendFor($schedule);
        $sentMessage = $this->transportBuilder->getSentMessage();
    }

    private function thenEmailShouldBeSent(?Message $sentMessage, string $expectedSender, array $expectedRecipients)
    {
        $this->assertNotNull($sentMessage, 'A mail should have been sent');
        $messageDetails = \Zend\Mail\Message::fromString($sentMessage->getRawMessage());
        $this->assertEquals([$expectedSender], \array_keys(\iterator_to_array($messageDetails->getFrom())));
        $this->assertEquals($expectedRecipients, \array_keys(\iterator_to_array($messageDetails->getTo())));
    }

    private function thenEmailShouldNotBeSent(?Message $sentMessage)
    {
        $this->assertNull($sentMessage, 'A mail should not have been sent');
    }

    private function andEmailShouldHaveContents(Message $sentMessage, array $expectedContents): void
    {
        $content = $sentMessage->getBody()->getParts()[0]->getContent();
        $content = \Zend_Mime_Decode::decodeQuotedPrintable($content);
        foreach ($expectedContents as $expectedKey => $expectedContent) {
            if (\method_exists($this, 'assertStringContainsString')) {
                $this->assertStringContainsString($expectedContent, $content, "Content should contain $expectedKey");
            } else {
                $this->assertContains($expectedContent, $content, "Content should contain $expectedKey");
            }
        }
    }
}
