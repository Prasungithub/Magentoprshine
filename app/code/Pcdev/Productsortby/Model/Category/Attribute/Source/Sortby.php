<?php

namespace Pcdev\Productsortby\Model\Category\Attribute\Source;


class Sortby extends \Magento\Catalog\Model\Category\Attribute\Source\Sortby
{

    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [['label' => __('Position'), 'value' => 'position']];
            foreach ($this->_getCatalogConfig()->getAttributesUsedForSortBy() as $attribute) {
                $this->_options[] = [
                    'label' => __($attribute['frontend_label']),
                    'value' => $attribute['attribute_code']
                ];
            }
        }
		$this->_options[] = ['label' => __('Popularity'), 'value' => 'mostviewed'];
		$this->_options[] = ['label' => __('New Arrivals'), 'value' => 'newarrivals'];
		$this->_options[] = ['label' => __('Offers'), 'value' => 'offers'];			
		$this->_options[] = ['label' => __('Featured'), 'value' => 'featured'];		
		
        return $this->_options;
    }
}
