<?php

namespace EthanYehuda\CronjobManager\Test\Integration\Controller\Adminhtml\Manage;

use Magento\TestFramework\TestCase\AbstractBackendController;

class CreateTest extends AbstractBackendController
{
    /** @var string */
    protected $uri = 'backend/cronjobmanager/manage/create';

    /** @var string */
    protected $resource = 'EthanYehuda_CronjobManager::cronjobmanager';

    public function testEditAction()
    {
        $this->dispatch($this->uri);
        $result = $this->getResponse()->getBody();

        if (\method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                '<title>Create Cron Job / Tools / System / Magento Admin</title>',
                $result
            );
        } else {
            $this->assertContains(
                '<title>Create Cron Job / Tools / System / Magento Admin</title>',
                $result
            );
        }
    }
}
