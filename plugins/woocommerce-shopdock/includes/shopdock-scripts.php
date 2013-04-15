<?php
/**
 * Shopdock Scripts Fuctions
 *
 * Get ride woocommerce scripts and plugins scripts
 *
 * @author 		Themify
 * @category 	Script
 * @package 	Shopdock
 */

/**
 * Load the styles required
 **/
function shopdock_enqueue_styles() {
	wp_enqueue_style( 'shopdock-style', SHOPDOCK_URL . '/' . 'css/' . 'shopdock.css', '', '1.0' );
}

/**
 * Load the scripts required
 **/
function shopdock_enqueue_scripts() {
	wp_enqueue_script( 'shopdock-jcarousel-script', SHOPDOCK_URL . '/' . 'js/' . 'jquery.jcarousel.min.js', array('jquery'), '1.0', true );
	wp_enqueue_script( 'shopdock-script', SHOPDOCK_URL . '/' . 'js/' . 'shopdock.js', array('jquery'), '1.0', true );

	// Queue frontend scripts conditionally
	if ( get_option( 'woocommerce_enable_ajax_add_to_cart' ) == 'yes' )
		wp_enqueue_script( 'shopdock-add-to-cart', SHOPDOCK_URL . '/' . 'js/' . 'shopdock-add-to-cart.js', array('jquery'), '1.0', true );
}

/**
 * Load Skins.
 *
 * @access public
 * @return void
 */
function shopdock_skins_style() {
	$skins = get_option( 'woocommerce_shopdock_skin' );

	if ( $skins !== 'default' && ! empty( $skins ) )
		wp_enqueue_style( 'shopdock-skin-style', SHOPDOCK_URL .'/' . 'css/skins/' . $skins .'/' . 'style.css' );
}

/**
 * Add body class dock if the dock is visible
 *
 * @access public
 * @param mixed $classes
 * @return void
 */
function shopdock_class_names($classes) {
	global $woocommerce;

	// add 'dock-on' to the $classes array
	if ( sizeof( $woocommerce->cart->get_cart() ) > 0 )
		$classes[] = 'dock-on';

	// return the $classes array
	return $classes;
}

add_filter( 'body_class','shopdock_class_names' );