<?php

namespace Pcdev\Erpdata\Controller\Adminhtml\Api;

class Salesodrdersync extends \Magento\Backend\App\Action
{
	protected $_resultPageFactory;
	protected $_resultJsonFactory;
	protected $_erpviewblock;
	protected $_categoryFactory;
	protected $_eavConfig;
	protected $_productRepository;
	//protected $_productFactory;
	protected $_publicActions = ['salesodrdersync'];
	
	//\Magento\Catalog\Model\ProductFactory $productFactory
	
	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Pcdev\Erpdata\Block\Adminhtml\View $erpviewblock,
		\Magento\Catalog\Model\CategoryFactory $categoryFactory,
		\Magento\Eav\Model\Config $eavConfig,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository
	)
	{
		$this->_resultPageFactory = $resultPageFactory;
		$this->_resultJsonFactory = $resultJsonFactory;
		$this->_erpviewblock = $erpviewblock;
		$this->_categoryFactory = $categoryFactory;
		$this->_eavConfig = $eavConfig;
		$this->_productRepository = $productRepository;
		//$this->_productFactory = $productFactory;
		parent::__construct($context);
	}

    public function execute()
    {
		$resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Sales Order Sync'));		
        return $resultPage;			
    }
	
}