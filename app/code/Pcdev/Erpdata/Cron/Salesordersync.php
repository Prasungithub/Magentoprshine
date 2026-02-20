<?php

namespace Pcdev\Erpdata\Cron;

class Salesordersync
{
    protected $_storeManager;
    protected $_resourceConnection;
	protected $_erpviewblock;
	protected $_erphelperdata;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpViewBlock,
		\Pcdev\Erpdata\Helper\Data $erpHelperData
    ) {
        $this->_storeManager = $storeManager;
        $this->_resourceConnection = $resourceConnection;
		$this->_erpviewblock = $erpViewBlock;
		$this->_erphelperdata = $erpHelperData;
    }

    public function execute()
    {
		$connection = $this->_resourceConnection->getConnection();
		$salesOrderTableName = $connection->getTableName('sales_order');
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order-sync-'.date('Y-m-d').'.log');
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
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$CustAccount = $this->_erphelperdata->getCustAccount();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();		
		
		/*$salesOrderQuery = "SELECT `entity_id`, `state`, `status`, `increment_id` FROM " . $salesOrderTableName . " WHERE `is_sync`='0' AND `erpsalesid` IS NULL AND (`status`='pending' OR `status` = 'processing') ORDER BY `entity_id` DESC LIMIT 5";*/
		
		$salesOrderQuery = "SELECT `sales_order`.`entity_id`, `sales_order`.`state`, `sales_order`.`status`, `sales_order`.`increment_id` FROM " . $salesOrderTableName . " INNER JOIN `sales_order_payment` ON `sales_order`.`entity_id`=`sales_order_payment`.`parent_id` WHERE `sales_order`.`is_sync`='0' AND `sales_order`.`erpsalesid` IS NULL AND ((`sales_order`.`status`='pending' AND `sales_order_payment`.`method`='cashondelivery') OR `sales_order`.`status` = 'processing') ORDER BY `sales_order`.`entity_id` DESC LIMIT 50";
		
		$salesOrderResults = $connection->fetchAll($salesOrderQuery);		
		if(count($salesOrderResults)>0){
			foreach($salesOrderResults as $salesOrderVal){
				
				$orderdata =[];		
				$increment_id = $salesOrderVal['increment_id'];	
				$logger->info('Order No '.$increment_id);
				
				try {
				$orderload = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);		
				$orderId = $orderload->getId();		
				$order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);	
				
				$is_sync = $order->getIsSync();		
				$paymentData = $order->getPayment();
				$paymentMethod = $paymentData->getMethod();
				$PaymTermId = '';		
				if($paymentMethod == 'ccavenue'){
					$PaymTermId = 'Card';			
				} elseif($paymentMethod == 'cashondelivery'){
					$PaymTermId = 'Cash';
				}			
				$order_status = $order->getStatus();  //pending, processing
				$DeliveryName = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
				$deliverydateTableName = $connection->getTableName('amasty_deliverydate_deliverydate_order');
				
				$deliverydate = '';
				$DeliveryRemarks = '';
				$tax_percent = 0;
				
				$finddeliverydateQuery = "SELECT `date`, `comment` FROM " . $deliverydateTableName . " WHERE `order_id`='".$orderId."'";
				$finddeliverydateResults = $connection->fetchAll($finddeliverydateQuery);		
				if(count($finddeliverydateResults)>0){
					$deliverydate = $finddeliverydateResults[0]['date'];
					$DeliveryRemarks = $finddeliverydateResults[0]['comment'];
				}
				
				$ShippingMethod = $order->getShippingMethod();	
				$discount_description = $order->getDiscountDescription();
				$ChargeCode = '';
				if($is_sync == '0' && ($order_status == 'pending' || $order_status == 'processing')){
					$SalesChargesList = [];
					if($order->getShippingDescription()){
						$ShippingDescriptionArr = explode(' - ',$order->getShippingDescription());
						$ShippingDescription = $ShippingDescriptionArr[1];
						if($ShippingMethod){
							$ChargeCode = 'Transport';
						}
						
						if($order->getShippingAmount() > 0){
							$SalesChargesList[] = ['EComSalesId'=>$order->getIncrementId(),'ChargeCode'=>$ChargeCode,'ChargeAmount'=>'+'.$order->getShippingAmount(),'ChargeDescription'=>$ShippingDescription];
						}
						
					}

					$SalesLineList = [];
					foreach($order->getAllItems() as $orderitem){
							
						$configurableSku = '';
						$SalesUnitId = '';
						$qty_ordered = 0;
						$SalesPrice = 0;
						$DiscAmountPerQty = 0;
						$FOCReason = '';
						$PackageType = '';
						$discount_amount = $orderitem->getDiscountAmount();
						if($discount_amount > 0){
							
							$applied_rule_ids = $order->getAppliedRuleIds();
							if(empty($discount_description) && !empty($applied_rule_ids)){
								$applied_rule_ids = explode(',',$applied_rule_ids);
								$cust_description = '';
								if(count($applied_rule_ids) > 0){
									foreach($applied_rule_ids as $applied_rule_id){
										$appliedRuleQuery = "SELECT `description` FROM `salesrule` WHERE `rule_id`='".$applied_rule_id."'";
										$appliedRuleResults = $connection->fetchAll($appliedRuleQuery);		
										if(count($appliedRuleResults)>0){
											if(!empty($appliedRuleResults[0]['description'])){
												$description_text = $appliedRuleResults[0]['description'];
												$cust_description = $cust_description .', '.$description_text;
											}
										}						
										
									}
									
									if(!empty($cust_description)){
										$discount_description = trim($cust_description,",");
									}
								}
							}	
							
							$FOCReason = $discount_description;
						}
						
						if($orderitem->getProductType() == 'configurable'){
							$p_id = $orderitem->getProductId();
							$loadproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($p_id);
							$configurableSku = $loadproduct->getSku();
							
							if($orderitem->getProductOptions()){
								$ProductOptions = $orderitem->getProductOptions();
								
								if($ProductOptions['attributes_info'][0]['label'] == 'Size'){
									$SalesUnitId = $ProductOptions['attributes_info'][0]['value'];
								}

								if(isset($ProductOptions['options'])){
									if($ProductOptions['options'][0]['label'] == 'Packing'){
										$PackageType = $ProductOptions['options'][0]['value'];
									}
								}	
							}
							
							$qty_ordered = $orderitem->getQtyOrdered();
							$SalesPrice = $orderitem->getPriceInclTax();
							$DiscAmountAllQty = $orderitem->getDiscountAmount();
							$tax_percent = $orderitem->getTaxPercent();
							$DiscAmountPerQty = 0;
							if($DiscAmountAllQty > 0){
								$DiscAmountPerQty = $DiscAmountAllQty / $qty_ordered;
							}
							
							$SalesLineList[] = [
								'EComSalesId'=>$order->getIncrementId(),
								'EComInventTransId'=>$orderitem->getItemId(),
								'ItemId'=>$configurableSku,
								'SalesUnitId'=>$SalesUnitId,
								'SalesQty'=>$qty_ordered, 
								'SalesPrice'=>$SalesPrice, 
								'DiscAmountPerQty'=>$DiscAmountPerQty, 
								'FOCReason'=>$FOCReason,
								'PackageType'=>$PackageType,
								'TaxRate'=>$tax_percent
							];			
							
						}
						
						/*Bundle Product Start*/
						if($orderitem->getProductType() == 'bundle'){

							$p_id = $orderitem->getProductId();
							$bundle_item_id = $orderitem->getItemId();
							$loadproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($p_id);
							$bundleSku = $loadproduct->getSku();
						}
						if(isset($bundle_item_id)){
							if($orderitem->getProductType() == 'simple' && $orderitem->getParentItemId() == $bundle_item_id){
								
								if($orderitem->getProductOptions()){
									$ProductOptions = $orderitem->getProductOptions();
									
									/*if($ProductOptions['attributes_info'][0]['label'] == 'Size'){
										$SalesUnitId = $ProductOptions['attributes_info'][0]['value'];
									}*/
									$SalesUnitId = '50gm';

									/*if($ProductOptions['options'][0]['label'] == 'Packing'){
										$PackageType = $ProductOptions['options'][0]['value'];
									}*/	
									$PackageType = '';
								}
								
								$qty_ordered = $orderitem->getQtyOrdered();
								$SalesPrice = $orderitem->getPriceInclTax();
								$DiscAmountAllQty = $orderitem->getDiscountAmount();
								$tax_percent = $orderitem->getTaxPercent();
								$DiscAmountPerQty = 0;
								if($DiscAmountAllQty > 0){
									$DiscAmountPerQty = $DiscAmountAllQty / $qty_ordered;
								}
								
								$bundle_simple_sku_arr = explode("-50g",$orderitem->getSku());
								$bundle_simple_sku = $bundle_simple_sku_arr[0];
								
								$SalesLineList[] = [
									'EComSalesId'=>$order->getIncrementId(),
									'EComInventTransId'=>$orderitem->getItemId(),
									'ItemId'=>$bundle_simple_sku,
									'SalesUnitId'=>$SalesUnitId,
									'SalesQty'=>$qty_ordered, 
									'SalesPrice'=>$SalesPrice, 
									'DiscAmountPerQty'=>$DiscAmountPerQty, 
									'FOCReason'=>$FOCReason,
									'PackageType'=>$PackageType,
									'TaxRate'=>$tax_percent
								];
							}
						}
						/*Bundle Product End*/	

						/*Simple Product Start*/
						if($orderitem->getProductType() == 'simple' && is_null($orderitem->getParentItemId()) && empty($orderitem->getParentItemId())){
							
							if($orderitem->getProductOptions()){
								$ProductOptions = $orderitem->getProductOptions();
								
								if(isset($ProductOptions['attributes_info'][0]['label']) && isset($ProductOptions['attributes_info'][0]['value']) && $ProductOptions['attributes_info'][0]['label'] == 'Size'){
									$SalesUnitId = $ProductOptions['attributes_info'][0]['value'];
								} else {
									$SalesUnitId = '50gm';
								}
								
								if(isset($ProductOptions['options'][0]['label']) && isset($ProductOptions['options'][0]['value']) && $ProductOptions['options'][0]['label'] == 'Packing'){
									$PackageType = $ProductOptions['options'][0]['value'];
								} else {
									$PackageType = '';
								}
							}
							
							$qty_ordered = $orderitem->getQtyOrdered();
							$SalesPrice = $orderitem->getPriceInclTax();
							$DiscAmountAllQty = $orderitem->getDiscountAmount();
							$tax_percent = $orderitem->getTaxPercent();
							$DiscAmountPerQty = 0;
							if($DiscAmountAllQty > 0){
								$DiscAmountPerQty = $DiscAmountAllQty / $qty_ordered;
							}
							
							$bundle_simple_sku_arr = explode("-50g",$orderitem->getSku());
							$bundle_simple_sku = $bundle_simple_sku_arr[0];
							
							$SalesLineList[] = [
								'EComSalesId'=>$order->getIncrementId(),
								'EComInventTransId'=>$orderitem->getItemId(),
								'ItemId'=>$bundle_simple_sku,
								'SalesUnitId'=>$SalesUnitId,
								'SalesQty'=>$qty_ordered, 
								'SalesPrice'=>$SalesPrice, 
								'DiscAmountPerQty'=>$DiscAmountPerQty, 
								'FOCReason'=>$FOCReason,
								'PackageType'=>$PackageType,
								'TaxRate'=>$tax_percent
							];
						}				
						/*Simple Product End*/
						
					}
					
					$ShippingAddress = $order->getShippingAddress();			
					$order_tax_amount = $order->getTaxAmount();
					$InclTax = 'Yes';
					if($order_tax_amount > 0){
						$InclTax = 'Yes';
					} else {
						$InclTax = 'No';
					}
					
					$regionId = $ShippingAddress->getRegionId();
					$getRegionDataSql = "SELECT `main_table`.*, `rname`.`name` FROM `directory_country_region` AS `main_table`
 LEFT JOIN `directory_country_region_name` AS `rname` ON main_table.region_id = rname.region_id AND rname.locale = 'en_US' WHERE (`rname`.`region_id` = '".$regionId."') ORDER BY name ASC, default_name ASC";
 					$getRegionData = $connection->fetchAll($getRegionDataSql);
					
					$logger->info('Region'.json_encode($getRegionData));
					$DeliveryState = ($getRegionData[0]['name']!='') ? $getRegionData[0]['name'] : $getRegionData[0]['default_name'];
					if($getRegionData[0]['code']=='AE-UQ'){
						$DeliveryState = 'UAQ';
					}
					if($getRegionData[0]['code']=='AE-RK'){
						$DeliveryState = 'RAK';
					}
					
					$DeliveryCountryRegionId = $ShippingAddress->getCountryId();
					if($DeliveryCountryRegionId == 'AE' || $DeliveryCountryRegionId == 'UAE'){
						$DeliveryCountryRegionId = 'ARE';
					}
					$DeliveryAddressArr = $ShippingAddress->getStreet();
					$DeliveryAddress = implode(', ',$DeliveryAddressArr);
					$PhoneMobile = $ShippingAddress->getTelephone();
					$customer_id = $order->getCustomerId();
					if(empty($PhoneMobile) && !empty($customer_id)){
						$phoneMobileQuery = "SELECT `customer_entity_varchar`.`value` FROM `eav_attribute` LEFT JOIN `customer_entity_varchar` ON `eav_attribute`.`attribute_id`= `customer_entity_varchar`.`attribute_id` WHERE `customer_entity_varchar`.`entity_id` = '".$customer_id."' AND `eav_attribute`.`attribute_code` = 'mobile'";
						
						$phoneMobileResults = $connection->fetchAll($phoneMobileQuery);		
						if(count($phoneMobileResults)>0){
							$PhoneMobile = $phoneMobileResults[0]['value'];					
						}
					}
					
					$quote_id = $order->getQuoteId();
					if(empty($PhoneMobile) && !empty($quote_id)){
						$phoneMobileQuoteQuery = "SELECT `telephone`, `address_type` FROM `quote_address` WHERE `quote_id`='".$quote_id."'";
						$phoneMobileQuoteResults = $connection->fetchAll($phoneMobileQuoteQuery);		
						if(count($phoneMobileQuoteResults)>0){
							$PhoneMobileShip = '';
							$PhoneMobileBill = '';
							foreach($phoneMobileQuoteResults as $phoneMobileQuoteVal){				
						
								if(!empty($phoneMobileQuoteVal['telephone']) && $phoneMobileQuoteVal['address_type'] == 'shipping'){
									$PhoneMobileShip = $phoneMobileQuoteVal['telephone'];
								}
								
								if(!empty($phoneMobileQuoteVal['telephone']) && $phoneMobileQuoteVal['address_type'] == 'billing'){
									$PhoneMobileBill = $phoneMobileQuoteVal['telephone'];
								}						
							}
							
							if(!empty($PhoneMobileShip)){
								$PhoneMobile = $PhoneMobileShip;
							}
							
							if(empty($PhoneMobile) && !empty($PhoneMobileBill) ){
								$PhoneMobile = $PhoneMobileBill;
							}						
						} 
					}

					if(empty($PhoneMobile) && !empty($quote_id)){
						$phoneMobileQuoteTablQuery = "SELECT `mobileno` FROM `quote` WHERE `entity_id`='".$quote_id."'";
						$phoneMobileQuoteTablResults = $connection->fetchAll($phoneMobileQuoteTablQuery);		
						if(count($phoneMobileQuoteTablResults)>0){
							$PhoneMobile = $phoneMobileQuoteTablResults[0]['mobileno'];	
						}
					}				
					
					$RequestedDate = date('Y-m-d',strtotime($order->getCreatedAt()));
					
					$shippingTaxCal = 0;
					if($order->getShippingAmount() > 0 && ($tax_percent > 0)){
						$shippingTaxCal = ($order->getShippingAmount() * $tax_percent) / (100 + $tax_percent);
						$shippingTaxCal = round($shippingTaxCal, 4);
					}
					$AllTaxCalculated = $order->getTaxAmount() + $shippingTaxCal;

					$orderdata = [
						'CustAccount'=>$CustAccount,
						'DataOrigin'=>$DataOrigin,
						'DeliveryName'=>$DeliveryName,
						'DeliveryAddress'=>$DeliveryAddress,
						'DeliveryCity'=>$ShippingAddress->getCity(),
						'DeliveryState'=>$DeliveryState,
						'DeliveryCountryRegionId'=>$DeliveryCountryRegionId,
						'DeliveryDate'=>$deliverydate,
						'DeliveryRemarks'=>$DeliveryRemarks,
						'EComSalesId'=>$order->getIncrementId(),
						'Email'=>$order->getCustomerEmail(),
						'InclTax'=>$InclTax,
						'PaymTermId'=>$PaymTermId,
						'PhoneMobile'=>$PhoneMobile,
						'RequestedDate'=>$RequestedDate,
						'CompanyId'=>$CompanyId,
						'SlefPickup'=>'no',
						'SubTotal'=>$order->getSubtotal(),
						'VATAmount'=>$order->getTaxAmount(),
						'OrderTotal'=>$order->getGrandTotal(),
						'SalesChargesList'=>$SalesChargesList,
						'SalesLineList'=>$SalesLineList
					];

					//echo '<pre>';
					//print_r($order->debug());
					//print_r($orderdata);
					//die();			
					
					$OrderSyncArr = $this->_erpviewblock->SalesOrderSync($access_token,$orderdata);
					
					if($OrderSyncArr['salesorderdata'] && isset($OrderSyncArr['salesorderdata']->SalesResult) && $OrderSyncArr['salesorderdata']->SalesResult == 'True'){
						$is_sync = 1;
						$erpSalesId = $OrderSyncArr['salesorderdata']->SalesId;
						$erpsalesremarks = $OrderSyncArr['salesorderdata']->SalesRemarks;
						$logger->info('ERP Order Id '.$erpSalesId);
						
						$order->setIsSync($is_sync);
						$order->setErpsalesid($erpSalesId);
						$order->setErpsalesremarks($erpsalesremarks);
						$order->save();
						
						/*Process for Payment Journal*/
						$PaymentJournalArr = $this->_erpviewblock->PaymentJournal($access_token,$increment_id);
						if(isset($PaymentJournalArr['paymentjournaldata']) && isset($PaymentJournalArr['paymentjournaldata']->JournalId)){
							$journalIdlog = $PaymentJournalArr['paymentjournaldata']->JournalId;
							$logger->info('Payment JournalId '.$journalIdlog);	
						}						
					}
					
					if($OrderSyncArr['salesorderdata'] && isset($OrderSyncArr['salesorderdata']->SalesRemarks)){
						$erpSalesRemarksforlog = $OrderSyncArr['salesorderdata']->SalesRemarks;
						$logger->info('ERP SalesRemarks '.$erpSalesRemarksforlog);	
					}
				
				}	

				} catch (\Exception $e){
												$logger->info('Error in Quantity in sku: '. $e->getMessage());
												
										}	
			}
		}		

    }

}
