<?php

namespace Pcdev\Erpdata\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface{
	
    public function install(SchemaSetupInterface $setup,ModuleContextInterface $context){
        $setup->startSetup();
        $conn = $setup->getConnection();
        $tableName = $setup->getTable('erp_product_data');
		
        if($conn->isTableExists($tableName) != true){
			
            $table = $conn->newTable($tableName)
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
						'erp_pid',
						\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
						null,
						[
							'nullable'=>true,
							'unsigned'=>true
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
					->addColumn(
						'updated_at',
						\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
						null,
						[
							'nullable' => false,
							'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE
						],
						'Updated At'
					)					
					->setOption('charset','utf8');
							
            $conn->createTable($table);			
        }
		
        $setup->endSetup();
    }
}
?>