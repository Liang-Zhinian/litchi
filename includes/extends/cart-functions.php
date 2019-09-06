<?php



add_filter( 'cocart_cart_contents', 'return_more_product_data', 15, 4 );

/*
 *
 * Co-Cart Plugin: 
 * Return more item details
 *   Sometimes you just need more product data for the items added. You can do so using cocart_cart_contents filter.
 *
 *   Parameter       Type	    Description
 *   $cart_contents	array	    An array of items added to the cart.
 *   $item_key	    string	    Unique generated ID for the item in cart.
 *   $cart_item	    array	    An array of details of the item in the cart.
 *   $_product	    object	    The product data of the item.
 */
function return_more_product_data( $cart_contents, $item_key, $cart_item, $_product ) {
    $cart_contents[$item_key]['sku'] = $_product->get_sku();

    return $cart_contents;
}


add_filter( 'wc_cart_rest_api_cart_item_product', 'prepare_cart_item_product', 15, 3 );
function prepare_cart_item_product( $cart_item_data, $cart_item, $item_key ) {
    $cart_item_data['sku'] = 'skuuuuuuuuu'; //$_product->get_sku();

    return $cart_item_data;
}
