<?php
namespace Pcdev\Productupdate\Block;

class Productdata extends \Magento\Framework\View\Element\Template
{
	protected $customerSession;
	
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Customer\Model\Session $customerSession,	
    	array $data = []
	){  
		parent::__construct($context, $data);
		$this->customerSession = $customerSession;
		$this->_wishlist = $wishlist;
		$this->customerRepository = $customerRepository;
        $this->_customerSession = $sessionCustomer;
	 }
	
    public function getWishlistItemsaa(){

    }	
	
}
