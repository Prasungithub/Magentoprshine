<?php

namespace Pcdev\Cartpricerules\Model\Quote;

class Discount extends \Magento\SalesRule\Model\Quote\Discount
{
    /**
     * Add discount total information to address
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $total->getDiscountAmount();

        if ($amount != 0) {
            $description = $total->getDiscountDescription();
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
					}
				}
			}
			
            $result = [
                'code' => $this->getCode(),
                'title' => strlen($description) ? __('Discount (%1)', $description) : __('Discount'),
                'value' => $amount
            ];
        }
        return $result;
    }
}
