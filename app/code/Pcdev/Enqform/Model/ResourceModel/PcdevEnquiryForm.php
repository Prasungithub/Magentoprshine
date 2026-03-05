<?php

namespace Pcdev\Enqform\Model\ResourceModel;

class PcdevEnquiryForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('pcdev_enquiry_form', 'enq_id');  //here "pcdev_enquiry_form" is table name and "enq_id" is the primary key of custom table
    }	
}