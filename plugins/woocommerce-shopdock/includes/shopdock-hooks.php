<?php
/**
 * Shopdock Hook Actions
 *
 * Get ride woocommerce action hook and plugin hook
 *
 * @author 		Themify
 * @category 	Actions
 * @package 	Shopdock
 */

/**
 * Shopdock Dock Bar
 **/
add_action( 'wp_footer', 'shopdock_dock_bar', 10 );

/* Products Loop */
/* remove default hook from woocommerce */
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 ); // add to cart button
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 ); // product thumbnail

/**
 * Add action shopdock loop add to cart
 **/
add_action( 'woocommerce_after_shop_loop_item', 'shopdock_template_loop_add_to_cart', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'shopdock_template_loop_product_thumbnail', 10 ); // product thumbnail

/**
 * single product on lightbox action
 **/
add_action( 'shopdock_single_product_image_ajax', 'woocommerce_show_product_sale_flash', 20 );
add_action( 'shopdock_single_product_image_ajax', 'woocommerce_show_product_images', 20 );
add_action( 'shopdock_single_product_ajax_content', 'woocommerce_template_single_add_to_cart', 10 );
add_action( 'shopdock_single_product_price', 'woocommerce_template_single_price', 10 );

/**
 * Shopdock hook style and js
 **/
add_action( 'wp_print_styles', 'shopdock_enqueue_styles' );
add_action( 'wp_enqueue_scripts', 'shopdock_enqueue_scripts', 20 );
add_action( 'wp_print_styles', 'shopdock_skins_style' );