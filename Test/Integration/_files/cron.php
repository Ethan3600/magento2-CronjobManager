<?php

use Magento\Cron\Model\Schedule;
use Magento\TestFramework\Helper\Bootstrap;
use EthanYehuda\CronjobManager\Test\Integration\Model\ManagerTest;

$objectManager = Bootstrap::getObjectManager();

/** @var Schedule $cron */
$cron = $objectManager->create(Schedule::class);

$cron->setId(ManagerTest::FIXTURE_CRON_ID)
    ->setJobCode('fake_job')
    ->setStatus(Schedule::STATUS_PENDING)
    ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
    ->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S', strtotime('+5 minutes')));

$cron->save();
