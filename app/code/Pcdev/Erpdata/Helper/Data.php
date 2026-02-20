<?php
namespace Pcdev\Erpdata\Helper;

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
	
	public function getCustAccount($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpcustaccount',
            $scope
        );		
	}	
	
	public function getCompanyId($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpcompanyid',
            $scope
        );		
	}

	public function getDataOrigin($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpdataorigin',
            $scope
        );		
	}	
	
	public function getGrantType($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpdatagranttype',
            $scope
        );		
	}	
	
	public function getClientId($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpdataclientid',
            $scope
        );		
	}	
	
	public function getClientSecret($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpdataclientsecret',
            $scope
        );		
	}

	public function getResourceUrl($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'erpdataconfig/erpdatagp/erpdataresource',
            $scope
        );		
	}	
	
}

?>