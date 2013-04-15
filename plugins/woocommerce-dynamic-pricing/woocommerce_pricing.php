<?php
/*
  Plugin Name: WooCommerce Dynamic Pricing
  Plugin URI: http://www.woothemes.com/woocommerce
  Description: WooCommerce Dynamic Pricing lets you configure dynamic pricing rules for products, categories and members. For WooCommerce 1.4+
  Version: 1.6.1
  Author: Lucas Stark
  Author URI: http://lucasstark.com
  Requires at least: 3.3
  Tested up to: 3.3

  Copyright: © 2009-2011 Lucas Stark.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '9a41775bb33843f52c93c922b0053986', '18643' );

if ( is_woocommerce_active() ) {

    require 'classes/woocommerce_dynamic_pricing.class.php';
    require 'classes/woocommerce_pricing_base.class.php';
    require 'classes/woocommerce_pricing_by_totals.class.php';
    require 'classes/woocommerce_pricing_by_membership.class.php';
    require 'classes/woocommerce_pricing_by_category.class.php';
    require 'classes/woocommerce_pricing_by_product.class.php';

    if (is_admin()) {
        require 'admin/admin-init.php';
    }

    global $woocommerce_pricing;
    $woocommerce_pricing = new woocommerce_dynamic_pricing();
}