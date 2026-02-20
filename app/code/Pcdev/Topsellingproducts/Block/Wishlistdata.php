<?php
namespace Pcdev\Topsellingproducts\Block;

class Wishlistdata extends \Magento\Framework\View\Element\Template
{
	protected $customerSession;
	protected $_wishlist;	
	public $customerRepository;
	public $_customerSession;
	
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Wishlist\Model\Wishlist $wishlist,	
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\SessionFactory $sessionCustomer,	
    	array $data = []
	){  
		parent::__construct($context, $data);
		$this->customerSession = $customerSession;
		$this->_wishlist = $wishlist;
		$this->customerRepository = $customerRepository;
        $this->_customerSession = $sessionCustomer;
	 }
	
    public function getWishlistItems(){
		$customer = $this->_customerSession->create();
		$customer_id = $customer->getCustomer()->getId();
		if($customer_id){
			$wishlist_collection = $this->_wishlist->loadByCustomerId($customer_id, true)->getItemCollection();
			return $wishlist_collection;
		} else {
			$wishlist_collection = [];
			return $wishlist_collection;
		}
    }	
	
}
