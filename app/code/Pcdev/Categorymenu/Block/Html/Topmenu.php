<?php
namespace Pcdev\Categorymenu\Block\Html;

use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;

class Topmenu extends \Magento\Framework\View\Element\Template
{
	
	protected $_helper;
	protected $_categoryRepository;
	protected $_categoryManagement;
	
	public function __construct(	
		\Magento\Catalog\Block\Product\Context $context,
		\Pcdev\Categorymenu\Helper\Data $helperData,
		\Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
		\Magento\Catalog\Api\CategoryManagementInterface $categoryManagement,
    	array $data = []
	){    
		$this->_helper = $helperData;
		$this->_categoryRepository = $categoryRepository;
		$this->_categoryManagement = $categoryManagement;		
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
							$cat_Ids[] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage(), 'menuname'=> $category->getCategoryMegamenuname() );
						}								
					}
				}			
			}
			
		}		
		return $cat_Ids;
	}

	public function getCategories() 
	{
		$rootCategoryId = 2;
		$cat_data = [];	
		$categoryTreeList = $this->_categoryManagement->getTree($rootCategoryId);
		foreach($categoryTreeList->getChildrenData() as $allCategory){
			
			$cat_id = $allCategory->getId();
			if($allCategory->getIsActive()){
				$category = $this->_categoryRepository->get($cat_id);				
				if($category->getIncludeInMenu()){				
					$cat_data[$allCategory->getLevel()][$category->getId()] = array('cat_id'=>$category->getId(), 'name'=>$category->getName(), 'category_menu_icon'=>$category->getCategoryMenuIcon(), 'cat_url'=>$category->getUrl(), 'category_menu_image'=>$category->getCategoryMenuImage(), 'menuname'=> $category->getCategoryMegamenuname()  );
				}				
			}
			
			if($allCategory->getChildrenData()){
				foreach($allCategory->getChildrenData() as $subCategory){
					$sub_cat_id = $subCategory->getId();
					if($subCategory->getIsActive()){
						$sub_cat_data = $this->_categoryRepository->get($sub_cat_id);
						if($sub_cat_data->getIncludeInMenu()){
							$sub_cat_url = $category->getUrl().'?cat='.$sub_cat_data->getId();
							$cat_data[$allCategory->getLevel()][$category->getId()]['sub_category_data'][$sub_cat_data->getId()] = array('cat_id'=>$sub_cat_data->getId(), 'name'=>$sub_cat_data->getName(), 'category_menu_icon'=>$sub_cat_data->getCategoryMenuIcon(), 'cat_url'=>$sub_cat_url, 'category_menu_image'=>$sub_cat_data->getCategoryMenuImage(), 'menuname'=> $sub_cat_data->getCategoryMegamenuname()  );
						}							
					}
				}
			}
		}
		
		return $cat_data;
	}	
	
}
