<?php
namespace Pcdev\Erpdata\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
	public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
		$installer = $setup;

		$installer->startSetup();

		if(version_compare($context->getVersion(), '1.0.6', '<')) {
			if (!$installer->tableExists('erp_product_data')) {
					$table = $installer->getConnection()->newTable(
					$installer->getTable('erp_product_data')
				)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity'=>true,
						'unsigned'=>true,
						'nullable'=>false,
						'primary'=>true
					]
				)
				->addColumn(
					'topcategory',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[
						'nullable'=>false,
						'default'=>''
					]
				)
				->addColumn(
					'name',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[
						'nullable'=>false,
						'default'=>''
					]
				)			
				->addColumn(
					'sku',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					100,
					[
						'nullable'=>false,
						'default'=>''
					]
				)
				->addColumn(
					'price',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,2',
					[
						'nullable' => true,
						'default'=>'0.00'
					]								
				)	
				->addColumn(
					'tax_percent',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,2',
					[
						'nullable' => true,
						'default'=>'0.00'
					]
				)	
				->addColumn(
					'quantity',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,2',
					[
						'nullable' => true,
						'default'=>'0.00'
					]
				)
				->addColumn(
					'quantity_unit',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					100,
					[
						'nullable'=>true,
						'default'=>''
					]
				)
				->addColumn(
					'uom',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					100,
					[
						'nullable'=>true,
						'default'=>''
					]
				)
				->addColumn(
					'categories',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[
						'nullable'=>true,
						'default'=>''
					]
				)
				->addColumn(
					'attributes',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					[
						'nullbale'=>false,
						'default'=>''
					]
				)				
				->addColumn(
					'packing',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					[
						'nullbale'=>false,
						'default'=>''
					]
				)					
				->addColumn(
					'unit_convert',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					[
						'nullbale'=>false,
						'default'=>''
					]
				)							
				->addColumn(
					'item_prices',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					[
						'nullbale'=>false,
						'default'=>''
					]
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					[
						'nullable' => false,
						'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
					],
					'Created At'
				)
				->setOption('charset','utf8');

				$installer->getConnection()->createTable($table);
			}						
			
		}

		$installer->endSetup();
	}
}