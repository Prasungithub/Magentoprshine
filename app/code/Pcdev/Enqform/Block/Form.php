<?php

namespace Pcdev\Enqform\Block;

class Form extends \Magento\Framework\View\Element\Template
{
	protected $_storeManager;
	
	public function __construct(	
		\Magento\Catalog\Block\Product\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
    	array $data = []
	){    
		$this->_storeManager = $storeManager;
    	parent::__construct($context, $data);
	 }

    public function getTitleName()
    {
        $pageTitle = "Test Title";

        return $pageTitle;
    }
}
