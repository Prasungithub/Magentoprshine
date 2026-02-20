<?php
namespace Pcdev\Erpinvoice\Block;

use Magento\Framework\View\Element\Template\Context;
//use Addonworks\Grid\Model\Grid;

class Erpinvoice extends \Magento\Framework\View\Element\Template
{
	public function __construct(Context $context)
    {
                //$this->model = $model;
        parent::__construct($context);

    }

	public function getLocations()
	{
		$Datas = $this->model->getCollection();
        return $Datas;
	}
}