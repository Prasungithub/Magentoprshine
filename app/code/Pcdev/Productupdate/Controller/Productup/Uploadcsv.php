<?php
namespace Pcdev\Productupdate\Controller\Productup;

class Uploadcsv extends \Magento\Framework\App\Action\Action
{
	protected $_storeManager;
	protected $_checkoutSession;
	protected $_escaper;
	protected $_scopeConfig;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\Escaper $escaper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	)
	{
		$this->_storeManager = $storeManager;
		$this->_checkoutSession = $checkoutSession;
		$this->_escaper = $escaper;
		$this->_scopeConfig = $scopeConfig;
		parent::__construct($context);
	}

    public function execute()
    {
		echo 'Yes here';
		
	}

}
