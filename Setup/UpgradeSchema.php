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
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $magentoMetaData;

    public function __construct(\Magento\Framework\App\ProductMetadataInterface $magentoMetaData)
    {
        $this->magentoMetaData = $magentoMetaData;
    }

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
        if (version_compare($this->magentoMetaData->getVersion(), '2.3.0', '>=')) {
            /*
             * For Magento 2.3+, db_schema.xml is used instead
             */
            return;
        }
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
