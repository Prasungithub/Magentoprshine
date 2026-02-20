<?php

namespace Pcdev\Erpdata\Controller\Adminhtml\Api;

class Disableproductspost extends \Magento\Backend\App\Action
{
	protected $_resultPageFactory;
	protected $_resultJsonFactory;
	protected $_erpviewblock;
	protected $_categoryFactory;
	protected $_eavConfig;
	protected $_productRepository;
	protected $_product;
	protected $_resourceConnection;
	protected $_productCollectionFactory;
	protected $_publicActions = ['disableproductspost'];
	
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpviewblock,
		\Magento\Catalog\Model\CategoryFactory $categoryFactory,
		\Magento\Eav\Model\Config $eavConfig,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Catalog\Model\Product $productDt,
		\Magento\Framework\App\ResourceConnection $resourceConnection,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
	)
	{
		$this->_resultPageFactory = $resultPageFactory;
		$this->_resultJsonFactory = $resultJsonFactory;
		$this->_erpviewblock = $erpviewblock;
		$this->_categoryFactory = $categoryFactory;
		$this->_eavConfig = $eavConfig;
		$this->_productRepository = $productRepository;
		$this->_product = $productDt;
		$this->_resourceConnection = $resourceConnection;
		$this->_productCollectionFactory = $productCollectionFactory;
		parent::__construct($context);
	}

    public function execute()
    {		
		$params = $this->getRequest()->getParams();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
		$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
		$storeId = 1;

		$connection = $this->_resourceConnection->getConnection();
		
		/*Disable Products Which Is Not Present In ERP Start*/
		if(isset($params['disableproducts']) && $params['requestdisableproduct'] == 1 && !empty($params['disableproducts'])){
			
			/*Magento Product Data Update Start*/
			$erpTableName = $connection->getTableName('erp_product_data');
			$allItemsQuery = "SELECT `id`, `topcategory`, `name`, `erp_pid`, `sku`, `price`, `tax_percent`, `quantity`, `quantity_unit`, `uom`, `categories`, `attributes`, `packing`, `unit_convert`, `item_prices`, `ispresent` FROM " . $erpTableName . " WHERE `ispresent` = '0'";
			$allItemsResults = $connection->fetchAll($allItemsQuery);
						
			if(count($allItemsResults) > 0){						
				foreach($allItemsResults as $allRecord){
				if($allRecord['ispresent'] == '0'){	
					$product = [];
					$product['sku'] = trim($allRecord['sku']);
					$sku = $product['sku'];					
					$price_array = [];
					if(isset($allRecord['item_prices'])){
						$price_array = unserialize($allRecord['item_prices']);
						if(isset($price_array['price'])){
							$price_array = $price_array['price'];									
						}
					}
					$product['pricearray'] = $price_array;						
					
					if($sku){						
						if(count($product['pricearray']) > 0){
							$minimal_price = min(array_column($product['pricearray'], 'AgreementValue'));
							foreach($product['pricearray'] as $priceVal){
								
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
								
								if ($this->_product->getIdBySku($associatedProductSku)){
									$simple_product = $productFactory->create();
									$simple_product->setStoreId($storeId)->load($simple_product->getIdBySku($associatedProductSku));
								}
	
								try {
									$simple_product->setStatus(0) // 1 = enabled	
											->setWebsiteIds(array(1)) // Default Website ID									
											->setStoreId(0) // Default store ID
											->save();
											
										echo 'Product Disabled SKU '.$associatedProductSku;
										echo '<br/>';												
								
										$productId = $simple_product->getId();
										$associatedProductIds[] = $productId;
											
								} catch (\Magento\Framework\Exception $e){
										echo 'Product not found SKU '.$associatedProductSku;
										echo '<br/>';									
										continue;
								}
								
								unset($simple_product);
		
							}
						}						
						
						//configurable product
						if ($this->_product->getIdBySku($sku)){
							$configurable_product = $productFactory->create();
							$configurable_product->setStoreId($storeId)->load($configurable_product->getIdBySku($sku));
							try {
								$configurable_product->setStatus(0)				
													 ->setWebsiteIds(array(1))
													 ->setStoreId(0) // Default store ID
													 ->save();
								$configurable_product->addAttributeUpdate('status', 0, 1);
													 
											echo 'Product Disabled SKU '.$sku;
											echo '<br/>';
							} catch (\Magento\Framework\Exception $e){
										echo 'Product not found SKU '.$sku;
										echo '<br/>';										
										continue;
									}
								
							unset($configurable_product);
						}
	
						
					} else {
						echo 'SKU not found ';
						echo '<br/>';						
					}						
				}
				}
			} else {
					echo 'No record found for disable a product';
					echo '<br/>';					
			}
			/*Magento Product Data Update End*/
		}			
		/*Disable Products Which Is Not Present In ERP End*/
		
	?>
		<a href="<?php echo $this->getUrl('erpdata/*/productupdate'); ?>"><?php echo __('Back to Main Page'); ?></a>
	<?php	
    }	
	
}