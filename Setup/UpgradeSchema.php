<?php

namespace EthanYehuda\CronjobManager\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /** @var SchemaSetupInterface */
    private $setup;

    /** @var ModuleContextInterface */
    private $context;

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $this->setup = $setup;
        $this->context = $context;

        $this->setup->startSetup();

        if (version_compare($context->getVersion(), '1.6.0') < 0) {
            $this->addPidToSchedule();
        }

        $this->setup->endSetup();
    }

    /**
     * Add column to cron_schedule to keep track of the processes running on the server
     */
    public function addPidToSchedule()
    {
        $this->setup->getConnection()->addColumn(
            $this->setup->getTable("cron_schedule"),
            "pid",
            [
                "type" => Table::TYPE_INTEGER,
                "comment" => "Process id (pid) on the server",
                "nullable" => true,
                "default" => null,
                "after" => "status",
                "unsigned" => true
            ]
        );
    }
}
