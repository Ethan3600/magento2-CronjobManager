<?php

namespace EthanYehuda\CronjobManager\Test\Integration\Controller\Adminhtml\Manage;

use Magento\TestFramework\TestCase\AbstractBackendController;

class IndexTest extends AbstractBackendController
{
    protected $uri = 'backend/cronjobmanager/manage';

    protected $resource = 'EthanYehuda_CronjobManager::cronjobmanager';

    /**
     * @magentoDataFixture loadDataFixtureCron
     */
    public function testIndexAction()
    {
        $this->dispatch($this->uri);
        $result = $this->getResponse()->getBody();

        $this->assertContains('<title>Cron Job Dashboard / Tools / System / Magento Admin</title>', $result);
    }

    public static function loadDataFixtureCron()
    {
        include __DIR__ . '/../../../_files/cron.php';
    }
}
