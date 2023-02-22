<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Util;

use Magento\Cron\Model\Schedule;

class FakeJobConfig implements \Magento\Cron\Model\ConfigInterface
{
    private const GROUP_ID = 'default';
    public const JOB_ID   = 'fake_job';

    /**
     * @var callable
     */
    private static $callback;

    public function getJobs()
    {
        return [
            self::GROUP_ID => [
                self::JOB_ID => [
                    'schedule' => '* * * * *',
                    'instance' => self::class,
                    'method'   => 'execute',
                ],
            ],
        ];
    }

    /**
     * Pass a callable that will be executed as cronjob
     *
     * Has to be static because job runner uses objectManager->create() to instantiate job instance
     *
     * @param callable $callback
     */
    public static function setCallback(callable $callback)
    {
        self::$callback = $callback;
    }

    public function execute(Schedule $schedule)
    {
        if (self::$callback !== null) {
            \call_user_func(self::$callback, $schedule);
        }
    }
}
