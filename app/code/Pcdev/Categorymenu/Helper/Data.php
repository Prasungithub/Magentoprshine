<?php
namespace Pcdev\Categorymenu\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $httpContext;
	protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
		\Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->httpContext = $httpContext;
		$this->_storeManager = $storeManager;
    }	
	
	public function getmainMenuCategories($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'ctmainmenuconfig/catlistmainmenu/mmcategoryids',
            $scope
        );		
	}
	
	public function getfooterMenuCategories($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'ctmainmenuconfig/catlistfootermenu/footermcategoryids',
            $scope
        );		
	}	
	
	public function getMediaUrl(){
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		return $mediaUrl;
    }	
	
    public function isLoggedIn()
    {
      $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
      return $isLoggedIn;
    }	
	
}

?>