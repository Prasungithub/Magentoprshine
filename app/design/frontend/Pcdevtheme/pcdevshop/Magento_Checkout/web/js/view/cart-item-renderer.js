/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent','jquery'
], function (Component,$) {
    'use strict';

    return Component.extend({
        /**
         * Prepare the product name value to be rendered as HTML
         *
         * @param {String} productName
         * @return {String}
         */
        getProductNameUnsanitizedHtml: function (productName) {
            // product name has already escaped on backend
            return productName;
        },

        /**
         * Prepare the given option value to be rendered as HTML
         *
         * @param {String} optionValue
         * @return {String}
         */
        getOptionValueUnsanitizedHtml: function (optionValue) {
            // option value has already escaped on backend
            return optionValue;
        },
        increaseQty: function (item_id) {           
            var requestedQtyInputId = '#minicart_item_qty_input_id_'+item_id;
            var qtyInputId = '#cart-item-'+item_id+'-qty';   
            var inputValAssociated = $(qtyInputId).val();  

            if(inputValAssociated !== '0'){
                var inputValAssociatedAdd = parseInt(inputValAssociated) + 1;
            }else{
                var inputValAssociatedAdd = 1;
            }              
                    
            $(qtyInputId).val(inputValAssociatedAdd);
            //$("#minicart_loader_"+item_id).show();
            var updateButtonId = '#update-cart-item-'+item_id;
            $(updateButtonId).trigger("click");
        },
        setMinicartQty: function (item_id) {
            /*
            var key = event.keyCode || event.charCode;
            if( key == 8 || key == 46 ){
                return false;
            }             
            */           
            var requestedQtyInputId = '#minicart_item_qty_input_id_'+item_id;
            var inputValAssociated = $(requestedQtyInputId).val();    

            if(inputValAssociated > 0 ){
                var inputValAssociatedAdd = inputValAssociated;

            }else{
                return false; 
            }
            
               
            var qtyInputId = '#cart-item-'+item_id+'-qty';           
            $(qtyInputId).val(inputValAssociatedAdd);
            //$("#minicart_loader_"+item_id).show();
            var updateButtonId = '#update-cart-item-'+item_id;
            $(updateButtonId).trigger("click"); 
        },
        resetMinicartQty: function (item_id) {
            /*
            var qtyInputId = '#cart-item-'+item_id+'-qty';           
            var inputValAssociatedAdd = $(qtyInputId).val();
            var requestedQtyInputId = '#minicart_item_qty_input_id_'+item_id;
            $(requestedQtyInputId).val(inputValAssociatedAdd);
            */            
        },
        decreaseQty: function (item_id) {
            $("#minicart_loader_"+item_id).show();
            var requestedQtyInputId = '#minicart_item_qty_input_id_'+item_id;
            var qtyInputId = '#cart-item-'+item_id+'-qty';   
            var inputValAssociated = $(qtyInputId).val();  

             if(inputValAssociated > 0 ){
                var inputValAssociatedAdd = inputValAssociated - 1;
            }else{
                var inputValAssociatedAdd = 0;
            }                     
            $(qtyInputId).val(inputValAssociatedAdd);
           
            var updateButtonId = '#update-cart-item-'+item_id;    
            $(updateButtonId).trigger("click");
             $("#minicart_loader_"+item_id).hide();
        },
        
        getDeleteQtyMinus: function (inputValAssociatedAdd) {
            if(inputValAssociatedAdd == 1){
                return "delete-minicart-qty-minus"; 
            }else{
                return "";  
            }
        }
    });
});
