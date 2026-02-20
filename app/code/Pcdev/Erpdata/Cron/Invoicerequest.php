<?php

namespace Pcdev\Erpdata\Cron;

class Invoicerequest
{
    protected $_storeManager;
    protected $_resourceConnection;
	protected $_erpviewblock;
	protected $_eventManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpViewBlock,
		\Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_resourceConnection = $resourceConnection;
		$this->_erpviewblock = $erpViewBlock;
		$this->_eventManager = $eventManager;
    }

    public function execute()
    {
		$connection = $this->_resourceConnection->getConnection();
		$salesOrderTableName = $connection->getTableName('sales_order');
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/invoice-request-'.date('Y-m-d').'.log');
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
		
		$orderInvoiceQuery = "SELECT `sales_order`.`entity_id`, `sales_order`.`state`, `sales_order`.`status`, `sales_order`.`increment_id`, `erp_invoices`.`invoiceid` FROM " . $salesOrderTableName . " LEFT JOIN `erp_invoices` ON `sales_order`.`increment_id`=`erp_invoices`.`increment_id` WHERE `sales_order`.`is_sync`='1' AND `sales_order`.`erpsalesid` IS NOT NULL AND (`sales_order`.`status`='pending' OR `sales_order`.`status` = 'processing') AND `erp_invoices`.`invoiceid` IS NULL ORDER BY `entity_id` DESC LIMIT 50";
		$orderInvoiceResults = $connection->fetchAll($orderInvoiceQuery);		
		if(count($orderInvoiceResults)>0){
			foreach($orderInvoiceResults as $orderInvoiceVal){ 
				
				$orderdata =[];		
				$increment_id = $orderInvoiceVal['increment_id'];
				$logger->info('Order No '.$increment_id);				
				$loadorder = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);				
				$orderId = $loadorder->getId();
				$lastInsertId = 0;
				
				$salesInvoicesArr = $this->_erpviewblock->SalesInvoices($access_token,$increment_id);
				
				if($salesInvoicesArr['salesinvoicesdata'] && isset($salesInvoicesArr['salesinvoicesdata'][0]->SalesResult) && $salesInvoicesArr['salesinvoicesdata'][0]->SalesResult == 'True'){
					
					$erpSalesId = $salesInvoicesArr['salesinvoicesdata'][0]->SalesId;
					$erp_invoice_id = $salesInvoicesArr['salesinvoicesdata'][0]->InvoiceId;
					$logger->info('ERP InvoiceId '.$erp_invoice_id);
					
					$erpInvoicesTableName = $connection->getTableName('erp_invoices');
					$invoicedateQuery = "SELECT `i_id`, `increment_id`, `invoiceid` FROM " . $erpInvoicesTableName . " WHERE `invoiceid`='".$erp_invoice_id."'";
					$invoicedateResults = $connection->fetchAll($invoicedateQuery);		
					if(count($invoicedateResults) == 0){
						$invoicedateInst = [];
						
						$erpid = '$id';
						$invoicedateInst['erp_id'] = $salesInvoicesArr['salesinvoicesdata'][0]->$erpid;
						$invoicedateInst['CustAccount'] = $salesInvoicesArr['salesinvoicesdata'][0]->CustAccount;
						$invoicedateInst['DataOrigin'] = $salesInvoicesArr['salesinvoicesdata'][0]->DataOrigin;
						$invoicedateInst['DeliveryAddress'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryAddress;
						$invoicedateInst['DeliveryCity'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryCity;
						$invoicedateInst['DeliveryCountryRegionId'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryCountryRegionId;
						$invoicedateInst['DeliveryDate'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryDate;
						$invoicedateInst['EComSalesId'] = $salesInvoicesArr['salesinvoicesdata'][0]->EComSalesId;
						$invoicedateInst['Email'] = $salesInvoicesArr['salesinvoicesdata'][0]->Email;
						$invoicedateInst['InclTax'] = $salesInvoicesArr['salesinvoicesdata'][0]->InclTax;
						$invoicedateInst['PaymTermId'] = $salesInvoicesArr['salesinvoicesdata'][0]->PaymTermId;
						$invoicedateInst['PhoneMobile'] = $salesInvoicesArr['salesinvoicesdata'][0]->PhoneMobile;
						$invoicedateInst['RequestedDate'] = $salesInvoicesArr['salesinvoicesdata'][0]->RequestedDate;
						$invoicedateInst['SalesId'] = $salesInvoicesArr['salesinvoicesdata'][0]->SalesId;
						$invoicedateInst['SalesRemarks'] = $salesInvoicesArr['salesinvoicesdata'][0]->SalesRemarks;
						$invoicedateInst['SalesResult'] = $salesInvoicesArr['salesinvoicesdata'][0]->SalesResult;
						$invoicedateInst['CompanyId'] = $salesInvoicesArr['salesinvoicesdata'][0]->CompanyId;
						$invoicedateInst['DlvModeId'] = $salesInvoicesArr['salesinvoicesdata'][0]->DlvModeId;
						$invoicedateInst['SubTotal'] = $salesInvoicesArr['salesinvoicesdata'][0]->SubTotal;
						$invoicedateInst['VATAmount'] = $salesInvoicesArr['salesinvoicesdata'][0]->VATAmount;
						$invoicedateInst['OrderTotal'] = $salesInvoicesArr['salesinvoicesdata'][0]->OrderTotal;
						$invoicedateInst['InvoiceId'] = $salesInvoicesArr['salesinvoicesdata'][0]->InvoiceId;
						$invoicedateInst['AWBNumber'] = $salesInvoicesArr['salesinvoicesdata'][0]->AWBNumber;
						$invoicedateInst['InvoiceDate'] = $salesInvoicesArr['salesinvoicesdata'][0]->InvoiceDate;
						$invoicedateInst['ChargeAmount'] = $salesInvoicesArr['salesinvoicesdata'][0]->ChargeAmount;
						$invoicedateInst['AmountInWords'] = $salesInvoicesArr['salesinvoicesdata'][0]->AmountInWords;
						$invoicedateInst['DeliveryName'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryName;
						$invoicedateInst['DeliveryTo'] = $salesInvoicesArr['salesinvoicesdata'][0]->DeliveryTo;
						$invoicedateInst['Currency'] = $salesInvoicesArr['salesinvoicesdata'][0]->Currency;
						$invoicedateInst['DueDate'] = $salesInvoicesArr['salesinvoicesdata'][0]->DueDate;
						$invoicedateInst['TaxRegNumber'] = $salesInvoicesArr['salesinvoicesdata'][0]->TaxRegNumber;
						$invoicedateInst['SalesInvoiceLineList'] = serialize($salesInvoicesArr['salesinvoicesdata'][0]->SalesInvoiceLineList);
						$invoicedateInst['SalesInvoiceChargeList'] = serialize($salesInvoicesArr['salesinvoicesdata'][0]->SalesInvoiceChargeList);
						
						$invSavesql = "INSERT INTO " . $erpInvoicesTableName . " (erp_id, custaccount, dataorigin, deliveryaddress, deliverycity, delcountregid, deliverydate, increment_id, email, incltax, paymtermid, phonemobile, requesteddate, erpsalesid, erpsalesremarks, erpsalesresult, companyid, dlvmodeid, subtotal, vatamount, ordertotal, invoiceid, awbnumber, invoicedate, chargeamount, amountinwords, deliveryname, deliveryto, currency, duedate, taxregnumber, salesinvoicelinelist, salesinvoicechargelist) Values ('".$invoicedateInst['erp_id']."','".$invoicedateInst['CustAccount']."','".$invoicedateInst['DataOrigin']."','".$invoicedateInst['DeliveryAddress']."','".$invoicedateInst['DeliveryCity']."','".$invoicedateInst['DeliveryCountryRegionId']."','".$invoicedateInst['DeliveryDate']."','".$invoicedateInst['EComSalesId']."','".$invoicedateInst['Email']."','".$invoicedateInst['InclTax']."','".$invoicedateInst['PaymTermId']."','".$invoicedateInst['PhoneMobile']."','".$invoicedateInst['RequestedDate']."','".$invoicedateInst['SalesId']."','".$invoicedateInst['SalesRemarks']."','".$invoicedateInst['SalesResult']."','".$invoicedateInst['CompanyId']."','".$invoicedateInst['DlvModeId']."','".$invoicedateInst['SubTotal']."','".$invoicedateInst['VATAmount']."','".$invoicedateInst['OrderTotal']."','".$invoicedateInst['InvoiceId']."','".$invoicedateInst['AWBNumber']."','".$invoicedateInst['InvoiceDate']."','".$invoicedateInst['ChargeAmount']."','".$invoicedateInst['AmountInWords']."','".$invoicedateInst['DeliveryName']."','".$invoicedateInst['DeliveryTo']."','".$invoicedateInst['Currency']."','".$invoicedateInst['DueDate']."','".$invoicedateInst['TaxRegNumber']."','".$invoicedateInst['SalesInvoiceLineList']."','".$invoicedateInst['SalesInvoiceChargeList']."')";
						$logger->info('Order ID '.$increment_id);
						$logger->info($invoicedateInst['SalesInvoiceLineList']);
						$connection->query($invSavesql);
						$lastInsertId = $connection->lastInsertId();
						// return $lastInsertId;
						
						/*Shipment Creation*/
						if(!empty($orderId)){
							$shipmentId = $this->_erpviewblock->ShipmentCreate($orderId);
							$logger->info('Order ID '.$increment_id.'-'.$orderId.' Shipment ID '.$shipmentId);
						}						
						/*Shipment Creation*/
						
						/*Email Send Event*/
						$this->_eventManager->dispatch('erpdata_erp_invoice_generate_email',
							[
								'order' => $loadorder,
								'invoice' => $salesInvoicesArr['salesinvoicesdata']
							]
						);
						/*Email Send Event*/
						
					}
					
				}
				
				if(isset($salesInvoicesArr['salesinvoicesdata']) && isset($salesInvoicesArr['salesinvoicesdata'][0]->SalesRemarks)){
					$erpSalesRemarksforlog = $salesInvoicesArr['salesinvoicesdata'][0]->SalesRemarks;
					$logger->info('ERP Remarks '.$erpSalesRemarksforlog);	
				}
					
			}
		}		

    }

}
