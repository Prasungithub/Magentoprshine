<?php

namespace Pcdev\Erpdata\Cron;

class Itempriceupdate
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
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/price-update'.date('Y-m-d').'.log');
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
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$CustAccount = $this->_erphelperdata->getCustAccount();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();
		$storeId = 1;

		$itemPriceResult = $this->_erpviewblock->getItemPrice($access_token);
		$itemPriceData = [];
			
		if($itemPriceResult['status'] == 'success' && isset($itemPriceResult['itemprice']) && count($itemPriceResult['itemprice']) > 0){
			
			/*All Item Price Record Stored Start*/
			foreach($itemPriceResult['itemprice'] as $itemPriceVal){
				$pr_id = '$id';
				$itemPriceData[$itemPriceVal->ItemId]['id'] = $itemPriceVal->$pr_id;
				$itemPriceData[$itemPriceVal->ItemId]['AccountNum'] = $itemPriceVal->AccountNum;
				$itemPriceData[$itemPriceVal->ItemId]['price'][] = array('AgreementValue'=>$itemPriceVal->AgreementValue, 'PriceUnitId'=>$itemPriceVal->PriceUnitId);					 
			}
			
			if(count($itemPriceData) > 0){
				foreach($itemPriceData as $itemPriceDataKey=>$itemPriceDataVal){
					$ip_p_sku = $itemPriceDataKey;
					$logger->info('SKU '.$ip_p_sku.' price record stored');
					$itemPriceData_sku = $itemPriceData[$ip_p_sku];
					$ip_up_result = $this->_erpviewblock->updateitemPrice($ip_p_sku,$itemPriceData_sku);
				}				
			}
			/*All Item Price Record Stored End*/
				
			/*Magento Product Price Update Start*/
			if(count($itemPriceData) > 0){
				foreach($itemPriceData as $itemPriceDataKeyM=>$itemPriceDataValM){
					$sku = $itemPriceDataKeyM;
					$logger->info('Magento Price Updated SKU '.$sku);
					$price_array = [];
					if(isset($itemPriceData[$sku]['price'])){
						$price_array = $itemPriceData[$sku]['price'];									
					}	

					// $logger->info('price array::'.print_r($price_array, true));					
					
					if($sku && count($price_array) > 0){
						foreach($price_array as $priceVal){
							
							// $erp_PriceUnitId = strtolower($priceVal['PriceUnitId']);
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
							
							$logger->info('Magento Price Set for Simple SKU '.$associatedProductSku);
							
							if ($this->_product->getIdBySku($associatedProductSku)){
								$simple_product = $productFactory->create(); 								
								$simple_product->setStoreId($storeId)->load($simple_product->getIdBySku($associatedProductSku));
								$product_stock_status = 1; //Product Enable
							} else {
								continue;
							}								
							
							$price = trim($priceVal['AgreementValue']);
							
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
							
							/*if(($sizeAttributeOptionText == '50gm') || ($sizeAttributeOptionText == '50g')){
								continue;
							}*/

							try {
								$simple_product->setPrice($price)
										->setWebsiteIds(array(1)) // Default Website ID
										->setStoreId(0) // Default store ID
										->save();
								
								$logger->info('Price '.$price.' updated for sku '.$associatedProductSku);
										
							} catch (\Magento\Framework\Exception $e){
									$logger->info('Error in Quantity in sku: '. $associatedProductSku);
									continue;
							}
							
							unset($simple_product);	
						}								
					}							
				}
			}						
			/*Magento Product Price Update End*/	
		} else {
			$logger->info('API ResponseStatus '. $itemPriceResult['status']);
		}
    }

}
