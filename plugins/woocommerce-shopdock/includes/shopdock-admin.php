<?php
/**
 * Shopdock Admin Pages
 *
 * Admin page setting
 *
 * @author    Themify
 * @category  Core
 * @package   Shopdock
 */

global $shopdock_settings;

$shopdock_settings = array(
	array( 'name' => __( 'Shopdock options', 'shopdock' ), 'type' => 'title', 'desc' => '', 'id' => 'shopdock' ),
	array(
		'name' 		=> __('Skin', 'wc_shopdock'),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_shopdock_skin',
		'type' 		=> 'select',
		'std'		=> 'default',
		'options'	=> array(
			'default' => 'default',
			'black' => 'black',
			'blue' => 'blue',
			'green' => 'green',
			'orange' => 'orange',
			'pink' => 'pink',
			'purple' => 'purple',
			'red' => 'red'
		)
	),
	array(
		'name' => __('Add item button position', 'wc_shopdock'),
		'desc' 		=> '',
		'id' 		=> 'woocommerce_shopdock_position',
		'type' 		=> 'select',
		'std'		=> 'top-left',
		'options'	=> array(
			'top-left' => 'top-left',
			'top-right' => 'top-right'
		)
	),
	array( 'type' => 'sectionend', 'id' => 'shopdock'),
);

function woocommerce_shopdock_ext_settings() {
	global $shopdock_settings;

	woocommerce_admin_fields( $shopdock_settings );
}
add_action( 'woocommerce_settings_general_options_after', 'woocommerce_shopdock_ext_settings' );

function woocommerce_shopdock_save_ext_settings() {
	global $shopdock_settings;

	woocommerce_update_options( $shopdock_settings );
}
add_action('woocommerce_update_options_general', 'woocommerce_shopdock_save_ext_settings' );