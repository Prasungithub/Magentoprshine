<?php

namespace Pcdev\Erpdata\Block\Adminhtml;

class View extends \Magento\Backend\Block\Widget\Container
{
    protected $_typeFactory;
    protected $_productFactory;
	protected $_resourceConnection;
	protected $_orderRepository;
	protected $_orderConverter;
	protected $_transactionFactory;
	protected $_messageManager;
	protected $_shipmentSender;
	protected $_erphelperdata;
	protected $_orderFactory;
	protected $_shipment;
	protected $_swftboxhelperdata;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Catalog\Model\Product\TypeFactory $typeFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\App\ResourceConnection $resourceConnection,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
		\Magento\Framework\DB\TransactionFactory $transactionFactory,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
		\Pcdev\Erpdata\Helper\Data $erpHelperData,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Order\Shipment $Shipment,
		\Pcdev\Swftboxshipment\Helper\Data $swftboxHelperData,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_typeFactory = $typeFactory;
		$this->_resourceConnection = $resourceConnection;
		$this->_orderRepository = $orderRepository;
		$this->_orderConverter = $convertOrderFactory->create();
		$this->_transactionFactory = $transactionFactory;
		$this->_messageManager = $messageManager;
		$this->_shipmentSender = $shipmentSender;
		$this->_erphelperdata = $erpHelperData;
		$this->_orderFactory = $orderFactory;
		$this->_shipment = $Shipment;
		$this->_swftboxhelperdata = $swftboxHelperData;
        parent::__construct($context, $data);
    }
	
	public function getAuthenticationTocken()
	{
		$data = [];
		$data['status'] = 'fail';
		$AuthResponseJson = '';
		
		$grant_type = $this->_erphelperdata->getGrantType();
		$client_id = $this->_erphelperdata->getClientId();
		$client_asecret = $this->_erphelperdata->getClientSecret();
		$resource = $this->_erphelperdata->getResourceUrl();

		$params = 'grant_type='.$grant_type.'&client_id='.$client_id.'&client_asecret='.$client_asecret.'&resource='.$resource.'';
		$url = "https://login.testerver.com/pathoferp/oauth2/token/";		
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Content-Type: application/x-www-form-urlencoded'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$AuthResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$AuthResponseJson_decode = json_decode($AuthResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($AuthResponseJson_decode->access_token)){
				$data['status'] = 'success';
				$data['access_token'] = $AuthResponseJson_decode->access_token;							
			}
		}

		return $data;
	}	
	
	public function getProducts($ItemId,$access_token)
	{
		$data = [];
		$data['status'] = 'fail';
		$ItemResponseJson = '';		
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();		

		$params = array(
			"_accountList"=>[array(
				"CompanyId"=>$CompanyId,
				"ItemId"=>$ItemId,
				"DataOrigin"=>$DataOrigin
			)]
		);		
		$params = json_encode($params);		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$ItemResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$ItemResponseJson_decode = json_decode($ItemResponseJson);	
		
		// echo "<pre>";
		// print_r($ItemResponseJson_decode);
		// die('kill here');
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($ItemResponseJson_decode)){
				$data['status'] = 'success';
				$data['items'] = $ItemResponseJson_decode;							
			}
		}

		return $data;
	}

	public function getItemOnhand($ItemId,$access_token)
	{
		$data = [];
		$data['status'] = 'fail';
		$ItemOnHandResponseJson = '';

		$CompanyId = $this->_erphelperdata->getCompanyId();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();			

		$params = array(
			"_accountList"=>[array(
				"CompanyId"=>$CompanyId,
				"ItemId"=>$ItemId,
				"DataOrigin"=>$DataOrigin
			)]
		);		
		$params = json_encode($params);		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$ItemOnHandResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$ItemOnHandResponseJson_decode = json_decode($ItemOnHandResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($ItemOnHandResponseJson_decode)){
				$data['status'] = 'success';
				$data['stock'] = $ItemOnHandResponseJson_decode;							
			}
		}

		return $data;
	}
	
	public function saveItem($sku,$data)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');	
		
		$findSkuQuery = "SELECT `sku`, `id` FROM " . $erpTableName . " WHERE `sku`='".$sku."'";
		$findSkuResults = $connection->fetchAll($findSkuQuery);		
		if(count($findSkuResults)>0){
			$p_id = $findSkuResults[0]['id'];	
			$categories = serialize($data['categories']);			
			$packing = serialize($data['packing']);	
			$attributes = serialize($data['attributes']);
			$unit_convert = serialize($data['unit_convert']);
			$item_prices = serialize($data['item_prices']);
			
			$updateItemSql = "UPDATE " . $erpTableName . " SET `topcategory`='".$data['topcategory']."', `name`='".$data['name']."', `erp_pid`='".$data['erp_pid']."', `sku`='".$data['sku']."', `price`='".$data['price']."', `tax_percent`='".$data['tax_percent']."', `quantity`='".$data['quantity']."', `quantity_unit`='".$data['quantity_unit']."', `uom`='".$data['uom']."', `categories`='".$categories."', `attributes`='".$attributes."', `packing`='".$packing."', `unit_convert`='".$unit_convert."', `item_prices`='".$item_prices."', `ispresent`='1' WHERE `id` = '".$p_id."'";
			$connection->query($updateItemSql);
			return $p_id;
		} else {
			$categories = serialize($data['categories']);			
			$packing = serialize($data['packing']);	
			$attributes = serialize($data['attributes']);
			$unit_convert = serialize($data['unit_convert']);
			$item_prices = serialize($data['item_prices']);
				
			$Savesql = "INSERT INTO " . $erpTableName . " (topcategory, name, erp_pid, sku, price, tax_percent, quantity, quantity_unit, uom, categories, attributes, packing, unit_convert, item_prices, ispresent) Values ('".$data['topcategory']."','".$data['name']."','".$data['erp_pid']."','".$data['sku']."','".$data['price']."','".$data['tax_percent']."','".$data['quantity']."','".$data['quantity_unit']."','".$data['uom']."','".$categories."','".$attributes."','".$packing."','".$unit_convert."','".$item_prices."','1')";
			$connection->query($Savesql);
			$lastInsertId = $connection->lastInsertId();
			return $lastInsertId;
		}

	}
	
	public function getAllItemsSku()
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');
		$allItemsQuery = "SELECT `id`, `sku` FROM " . $erpTableName . " WHERE 1=1";		
		$allItemsResults = $connection->fetchAll($allItemsQuery);
		return $allItemsResults;		
	}
	
	public function getLimitItemsSku($limit_start,$fatchrows)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');
		$allItemsQuery = "SELECT `id`, `sku` FROM " . $erpTableName . " LIMIT " . $limit_start . ", " . $fatchrows;
		$allItemsResults = $connection->fetchAll($allItemsQuery);
		return $allItemsResults;		
	}	
	
	public function updateStockOnHand($sku,$data)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');	
		$result = '';	
		if($sku){			
			$updateItemSql = "UPDATE " . $erpTableName . " SET `quantity`='".$data['quantity']."', `quantity_unit`='".$data['quantity_unit']."' WHERE `sku` = '".$sku."'";
			$result = $connection->query($updateItemSql);
			return $result;
		}
		
		return $result;
	}	

	public function getUnitConvert($access_token)
	{
		$data = [];
		$data['status'] = 'fail';
		$UnitConvertResponseJson = '';
		$ItemId = 'SharedUnit';	
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();		

		$params = array(
			"_accountList"=>[array(
				"CompanyId"=>$CompanyId,
				"ItemId"=>$ItemId,
				"DataOrigin"=>$DataOrigin
			)]
		);		
		$params = json_encode($params);		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$UnitConvertResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$UnitConvertResponseJson_decode = json_decode($UnitConvertResponseJson);

		// echo "<pre>";
		// print_r($UnitConvertResponseJson_decode);
		// die('kill here');
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($UnitConvertResponseJson_decode)){
				$data['status'] = 'success';
				$data['convertdata'] = $UnitConvertResponseJson_decode;							
			}
		}

		return $data;
	}	
	
	public function updateUnitConvert($sku,$data)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');	
		$result = '';	
		$unit_convert = serialize($data);
		if($sku){			
			$updateItemSql = "UPDATE " . $erpTableName . " SET `unit_convert`='".$unit_convert."' WHERE `sku` = '".$sku."'";
			$result = $connection->query($updateItemSql);
			return $result;
		}
		
		return $result;
	}

	public function getItemPrice($access_token)
	{
		$data = [];
		$data['status'] = 'fail';
		$ItemPriceResponseJson = '';

		$CompanyId = $this->_erphelperdata->getCompanyId();
		$CustAccount = $this->_erphelperdata->getCustAccount();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();	

		$params = array(
			"_accountList"=>[array(
				"CompanyId"=>$CompanyId,
				"AccountNum"=>$CustAccount,
				"DataOrigin"=>$DataOrigin
			)]
		);		
		$params = json_encode($params);		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$ItemPriceResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$ItemPriceResponseJson_decode = json_decode($ItemPriceResponseJson);

		// echo "<pre>";
		// print_r($ItemPriceResponseJson_decode);
		// die('kill here');
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($ItemPriceResponseJson_decode)){
				$data['status'] = 'success';
				$data['itemprice'] = $ItemPriceResponseJson_decode;							
			}
		}

		return $data;
	}

	public function updateitemPrice($sku,$data)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');	
		$result = '';	
		$item_prices = serialize($data);
		if($sku){			
			$updateItemSql = "UPDATE " . $erpTableName . " SET `item_prices`='".$item_prices."' WHERE `sku` = '".$sku."'";
			$result = $connection->query($updateItemSql);
			return $result;
		}
		
		return $result;
	}
	
	public function getAllRecordswithLimit($start,$rowno)
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');
		$allItemsQuery = "SELECT `id`, `topcategory`, `name`, `erp_pid`, `sku`, `price`, `tax_percent`, `quantity`, `quantity_unit`, `uom`, `categories`, `attributes`, `packing`, `unit_convert`, `item_prices`, `ispresent` FROM " . $erpTableName . " LIMIT " . $start . ", " . $rowno;
		// $allItemsQuery = "SELECT `id`, `topcategory`, `name`, `erp_pid`, `sku`, `price`, `tax_percent`, `quantity`, `quantity_unit`, `uom`, `categories`, `attributes`, `packing`, `unit_convert`, `item_prices`, `ispresent` FROM " . $erpTableName;
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/inventorySQL-'.date('Y-m-d').'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info("SQL: ".$allItemsQuery);
		$allItemsResults = $connection->fetchAll($allItemsQuery);
		return $allItemsResults;		
	}
	
	public function SalesOrderSync($access_token,$orderdata)
	{
		$data = [];
		$data['status'] = 'fail';
		$SalesOrderSyncResponseJson = '';
		$CompanyId = $orderdata['CompanyId'];
		$EComSalesId = $orderdata['EComSalesId'];
		$CustAccount = $orderdata['CustAccount'];
		$DeliveryName = $orderdata['DeliveryName'];
		$DeliveryDate = $orderdata['DeliveryDate'];
		$DeliveryRemarks = $orderdata['DeliveryRemarks'];
		$RequestedDate = $orderdata['RequestedDate'];
		$PaymTermId = $orderdata['PaymTermId'];
		$InclTax = $orderdata['InclTax'];
		$DeliveryAddress = $orderdata['DeliveryAddress'];
		$DeliveryCountryRegionId = $orderdata['DeliveryCountryRegionId'];
		$DeliveryCity = $orderdata['DeliveryCity'];
		$DeliveryState = $orderdata['DeliveryState'];
		$Email = $orderdata['Email'];
		$PhoneMobile = $orderdata['PhoneMobile'];
		$DataOrigin = $orderdata['DataOrigin'];
		$SubTotal = $orderdata['SubTotal'];
		$VATAmount = $orderdata['VATAmount'];
		$OrderTotal = $orderdata['OrderTotal'];
		$SlefPickup = $orderdata['SlefPickup'];		
		$SubTotal = $orderdata['SubTotal'];
		$VATAmount = $orderdata['VATAmount'];
		
		$SalesChargesList = $orderdata['SalesChargesList'];
		$SalesLineListArr = $orderdata['SalesLineList'];

		$params = array(
			"_salesTableDC"=>array(
				'CustAccount'=>$CustAccount,
				'DataOrigin'=>$DataOrigin,
				'DeliveryName'=>$DeliveryName,
				'DeliveryAddress'=>$DeliveryAddress,
				'DeliveryCity'=>$DeliveryCity,
				'DeliveryState'=>$DeliveryState,
				'DeliveryCountryRegionId'=>$DeliveryCountryRegionId,
				'DeliveryDate'=>$DeliveryDate,
				'DeliveryRemarks'=>$DeliveryRemarks,
				'EComSalesId'=>$EComSalesId,
				'Email'=>$Email,
				'InclTax'=>$InclTax,
				'PaymTermId'=>$PaymTermId,
				'PhoneMobile'=>$PhoneMobile,
				'RequestedDate'=>$RequestedDate,
				'CompanyId'=>$CompanyId,
				'SlefPickup'=>'no',
				'SubTotal'=>$SubTotal,
				'VATAmount'=>$VATAmount,
				'OrderTotal'=>$OrderTotal,
				"SalesChargesList"=>$SalesChargesList,
				"SalesLineList"=>$SalesLineListArr
			)
		);	
	
		$params = json_encode($params);
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order-sync-SENT-'.date('Y-m-d').'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info('URL:- '.$url);
		$logger->info('PARAMS:- '.$params);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SalesOrderSyncResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SalesOrderSyncResponseJson_decode = json_decode($SalesOrderSyncResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SalesOrderSyncResponseJson_decode)){
				$data['status'] = 'success';
				$data['salesorderdata'] = $SalesOrderSyncResponseJson_decode;							
			}
		}

		return $data;
	}
	
	public function SalesOrderStatus($access_token,$orderincrementid)
	{
		$data = [];
		$data['status'] = 'fail';
		$SalesOrderStatusResponseJson = '';
		
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();			

		$params = array(
			"_accountTypeList"=>[array(
					"CompanyId"=>$CompanyId,
					"EComSalesId"=>$orderincrementid,
					"DataOrigin"=>$DataOrigin
			)]
		);	
	
		$params = json_encode($params);
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SalesOrderStatusResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SalesOrderStatusResponseJson_decode = json_decode($SalesOrderStatusResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SalesOrderStatusResponseJson_decode)){
				$data['status'] = 'success';
				$data['salesorderstatusdata'] = $SalesOrderStatusResponseJson_decode;							
			}
		}

		return $data;
	}

	public function SalesInvoices($access_token,$orderincrementid)
	{
		$data = [];
		$data['status'] = 'fail';
		$SalesInvoicesResponseJson = '';
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();		
		$params = array(
			"_accountList"=>[array(
					"CompanyId"=>$CompanyId,
					"EComSalesId"=>$orderincrementid,
					"DataOrigin"=>$DataOrigin
			)]
		);	
	
		$params = json_encode($params);
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SalesInvoicesResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SalesInvoicesResponseJson_decode = json_decode($SalesInvoicesResponseJson);
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/GetSalesInvoice-API-DATA-'.date('Y-m-d').'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info('get Data From API');
		$logger->info($SalesInvoicesResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SalesInvoicesResponseJson_decode)){
				$data['status'] = 'success';
				$data['salesinvoicesdata'] = $SalesInvoicesResponseJson_decode;							
			}
		}

		return $data;
	}	
	
	public function PaymentJournal($access_token,$increment_id)
	{
		$data = [];
		$data['status'] = 'fail';
		$PaymentJournalResponseJson = '';
		$CompanyId = $this->_erphelperdata->getCompanyId();
		$CustAccount = $this->_erphelperdata->getCustAccount();
		$DataOrigin = $this->_erphelperdata->getDataOrigin();			
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$loadorder = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
		
		$orderId = $loadorder->getId();		
		
		$paymentData = $loadorder->getPayment();
		$paymentMethod = $paymentData->getMethod();
		$methodInst = $paymentData->getMethodInstance();
		$paymentMethodTitle = $methodInst->getTitle();

		$PaymentCode = '';		
		if($paymentMethod == 'ccavenue'){
			$PaymentCode = 'Card';			
		} elseif($paymentMethod == 'cashondelivery'){
			$PaymentCode = 'Cash';
		}
		
		$SalesReference = $loadorder->getIncrementId();
		$EComJournalId = $SalesReference.''.$paymentData->getId();
		// $Description = $paymentData->getAdditionalInformation("instructions");
		$Description = $paymentMethodTitle;
		$order_created = strtotime($loadorder->getCreatedAt());		
		$TransDate = date('Y-m-d',$order_created);
		$Description_JournalLine = $paymentMethodTitle;
		$CurrencyCode = $loadorder->getOrderCurrencyCode();
		$PaymentAmount = $paymentData->getAmountOrdered();
		if($PaymentAmount > 0){
			$PaymentAmount = '+'.$PaymentAmount;
		}
		
		$JournalLineList = [
				"TransDate"=>$TransDate,
                "CustAccount"=>$CustAccount,
                "Description"=>$Description_JournalLine,
                "CurrencyCode"=>$CurrencyCode,
                "PaymentAmount"=>$PaymentAmount,
                "SalesReference"=>$SalesReference,
                "PaymentCode"=>$PaymentCode
		];	

		$params = array(
			"_journalTableDC"=>array(
					"CompanyId"=>$CompanyId,
					"EComJournalId"=>$EComJournalId,
					"Description"=>$Description,
					"DataOrigin"=>$DataOrigin,
					"JournalLineList"=>[$JournalLineList]
			)
		);		
	
		$params = json_encode($params);
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: Bearer '.$access_token.'',
			  'Content-Type: text/plain'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$PaymentJournalResponseJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$PaymentJournalResponseJson_decode = json_decode($PaymentJournalResponseJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($PaymentJournalResponseJson_decode)){
				$data['status'] = 'success';
				$data['paymentjournaldata'] = $PaymentJournalResponseJson_decode;							
			}
		}

		return $data;
	}
	
	public function ShipmentCreate($orderid)
	{
		$result = 0;
		try {
			$order = $this->_orderRepository->get($orderid);
			
			if ($order->getId()) {			
				// Check if order can be shipped or has already shipped
				if (!$order->canShip()) {
					throw new \Magento\Framework\Exception\LocalizedException(
									__('You can\'t create an shipment.')
								);
				}

			

				// Initialize the order shipment object
				$shipment = $this->_orderConverter->toShipment($order);
				foreach ($order->getAllItems() AS $orderItem) {
					// Check if order item has qty to ship or is order is virtual
					if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
						continue;
					}
					
					$qtyShipped = $orderItem->getQtyToShip();
					
					// Create shipment item with qty
					$shipmentItem = $this->_orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
					// Add shipment item to shipment
					$shipment->addItem($shipmentItem);
				}
			
				$shipment->register();
				$shipment->getOrder()->setIsInProcess(true);
			
				try {
					$transaction = $this->_transactionFactory->create()->addObject($shipment)
																	->addObject($shipment->getOrder())
																	->save();
					 $shipmentId = $shipment->getIncrementId();
				} catch (\Exception $e) {
					$this->_messageManager->addError(__('We can\'t generate shipment.'));
				}

				if($shipment) {
					try {
						$this->_shipmentSender->send($shipment);
					} catch (\Exception $e) {
						$this->_messageManager->addError(__('We can\'t send the shipment right now.'));
					}
				}
			
				return $shipmentId;
			}			
			
		} catch (\Exception $e) {
			$this->_messageManager->addError($e->getMessage());
		}		
		
		return $result;
	}

	public function SwftboxOrderRequest($increment_id)
	{
		$result = 0;
		$SwftboxOrderRequestJson = [];
		$swftboxOrderData = [];
		
		/*$shipment = $this->_shipment->create()->loadByIncrementId($increment_id);
		echo $orderId = $shipment->getOrderId();*/

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$shipmentCollection = $objectManager->create('Magento\Sales\Model\Order\Shipment'); 
		$shipment = $shipmentCollection->loadByIncrementId($increment_id);
		$orderId = $shipment->getOrderId();
		$shipment_increment_id = $shipment->getIncrementId();		
		$order = $this->_orderFactory->create()->load($orderId);
		
		//$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		//$loadorder = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
		//$orderId = $loadorder->getId();
		
		$connection = $this->_resourceConnection->getConnection();
		$deliverydateTableName = $connection->getTableName('amasty_deliverydate_deliverydate_order');
		
		$orderId = $order->getId();
		$pickup_location_id	= 10040;
		$tracking_number = $order->getIncrementId();
		$delivery_profile_type = ''; // optional, one of “ondemand” | “sameday” | “nextday” | “customer”
		$delivery_profile_id = '';
		$brand_name = ''; // Bayara optional
		$date = ''; // sets the expected date for delivery in the format "YYYY-MM-DD"
		$pickup_time = ''; // optional, sets the expected time for a pickup in the format HHMM
		$drop_time = ''; // optional, sets the expected time of day for a drop in the format HHMM.
		$cash_collection = 0; // set to 0 or skipped, the order is considered prepaid.
		
		$customerName = $order->getCustomerFirstname() .' '.$order->getCustomerLastname();
		
		$shippingAddress = $order->getShippingAddress();		
		$telephone = $shippingAddress->getTelephone();
		$country_id =  $shippingAddress->getCountryId();
		$city = $shippingAddress->getCity();
		$DeliveryAddressArr = $shippingAddress->getStreet();
		$DeliveryAddress = implode(', ',$DeliveryAddressArr);
		$latitude = $shippingAddress->getLatitude(); 
		if(empty($latitude)){
			$latitude = 25.276987;
		}
		$longitude = $shippingAddress->getLongitude(); 
		if(empty($longitude)){
			$longitude = 55.296249;
		}		
		$customer_comment = '';	
		
		$customer_id = $order->getCustomerId();
		if(empty($telephone) && !empty($customer_id)){
			$phoneMobileQuery = "SELECT `customer_entity_varchar`.`value` FROM `eav_attribute` LEFT JOIN `customer_entity_varchar` ON `eav_attribute`.`attribute_id`= `customer_entity_varchar`.`attribute_id` WHERE `customer_entity_varchar`.`entity_id` = '".$customer_id."' AND `eav_attribute`.`attribute_code` = 'mobile'";
			
			$phoneMobileResults = $connection->fetchAll($phoneMobileQuery);		
			if(count($phoneMobileResults)>0){
				$telephone = $phoneMobileResults[0]['value'];					
			}
		}
		
		$quote_id = $order->getQuoteId();
		if(empty($telephone) && !empty($quote_id)){
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
					$telephone = $PhoneMobileShip;
				}
				
				if(empty($telephone) && !empty($PhoneMobileBill) ){
					$telephone = $PhoneMobileBill;
				}						
			} 
		}

		if(empty($telephone) && !empty($quote_id)){
			$phoneMobileQuoteTablQuery = "SELECT `mobileno` FROM `quote` WHERE `entity_id`='".$quote_id."'";
			$phoneMobileQuoteTablResults = $connection->fetchAll($phoneMobileQuoteTablQuery);		
			if(count($phoneMobileQuoteTablResults)>0){
				$telephone = $phoneMobileQuoteTablResults[0]['mobileno'];	
			}
		}		

		$deliverydateQuery = "SELECT `deliverydate_id`, `date`, `time_from`, `time_to`, `comment`, `time_interval_id` FROM " . $deliverydateTableName . " WHERE `order_id`='".$orderId."'";
		$deliverydateResults = $connection->fetchAll($deliverydateQuery);
		$delivery_time = '';
		$time_from = '';	
		$time_to = '';		
		if(count($deliverydateResults)>0){
			$date = $deliverydateResults[0]['date'];
		
			$date_strtotime = strtotime($date." 23:59:59");
			$current_date_time = date("Y-m-d h:i:sa");
			$current_date_time_strtotime = strtotime($current_date_time);
			if($current_date_time_strtotime > $date_strtotime){
				$date = '';
			}
			
			$delivery_comment = $deliverydateResults[0]['comment'];
			if(!empty($delivery_comment)){
				$customer_comment = $delivery_comment;	
			}
			
			$delivery_time = $deliverydateResults[0]['time_from'];
			if(!empty($delivery_time) && $delivery_time >= 480 && $delivery_time < 720){
				$drop_time = '0901';
			} elseif(!empty($delivery_time) && $delivery_time >= 720 && $delivery_time < 1200){
				$drop_time = 1301;
			}
			$time_from = $deliverydateResults[0]['time_from'];
			$time_to = $deliverydateResults[0]['time_to'];
		}
		if($time_from != null || $time_to != null){
			$time_from = mktime(0, $time_from);
			$time_to = mktime(0, $time_to);
			$time_from = date('H:i',$time_from);
			$time_to = date('H:i',$time_to);
			
		}			
		
		
		$paymentData = $order->getPayment();
		$paymentMethod = $paymentData->getMethod();
		
		if($paymentMethod == 'cashondelivery'){
			$cash_collection = $order->getTotalDue() * 100;
		}

		$swftboxOrderData = array(
						"pickup_location_id"=>$pickup_location_id,
						"pickup_location"=>array(
								"name"=>"Bayara",
								"phone"=>"971547939001",
								"address"=>"Dubai Investment Park 2",
								"latitude"=>24.9745,
								"longitude"=>55.1983,
								"city_name"=>"Dubai",
								"country_code"=>"AE",
								),
						"order_reference"=>$shipment_increment_id,
						"tracking_number"=>$tracking_number,
						"brand_id"=>'',
						"brand_name"=>$brand_name,
						"delivery_profile_type"=>$delivery_profile_type,
						"delivery_profile_id"=>$delivery_profile_id,
						"date"=>$date,
						"pickup_time"=>$pickup_time,
						"drop_time"=>$drop_time,
						"cash_collection"=>$cash_collection,
						"destination_location_id"=>'',
						"destination"=>array(
										"name"=>$customerName,
										"phone"=>$telephone, 
										"address"=>$DeliveryAddress,
										"latitude"=>$latitude,
										"longitude"=>$longitude,
										"city_name"=>$city,
										"country_code"=>$country_id,
									),
				"package_details"=>array(
									"height"=>'', 
									"width"=>'',
									"length"=>'',
									"weight"=>'',
									"weight_unit"=>"g",
									"length_unit"=>"cm",
					),
				"special_request"=>$customer_comment,
				"pre_schedule_drop_slots"	=>	array("from"=>$time_from,"to"=>$time_to)
		);	
		
		$params = array(		
			"data"=>$swftboxOrderData
		);
	
		$params = json_encode($params);	
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: c6b8e18d00d4c845b2bfdd22bbd64b6f2',
			  'auth-token: af04910b1c1f5984ea8f5e31f2c05095',
			  'namespace: CAR',
			  'Content-Type: application/json'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SwftboxOrderRequestJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SwftboxOrderRequestJson_decode = json_decode($SwftboxOrderRequestJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SwftboxOrderRequestJson_decode)){				
				if(isset($SwftboxOrderRequestJson_decode->meta)){					
					if(isset($SwftboxOrderRequestJson_decode->meta->ok) && $SwftboxOrderRequestJson_decode->meta->ok == '1' && isset($SwftboxOrderRequestJson_decode->data)){
						$swftboxId = $SwftboxOrderRequestJson_decode->data;
						$shipment->setSwftboxId($swftboxId);
						$shipment->save();
					}

					if(isset($SwftboxOrderRequestJson_decode->meta->message) && $SwftboxOrderRequestJson_decode->meta->message){
						$comment = $SwftboxOrderRequestJson_decode->meta->message;
						$shipment->addComment($comment);
						$shipment->save();
					}
					
					if(isset($SwftboxOrderRequestJson_decode->meta->ok) && $SwftboxOrderRequestJson_decode->meta->ok == '0' && isset($SwftboxOrderRequestJson_decode->meta->error) && !empty($SwftboxOrderRequestJson_decode->meta->error)){
						$error_comment = $SwftboxOrderRequestJson_decode->meta->error;
						$shipment->addComment($error_comment);
						$shipment->save();
					}					

				}
	
			}
		}
		
		echo '<pre>';
		print_r($SwftboxOrderRequestJson_decode);
		die();

		return $data;
	}
	
	public function SwftboxUpdateOrderRequest($increment_id)
	{
		$result = 0;
		$SwftboxUpdateOrderRequestJson = [];
		
		$order = $this->_orderFactory->create()->loadByIncrementId($increment_id);		
		$orderId = $order->getId();

		$swftbox_id = '';
		$customerName = $order->getCustomerFirstname() .' '.$order->getCustomerLastname();
		
		$shippingAddress = $order->getShippingAddress();		
		$telephone = $shippingAddress->getTelephone();		
		
		$country_id =  $shippingAddress->getCountryId();
		$city = $shippingAddress->getCity();
		$DeliveryAddressArr = $shippingAddress->getStreet();
		$DeliveryAddress = implode(', ',$DeliveryAddressArr);
		$fullAddress = $DeliveryAddress.', '.$city;
		$latitude = $shippingAddress->getLatitude(); 
		$longitude = $shippingAddress->getLongitude();
		
		$params = array(		
			"data"=>array(
					"id"=>$swftbox_id,
					"order_reference"=>$orderId,
					"destination"=>array(
									"name"=>$customerName,
									"phone"=>$telephone, 
									"address"=>$fullAddress,
									"latitude"=>$latitude,
									"longitude"=>$longitude,
							),
			)
		);	
	
		$params = json_encode($params);	
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: c6b8e18d00d4c845b2bfdd22bbd64b6f2',
			  'auth-token: af04910b1c1f5984ea8f5e31f2c05095',
			  'namespace: CAR',
			  'Content-Type: application/json'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SwftboxUpdateOrderRequestJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SwftboxUpdateOrderRequestJson_decode = json_decode($SwftboxUpdateOrderRequestJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SwftboxUpdateOrderRequestJson_decode)){
				$data['status'] = 'success';
				$data['swftboxupdateorderdata'] = $SwftboxUpdateOrderRequestJson_decode;							
			}
		}

		return $data;
	}

	public function SwftboxCancelOrder($increment_id)
	{
		$result = 0;
		$SwftboxCancelOrderJson = [];	

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$shipmentCollection = $objectManager->create('Magento\Sales\Model\Order\Shipment'); 
		$shipment = $shipmentCollection->loadByIncrementId($increment_id);
		$orderId = $shipment->getOrderId();
		$shipment_increment_id = $shipment->getIncrementId();
		
		$order = $this->_orderFactory->create()->load($orderId);

		$swftbox_id = $shipment->getSwftboxId();		
		
		$params = array(		
			"data"=>array(
					"id"=>$swftbox_id,
					"order_reference"=>$shipment_increment_id,
			)
		);	
	
		$params = json_encode($params);	
		
		$url = "https://testcode.api.testsandbox.com/serverpath/apiendpoint";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'Authorization: c6b8e18d00d4c845b2bfdd22bbd64b6f2',
			  'auth-token: af04910b1c1f5984ea8f5e31f2c05095',
			  'namespace: CAR',
			  'Content-Type: application/json'
		   ));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);    //Tell cURL that it should only spend 10 seconds
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$SwftboxCancelOrderJson = curl_exec($curl);
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
		}
		curl_close($curl);
		
		$SwftboxCancelOrderJson_decode = json_decode($SwftboxCancelOrderJson);
		
		if(isset($error_msg)){	
			$data['status'] = 'fail';
		} else {
			if(isset($SwftboxCancelOrderJson_decode)){
				
				if(isset($SwftboxCancelOrderJson_decode->meta)){

					$connection = $this->_resourceConnection->getConnection();
					$salesShipmentTableName = $connection->getTableName('sales_shipment');
		
					if(isset($SwftboxCancelOrderJson_decode->meta->ok) && $SwftboxCancelOrderJson_decode->meta->ok == '1'){
						$status_value =  $this->_swftboxhelperdata->getSwftboxStatus('ORDER_CANCELLED');
						$updateShipStatusSql = "UPDATE " . $salesShipmentTableName . " SET `ship_status` = '".$status_value."' WHERE `increment_id` = '".$shipment_increment_id."' AND `swftbox_id` = '".$swftbox_id."'";
						$connection->query($updateShipStatusSql);					
					}
				}
				
				$data['status'] = 'success';
				$data['swftboxcancelorderdata'] = $SwftboxCancelOrderJson_decode;							
			}
		}

		return $data;
	}

	public function getInvoiceId($increment_id,$erpsalesid)
	{
		$InvoiceId = '';
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_invoices');
		
		$allItemsQuery = "SELECT `i_id`, `invoiceid` FROM " . $erpTableName . " WHERE `increment_id`='".$increment_id."' AND `erpsalesid` = '".$erpsalesid."'";
		$invoiceResults = $connection->fetchAll($allItemsQuery);
		if(count($invoiceResults)>0){
			$InvoiceId = $invoiceResults[0]['invoiceid'];
		}
		return $InvoiceId;	
	}

	public function SpecialCharRemove($str)
	{
		$str = str_replace(array( "'", '\'', '"', ',' , ';', '<', '>' ), '', $str);
		return $str;
	}

	public function updateItemsIsNotPresent()
	{
		$connection = $this->_resourceConnection->getConnection();
		$erpTableName = $connection->getTableName('erp_product_data');			
		$updateItemSql = "UPDATE " . $erpTableName . " SET `ispresent`='0' ";
		$result = $connection->query($updateItemSql);
		return $result;
	}	
	
}
