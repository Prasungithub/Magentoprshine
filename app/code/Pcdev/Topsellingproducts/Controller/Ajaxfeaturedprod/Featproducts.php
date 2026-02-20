<?php
namespace Pcdev\Topsellingproducts\Controller\Ajaxfeaturedprod;

class Featproducts extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_topsellingdata;
	protected $_imageHelper;
	protected $_productattribute;
	protected $_abstractProduct;
	protected $_escaper;
	protected $_qtyupdate;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Pcdev\Topsellingproducts\Block\Widget\Topsellingproductsblk $topsellingdata,
		\Magento\Catalog\Helper\Image $imageHelper,
		\Pcdev\Categoryattributes\Block\Productattribute $productattribute,
		\Magento\Catalog\Block\Product\AbstractProduct $abstractProduct,
		\Magento\Framework\Escaper $escaper,
		\Pcdev\Customization\Block\Cart\Qtyupdate $qtyupdate)
	{
		$this->_pageFactory = $pageFactory;
		$this->_topsellingdata = $topsellingdata;
		$this->_imageHelper  = $imageHelper;
		$this->_productattribute  = $productattribute;
		$this->_abstractProduct  = $abstractProduct;
		$this->_escaper  = $escaper;
		$this->_qtyupdate  = $qtyupdate;
		return parent::__construct($context);
	}

	public function execute()
	{
		$cat_id = $this->getRequest()->getParam('cid');
		
		$data = '';

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');	
		$templateBlock = $objectManager->create('Magento\Framework\View\Element\Template');		

		$featured_products_data = $this->_topsellingdata->getFeaturedProdsByCatId($cat_id);
		if(count($featured_products_data) > 0){
			$data = '';

			
			$data .=  '<ul class="featured_product_slider_newn product_slider">';			
			
			foreach($featured_products_data as $feat_cat_prod_data){
				
				if($feat_cat_prod_data){
					if ($feat_cat_prod_data->getStatus() == 1) {

				$data .=  '<li class="product-item">
					<div class="top_selling_product">								
						<div class="top_selling_prod_img_ch">
						<a href="'. $feat_cat_prod_data->getProductUrl().'">';
							if($feat_cat_prod_data->getImage()){
								
								$imageUrl = $this->_imageHelper->init($feat_cat_prod_data, 'product_base_image')
										->constrainOnly(true)
										->keepAspectRatio(true)
										->keepTransparency(true)
										->keepFrame(true)
										->resize(300,300)->getUrl();
							
									$data .=  '<img src="'.$imageUrl.'" alt="'.$feat_cat_prod_data->getData('name').'" height="207" width="207" loading="lazy" />';
							}else{ 
								$data .=  '<img src="'. $placeholderImageUrl.'" alt="" title="" height="207" width="207" loading="lazy"  />';
							}
						$data .=  '</a>	
						</div>
						<div class="top_selling_prod_info">
						<a href="'. $feat_cat_prod_data->getProductUrl().'">
							<p>'. $feat_cat_prod_data->getData('name').'</p>
						</a>

						<div class="prod_default_size">';	
						
							$DefaultSize = $this->_productattribute->getProductDefaultSize($feat_cat_prod_data->getId());
							$data .=  $DefaultSize;							 
						
						$data .=  '</div>							

						<div class="prod_price_area">
							<div class="prod_price_amt">						
								<span class="top_price">
								<div class="price-box price-final_price">
								<span class="price">';
								
									$price = $priceHelper->currency($feat_cat_prod_data->getFinalPrice(), true, false);							
									$data .= $price;
								
								$data .=  '</div></span></span>
							</div>
							<div class="prod_price_vat">';
							$data .=  __('(Inc. VAT)');
							$data .=  '</div>
						</div>

						<div class="product-item-inner">
							<div class="product actions product-item-actions">
								<div class="actions-primary">';						
									if ($feat_cat_prod_data->isSaleable()){ ?>
										<?php $cartqty = 0; if ($feat_cat_prod_data->isAvailable()){ $cartqty = 1; } ?>
										<?php
										$cart_qty = 0;
										$item_id = 0;
										?>
										<?php $_product = $feat_cat_prod_data ; ?>
									<?php $product_id = $_product->getId();?>
		
									<?php
										$IdDoProduto = $_product->getId();
										$sku = $_product->getSku();

										$cart = $objectManager->get('\Magento\Checkout\Model\Session');

										$itemsCollection = $cart->getQuote()->getItemsCollection();

										// get array of all items what can be display directly
										$itemsVisible = $cart->getQuote()->getAllVisibleItems();

										// retrieve quote items array
										 $items = $cart->getQuote()->getAllItems();
										 $cartbaselink = $templateBlock->escapeUrl($templateBlock->getUrl('checkout/cart/add').'/uenc/product/');
										 
										//print_r($items);
										foreach($items as $item) {
											//echo "--".$item->getProduct_id();
											 if($IdDoProduto == $item->getProduct_id()){
												 //echo "uu-".$item->getProduct_id();
													$cart_qty = $item->getQty();
													$item_id = $item->getId();
											 }         
										  }
										  
										 // echo "tt".$cart_qty."-".$item_id;
										
										$data .= '<form data-role="tocart-form"
										  data-product-sku="'.$this->_escaper->escapeHtml($_product->getSku()).'"
										  action="'.$cartbaselink.''.$_product->getEntityId().'"
										  method="post">
										
										<input type="hidden" name="product" value="'.$_product->getEntityId().'" />
										<input type="hidden" name="uenc" value="" />
										<input name="form_key" type="hidden" value="'.rand(100000000000,999999999999).'" />

										<div class="qty-box" style="display:none">
											<a href="javascript:void(0)" class="qtyminus"><i class="fa fa-minus"></i></a>
											<input type="text" name="qty" id="qty'.$_product->getId().'" maxlength="4" min="1" value="'. $cartqty*1 .'" title="'. __('Qty').'" class="input-text qty cart-item-qty" data-validate="'.$templateBlock->escapeHtmlAttr(json_encode($templateBlock->getQuantityValidators())).'"/>
											<a href="javascript:void(0)" class="qtyplus"><i class="fa fa-plus"></i></a>
										</div>            
										<div class="associated-product-qty-box-wrapper">'; ?>
											<?php $_product         = $_product; ?> 
											<?php $product_id       = $_product->getId();?>
											<?php $itemQtyInCart    = $this->_qtyupdate->getItemQty($product_id); ?>
											<?php $productType    = $this->_qtyupdate->getProductType($_product); ?>
											<?php $type    = "featured"; 
												if($itemQtyInCart > 0){
													$hasProduct = 'has-product';
													$qtyorsign = $itemQtyInCart;
												} else {
													$hasProduct = '';
													$qtyorsign = "+";
												}
											?>
											<?php $data .=	'<div  section="'.$type.'"  id="qty_box_counter_'. $product_id.$type.'" class="qty-box-counter '.$hasProduct.'">'.$qtyorsign.'</div>
											<div class="associated-product-qty" style="display:none;">'; 
													$deleteCLsQty ="";
													if($itemQtyInCart ==1){
														$deleteCLsQty = "delete-qty-minus";
													}
												
												$data .= '<span 
													id="associated_product_qty_minus_id_'.$product_id.$type.'"
													class="associated-product-qty-minus '.$deleteCLsQty.'" 
													product_id="'. $product_id .'" 
													section="'.$type.'" 
													
												>-</span> 
												<span>
													<input name="quantity['.$product_id.']" 
														   id="quantity_'.$product_id.$type.'"
														   type="number" 
														   class="associated-product-qty-input" 
														   value="'.$itemQtyInCart.'"               
														   product_id="'. $product_id .'"
														   section="'. $type .'"
													>
												</span>
												<span class="associated-product-qty-plus" 
													  product_id="'.$product_id.'" 
													  section="'.$type.'"
												>+ </span>
											</div>
										</div>
									</form>';
									
									
												
									} 	
								$data .= '</div>								
								<div data-role="add-to-links" class="actions-secondary">'.$templateBlock->getLayout()->createBlock("Magento\Wishlist\Block\Catalog\Product\ProductList\Item\AddTo\Wishlist")->setProduct($feat_cat_prod_data)->setTemplate("Magento_Wishlist::catalog/product/list/addto/wishlist.phtml")->toHtml().'</div>								
							</div>
						</div>

						<script type="text/x-magento-init">
						{
							"[data-role=tocart-form], .form.map.checkout": {
								"catalogAddToCart": {
									"product_sku": "'.$this->_escaper->escapeJs($feat_cat_prod_data->getSku()).'"
								}
							}
						}
						</script>								
							
						</div>
					</div>
				</li>';											
				
					}
				}								
				
			}
			
			$data .=  '</ul>';			
			
		}
		
		$result = [
			'success' => 0, 
			'products' => $data
		];
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($result));
		
		if(!$data){
			$hmurl = $templateBlock->getUrl('/');
			header("Location: $hmurl", true, 301);
			die();
		}
		
	}
}