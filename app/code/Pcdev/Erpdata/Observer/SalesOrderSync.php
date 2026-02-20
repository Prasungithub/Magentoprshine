<?php

namespace Pcdev\Erpdata\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderSync implements ObserverInterface
{	
    protected $_orderFactory;
	protected $_productloader;
	protected $_resourceConnection;
	
	public function __construct(
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Model\ProductFactory $_productloader,
		\Magento\Framework\App\ResourceConnection $resourceConnection
	) {
		$this->_orderFactory  = $orderFactory;
		$this->_productloader = $_productloader;
		$this->_resourceConnection = $resourceConnection;
	}
	
    public function execute(Observer $observer)
    {
		$order = $observer->getEvent()->getOrder();
		
		$orderId = $order->getId();
		$loadorder = $this->_orderFactory->create()->load($orderId);
		
		$connection = $this->_resourceConnection->getConnection();
		$deliverydateTableName = $connection->getTableName('amasty_deliverydate_deliverydate_order');	
		
		$deliverydate = '';		
		$finddeliverydateQuery = "SELECT `date` FROM " . $deliverydateTableName . " WHERE `order_id`='".$orderId."'";
		$finddeliverydateResults = $connection->fetchAll($finddeliverydateQuery);		
		if(count($finddeliverydateResults)>0){
			$deliverydate = $finddeliverydateResults[0]['date'];
		}		
		
		$is_sync = $loadorder->getIsSync();
		$paymentData = $loadorder->getPayment();
		$paymentMethod = $paymentData->getMethod();	
		$PaymTermId = '';		
		if($paymentMethod == 'ccavanue'){
			$PaymTermId = 'Card';			
		} elseif($paymentMethod == 'cashondelivery'){
			$PaymTermId = 'Cash';
		}		
		$order_status = $loadorder->getStatus();  //pending, processing
		$DeliveryName = $loadorder->getCustomerFirstname().' '.$loadorder->getCustomerLastname();

		$ShippingMethod = $loadorder->getShippingMethod();	
		$ChargeCode = '';		

		if($is_sync == '0' && ($order_status == 'pending' || $order_status == 'processing')){
			$SalesChargesList = [];
			if($loadorder->getShippingDescription()){
				$ShippingDescriptionArr = explode(' - ',$loadorder->getShippingDescription());
				$ShippingDescription = $ShippingDescriptionArr[1];
				if($ShippingMethod){
					$ChargeCode = 'Transport';
				}
				$SalesChargesList[] = ['ChargeCode'=>$ChargeCode,'ChargeAmount'=>$loadorder->getShippingAmount(),'ChargeDescription'=>$ShippingDescription];
			}
			
			$SalesLineList = [];
			foreach($loadorder->getAllItems() as $orderitem){
					
				$configurableSku = '';
				$SalesUnitId = '';
				$qty_ordered = 0;
				$SalesPrice = 0;
				$DiscAmountPerQty = 0;
				$FOCReason = '';
				
				if($orderitem->getProductType() == 'configurable'){
					$p_id = $orderitem->getProductId();
					$loadproduct = $this->_productloader->create()->load($p_id);
					$configurableSku = $loadproduct->getSku();
					
					if($orderitem->getProductOptions()){
						$ProductOptions = $orderitem->getProductOptions();
						
						if($ProductOptions['attributes_info'][0]['label'] == 'Size'){
							$SalesUnitId = $ProductOptions['attributes_info'][0]['value'];
						}			
					}
					
					$qty_ordered = $orderitem->getQtyOrdered();
					$SalesPrice = $orderitem->getPrice();
					$DiscAmountPerQty = $orderitem->getDiscountAmount();
					
					$SalesLineList[] = ['EComInventTransId'=>$orderitem->getItemId(), 'ItemId'=>$configurableSku,'SalesUnitId'=>$SalesUnitId, 'SalesQty'=>$qty_ordered, 'SalesPrice'=>$SalesPrice, 'DiscAmountPerQty'=>$DiscAmountPerQty, 'FOCReason'=>$FOCReason];			
					
				}
				
			}
			
			$ShippingAddress = $loadorder->getShippingAddress();			
			$DeliveryCountryRegionId = $ShippingAddress->getCountryId();
			if($DeliveryCountryRegionId == 'AE'){
				$DeliveryCountryRegionId = 'UAE';
			}
			$DeliveryAddressArr = $ShippingAddress->getStreet();
			$DeliveryAddress = implode(', ',$DeliveryAddressArr);
			$PhoneMobile = $ShippingAddress->getTelephone();	

			$orderdata = [
				'CompanyId'=>'AE01',
				'EComSalesId'=>$loadorder->getIncrementId(),
				'CustAccount'=>'C20000832',
				'DeliveryName'=>$DeliveryName,
				'DeliveryDate'=>$deliverydate,
				'RequestedDate'=>$deliverydate,
				'PaymTermId'=>$PaymTermId,
				'InclTax'=>'Yes',
				'DeliveryAddress'=>$DeliveryAddress,
				'DeliveryCountryRegionId'=>$DeliveryCountryRegionId,
				'DeliveryCity'=>$ShippingAddress->getCity(),
				'Email'=>$loadorder->getCustomerEmail(),
				'PhoneMobile'=>$PhoneMobile,
				'DataOrigin'=>'ECom',
				'SlefPickup'=>'no',
				'SubTotal'=>$loadorder->getSubtotal(),
				'VATAmount'=>$loadorder->getTaxAmount(),
				'OrderAmount'=>$loadorder->getGrandTotal(),
				'SalesChargesList'=>$SalesChargesList,
				'SalesLineList'=>$SalesLineList
			];
			
			$OrderSyncArr = $block->SalesOrderSync($access_token,$orderdata);		
			
			if($OrderSyncArr['salesorderdata'] && isset($OrderSyncArr['salesorderdata']->SalesResult) && $OrderSyncArr['salesorderdata']->SalesResult == 'True'){
				$is_sync = 1;
				$erpSalesId = $OrderSyncArr['salesorderdata']->SalesId;
				$erpsalesremarks = $OrderSyncArr['salesorderdata']->SalesRemarks;
				$loadorder->setIsSync($is_sync);
				$loadorder->setErpsalesid($erpSalesId);
				$loadorder->setErpsalesremarks($erpsalesremarks);
				$loadorder->save();
			}
		}		
    }

}
