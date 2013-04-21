<?php
/*
Plugin Name: Woocommerce Product Slider
Plugin URI: http://www.zippydev.com/
Description: Woocommerce Product Slider and Carousel Plugin
Version: 1.4
Author: Kiran Polapragada
Author URI: http://www.zippydev.com/
Text Domain: zippydev
*/

global $wpdb; //global wpdb var and version
$zdev_wcps_pluginname = 'Woocommerce Product Slider';
$zdev_wcps_pluginversion = "1.3";

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	ob_start();
	
	//include required files based on admin or site
	
	if (is_admin()) { //for admin
	
		//admin  files 
		add_action('init', 'zdev_wcps_initialize');	
		
	}  else { // for site
		require_once("class-zdev-wcps-shortcodes.php"); //shortcodes inclusion	
	
	}
	
	//register scripts	
	add_action( 'wp_enqueue_scripts','zdev_wcps_register_scripts');
	add_action('wp_head', 'zdev_wcps_wp_head');
	add_action('admin_head', 'zdev_wcps_wp_head');

	/*
	* function zdev_wcps_wp_head - called for head section , adds the plugin url in the head tag
	*/
	function zdev_wcps_wp_head() {
		echo '<script>var woo_product_slider_url ="'.plugin_dir_url( __FILE__ ).'";</script>';
	}
	
	/*
	* function zdev_wcps_register_scripts - callaback function, registers the scripts and styles
	*/
	
	function zdev_wcps_register_scripts() {
	
		wp_register_script('jquery_flex', plugin_dir_url( __FILE__ ) .'FlexSlider/jquery.flexslider-min.js', 'jquery');	
		wp_register_style('flexslider-style', plugin_dir_url( __FILE__ ) . 'FlexSlider/flexslider.css', array(), '1', 'all' ); 
	
	}
	
	
	/*
	* function zdev_wcps_initialize - initializes the plugin
	*/
	
	function zdev_wcps_initialize() {
	
		//add button to the visual editor
		if ( current_user_can('edit_posts') &&  current_user_can('edit_pages')){  
	    
		 add_filter('mce_external_plugins', 'add_zdev_wcps_plugin');  
	     add_filter('mce_buttons', 'register_add_zdev_wcps_button');  
	   
		}  
	   
	}
	
	
	/*
	* function register_add_zdev_wcps_button - callback function
	*/
	
	function register_add_zdev_wcps_button($buttons) {
	
	   array_push($buttons, "woo_product_slider");
	   return $buttons;
	
	}
	
	
	/*
	* function add_zdev_wcps_plugin - callback function
	*/
	function add_zdev_wcps_plugin($plugin_array) {
	
	   $plugin_array['woo_product_slider'] =  plugin_dir_url( __FILE__ ).'js/customcodes.js';
	   return $plugin_array;
	
	}
}
?>