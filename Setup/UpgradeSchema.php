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
     * @inheritdoc
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

        if (version_compare($context->getVersion(), '1.6.4') < 0) {
            $this->addKillRequestToSchedule();
        }

        if (version_compare($context->getVersion(), '1.9.0') < 0) {
            $this->addHostnameToSchedule();
        }

        if (version_compare($context->getVersion(), '1.10.0') < 0) {
            $this->addGroupToSchedule();
            $this->addDurationToSchedule();
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
            ]
        );
    }

    /**
     * Add column to cron_schedule to keep track of which a job's duration
     */
    public function addDurationToSchedule()
    {
        if (version_compare($this->magentoMetaData->getVersion(), '2.3.0', '>=')) {
            // For Magento 2.3+, db_schema.xml is used instead
            return;
        }
        $this->setup->getConnection()->addColumn(
            $this->setup->getTable('cron_schedule'),
            'duration',
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => 'Number of seconds job ran for',
                'nullable' => true,
                'default' => null,
                'after' => 'group',
            ]
        );
    }

    /**
     * Add column to cron_schedule to keep track of which group a job belongs to
     */
    public function addGroupToSchedule()
    {
        if (version_compare($this->magentoMetaData->getVersion(), '2.3.0', '>=')) {
            // For Magento 2.3+, db_schema.xml is used instead
            return;
        }
        $this->setup->getConnection()->addColumn(
            $this->setup->getTable('cron_schedule'),
            'group',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Cron group for this job',
                'nullable' => true,
                'default' => null,
                'after' => 'pid',
            ]
        );
    }

    /**
     * Add column to cron_schedule to keep track of which server is running each process
     */
    public function addHostnameToSchedule()
    {
        if (version_compare($this->magentoMetaData->getVersion(), '2.3.0', '>=')) {
            // For Magento 2.3+, db_schema.xml is used instead
            return;
        }
        $this->setup->getConnection()->addColumn(
            $this->setup->getTable('cron_schedule'),
            'hostname',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Hostname of the server running this job',
                'nullable' => true,
                'default' => null,
                'after' => 'pid',
            ]
        );
    }

    /**
     * Add column to cron_schedule to send kill requests
     */
    public function addKillRequestToSchedule()
    {
        if (version_compare($this->magentoMetaData->getVersion(), '2.3.0', '>=')) {
            /*
             * For Magento 2.3+, db_schema.xml is used instead
             */
            return;
        }
        $this->setup->getConnection()->addColumn(
            $this->setup->getTable("cron_schedule"),
            "kill_request",
            [
                "type" => Table::TYPE_TIMESTAMP,
                "comment" => "Timestamp of kill request",
                "nullable" => true,
                "default" => null,
                "after" => "pid",
                "unsigned" => true
            ]
        );
    }
}
