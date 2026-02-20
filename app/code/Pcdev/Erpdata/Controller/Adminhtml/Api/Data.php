<?php

namespace Pcdev\Erpdata\Controller\Adminhtml\Api;

class Data extends \Magento\Backend\App\Action
{
	protected $_resultPageFactory;
	protected $_resultJsonFactory;
	


	public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory	
	)
	{
		$this->_resultPageFactory = $resultPageFactory;
		$this->_resultJsonFactory = $resultJsonFactory;
		parent::__construct($context);
	}

    public function execute()
    {		
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage ERP Data'));
        return $resultPage;		
    }
}