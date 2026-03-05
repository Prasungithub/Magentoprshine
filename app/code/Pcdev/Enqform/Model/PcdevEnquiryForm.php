<?php

namespace Pcdev\Enqform\Model;

class PcdevEnquiryForm extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Pcdev\Enqform\Model\ResourceModel\PcdevEnquiryForm');
    }
}