<?php
/**
 * Loop Add to Cart
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product;

if ( ! $product->is_purchasable() ) return;
?>

<?php if ( $product->is_in_stock() ) :

		// set class of add item position
		$add_item_class = get_option('woocommerce_shopdock_position');

		switch ( $product->product_type ) {
			case "variable" :
			case "grouped" :
			case "external" :
				$link 	= get_permalink( $product->id );
				$label 	= __( 'View', 'woocommerce' );
				$add_item_class  .= " view-item";
			break;
			default :
				$link 	= apply_filters( 'add_to_cart_url', esc_url( $product->add_to_cart_url() ) );
				$label 	= apply_filters( 'add_to_cart_text', __( 'Add to cart', 'woocommerce' ) );
				$add_item_class  .= " add-item";
			break;
		}

		echo apply_filters( 'woocommerce_loop_add_to_cart_link', sprintf('<a href="%s" rel="nofollow" data-product_id="%s" class="add_to_cart_button product_type_%s %s">%s</a>', $link, $product->id, $product->product_type, $add_item_class, $label ), $link, $product, $label );

	?>

<?php endif; ?>