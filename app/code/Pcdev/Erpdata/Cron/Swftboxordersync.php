<?php

namespace Pcdev\Erpdata\Cron;

class Swftboxordersync
{
    protected $_storeManager;
    protected $_resourceConnection;
	protected $_erpviewblock;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpViewBlock
    ) {
        $this->_storeManager = $storeManager;
        $this->_resourceConnection = $resourceConnection;
		$this->_erpviewblock = $erpViewBlock;
    }

    public function execute()
    {
		$connection = $this->_resourceConnection->getConnection();
		$shipmentTableName = $connection->getTableName('sales_shipment');
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/swftbox-shipment-'.date('Y-m-d').'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);

		$AuthTocken = $this->_erpviewblock->getAuthenticationTocken();
		if(isset($AuthTocken['access_token']) && $AuthTocken['access_token']){
			$access_token = $AuthTocken['access_token'];
		} else {
			$logger->info('Auth Tocken not found');
			return false;
		}		
		
		$findshipmentQuery = "SELECT `order_id`, `increment_id` FROM " . $shipmentTableName . " WHERE `swftbox_id` IS NULL ORDER BY `entity_id` DESC LIMIT 50";
		$findshipmentResults = $connection->fetchAll($findshipmentQuery);		
		if(count($findshipmentResults)>0){
			foreach($findshipmentResults as $findshipmentVal){
				
				$orderdata =[];		
				$shipment_increment_id = $findshipmentVal['increment_id']; // Shipment Increment Id
				$logger->info('Shipment Increment Id '.$shipment_increment_id);
				
				$SwftboxOrderRequestResponse =  $this->_erpviewblock->SwftboxOrderRequest($shipment_increment_id);
					
			}
		}		

    }

}
