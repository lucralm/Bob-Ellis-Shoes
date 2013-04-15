<?php
/*
	Plugin Name: WooCommerce FedEx Shipping
	Plugin URI: http://woothemes.com/woocommerce
	Description: Obtain shipping rates dynamically via the FedEx API for your orders.
	Version: 3.0.1
	Author: WooThemes
	Author URI: http://woothemes.com

	Copyright: 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	Developers: https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/index.html
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '1a48b598b47a81559baadef15e320f64', '18620' );

/**
 * Plugin activation check
 */
function wc_fedex_activation_check(){
	if ( ! class_exists( 'SoapClient' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die( 'Sorry, but you cannot run this plugin, it requires the <a href="http://php.net/manual/en/class.soapclient.php">SOAP</a> support on your server/hosting to function.' );
	}
}

register_activation_hook( __FILE__, 'wc_fedex_activation_check' );

/**
 * Localisation
 */
load_plugin_textdomain( 'wc_fedex', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * Plugin page links
 */
function wc_fedex_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=shipping&section=WC_Shipping_Fedex' ) . '">' . __( 'Settings', 'wc_fedex' ) . '</a>',
		'<a href="http://support.woothemes.com/">' . __( 'Support', 'wc_fedex' ) . '</a>',
		'<a href="http://wcdocs.woothemes.com/user-guide/fedex/">' . __( 'Docs', 'wc_fedex' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_fedex_plugin_links' );

/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	/**
	 * woocommerce_init_shipping_table_rate function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_fedex_init() {
		include_once( 'classes/class-wc-shipping-fedex.php' );
	}

	add_action( 'woocommerce_shipping_init', 'wc_fedex_init' );

	/**
	 * wc_fedex_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wc_fedex_add_method( $methods ) {
		$methods[] = 'WC_Shipping_Fedex';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'wc_fedex_add_method' );

	/**
	 * wc_fedex_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_fedex_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	add_action( 'admin_enqueue_scripts', 'wc_fedex_scripts' );
}