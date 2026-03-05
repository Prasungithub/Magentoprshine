<?php
namespace Pcdev\Enqform\Controller\Enquiry;

class Form extends \Magento\Framework\App\Action\Action
{
	protected $_storeManager;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManager
	)
	{
		$this->_storeManager = $storeManager;
		parent::__construct($context);
	}

    public function execute()
    {
		//echo 'Yes here';
		$this->_view->loadLayout();
		$this->_view->renderLayout();
		
	}

}