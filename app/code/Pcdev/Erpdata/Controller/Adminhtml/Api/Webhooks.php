<?php

namespace Pcdev\Erpdata\Controller\Adminhtml\Api;

class Webhooks extends \Magento\Backend\App\Action
{
	protected $_resultPageFactory;
	protected $_resultJsonFactory;
	protected $_swftboxhelperdata;	
	protected $_publicActions = ['webhooks'];
	
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Pcdev\Swftboxshipment\Helper\Data $swftboxHelperData
	)
	{
		$this->_resultPageFactory = $resultPageFactory;
		$this->_resultJsonFactory = $resultJsonFactory;
		$this->_swftboxhelperdata = $swftboxHelperData;
		parent::__construct($context);
	}

    public function execute()
    {		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/SwftBox_ShipmentStatus'.date("d-m-y").'.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);	
		$logger->info('Webwook Called');	

		try{		
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$shipmentCollection = $objectManager->create('Magento\Sales\Model\Order\Shipment');
			
			$params = $this->getRequest()->getParams();
			$logger->info($this->getRequest()->getParams());
			$order_reference = '';
			if(isset($params['order_reference'])){
				$order_reference = $params['order_reference'];
			}
			
			$swftboxId = '';
			if(isset($params['swftbox_order_id'])){
				$swftboxId = $params['swftbox_order_id'];
			}	

			$event_name = '';
			if(isset($params['event_name'])){
				$event_name = $params['event_name'];
			}			
			
			$logger->info("order_reference=".$order_reference);
			$logger->info("swftboxId=".$swftboxId);
			$logger->info("event_name=".$event_name);

			if($order_reference){
				$shipment_increment_id = $order_reference;
				$shipment = $shipmentCollection->loadByIncrementId($shipment_increment_id);
				$re_shipment_increment_id = $shipment->getIncrementId();

				if(isset($params['order_reference']) && !empty($params['order_reference']) && !empty($swftboxId) && ($re_shipment_increment_id == $order_reference)){			
					if(!empty($event_name)){				
						$shipment->setShipStatus($event_name);
						$shipment->save();
						$logger->info("Status Updated");
					}
				}
			}

		}catch(\Exception $e){
			$logger->info('Error = '.$e->getMessage());
		}
		
    }
	
}