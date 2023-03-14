<?php
namespace EthanYehuda\CronjobManager\Model\System\Message;

use DateTime;
use DateTimeZone;
use EthanYehuda\CronjobManager\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime as MagentoDateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class CronNotRunning implements MessageInterface
{
    // Cache the database value for this long in Magento's cache. This is a
    // trade-off between accurate data and going to the database too often.
    protected const CACHE_TIMEOUT = 120; // seconds (2 minutes)

    // Complain if most recent job to complete was this long ago or more.
    protected const THRESHOLD = 1800; // seconds (30 minutes)

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        CacheInterface $cache,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone
    ) {
        $this->cache = $cache;
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
    }

    public function getIdentity(): string
    {
        return 'cronjobmanager_recently_run';
    }

    public function isDisplayed(): bool
    {
        return $this->getLastRuntime() < (time() - self::THRESHOLD);
    }

    public function getText(): Phrase
    {
        if ($this->getLastRuntime()) {
            $datetime = new DateTime();
            $datetime->setTimestamp($this->getLastRuntime());
            $datetime->setTimezone(new DateTimeZone($this->timezone->getConfigTimezone()));

            return new Phrase(
                'Cron does not appear to be running properly. Most recent job completed on %1.',
                [$datetime->format('l jS \of F Y \a\t g:i a')]
            );
        }

        return new Phrase('Cron does not appear to be running properly. No jobs have ever completed.');
    }

    public function getSeverity(): int
    {
        return MessageInterface::SEVERITY_MAJOR;
    }

    protected function getLastRuntime(): int
    {
        $cacheEntry = $this->cache->load($this->getIdentity());
        if ($cacheEntry) {
            list($lookupTime, $lastRuntime) = explode(';', $cacheEntry);
            if ($lookupTime > (time() - self::CACHE_TIMEOUT)) {
                return (int) $lastRuntime;
            }
        }

        $lastRuntime = $this->calculateLastRuntime();

        $this->cache->save(
            implode(';', [time(), $lastRuntime]),
            $this->getIdentity()
        );

        return $lastRuntime;
    }

    protected function calculateLastRuntime(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect('finished_at');
        $collection->setOrder('finished_at', $collection::SORT_ORDER_DESC);
        $collection->setPageSize(1);

        if (!$collection->getSize()) {
            return 0;
        }

        $job = $collection->fetchItem();
        if ($job && $job->getFinishedAt()) {
            $datetime = DateTime::createFromFormat(
                MagentoDateTime::DATETIME_PHP_FORMAT,
                $job->getFinishedAt()
            );
            return $datetime->getTimestamp();
        }

        return 0;
    }
}
