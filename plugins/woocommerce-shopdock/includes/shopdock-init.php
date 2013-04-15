<?php
/**
 * Shopdock Init Functions
 *
 * Get ride woocommerce default
 *
 * @author 		Themify
 * @category 	Core
 * @package 	Shopdock
 */

/**
 * Add custom image size
 */
function shopdock_custom_image() {
	add_image_size('cart_thumbnail', 65, 65, true);
}
add_action( 'init', 'shopdock_custom_image' );
