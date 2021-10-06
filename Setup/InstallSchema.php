<?php
namespace RedMonks\ImprovedLayeredNavigation\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        /**
         * Create table 'redmonks_attribute_options'
         */
        $table = $setup->getConnection()->newTable(
            $setup->getTable('redmonks_attribute_options')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'attribute_id',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Attribute Id'
        )->addColumn(
            'display_option',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Filter Display Option'
        )->addColumn(
            'is_multiselect',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Enable Multiselect'
        )->addIndex(
            $setup->getIdxName($setup->getTable('redmonks_attribute_options'),['id']),
            ['id']
        )->addForeignKey(
                $setup->getFkName('redmonks_attribute_options', 'attribute_id', 'eav_attribute', 'attribute_id'),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                Table::ACTION_CASCADE
            )->setComment(
            'RedMonks Layered Navigation Attribute Options'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();


    }
}
