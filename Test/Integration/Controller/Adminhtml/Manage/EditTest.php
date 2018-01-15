<?php

namespace EthanYehuda\CronjobManager\Test\Integration\Controller\Adminhtml\Manage;

use Magento\TestFramework\TestCase\AbstractBackendController;

class EditTest extends AbstractBackendController
{
    protected $uri = 'backend/cronjobmanager/manage/edit/id/1';

    protected $resource = 'EthanYehuda_CronjobManager::cronjobmanager';

    public function testEditAction()
    {
        $this->dispatch($this->uri);
        $result = $this->getResponse()->getBody();

        $this->assertContains('<title>Edit Cron Job / Tools / System / Magento Admin</title>', $result);
    }
}
