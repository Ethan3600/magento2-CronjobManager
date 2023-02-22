<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

use Magento\Cron\Model\Schedule;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ErrorNotificationEmail implements ErrorNotification
{
    private const XML_PATH_EMAIL_ENABLED    = 'system/cron_job_manager/email_notification';
    private const XML_PATH_EMAIL_TEMPLATE   = 'system/cron_job_manager/email_template';
    private const XML_PATH_EMAIL_IDENTITY   = 'system/cron_job_manager/email_identity';
    private const XML_PATH_EMAIL_RECIPIENTS = 'system/cron_job_manager/email_recipients';

    /**
     * @var TransportBuilder
     */
    private $mailTransportBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ErrorNotificationEmail constructor.
     * @param TransportBuilder $mailTransportBuilder
     */
    public function __construct(
        TransportBuilder $mailTransportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        SenderResolverInterface $senderResolver,
        LoggerInterface $logger
    ) {
        $this->mailTransportBuilder = $mailTransportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->senderResolver = $senderResolver;
        $this->logger = $logger;
    }

    public function sendFor(Schedule $schedule): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_ENABLED)) {
            return;
        }
        try {
            $recipients = explode(',', (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENTS));
            $recipients = array_map('trim', $recipients);
            $sender = $this->senderResolver->resolve(
                $this->scopeConfig->getValue(self::XML_PATH_EMAIL_IDENTITY)
            );

            $this->mailTransportBuilder->setTemplateIdentifier($this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE));
            $this->mailTransportBuilder->setTemplateVars(['schedule' => $schedule]);
            $this->mailTransportBuilder->setTemplateOptions(
                [
                    'area'  => Area::AREA_ADMINHTML,
                    'store' => $this->storeManager->getDefaultStoreView()->getId(),
                ]
            );
            $this->mailTransportBuilder->setFrom($sender);
            $this->mailTransportBuilder->addTo($recipients);
            $this->mailTransportBuilder->getTransport()->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
