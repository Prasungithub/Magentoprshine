<?php
namespace Pcdev\Categorymenu\Block;

use Magento\Store\Model\ScopeInterface;

class Mainmenucategory extends \Magento\Framework\View\Element\Template
{
	
	protected $_helper;
	protected $_categoryRepository;
	
	public function __construct(	
		\Magento\Catalog\Block\Product\Context $context,
		\Pcdev\Categorymenu\Helper\Data $helperData,
		\Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
    	array $data = []
	){    
		$this->_helper = $helperData;
		$this->_categoryRepository = $categoryRepository;
    	parent::__construct($context, $data);
	 }
	 
	public function getMainMenuCategories()
	{
		$cat_Ids = [];
		
		if($this->_helper->getmainMenuCategories())
		{
			$cat_ids_string = $this->_helper->getmainMenuCategories();		
			if($cat_ids_string){
				$cat_ids_arr = explode(',',$cat_ids_string);	
				
				if(count($cat_ids_arr) > 0){		
					foreach($cat_ids_arr as $cat_id){
									
						$category = $this->_categoryRepository->get($cat_id);						
						if($category->getIsActive()){
							$cat_Ids[] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage()  );
						}								
					}
				}			
			}			
		}		
		return $cat_Ids;
	}	
	
	public function getFooterMenuCategories()
	{
		$cat_Ids = [];
		
		if($this->_helper->getfooterMenuCategories())
		{
			$cat_ids_string = $this->_helper->getfooterMenuCategories();
			if($cat_ids_string){
				$cat_ids_arr = explode(',',$cat_ids_string);
				
				if(count($cat_ids_arr) > 0){		
					foreach($cat_ids_arr as $cat_id){
									
						$category = $this->_categoryRepository->get($cat_id);						
						if($category->getIsActive()){
							$cat_Ids[] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage()  );
						}								
					}
				}			
			}
			
		}		
		return $cat_Ids;
	}	
	
}
