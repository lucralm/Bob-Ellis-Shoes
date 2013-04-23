<?php
/*
Plugin Name: WooCommerce Menu Cart
Plugin URI: www.wpovernight.com/plugins
Description: Woocommerce plugin that places a cart icon with number of items and total cost in the menu bar. Activate the plugin, set your options and you're ready to go! Will automatically conform to your theme styles.
Version: 2.0.1
Author: Jeremiah Prummer, Ewout Fernhout
Author URI: www.wpovernight.com/about
License: GPL2
*/
/*  Copyright 2012 Jeremiah Prummer (email : support@wpovernight.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

class WcMenuCart {     

    /**
     * Construct.
     */
    public function __construct() {
		global $options;
		$this->options = get_option('wcmenucart');

		$this->includes();
		$this->settings = new WcMenuCart_Settings();
				
        add_action( 'plugins_loaded', array( &$this, 'wcmenucart_languages' ), 0 );

		add_action('wp_print_styles', array( &$this, 'load_styles' ), 15 );

		//grab menu names
		if ( isset( $this->options['menu_name_1'] ) && $this->options['menu_name_1'] != '0' ) {
			add_filter( 'wp_nav_menu_' . $this->options['menu_name_1'] . '_items', array( &$this, 'add_itemcart_to_menu' ) , 10, 2 );
		}

		add_filter('add_to_cart_fragments', array( &$this, 'wcmenucart_add_to_cart_fragment' ) );

		register_activation_hook( __FILE__, array( 'WcMenuCart_Settings', 'default_settings' ) );
    }

	/**
	 * Load additional classes and functions
	 */
	public function includes() {
		include_once( 'includes/wcmenucart-settings.php' );
	}


    /**
     * Load translations.
     */
    public function wcmenucart_languages() {
		load_plugin_textdomain( 'wcmenucart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

     
    /**
     * Load CSS
     */
	public function load_styles() {
		global $options;
		
		//Load cart icon css		
		if (isset($this->options['icon_display'])) {
			wp_register_style( 'wcmenucart-icons', plugins_url( '/css/wcmenucart-icons.css', __FILE__ ), array(), '', 'all' );
			wp_enqueue_style( 'wcmenucart-icons' );
		}
		
		//Load plugin specific css
		wp_register_style( 'wcmenucart', plugins_url( '/css/wcmenucart-main.css', __FILE__ ), array(), '', 'all' );
		wp_enqueue_style( 'wcmenucart' );
	}
	
    /**
     * Add Menu Cart to menu
	 * 
	 * @return menu items including cart
     */
	public function add_itemcart_to_menu( $items ) {
		global $options;
		$classes = 'wcmenucart-display-'.$this->options['items_alignment'];

		$items .= '<li class="'.$classes.'">' . $this->wcmenucart_menu_item() . '</li>';
		return $items;
	}

	public function wcmenucart_add_to_cart_fragment( $fragments ) {
		$fragments['a.wcmenucart-contents'] = $this->wcmenucart_menu_item();
		return $fragments;
	}

	public function wcmenucart_menu_item() {
		global $woocommerce;
		global $options;
	
		$viewing_cart = __('View your shopping cart', 'wcmenucart');
		$start_shopping = __('Start shopping', 'wcmenucart');
		$cart_url = $woocommerce->cart->get_cart_url();
		$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
		$cart_contents_count = $woocommerce->cart->cart_contents_count;
		$cart_contents = sprintf(_n('%d item', '%d items', $cart_contents_count, 'wcmenucart'), $cart_contents_count);
		$cart_total = $woocommerce->cart->get_cart_total();
	
		if ($cart_contents_count > 0 || isset($this->options['always_display'])) {
			if ($cart_contents_count == 0) {
				$menu_item = '<a class="wcmenucart-contents" href="'. $shop_page_url .'" title="'. $start_shopping .'">';
			} else {
				$menu_item = '<a class="wcmenucart-contents" href="'. $cart_url .'" title="'. $viewing_cart .'">';
			}
			
			if (isset($this->options['icon_display'])) {
				$menu_item .= '<i class="wcmenucart-icon-shopping-cart-'.$this->options['cart_icon'].'"></i>';
			}
			
			switch ($this->options['items_display']) {
				case 1: //items only
					$menu_item .= $cart_contents;
					break;
				case 2: //price only
					$menu_item .= $cart_total;
					break;
				case 3: //items & price
					$menu_item .= $cart_contents.' - '. $cart_total;
					break;
			}
			$menu_item .= '</a>';
		}
		return $menu_item;		
	}
	
}


/**
 * WooCommerce fallback notice.
 *
 * @return string Fallack notice.
 */
function wcmenucart_fallback_notice() {
    $message = '<div class="error">';
        $message .= '<p>' . sprintf( __( 'WooCommerce Menu Cart depends on <a href="%s">WooCommerce</a> to work!' , 'wcmenucart' ), 'http://wordpress.org/extend/plugins/woocommerce/' ) . '</p>';
    $message .= '</div>';

    echo $message;
}

/**
 * Check if WooCommerce is active.
 *
 * Ref: http://wcdocs.woothemes.com/codex/extending/create-a-plugin/.
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    $wcMenuCart = new WcMenuCart();
} else {
    add_action( 'admin_notices', 'wcmenucart_fallback_notice' );
}