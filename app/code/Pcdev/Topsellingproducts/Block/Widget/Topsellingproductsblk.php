<?php
namespace Pcdev\Topsellingproducts\Block\Widget;
 
use Magento\Widget\Block\BlockInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
 
class Topsellingproductsblk extends \Magento\Framework\View\Element\Template implements BlockInterface
{
 
    protected $_template = 'widget/topsellingproductsblk.phtml';
    protected $_productCollectionFactory;
	protected $_productRepositoryFactory;
    protected $_storeManager;
	protected $_imageHelper;
	protected $_categoryRepository;
	protected $_checkoutSession;
 
    public function __construct(Context $context, StoreManagerInterface $storeManager, CollectionFactory $productCollectionFactory, ProductRepositoryInterfaceFactory $ProductFactory, Image $imageHelper, CategoryRepositoryInterface $categoryRepository, ProductRepository $productRepository, Session $checkoutSession)
    {
 
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory  = $productCollectionFactory;
		$this->_productRepositoryFactory  = $ProductFactory;
		$this->_imageHelper  = $imageHelper;
		$this->_categoryRepository = $categoryRepository;
		$this->_productRepository = $productRepository;
		$this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }
	
	public function getCacheLifetime()
	{
		return null;
	}	
 
    public function getTitle()
    {	
        return $this->getData('topsellingtitle');
    }
	
    public function getFeaturedProductsTitle()
    {	
        return $this->getData('featuredproductstitle');
    }	
 
    public function getProductIds()
    {
        if ($this->hasData('topsellingproductids')) {
            return $this->getData('topsellingproductids');
        }
        return $this->getData('topsellingproductids');
    }
	
    public function getCategoryIds()
    {
        if ($this->hasData('featuredproductcategoryids')) {
            return $this->getData('featuredproductcategoryids');
        }
        return $this->getData('featuredproductcategoryids');
    }
 
    public function getProductCollection()
    {		
		$abstractProductBlock = $this->getLayout()->createBlock('\Magento\Catalog\Block\Product\AbstractProduct');
		
		$product_ids = explode(",", $this->getProductIds());
		
		$collection = [];
		$c = 1;
		foreach($product_ids as $product_id){
			
			$product = $this->_productRepositoryFactory->create()
						->getById($product_id);
			
			$collection[$c]['product_id'] = $product->getData('entity_id');
			$collection[$c]['name'] = $product->getData('name');
			$collection[$c]['image'] = $imageUrl = $this->_imageHelper->init($product, 'product_base_image')
							->constrainOnly(true)
							->keepAspectRatio(true)
							->keepTransparency(true)
							->keepFrame(false)
							->resize(300,300)->getUrl();
			$collection[$c]['status'] = $product->getData('status');
			$collection[$c]['url'] = $product->getProductUrl();
			$collection[$c]['price'] = $abstractProductBlock->getProductPrice($product);
			$collection[$c]['is_available'] = $product->isAvailable();
			$collection[$c]['data'] = $product->getData();			
			$c++;			
		}

		return $collection;
    }
	
    public function getTopSellingProduct($product_id)
    {		
		// $abstractProductBlock = $this->getLayout()->createBlock('\Magento\Catalog\Block\Product\AbstractProduct');
		try{
			$product = $this->_productRepository->getById($product_id);
			return $product;
		} 
		catch (\Magento\Framework\Exception\NoSuchEntityException $e){
			$msg = 'Product Id '.$product_id.' not found';
			$this->messageManager->addError(__($msg));
		}

    }	
	
    public function getFeaturedProductsCollection()
    {	
		$abstractProductBlock = $this->getLayout()->createBlock('\Magento\Catalog\Block\Product\AbstractProduct');
		$category_ids =  explode(",", $this->getCategoryIds());

		$product_collection = [];
		$c = 1;
		if(count($category_ids) > 0){
			foreach($category_ids as $category_id){
				
				
				$category = $this->_categoryRepository->get($category_id);						
						if($category->getIsActive()){
							$cat_Ids[] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage(), 'category_menu_hover_icon'=>$category->getCategoryMenuHoverIcon());
						}				
				
				
				$collection = $this->_productCollectionFactory->create();
				$collection->addAttributeToSelect('*');
				$collection->addCategoriesFilter(['eq' => $category_id]);
				$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
				$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
				$collection->addAttributeToFilter('featured', array('in'=>'1'));
				//$collection->setPageSize(10);
				if($c <= 10){
					foreach ($collection as $product) {
						$imageUrl = $this->_imageHelper->init($product, 'product_base_image')
							->constrainOnly(true)
							->keepAspectRatio(true)
							->keepTransparency(true)
							->keepFrame(false)
							->resize(300,300)->getUrl();
							
						$price = $abstractProductBlock->getProductPrice($product);
						
						$product_collection[$c]['category'] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'category_menu_hover_icon'=>$category->getCategoryMenuHoverIcon());
							
						$product_collection[$c]['products'][] = array("data"=>$product->getData(),"product_url"=>$product->getProductUrl(),"product_img"=>$imageUrl,"price"=>$price,"is_available"=>$product->isAvailable());
					}
				}
			$c++;	
			}
		}	

		return $product_collection;
    }
	
    public function getCartdata()
    {	
		return $this->_checkoutSession->getQuote();
    }	
	
    public function getTopSellingAllProducts()
    {	
		$productIds_string = $this->getProductIds();
		$productIds = explode(",", $productIds_string);
		$noData = [];
		
		if(is_array($productIds) && count($productIds) > 0){			
			$collection = $this->_productCollectionFactory->create();
			$collection->addAttributeToSelect(['name','sku','image','price']);
			$collection->addFieldToFilter('entity_id', array('in' => $productIds));
			return $collection;
		}		

		return $noData;
    }

    public function getFeaturedCatCollection()
    {	
		$category_ids =  explode(",", $this->getCategoryIds());
		$noData = [];
		$cat_Ids = [];
		if(count($category_ids) > 0){
			foreach($category_ids as $category_id){
				$category = $this->_categoryRepository->get($category_id);						
				if($category->getIsActive()){
					$cat_Ids[] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage(), 'category_menu_hover_icon'=>$category->getCategoryMenuHoverIcon());
				}
			}
			return $cat_Ids;			
		}
		
		return $noData;		
    }	
	
    public function getFeaturedProdsByCatId($cat_id)
    {	
		$collection = $this->_productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addCategoriesFilter(['eq' => $cat_id]);
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addAttributeToFilter('featured', array('in'=>'1'));
		$collection->setPageSize(10);
		return $collection;
    }	
	
}