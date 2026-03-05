<?php

namespace Pcdev\Enqform\Model\ResourceModel;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Pcdev\Enqform\Model\PcdevEnquiryForm',
            'Pcdev\Enqform\Model\ResourceModel\PcdevEnquiryForm'
        );
    }
}