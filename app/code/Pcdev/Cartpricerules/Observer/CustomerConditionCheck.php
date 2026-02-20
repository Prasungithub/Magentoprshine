<?php

namespace Pcdev\Cartpricerules\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerConditionCheck implements ObserverInterface
{	
    /**
     * Execute observer.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $additional = $observer->getAdditional();
        $conditions = (array) $additional->getConditions();

        $conditions = array_merge_recursive($conditions, [
            $this->getCustomerFirstOrderCondition()
        ]);

        $additional->setConditions($conditions);
        return $this;
    }

    /**
     * Get condition for customer first order.
     * @return array
     */
    private function getCustomerFirstOrderCondition()
    {
        return [
            'label'=> __('Customer first order'),
            'value'=> \Pcdev\Cartpricerules\Model\Rule\Condition\Customer::class
        ];
    }

}
