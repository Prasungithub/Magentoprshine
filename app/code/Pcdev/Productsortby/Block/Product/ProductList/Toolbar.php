<?php

namespace Pcdev\Productsortby\Block\Product\ProductList;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
	
	/* protected $_productsFactory;
	protected $_storeManager;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $productsFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
	) {
		$this->_productsFactory = $productsFactory;
	    $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
	}*/	

    /**
     * Set collection to pager
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
		
        if ($this->getCurrentOrder()) {			
			
            if (($this->getCurrentOrder()) == 'position') {
				
                $this->_collection->addAttributeToSort(
                    $this->getCurrentOrder(),
                    $this->getCurrentDirection()
                ); 
            } elseif (($this->getCurrentOrder()) == 'special_price') {
			
				$this->_collection->setOrder('special_price', 'desc');		
				
			} elseif (($this->getCurrentOrder()) == 'mostviewed') {
				
				$this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());

				/*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
				$currentStoreId = $storeManager->getStore()->getId();
				
				$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
				
				/* $productCollection->addAttributeToSelect('*')
								   ->addViewsCount()
								   ->setStoreId($currentStoreId)
								   ->addStoreFilter($currentStoreId);
								   
								   $col = $productCollection->getItems(); */
				/*				   
				$productCollection = $objectManager->create('Magento\Reports\Model\ResourceModel\Report\Collection\Factory'); 
				$collection = $productCollection->create('Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection');	*/			   
				
				// $currentStoreId = $this->_storeManager->getStore()->getId();

				/* $this->_productsFactory->create()
								   ->addAttributeToSelect('*')
								   ->addViewsCount()
								   ->setStoreId($currentStoreId)
								   ->addStoreFilter($currentStoreId)
								   ->getItems();	*/	
				// $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());		
			
			} elseif (($this->getCurrentOrder()) == 'news_from_date') {
				
				$this->_collection->setOrder('news_from_date', 'desc');			
				
				/* $this->_collection->addAttributeToFilter(
								'news_from_date',
								['date' => true, 'to' => $this->getEndOfDayDate()],
								'left'
							)
							->addAttributeToFilter(
								'news_to_date',
								[
									'or' => [
										0 => ['date' => true, 'from' => $this->getStartOfDayDate()],										
										1 => ['is' => new Zend_Db_Expr('null')],
									]
								],
								'left'
							)
							->addAttributeToSort(
								'news_from_date',
								'desc'
							)
							->addStoreFilter($this->getStoreId()); */
				
			} elseif (($this->getCurrentOrder()) == 'featured') {
				
				$this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());					
				
			}else {
                $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
            }
							
        }
        return $this;
    }
}
