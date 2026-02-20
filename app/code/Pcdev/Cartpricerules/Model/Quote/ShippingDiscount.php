<?php

namespace Pcdev\Cartpricerules\Model\Quote;

use Magento\SalesRule\Model\Quote\Discount as DiscountCollector;

class ShippingDiscount extends \Magento\SalesRule\Model\Quote\Address\Total\ShippingDiscount
{	
    /**
     * @inheritdoc
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total): array
    {
        $result = [];
        $amount = $total->getDiscountAmount();

        if ($amount != 0) {
            $description = (string)$total->getDiscountDescription() ?: '';
			
			$applied_rule_ids = $total->getAppliedRuleIds();
			if(empty($description) && !empty($applied_rule_ids)){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
				$connection = $resource->getConnection();
				$applied_rule_ids = explode(',',$applied_rule_ids);
				$cust_description = '';
				if(count($applied_rule_ids) > 0){
					foreach($applied_rule_ids as $applied_rule_id){
						$appliedRuleQuery = "SELECT `description` FROM `salesrule` WHERE `rule_id`='".$applied_rule_id."'";
						$appliedRuleResults = $connection->fetchAll($appliedRuleQuery);		
						if(count($appliedRuleResults)>0){
							if(!empty($appliedRuleResults[0]['description'])){
								$description_text = $appliedRuleResults[0]['description'];
								$cust_description = $cust_description .', '.$description_text;
							}
						}						
						
					}
					
					if(!empty($cust_description)){
						$description = trim($cust_description,",");
						$description = trim($description);
					}
				}
			}
			
            $result = [
                'code' => DiscountCollector::COLLECTOR_TYPE_CODE,
                'title' => strlen($description) ? __('Discount (%1)', $description) : __('Discount'),
                'value' => $amount
            ];
        }
        return $result;
    }	
}
