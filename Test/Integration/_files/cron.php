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
    ->setCreatedAt(date('Y-m-d H:i:s', time()))
    ->setScheduledAt(date('Y-m-d H:i:s', strtotime('+5 minutes')));

$cron->save();
