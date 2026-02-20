<?php

namespace Pcdev\Erpdata\Cron;

class Iteminventoryupdate
{
    protected $_storeManager;
    protected $_resourceConnection;
	protected $_erpviewblock;
	protected $_erphelperdata;
	protected $_product;
	protected $_eavConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpViewBlock,
		\Pcdev\Erpdata\Helper\Data $erpHelperData,
		\Magento\Catalog\Model\Product $productDt,
		\Magento\Eav\Model\Config $eavConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->_resourceConnection = $resourceConnection;
		$this->_erpviewblock = $erpViewBlock;
		$this->_erphelperdata = $erpHelperData;
		$this->_product = $productDt;
		$this->_eavConfig = $eavConfig;
    }

    public function execute()
    {		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/inventory-update'.date('Y-m-d').'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);

		$AuthTocken = $this->_erpviewblock->getAuthenticationTocken();
		if(isset($AuthTocken['access_token']) && $AuthTocken['access_token']){
			$access_token = $AuthTocken['access_token'];
		} else {
			$logger->info('Auth Tocken not found');
			return false;
		}
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
		$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
		$storeId = 1;		
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$CustAccount = $this->_erphelperdata->getCustAccount();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();

		$selectAllitems = $this->_erpviewblock->getAllItemsSku();		
		$selectAllitems_count = count($selectAllitems);
		
		if($selectAllitems_count > 0){				
			$fatchrows = 40;
			$loopcount = round($selectAllitems_count/$fatchrows);				
			$limit_start = 0;
			for($c = 1; $c <= $loopcount;){
				
				$selectLimitItems = $this->_erpviewblock->getLimitItemsSku($limit_start,$fatchrows);
				if(count($selectLimitItems) > 0){
					
					foreach($selectLimitItems as $selectLimitItem){
						$stockOnhand = [];
						$p_sku = $selectLimitItem['sku'];
						$ItemOnhand = $this->_erpviewblock->getItemOnhand($p_sku,$access_token);
						if($ItemOnhand['status'] == 'success' && isset($ItemOnhand['stock'])){
							if(isset($ItemOnhand['stock'][0]->AvailableQty) && isset($ItemOnhand['stock'][0]->InventUnitId)){
								$stockOnhand['quantity'] = $ItemOnhand['stock'][0]->AvailableQty;
								$stockOnhand['quantity_unit'] = $ItemOnhand['stock'][0]->InventUnitId;
								$up_result = $this->_erpviewblock->updateStockOnHand($p_sku,$stockOnhand);	
								$logger->info('Product Inventory Update SKU '.$p_sku);
							}				
						}
					}
				}
				
				$limit_start = $fatchrows * $c;
				$c = $c + 1;
			}
		}
		
		/*Magento Simple Product Item On-hand*/
		if($selectAllitems_count > 0){
			$connection = $this->_resourceConnection->getConnection();
			$erpTableName = $connection->getTableName('erp_product_data');
			$allItemsQuery = "SELECT `id`, `topcategory`, `name`, `erp_pid`, `sku`, `price`, `tax_percent`, `quantity`, `quantity_unit`, `uom`, `categories`, `attributes`, `packing`, `unit_convert`, `item_prices` FROM " . $erpTableName;
			$allItemsResults = $connection->fetchAll($allItemsQuery);			
			
			foreach($allItemsResults as $allItemsVal)
			{
				try{
				$price_array = [];
				$sku = $allItemsVal['sku'];
					
				$price_array = unserialize($allItemsVal['item_prices']);
				if(isset($price_array['price'])){
					$price_array = $price_array['price'];									
				}

				if(strtolower($allItemsVal['quantity_unit']) == 'kg'){
					$totalErpInventory = $allItemsVal['quantity'] * 1000;
					$uom_Label = 'gm';
					$conf_uom = $this->getUom($uom_Label);
				} elseif(strtolower($allItemsVal['quantity_unit']) == 'pc'){
					$uom_Label = 'pc';
					$conf_uom = $this->getUom($uom_Label);
					$totalErpInventory = $allItemsVal['quantity'];
				} else{
					$uom_Label = 'pc';
					$conf_uom = $this->getUom($uom_Label);									
					$totalErpInventory = $allItemsVal['quantity'];
				}	

				if($sku && count($price_array) > 0){
					foreach($price_array as $priceVal){
						
						$erp_PriceUnitId = preg_replace('/[^a-z]/i', '', strtolower($priceVal['PriceUnitId']));
						$weight_no_only = preg_replace('/[^0-9]/i', '', strtolower($priceVal['PriceUnitId']));
						
						if($erp_PriceUnitId == 'gm'){
							$erp_PriceUnitId = 'g';
						}
						// for handeling PriceUnitId g and we set it as 1g
						if($weight_no_only == ''){
							$weight_no_only = 1;
						}
						
						$associatedProductSku = $sku.'-'.$weight_no_only.''.$erp_PriceUnitId;
						
						if ($this->_product->getIdBySku($associatedProductSku)){
							$simple_product = $productFactory->create();
							$simple_product->setStoreId($storeId)->load($simple_product->getIdBySku($associatedProductSku));
							$product_stock_status = 1; //Product Enable
						}						
						
						$sizeAttributeOptionText = strtolower($priceVal['PriceUnitId']);
						if($sizeAttributeOptionText == 'g'){
							$sizeAttributeOptionText = '1gm';
						}
						$size_attribute_code = 'size';
						$sizeattribute = $this->_eavConfig->getAttribute('catalog_product', $size_attribute_code);
						$sizeAttributeOptionId = $sizeattribute->getSource()->getOptionId($sizeAttributeOptionText);

						$weight = preg_replace('/[^0-9]/i', '', strtolower($priceVal['PriceUnitId']));
						$uom_Label = preg_replace('/[^a-z]/i', '', strtolower($priceVal['PriceUnitId']));	
						if($uom_Label == 'g'){
							$uom_Label = 'gm';
						}
						if($uom_Label == 'kg'){
							if(is_numeric($weight)){
								$weight = $weight * 1000;
							}											
							$uom_Label = 'gm';
						}									
						$simple_product_uom = $this->getUom($uom_Label);
						
						/* For Have It Your Way Products Disable Start */
						$productenabledisable = 1;
						if($weight < 50 || $allItemsVal['quantity'] < 1){
							$productenabledisable = 0; //Product Disable 
						}
						/* For Have It Your Way Products Disable End */						
						
						try {
							$simple_product->setTotalErpInventory($totalErpInventory)
									->setStatus($productenabledisable) // 1 = enabled
									->setUom($simple_product_uom)
									->setWebsiteIds(array(1)) // Default Website ID
									->setStoreId(0) // Default store ID
									->save();
							
							$logger->info('Total Erp Inventory '.$totalErpInventory.' updated for sku '.$associatedProductSku);
									
						} catch (\Magento\Framework\Exception $e){
							
							$logger->info('sku not found '.$associatedProductSku);								

							continue;
						}							

						try
						{
							$stockItem = $stockRegistry->getStockItemBySku($associatedProductSku);
				 
							if ($stockItem->getQty() != $allItemsVal['quantity'])
							{
								$quantity = $allItemsVal['quantity'];
								$stockItem->setQty($quantity);
								$stockItem->setIsQtyDecimal(1);
								if ($allItemsVal['quantity'] > 0)
								{
									$stockItem->setIsInStock(1);													
								}else{
									$stockItem->setIsInStock(0);
								}
								$stockRegistry->updateStockItemBySku($associatedProductSku, $stockItem);
							}
						} catch (\Magento\Framework\Exception $e){
							
							$logger->info('Quantity not updated SKU '.$associatedProductSku);								

							continue;
						}
						
						unset($simple_product);
					}								
				}
				
				$isDisable = 1;
				if($sku && $this->_product->getIdBySku($sku)){
					$configurable_product = $productFactory->create();
					$configurable_product->setStoreId($storeId)->load($configurable_product->getIdBySku($sku));
					
					$defaultChildItem = $configurable_product->getDefaultChildProduct();
					$isDisable = 1;
					if($defaultChildItem != ''){
						$base_product = $productFactory->create();
						$base_product->setStoreId($storeId)->load($base_product->getIdBySku($defaultChildItem));
						$sizeattribute = $base_product->getResource()->getAttribute('size');
						$sizeattributeval = $sizeattribute->getFrontend()->getValue($base_product);
						$baseWeighttobe = str_replace('gm','', strtolower($sizeattributeval));
						$baseWeighttobe = str_replace('km','', strtolower($baseWeighttobe));
						$baseWeighttobe = str_replace('k','', strtolower($baseWeighttobe));
						$baseWeighttobe = str_replace('g','', strtolower($baseWeighttobe));
						$uomEav = $this->_eavConfig->getAttribute('catalog_product', 'uom');
						$uomattributeval = $uomEav->getSource()->getOptionText($conf_uom);
						if(strtolower($uomattributeval) == 'pc'){
							if($totalErpInventory < 1){
								$isDisable = 0;
							}
						}else{
							if($totalErpInventory < $baseWeighttobe){
								$isDisable = 0;
							}
						}
					}
				}
				
				try
				{
					$configurable_product->setTotalErpInventory($totalErpInventory)
										 ->setUom($conf_uom)			
										 ->setWebsiteIds(array(1))
										 ->setStoreId(0) // Default store ID
										 ->setStatus($isDisable)
										 ->save();
					$configurable_product->addAttributeUpdate('status', $isDisable, 1);
										 
					$logger->info('Total Erp Inventory '.$totalErpInventory.' updated for sku '.$sku);
							
				} catch (\Magento\Framework\Exception $e){
					
					$logger->info('sku not found '.$sku);
					
				}											 
				
				try
				{
					$conf_stockItem = $stockRegistry->getStockItemBySku($sku);
		 
					if ($conf_stockItem->getQty() != $allItemsVal['quantity'])
					{
						$quantity = $allItemsVal['quantity'];
						$conf_stockItem->setQty($quantity);
						$conf_stockItem->setIsQtyDecimal(1);
						if ($allItemsVal['quantity'] > 0)
						{
							$conf_stockItem->setIsInStock(1);												
						}else{
							$conf_stockItem->setIsInStock(0);
						}
						$stockRegistry->updateStockItemBySku($sku, $conf_stockItem);
					}
				} catch (\Magento\Framework\Exception $e){
					
					$logger->info('Quantity not updated '.$sku);
				}									

				unset($configurable_product);
				}catch(\Exception $e){
					$logger->info($e->getMessage());
					continue;
				}
			}
		}
		/*Magento Simple Product Item On-hand Update*/	
		
    }
	
	public function getUom($uomLabel)
	{
		$UomArr = array('kg'=>18,'KG'=>18,'gm'=>19,'lt'=>20,'PC'=>77,'pc'=>77);
		if(isset($UomArr[$uomLabel])){
			return $UomArr[$uomLabel];
		} else {
			return '';
		}
	}
	
	public function getAttributeSetId($attributeSetName)
	{
		$attributeSetName = strtoupper($attributeSetName);
		$AttributeSetArr = array('DEFAULT'=>4,'BAYARA'=>9,'NUTS & SEEDS'=>10,'DRIED FRUITS & DATES'=>11,'SPICES & SEASONING'=>12,'PULSES GRAINS'=>13, 'PULSES & GRAINS'=>13, 'TEAS & COFFEES'=>4);
		if(isset($AttributeSetArr[$attributeSetName])){
			return $AttributeSetArr[$attributeSetName];
		} else {
			return false;
		}
	}	

}
