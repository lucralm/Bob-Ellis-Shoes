<?php
/*
Plugin Name: WooCommerce Shopdock
Plugin URI: http://woothemes.com/woocommerce/
Description: Add an Ajax shop dock to any theme powered with WooCommerce. Users can add or remove item to the cart with a single click. The cart total and quantity are updated instantly. The layout is mobile ready (responsive).
Version: 1.0.4
Author: Themify
Author URI: http://themify.me
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'a2bd59e22ba77754934b9b341ee252cb', '138584' );

/**
 * Localisation
 **/
load_plugin_textdomain( 'wc_shopdock', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

if ( is_woocommerce_active() ) {

	add_action( 'plugins_loaded', 'woocommerce_shopdock_init' );

	function woocommerce_shopdock_init() {

		// Setup Global Variables
		if ( ! defined( 'SHOPDOCK_NAME' ) )
		    define( 'SHOPDOCK_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );

		if ( ! defined( 'SHOPDOCK_DIR' ) )
		    define( 'SHOPDOCK_DIR', WP_PLUGIN_DIR . '/' . SHOPDOCK_NAME );

		if ( ! defined( 'SHOPDOCK_URL' ) )
		    define( 'SHOPDOCK_URL', WP_PLUGIN_URL . '/' . SHOPDOCK_NAME );

		if ( ! defined( 'SHOPDOCK_TEMPLATE_URL' ) )
		    define( 'SHOPDOCK_TEMPLATE_URL', 'shopdock/' );

		add_action( 'woocommerce_shopdock_skin', 'default' );
		add_action( 'woocommerce_shopdock_position', 'top-left' );

		/**
		 * Include functions and scripts
		 **/
		include_once SHOPDOCK_DIR . '/' . 'includes' . '/' . 'shopdock-init.php';
		include_once SHOPDOCK_DIR . '/' . 'includes' . '/' . 'shopdock-admin.php';
		include_once SHOPDOCK_DIR . '/' . 'includes' . '/' . 'shopdock-templates.php';
		include_once SHOPDOCK_DIR . '/' . 'includes' . '/' . 'shopdock-scripts.php';
		include_once SHOPDOCK_DIR . '/' . 'includes' . '/' . 'shopdock-hooks.php';

	}

}