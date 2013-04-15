<?php
/*
Plugin Name: WooCommerce Google Product Feed
Plugin URI: http://www.leewillis.co.uk/wordpress-plugins/?utm_source=wordpress&utm_medium=www&utm_campaign=woocommerce-gpf
Description: Woocommerce extension that allows you to more easily populate advanced attributes into the Google Merchant Centre feed
Author: Lee Willis
Version: 2.1
Author URI: http://www.leewillis.co.uk/
License: GPLv3
*/


/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 **/
if (is_admin()) {
    $woo_plugin_updater_google_feed = new WooThemes_Plugin_Updater( __FILE__ );
    $woo_plugin_updater_google_feed->api_key = '231ef495a163f7f1c8f57beef93d0502';
    $woo_plugin_updater_google_feed->init();
}

if ( is_admin() ) {

    require_once ( 'woocommerce-gpf-common.php' );
    require_once ( 'woocommerce-gpf-admin.php' );

}



/**
 * Bodge ffor WPEngine.com users - provide the feed at a URL that doesn't
 * rely on query arguments as WPEngine don't support URLs with query args
 * if the requestor is a googlebot. #broken
 */
function woocommerce_gpf_endpoints() {
    add_rewrite_endpoint('woocommerce_gpf', EP_ROOT );
}   
add_action ( 'init', 'woocommerce_gpf_endpoints' );



/**
 * Include the relevant files dependant on the page request type
 */
function woocommerce_gpf_includes() {

    global $wp_query;

    if ( isset ( $wp_query->query_vars['woocommerce_gpf'] ) ) {
        $_REQUEST['action'] = 'woocommerce_gpf';
        $_REQUEST['feed_format'] = $wp_query->query_vars['woocommerce_gpf'];
    }

    if ( isset ( $_REQUEST['xmlformat'] ) ) {
        $_REQUEST['feed_format'] = $_REQUEST['xmlformat'];
    }

    if ( ( isset ( $_REQUEST['action'] ) && 'woocommerce_gpf' == $_REQUEST['action'] ) ) {

        require_once ( 'woocommerce-gpf-common.php' );
        require_once ( 'woocommerce-gpf-feed.class.php' );

        if ( ! isset ( $_REQUEST['feed_format'] ) || $_REQUEST['feed_format'] == 'google' ) {

            require_once 'woocommerce-gpf-feed-google.php';

        } else if ( $_REQUEST['feed_format'] == 'bing' ) {

            require_once 'woocommerce-gpf-feed-bing.php';

        }

        require_once ( 'woocommerce-gpf-frontend.php' );

    }

}

add_action ( 'template_redirect', 'woocommerce_gpf_includes');



/**
 * Create database tabe to cache the Google product taxonomy.
 */
function woocommerce_gpf_install() {

    global $wpdb;

    $table_name = $wpdb->prefix . "woocommerce_gpf_google_taxonomy";

    $sql = "CREATE TABLE $table_name (
                         taxonomy_term text,
                         search_term text
                             )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook ( __FILE__, 'woocommerce_gpf_install' );
