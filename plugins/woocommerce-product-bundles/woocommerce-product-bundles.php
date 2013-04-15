<?php
/*
Plugin Name: WooCommerce Product Bundles
Plugin URI: http://woothemes.com/woocommerce
Description: WooCommerce extension for creating configurable product bundles, kits and assemblies.
Author: SomewhereWarm
Author URI: http://www.somewherewarm.net/
Version: 2.53
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'fbca839929aaddc78797a5b511c14da9', '18716' );

if ( is_woocommerce_active() ) {

	class WC_Product_Bundles {

		var $is_wc_v2 = false;

		public function __construct() {

			add_action( 'plugins_loaded', array( &$this, 'woo_bundles_plugins_loaded' ) );
			add_action( 'init', array( &$this, 'woo_bundles_init' ) );
			add_action( 'admin_init', array( &$this, 'woo_bundles_admin_init') );
		}

		function is_wc_v2() {
			return $this->is_wc_v2;
		}

		function woo_bundles_plugin_url() {
			return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
		}

		function woo_bundles_plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}


		function woo_bundles_plugins_loaded() {

			global $woocommerce;

			// Admin Hooks & Scripts

			if ( function_exists( 'get_product' ) && version_compare( $woocommerce->version, '2.0.0' ) >= 0 ) {
				$this->is_wc_v2 = true;
			}

			include( 'classes/class-wc-product-bundle.php' );

			// Admin jquery
			add_action( 'admin_enqueue_scripts', array( &$this, 'woo_bundles_admin_scripts' ), 11 );
			// Front end variation select box jquery for multiple variable products
			add_action( 'wp_enqueue_scripts', array( &$this, 'woo_bundles_frontend_scripts' ) );

			// Creates the admin panel tab 'Bundled Products'
			add_action( 'woocommerce_product_write_panel_tabs', array( &$this, 'woo_bundles_product_write_panel_tab' ) );

			// Creates the panel for selecting bundled product options
			add_action( 'woocommerce_product_write_panels', array( &$this, 'woo_bundles_product_write_panel' ) );
			add_action( 'woocommerce_product_options_stock', array( &$this, 'woo_bundles_stock_group' ) );

			add_filter( 'product_type_options', array( &$this, 'woo_bundles_type_options' ) );

			// Processes and saves the necessary post metas from the selections made above
			add_action( 'woocommerce_process_product_meta_bundle', array( &$this, 'woo_bundles_process_bundle_meta' ) );

			// Allows the selection of the 'bundled product' type
			add_filter( 'product_type_selector', array( &$this, 'woo_bundles_product_selector_filter' ) );

			// Front End Hooks

			// Load bundle data from session into the cart
			add_filter( 'woocommerce_get_cart_item_from_session', array( &$this, 'woo_bundles_get_cart_data_from_session' ), 10, 2 );

			// Sync quantities of bundled items with bundle quantity
			add_action( 'woocommerce_after_cart_item_quantity_update', array( &$this, 'woo_bundles_update_quantity_in_cart' ), 1, 2 );
			add_action( 'woocommerce_before_cart_item_quantity_zero', array( &$this, 'woo_bundles_update_quantity_in_cart' ), 1, 2 );

			// Filter these WC_Product functions when product type is 'bundle'
			add_filter( 'woocommerce_is_sold_individually', array( &$this, 'woo_bundles_sold_individually' ), 10, 2 );
			add_filter( 'woocommerce_get_price', array( &$this, 'woo_bundles_get_price' ), 10, 2 );

			// Validate bundle add-to-cart
			add_filter( 'woocommerce_add_to_cart_validation', array( &$this, 'woo_bundles_validation' ), 10, 3);

			// Add bundle-specific cart item data
			add_filter( 'woocommerce_add_cart_item_data', array( &$this, 'woo_bundles_add_cart_item_data' ), 10, 2 );

			// Add bundled items to the cart
			add_action( 'woocommerce_add_to_cart', array( &$this, 'woo_bundles_add_bundle_to_cart' ), 10, 6 );


			// Front End Hooks (contd.)

			// Single product template for product bundles
			add_action( 'woocommerce_bundle_add_to_cart', array( &$this, 'woo_bundles_add_to_cart' ) );

			// Sync quantities of bundled items with bundle quantity
			add_filter( 'woocommerce_cart_item_quantity', array( &$this, 'woo_bundles_cart_item_quantity' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_remove_link', array( &$this, 'woo_bundles_cart_item_remove_link' ), 10, 2 );

			// Add 'part of' text in cart
			add_filter( 'woocommerce_get_item_data',  array( &$this, 'woo_bundles_get_item_data' ), 10, 2 );

			// Filter add_to_cart_url & add_to_cart_text when product type is 'bundle'
			add_filter( 'add_to_cart_url', array( &$this, 'woo_bundles_add_to_cart_url' ), 10 );
			add_filter( 'add_to_cart_text', array( &$this, 'woo_bundles_add_to_cart_text' ), 10 );

			// Filter price output shown in cart, review-order & order-details templates
			add_action( 'woocommerce_order_formatted_line_subtotal', array( &$this, 'woo_bundles_order_item_subtotal' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_price_html', array( &$this, 'woo_bundles_cart_item_price_html' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( &$this, 'woo_bundles_item_subtotal' ), 10, 3 );
			add_filter( 'woocommerce_checkout_item_subtotal', array( &$this, 'woo_bundles_item_subtotal' ), 10, 3 );

			// Change the tr class attributes when displaying bundled items in templates
			add_filter( 'woocommerce_cart_table_item_class', array( &$this, 'woo_bundles_table_item_class' ), 10, 3 );
			add_filter( 'woocommerce_order_table_item_class', array( &$this, 'woo_bundles_table_item_class' ), 10, 3 );
			add_filter( 'woocommerce_checkout_table_item_class', array( &$this, 'woo_bundles_table_item_class' ), 10, 3 );

			// Load the bundled product class
			if ( ! $this->is_wc_v2() ) {
				add_action( 'woocommerce_before_single_product', array( &$this, 'woo_bundles_init_bundled_product' ), 100 );
				add_action( 'woocommerce_before_shop_loop_item', array( &$this, 'woo_bundles_init_bundled_product' ), 100 );
			}
			// Modify cart items for bundled pricing strategy
			add_filter( 'woocommerce_add_cart_item', array( &$this, 'woo_bundles_add_cart_item_filter' ), 10, 2 );

			// Modify order items to include bundled_by info
			add_filter( 'new_order_item', array( &$this, 'woo_bundles_new_order_item_filter' ), 10, 2 );

			// Set empty price message
			add_filter( 'woocommerce_empty_price_html', array( &$this, 'woo_bundles_empty_price' ), 10, 2 );

			// Filter cart widget items
			add_filter( 'woocommerce_widget_cart_item_visible', array( &$this, 'woo_bundles_cart_widget_filter' ), 10, 3 );

			// Filter cart item count
			add_filter( 'woocommerce_cart_contents_count',  array( &$this, 'woo_bundles_cart_contents_count' ) );

			// Support for bundled item addons
			// add_action( 'woocommerce_bundled_product_add_to_cart', array($this, 'woo_bundles_addons_support') );

			// Debug
			// Add_action('woocommerce_before_cart_contents', array($this, 'woo_bundles_before_cart') );

		}


		/**
		 * Activation script
		 **/
		function woo_bundles_admin_init() {

			// if 'bundle' term exists, get rid of it
			$bundle_term_id = term_exists( 'bundle' );

			if ( $bundle_term_id && ! get_term_by( 'slug', 'bundle', 'product_type' ) ) {

				$taxonomies = get_taxonomies( '', 'names' );

				foreach ( $taxonomies as $taxonomy ) {
					$bundle_term = get_term_by( 'id', $bundle_term_id, $taxonomy );
					if ( $bundle_term ) {
						wp_update_term( $bundle_term->term_id, $taxonomy, array( 'slug' => 'bundle-99' ) );
						return;
					}
				}
			}

		}


		function woo_bundles_init() {

			load_plugin_textdomain( 'woo-bundles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Filter bundled item attributes based on active variation filters
			add_filter( 'woocommerce_attribute',  array( $this, 'woo_bundles_attribute' ), 10, 3 );
		}


		/**
		 * Support for bundled item addons
		 **/
		function woo_bundles_addons_support( $post_id ) {

			global $woocommerce_product_addons;

			if ( isset( $woocommerce_product_addons ) )
				$woocommerce_product_addons->product_addons( $post_id );
		}


		/**
		 * Validate bundle add-to-cart
		 **/
		function woo_bundles_validation( $add, $product_id, $quantity ) {

			global $woocommerce;

			// Get product type
			$transient_name = 'wc_product_type_' . $product_id;
			$product_type = '';

			if ( false === ( $product_type = get_transient( $transient_name ) ) ) {
				$terms = wp_get_object_terms( $product_id, 'product_type', array('fields' => 'names') );
				$product_type = (isset($terms[0])) ? sanitize_title($terms[0]) : 'simple';
				set_transient( $transient_name, $product_type );
			}


			if ( $product_type == 'bundle' ) {

				if ( isset( $_REQUEST['add-product-to-cart'] ) && is_array( $_REQUEST['add-product-to-cart'] ) ) {

					$valid_ids = get_post_meta( $product_id, '_bundled_ids', true );


					// Check request and prepare variation stock check data
					$stock_check_data = array();

					foreach ( $_REQUEST['add-product-to-cart'] as $bundled_item_id => $bundled_product_type ) {

						// Prevent people from fucking around
						if ( ! in_array( $bundled_item_id, $valid_ids ) ) {
							return false;
						}

						$quantity 	= get_post_meta( $product_id, 'bundle_quantity_'.$bundled_item_id, true );
						$quantity 	= ( isset( $quantity ) && $quantity > 0 ) ? (int) $quantity : 1;
						$quantity	= ( isset($_REQUEST['quantity']) ) ? (int) $_REQUEST['quantity'] * $quantity : 1 * $quantity ;

						$sep 	= explode( '_', $bundled_item_id );
						$id 	= $sep[0];

						if ( $bundled_product_type == 'variable' ) {

							if ( isset( $_REQUEST['variation_id'][$bundled_item_id] ) && is_numeric( $_REQUEST['variation_id'][$bundled_item_id] ) && $_REQUEST['variation_id'][$bundled_item_id] > 1 ) {

								$stock_check_data[$id]['type'] = 'variable';

								$variation_id 		= $_REQUEST['variation_id'][$bundled_item_id];
								$variation_stock 	= get_post_meta( $variation_id, '_stock', true );

								if ( !isset( $stock_check_data[$id]['variations'] ) )
									$stock_check_data[$id]['variations'] = array();

								if ( !isset( $stock_check_data[$id]['managed_quantities'] ) )
									$stock_check_data[$id]['managed_quantities'] = array();

								if ( !in_array( $variation_id, $stock_check_data[$id]['variations'] ) )
									$stock_check_data[$id]['variations'][] = $variation_id;

								// If stock is managed on a variation level
								if ( isset( $variation_stock ) && $variation_stock !== '' ) {

									// If a stock-managed variation is added to the cart multiple times,
									// its stock must be checked for the sum of all quantities
									if ( isset( $stock_check_data[$id]['managed_quantities'][$variation_id] ) )
										$stock_check_data[$id]['managed_quantities'][$variation_id] += $quantity;
									else
										$stock_check_data[$id]['managed_quantities'][$variation_id] = $quantity;

								}
								else {

									// Non-stock-managed variations of the same item
									// must be stock-checked together
									if ( isset( $stock_check_data[$id]['quantity'] ) )
										$stock_check_data[$id]['quantity'] += $quantity;
									else {
										$stock_check_data[$id]['quantity'] = $quantity;
									}
								}

							}
							else {
		    					$woocommerce->add_error( __('Please choose product options&hellip;', 'woocommerce') );
								return false;
							}

							// Verify all attributes for the variable product were set - TODO: verify with filters

							$attributes = ( array ) maybe_unserialize( get_post_meta( $bundled_item_id, '_product_attributes', true ) );
				    		$variations = array();
				    		$all_set 	= true;

				    		foreach ( $attributes as $attribute ) {

								if ( !$attribute['is_variation'] ) continue;

								$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );

								if ( !empty( $_REQUEST[$taxonomy][$id] ) ) {
									// Get value from post data
									$value = esc_attr( stripslashes( $_REQUEST[$taxonomy][$id] ) );

									// Use name so it looks nicer in the cart widget/order page etc - instead of a sanitized string
									$variations[esc_attr($attribute['name'])] = $value;
								} else {
									$all_set = false;
								}

							}

							if ( !$all_set ) {
								$woocommerce->add_error( __('Please choose product options&hellip;', 'woocommerce') );
								return false;
							}


						}
						elseif ( $bundled_product_type == 'simple' ) {
							$stock_check_data[$id]['type'] = 'simple';
							$stock_check_data[$id]['quantity'] = $quantity;

						}

					}


					// Check stock for bundled items one by one
					// If out of stock, don't proceed

					foreach ( $stock_check_data as $item_id => $data ) {

						if ( $data['type'] == 'variable' ) {

							foreach( $data['variations'] as $variation_id ) {

								if ( array_key_exists( $variation_id, $data['managed_quantities'] ) )
									$quantity = $data['managed_quantities'][$variation_id];
								else
									$quantity = $data['quantity'];

								if ( ! $this->validate_stock( $item_id, $variation_id, $quantity, false, false ) )
									return false;

							}

						}
						elseif ( $data['type'] == 'simple' ) {

							// if out of stock, don't proceed
							if ( ! $this->validate_stock( $item_id, '', $data['quantity'], false, false ) ) {
								return false;
							}

						}

					}

				} else {
					return false;
				}
			}

			return $add;
		}


		/**
		 * Adds bundle specific cart-item data
		 * The 'stamp' var is a unique identifier for that particular bundle configuration
		 **/
		function woo_bundles_add_cart_item_data( $cart_item_data, $product_id ) {

			// Get product type
			$transient_name = 'wc_product_type_' . $product_id;
			$product_type = '';

			if ( false === ( $product_type = get_transient( $transient_name ) ) ) {
				$terms = wp_get_object_terms( $product_id, 'product_type', array('fields' => 'names') );
				$product_type = (isset($terms[0])) ? sanitize_title($terms[0]) : 'simple';
				set_transient( $transient_name, $product_type );
			}

			if ( $product_type == 'bundle' && isset( $_REQUEST['add-product-to-cart'] ) && is_array( $_REQUEST['add-product-to-cart'] ) ) {

				// Create a unique stamp id with the bundled items' configuration
				$stamp = array();

				foreach ( $_REQUEST['add-product-to-cart'] as $bundled_product_id => $bundled_product_type ) {

					$sep 	= explode( '_', $bundled_product_id );
					$id 	= $sep[0];

					if ( $bundled_product_type == 'simple' ) {

						$stamp[ $bundled_product_id ] = $bundled_product_id;
					}
					elseif ( $bundled_product_type == 'variable' ) {

						$var_stamp = '';
						$attributes 	= ( array ) maybe_unserialize( get_post_meta( $id, '_product_attributes', true ) );

						foreach ( $attributes as $attribute ) {

							if ( !$attribute['is_variation'] ) continue;

							$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );
							$value = esc_attr( stripslashes($_REQUEST[$taxonomy][$bundled_product_id]) );
							$var_stamp .= esc_attr($attribute['name']) . ': ' . $value . ', ';

						}

						$stamp[ $bundled_product_id ] = rtrim( $var_stamp, ', ');
					}
				}

				$cart_item_data['stamp'] = $stamp;
				return $cart_item_data;

			} else {
				return $cart_item_data;
			}

		}


		/**
		 * Adds bundled items to the cart.
		 * The 'bundled by' var is added to each item to identify between bundled and non-bundled instances of products.
		 **/
		function woo_bundles_add_bundle_to_cart( $bundle_cart_key, $bundle_id, $quantity, $variation_id, $variation, $cart_item_data ) {

			global $woocommerce;

			if ( isset( $cart_item_data['stamp'] ) && !isset( $cart_item_data['bundled_by'] ) ) {

				// Only attempt to add bundled items if they don't already exist
				foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
					if ( isset( $value['bundled_by'] ) && $bundle_cart_key == $value['bundled_by'] ) {
						return;
					}
				}

				// This id is unique, so that bundled and non-bundled versions of the same product will be added separately to the cart.
				$bundled_item_cart_data = array( 'bundled_by' => $bundle_cart_key, 'stamp' => $cart_item_data['stamp'] );

				// Now add all items - yay
				foreach ( $_REQUEST['add-product-to-cart'] as $bundled_item_id => $bundled_product_type ) {

					// identifier needed for fetching post meta
					$bundled_item_cart_data['bundled_item_id'] = $bundled_item_id;

					// instruct dynamic pricing extension to ignore bundled items
					$bundled_item_cart_data['dynamic_pricing_allowed'] = 'no';

					$quantity 	= get_post_meta( $bundle_id, 'bundle_quantity_'.$bundled_item_id, true );
					$quantity 	= ( isset( $quantity ) && $quantity > 0 ) ? (int) $quantity : 1;
					$quantity	= ( isset($_REQUEST['quantity']) ) ? (int) $_REQUEST['quantity'] * $quantity : 1 * $quantity ;

					$sep 	= explode( '_', $bundled_item_id );
					$id 	= $sep[0];

					if ( $bundled_product_type == 'simple' ) {
						$variation_id 	= '';
						$variations		= array();
					}
					elseif ( $bundled_product_type == 'variable' ) {

						$variation_id 	= ( int ) $_REQUEST['variation_id'][$bundled_item_id];
						$variations		= array();
						$attributes 	= ( array ) maybe_unserialize( get_post_meta( $id, '_product_attributes', true ) );

						foreach ( $attributes as $attribute ) {

							if ( !$attribute['is_variation'] ) continue;

							$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );
							$value = esc_attr( stripslashes($_REQUEST[$taxonomy][$bundled_item_id]) );
							$variations[esc_attr($attribute['name'])] = $value;

						}

					}

					$woocommerce->cart->add_to_cart( $id, $quantity, $variation_id, $variations, $bundled_item_cart_data );
				}

			}

		}

		/**
		 * Replaces add_to_cart button url with something more appropriate.
		 **/
		function woo_bundles_add_to_cart_url( $url ) {

			global $product;

			if ( $product->is_type('bundle') ) {

				if ( $product->get_available_bundle_variations() ) {
					return get_permalink( $product->id );
				} else {

					$simple_ids = get_post_meta( $product->id, '_bundled_ids', true );

					foreach( $simple_ids as $key => $id )
						$url = add_query_arg( urlencode( 'add-product-to-cart[' . $id . ']' ), 'simple', $url );
				}
			}

			return $url;
		}


		/**
		 * Replaces add_to_cart text with something more appropriate.
		 **/
			function woo_bundles_add_to_cart_text( $text ) {

			global $product;

			if ( $product->is_type('bundle') ) {
				if ( $product->get_available_bundle_variations() )
					return __('View options', 'woocommerce');
			}
			return $text;
		}


		/**
		 * Do not show bundles or bundled items, depending on the chosen pricing method
		 **/
		function woo_bundles_cart_widget_filter( $show, $cart_item, $cart_item_key ) {

			global $woocommerce;

			if ( isset( $cart_item['bundled_by'] ) ) {
				// not really necessary since we know its going to be there
				$bundle_key = $woocommerce->cart->find_product_in_cart( $cart_item['bundled_by'] );
				if ( ! empty( $bundle_key ) ) {
					$product_id = $woocommerce->cart->cart_contents[ $bundle_key ]['product_id'];
					if ( get_post_meta( $product_id, '_per_product_pricing_active', true ) == 'no' )
						return false;
				}
			}

			if ( !isset( $cart_item['bundled_by'] ) && isset( $cart_item['stamp'] ) ) {
				if ( get_post_meta( $cart_item['product_id'], '_per_product_pricing_active', true ) == 'yes' )
						return false;
			}

			return $show;

		}



		/**
		 * Filters the reported number of cart items depending on pricing strategy
		 * - per-item price: container is subtracted
		 * - bundle price: items are subtracted
		 **/
		function woo_bundles_cart_contents_count( $count ) {

			global $woocommerce;

			$cart = $woocommerce->cart->get_cart();

			$subtract = 0;

			foreach ( $cart as $key => $value ) {

				if ( isset( $value['bundled_by'] ) ) {

					$bundle_cart_id = $value['bundled_by'];
					$bundle_product_id = $cart[$bundle_cart_id]['product_id'];

					$per_product_pricing = ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;

					if ( ! $per_product_pricing ) {
						$subtract += $value['quantity'];
					}
				}

				if ( isset( $value['stamp'] ) && !isset( $value['bundled_by'] ) ) {

					$bundle_product_id = $value['product_id'];

					$per_product_pricing = ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;

					if ( $per_product_pricing ) {
						$subtract += $value['quantity'];
					}
				}
			}

			return $count - $subtract;

		}


		/**
		 * Hide attributes if they correspond to inactive variations
		 **/
		function woo_bundles_attribute( $output, $attribute, $values ) {

			global $product;

			if ( $product->is_type('bundle') && isset( $GLOBALS['listing_attributes_of'] ) ) {

				if ( $attribute['is_variation'] ) {

					$attribute_name = sanitize_title( $attribute['name'] );

					if( $product->variation_filters_active[ $GLOBALS['listing_attributes_of'] ] && is_array( $product->filtered_variation_attributes[ $GLOBALS['listing_attributes_of'] ] ) && array_key_exists( $attribute_name, $product->filtered_variation_attributes[ $GLOBALS['listing_attributes_of'] ] ) ) {

						return wpautop( wptexturize( implode( ', ', $product->filtered_variation_attributes[ $GLOBALS['listing_attributes_of'] ][ $attribute_name ]['descriptions'] ) ) );
					}

				}


			}

			return $output;
		}


		/**
		 * Change the tr class of bundled items in all templates to allow their styling
		 **/
		function woo_bundles_table_item_class( $classname, $values, $cart_item_key ) {
			if ( isset( $values['bundled_by'] ) )
				return 'bundled_table_item';
			return $classname;
		}


		/**
		 * Add bundled_by info to order items
		 **/
		function woo_bundles_new_order_item_filter( $order_item_values, $cart_item_values ) {

			global $woocommerce;

			if ( isset( $cart_item_values['bundled_by'] ) ) {
				$order_item_values['bundled_by'] = $cart_item_values['bundled_by'];

				// not really necessary since we know its going to be there
				$product_key = $woocommerce->cart->find_product_in_cart( $cart_item_values['bundled_by'] );
				if ( ! empty( $product_key ) ) {
					$product_name = $woocommerce->cart->cart_contents[ $product_key ]['data']->post->post_title;
					$order_item_values['item_meta'][] = array(
							'meta_name'    => __( 'Included with', 'woo-bundles' ),
							'meta_value' => __( $product_name ),
					);
				}

			}
			if ( isset($cart_item_values['stamp']) )
				$order_item_values['stamp'] = $cart_item_values['stamp'];

			return $order_item_values;
		}


		/**
		 * Hide the subtotal of order-items (order-details.php) depending on the bundles's pricing strategy
		 **/
		function woo_bundles_order_item_subtotal( $subtotal, $item, $order ) {

			// If it's a bundled item
			if ( isset( $item['bundled_by'] ) ) {
				$bundle_stamp = $item['stamp'];
				// find bundle item by its stamp
				foreach ( $order->get_items() as $order_item ) {
					if ( $order_item['stamp'] == $bundle_stamp && !isset( $order_item['bundled_by'] ) )
						$bundle_product_id = $order_item['id'];
				}

				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'no' )
					return '';
			}

			// If it's a bundle (grouping item)
			if ( !isset( $item['bundled_by'] ) && isset( $item['stamp'] ) ) {
				$bundle_product_id = $item['id'];
				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' )
					return '';
			}

			return $subtotal;
		}


		/**
		 * Same logic as above and below
		 **/
		function woo_bundles_cart_item_price_html($price, $values, $cart_item_key ) {
			global $woocommerce;

			if ( isset( $values['bundled_by'] ) ) {
				$bundle_cart_key = $values['bundled_by'];
				$bundle_product_id = $woocommerce->cart->cart_contents[ $bundle_cart_key ]['product_id'];
				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'no' )
					return '';
			}

			if ( !isset( $values['bundled_by'] ) && isset( $values['stamp'] ) ) {
				$bundle_product_id = $values['product_id'];
				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' )
					return '';
			}

			return $price;
		}


		/**
		 * Same logic as above in cart.php & review-order.php templates
		 **/
		function woo_bundles_item_subtotal($subtotal, $values, $cart_item_key ) {
			global $woocommerce;

			if ( isset( $values['bundled_by'] ) ) {
				$bundle_cart_key = $values['bundled_by'];
				$bundle_product_id = $woocommerce->cart->cart_contents[ $bundle_cart_key ]['product_id'];
				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'no' )
					return '';
			}

			if ( !isset( $values['bundled_by'] ) && isset( $values['stamp'] ) ) {
				$bundle_product_id = $values['product_id'];
				if ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' )
					return '';
			}

			return $subtotal;
		}


		/**
		 * The WC_Product class assumes that a product is 'simple' if no other product type matches
		 * So, if a product's type is "bundle", and it configured as a fixed-price bundle, get_price_html will return an empty string
		 * Here we fix that shortcoming
		 **/
		function woo_bundles_empty_price( $price, $product ) {
			if ( ($product->product_type == 'bundle') && (get_post_meta($product->id, '_per_product_pricing_active', true) == 'yes') ) {
				$bundle = new WC_Product_Bundle ( $product->id );
				return $bundle->get_price_html();
			}
			elseif ( ($product->product_type == 'bundle') && (get_post_meta($product->id, '_per_product_pricing_active', true) == 'no') )
				return __('Price not set', 'woo-bundles');
			return $price;
		}


		/**
		 * If the product type is 'bundle', replace WC_Product with WC_Product_Bundle in the loop and single-product pages
		 **/
		function woo_bundles_init_bundled_product() {
			global $woocommerce, $product;

			if ($product->product_type == 'bundle' ) :
				$product = new WC_Product_Bundle($product->id);
			endif;
		}

		function woo_bundles_loop_add_to_cart_template($template, $name, $path) {
			if ( $name == 'loop/add-to-cart.php') {
				return $this->woo_bundles_locate_template($name);
			}
			return $template;
		}


		/**
		 * Filters result of is_sold_individually()
		 **/
		function woo_bundles_sold_individually( $is, $product ) {
			if ( $product->is_type('bundle') && !isset( $product->bundled_item_ids ) ) {
				$product = new WC_Product_Bundle( $product->id );
				return $product->is_sold_individually();
			}
			return $is;
		}


		/**
		 * Filters result of get_price()
		 **/
		function woo_bundles_get_price( $price, $product ) {
			if ( $product->is_type('bundle') && !isset( $product->bundled_item_ids ) ) {
				$product = new WC_Product_Bundle( $product->id );
				return $product->get_price();
			}
			return $price;
		}


		/**
		 * Similar to the forced-sells logic, only it takes into account bundled products that are sold individually
		 **/
		function woo_bundles_update_quantity_in_cart( $cart_item_key, $quantity = 0 ) {
			global $woocommerce;

			if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ] ) ) {
				if ( $quantity == 0 || $quantity < 0 ) {
					$quantity = 0;
				} else {
					$quantity = $woocommerce->cart->cart_contents[ $cart_item_key ]['quantity'];
				}

				if ( isset( $woocommerce->cart->cart_contents[ $cart_item_key ]['stamp'] ) && ! empty( $woocommerce->cart->cart_contents[ $cart_item_key ]['stamp'] ) ) {

					// unique bundle stamp added to all bundled items & the grouping item
					$stamp = $woocommerce->cart->cart_contents[ $cart_item_key ]['stamp'];

					// change the quantity of all bundled items that belong to the same bundle config
					foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
						if ( isset( $value['bundled_by'] ) && isset( $value['stamp'] ) && $cart_item_key == $value['bundled_by'] && $stamp == $value['stamp'] ) {
							if ( $value['data']->is_sold_individually() && $quantity > 0 ) {
								$woocommerce->cart->set_quantity( $key, 1 );
							} else {
								$bundle_id = $woocommerce->cart->cart_contents[ $cart_item_key ]['product_id'];
								$bundle_quantity = get_post_meta( $bundle_id, 'bundle_quantity_'.$value['bundled_item_id'], true );
					    		$bundle_quantity	= ( isset($bundle_quantity) && $bundle_quantity > 0 ) ? (int) $bundle_quantity : 1;
								$woocommerce->cart->set_quantity( $key, $quantity * $bundle_quantity );
							}
						}
					}

				}

			}

		}


		/**
		 * When the bundle is fix-priced, all bundled items' prices are set to 0
		 * When shipping is bundled, all bundled items are marked as virtual when they are added to the cart
		 * Otherwise, the bundle has already been marked as virtual in the first place
		 **/
		function woo_bundles_add_cart_item_filter( $cart_data, $id ) {

			global $woocommerce;

			$cart = $woocommerce->cart->get_cart();

			if ( isset( $cart_data['bundled_by'] ) ) {
				$bundle_cart_id = $cart_data['bundled_by'];
				$bundle_product_id = $cart[$bundle_cart_id]['product_id'];

				$per_product_pricing = ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;
				$per_product_shipping = ( get_post_meta( $bundle_product_id, '_per_product_shipping_active', true ) == 'yes' ) ? true : false;

				if ( $per_product_pricing == false) {
					$cart_data['data']->price = 0;
					$cart_data['data']->sale_price = 0;
					$cart_data['data']->regular_price = 0;
				}

				if ( $per_product_shipping == false ) {
					$cart_data['data']->virtual = 'yes';
				}

			}

			return $cart_data;
		}


		/**
		 * Add all bundle-related session data to the cart
		 **/
		function woo_bundles_get_cart_data_from_session( $cart_item, $item_session_values ) {

			global $woocommerce;

			if ( $this->is_wc_v2() )
				$cart = $woocommerce->session->cart;
			else
				$cart = $_SESSION['cart'];

			if ( isset($item_session_values['bundled_by']) ) {

				// load 'bundled_by' field
				$cart_item['bundled_by'] = $item_session_values['bundled_by'];

				// load product bundle post meta identifier
				$cart_item['bundled_item_id'] = $item_session_values['bundled_item_id'];

				// load dynamic pricing permission
				$cart_item['dynamic_pricing_allowed'] = $item_session_values['dynamic_pricing_allowed'];

				// now modify item depending on bundle pricing & shipping options
				$bundle_cart_id = $cart_item['bundled_by'];
				$bundle_product_id = $cart[$bundle_cart_id]['product_id'];

				// per-item pricing
				$per_product_pricing = ( get_post_meta( $bundle_product_id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;

				// non bundled shipping
				$per_product_shipping = ( get_post_meta( $bundle_product_id, '_per_product_shipping_active', true ) == 'yes' ) ? true : false;

				if ( $per_product_pricing == false ) {
					$cart_item['data']->price = 0;
					$cart_item['data']->sale_price = 0;
					$cart_item['data']->regular_price = 0;
				}

				if ( $per_product_shipping == false ) {
					$cart_item['data']->virtual = 'yes';
				}
			}

			if ( isset($item_session_values['stamp']) ) {
				$cart_item['stamp'] = $item_session_values['stamp'];
			}

			return $cart_item;
		}


		/**
		 * Add "included with" metadata
		 **/
		function woo_bundles_get_item_data( $data, $cart_item ) {
			global $woocommerce;

			if ( isset ( $cart_item['bundled_by'] ) && isset ( $cart_item['stamp'] ) ) {
				// not really necessary since we know its going to be there
				$product_key = $woocommerce->cart->find_product_in_cart( $cart_item['bundled_by'] );
				if ( ! empty( $product_key ) ) {
					$product_name = get_post( $woocommerce->cart->cart_contents[ $product_key ]['product_id'] )->post_title;
					$data[] = array(
							'name'    => __( 'Included with', 'woo-bundles' ),
							'display' => __( $product_name )
					);
				}
			}

			return $data;
		}


		/**
		 * Bundled items can't be removed individually
		 **/
		function woo_bundles_cart_item_remove_link( $link, $cart_item_key ) {
			global $woocommerce;

			if ( isset ( $woocommerce->cart->cart_contents[ $cart_item_key ]['bundled_by'] ) )
				return '';

			return $link;
		}


		/**
		 * Bundled item quantities can't be changed individually
		 **/
		function woo_bundles_cart_item_quantity( $quantity, $cart_item_key ) {
			global $woocommerce;

			if ( isset ( $woocommerce->cart->cart_contents[ $cart_item_key ]['stamp'] ) ) {
				if ( isset ( $woocommerce->cart->cart_contents[ $cart_item_key ]['bundled_by'] ) )
					return $woocommerce->cart->cart_contents[ $cart_item_key ]['quantity'];
			}
			return $quantity;
		}


		/**
		 * Add-to-cart template for product bundles
		 **/
		function woo_bundles_add_to_cart() {
			global $woocommerce, $product, $post;

			// Enqueue variation scripts
			wp_enqueue_script( 'wc-add-to-cart-variation' );
			wp_enqueue_script( 'wc-add-to-cart-bundle' );

			wp_enqueue_style( 'wc-bundle-css' );

			if ( $product->bundled_item_ids )
				$this->woo_bundles_get_template('single-product/add-to-cart/bundle.php', array(
					'available_variations' 		=> $product->get_available_bundle_variations(),
					'attributes'   				=> $product->get_bundle_attributes(),
					'selected_attributes' 		=> $product->get_selected_bundle_attributes(),
					'bundle_price_data' 		=> $product->get_bundle_price_data(),
					'bundled_products' 			=> $product->get_bundled_products(),
					'bundled_item_quantities' 	=> $product->get_bundled_item_quantities()
				) );

		}


		/**
		 * Admin & Frontend scripts
		 **/
		function woo_bundles_admin_scripts() {

			wp_register_script( 'woo_bundles_writepanel', $this->woo_bundles_plugin_url() . '/assets/js/bundled-product-write-panels.js?v=2.50', array('jquery', 'jquery-ui-datepicker', 'woocommerce_writepanel') );

			wp_register_style( 'woo_bundles_css', $this->woo_bundles_plugin_url() . '/assets/css/bundles-write-panels.css?v=2.50', array('woocommerce_admin_styles') );

			// Get admin screen id
			$screen = get_current_screen();

			// WooCommerce admin pages
			if (in_array( $screen->id, array( 'product' )))
				wp_enqueue_script( 'woo_bundles_writepanel' );

			if (in_array( $screen->id, array( 'edit-product', 'product' )))
				wp_enqueue_style( 'woo_bundles_css' );
		}

		function woo_bundles_frontend_scripts() {
			wp_register_script( 'wc-add-to-cart-bundle', $this->woo_bundles_plugin_url() . '/assets/js/add-to-cart-bundle.js?v=2.51', array('jquery') );
			wp_register_style( 'wc-bundle-css', $this->woo_bundles_plugin_url() . '/assets/css/bundles-frontend.css?v=2.53', false );

		}


		/**
		 * Process, verify and save bundle data
		 **/
		function woo_bundles_process_bundle_meta( $post_id ) {

			global $woocommerce_errors, $woocommerce;

			// Bundle Pricing

			$date_from = (isset($_POST['_sale_price_dates_from'])) ? $_POST['_sale_price_dates_from'] : '';
			$date_to = (isset($_POST['_sale_price_dates_to'])) ? $_POST['_sale_price_dates_to'] : '';

			// Dates
			if ($date_from) :
				update_post_meta( $post_id, '_sale_price_dates_from', strtotime($date_from) );
			else :
				update_post_meta( $post_id, '_sale_price_dates_from', '' );
			endif;

			if ($date_to) :
				update_post_meta( $post_id, '_sale_price_dates_to', strtotime($date_to) );
			else :
				update_post_meta( $post_id, '_sale_price_dates_to', '' );
			endif;

			if ($date_to && !$date_from) :
				update_post_meta( $post_id, '_sale_price_dates_from', strtotime('NOW', current_time('timestamp')) );
			endif;

			// Update price if on sale
			if ($_POST['_sale_price'] != '' && $date_to == '' && $date_from == '') :
				update_post_meta( $post_id, '_price', stripslashes($_POST['_sale_price']) );
			else :
				update_post_meta( $post_id, '_price', stripslashes($_POST['_regular_price']) );
			endif;

			if ($date_from && strtotime($date_from) < strtotime('NOW', current_time('timestamp'))) :
				update_post_meta( $post_id, '_price', stripslashes($_POST['_sale_price']) );
			endif;

			if ($date_to && strtotime($date_to) < strtotime('NOW', current_time('timestamp'))) :
				update_post_meta( $post_id, '_price', stripslashes($_POST['_regular_price']) );
				update_post_meta( $post_id, '_sale_price_dates_from', '');
				update_post_meta( $post_id, '_sale_price_dates_to', '');
			endif;


			// Per-Item Pricing

			if ( isset($_POST['_per_product_pricing_active']) ) {
				update_post_meta( $post_id, '_per_product_pricing_active', 'yes' );
				delete_post_meta( $post_id, '_regular_price' );
				delete_post_meta( $post_id, '_sale_price' );
				delete_post_meta( $post_id, '_price' );
			} else {
				update_post_meta( $post_id, '_per_product_pricing_active', 'no' );
				update_post_meta( $post_id, '_regular_price', stripslashes( $_POST['_regular_price'] ) );
				update_post_meta( $post_id, '_sale_price', stripslashes( $_POST['_sale_price'] ) );
			}



			// Shipping
			// Non-Bundled (per-item) Shipping

			if ( isset($_POST['_per_product_shipping_active']) ) {
				update_post_meta( $post_id, '_per_product_shipping_active', 'yes' );
				update_post_meta( $post_id, '_virtual', 'yes' );
				update_post_meta( $post_id, '_weight', '' );
				update_post_meta( $post_id, '_length', '' );
				update_post_meta( $post_id, '_width', '' );
				update_post_meta( $post_id, '_height', '' );
			} else {
				update_post_meta( $post_id, '_per_product_shipping_active', 'no' );
				update_post_meta( $post_id, '_virtual', 'no' );
				update_post_meta( $post_id, '_weight', stripslashes( $_POST['_weight'] ) );
				update_post_meta( $post_id, '_length', stripslashes( $_POST['_length'] ) );
				update_post_meta( $post_id, '_width', stripslashes( $_POST['_width'] ) );
				update_post_meta( $post_id, '_height', stripslashes( $_POST['_height'] ) );
			}


			// Process Bundled Product Configuration

			// Attempt to delete old post meta
			$old_ids = get_post_meta( $post_id, '_bundled_ids', true );
			if ( $old_ids ) {
				foreach ( $old_ids as $old_id ) {
					if ( !in_array( $old_id, $_POST['bundled_ids'] ) ) {
						delete_post_meta( $post_id, 'filter_variations_'.$old_id );
						delete_post_meta( $post_id, 'override_defaults_'.$old_id );
						delete_post_meta( $post_id, 'bundle_quantity_'.$old_id );
						delete_post_meta( $post_id, 'hide_thumbnail_'.$old_id );
						delete_post_meta( $post_id, 'override_title_'.$old_id );
						delete_post_meta( $post_id, 'product_title_'.$old_id );
						delete_post_meta( $post_id, 'override_description_'.$old_id );
						delete_post_meta( $post_id, 'product_description_'.$old_id );
						delete_post_meta( $post_id, 'hide_filtered_variations_'.$old_id );
					}
				}
			}

			if (isset($_POST['bundled_ids'])) {

				// Now start saving new data
				$bundled_ids = array();
				$times = array();
				$save_defaults = array();

				$ids = $_POST['bundled_ids'];

				foreach ($ids as $id) :

					if ( $terms = wp_get_object_terms( $id, 'product_type' ) ) { $product_type = current($terms)->slug; }
					if ( ( $id && $id>0 ) && ( $product_type == 'simple' || $product_type == 'variable' ) && ( $post_id != $id ) ) {

						// only allow multiple instances of variable items
						if ( in_array( $id, $bundled_ids ) && $product_type != 'variable' )
							continue;

						// allow bundling the same variable item id multiple times by adding a suffix
						if ( !isset( $times[$id] ) ) {

							$times[$id] = 1;
							$val = $id;

						}
						else {

							// only allow multiple instances of non-sold-individually items
							if ( get_post_meta($id, '_downloadable', true) == 'yes' && get_post_meta($id, '_virtual', true) == 'yes' && get_option('woocommerce_limit_downloadable_product_qty') == 'yes' ) {

								$woocommerce_errors[] = sprintf( __('\'%s\' (#%s) is sold individually and cannot be bundled more than once.', 'woo-bundles'), get_the_title( $id ), $id );
								continue;

							}

							$times[$id] += 1;
							$val = $id . '_' . $times[$id];

						}

						$bundled_ids[] = $val;

						// Save thumbnail preferences first
						if ( isset( $_POST['hide_thumbnail_'.$val] ) ) {
							update_post_meta( $post_id, 'hide_thumbnail_'.$val, 'yes' );
						} else {
							update_post_meta( $post_id, 'hide_thumbnail_'.$val, 'no' );
						}

						// Save title preferences
						if ( isset( $_POST['override_title_'.$val] ) && isset( $_POST['product_title_'.$val] ) && !empty( $_POST['product_title_'.$val] ) ) {
							update_post_meta( $post_id, 'override_title_'.$val, 'yes' );
							update_post_meta( $post_id, 'product_title_'.$val, $_POST['product_title_'.$val] );
						} else {
							update_post_meta( $post_id, 'override_title_'.$val, 'no' );
							delete_post_meta( $post_id, 'product_title_'.$val );
						}

						// Save description preferences
						if ( isset( $_POST['override_description_'.$val] ) && isset( $_POST['product_description_'.$val] ) && !empty( $_POST['product_description_'.$val] ) ) {
							update_post_meta( $post_id, 'override_description_'.$val, 'yes' );
							update_post_meta( $post_id, 'product_description_'.$val, $_POST['product_description_'.$val] );
						} else {
							update_post_meta( $post_id, 'override_description_'.$val, 'no' );
							delete_post_meta( $post_id, 'product_description_'.$val );
						}

						// Save quantity data
						if ( isset( $_POST['bundle_quantity_'.$val] ) ) {

							if ( is_numeric( $_POST['bundle_quantity_'.$val] ) ) {

								$quantity = (int) $_POST['bundle_quantity_'.$val];
								if ( $quantity > 0 && $_POST['bundle_quantity_'.$val] - $quantity == 0 ) {

									if ( get_post_meta($id, '_downloadable', true) == 'yes' && get_post_meta($id, '_virtual', true) == 'yes' && get_option('woocommerce_limit_downloadable_product_qty') == 'yes' && $quantity != 1 ) {

										$woocommerce_errors[] = sprintf( __('\'%s\' (#%s) is sold individually and cannot be bundled more than once.', 'woo-bundles'), get_the_title( $id ), $id );
										update_post_meta( $post_id, 'bundle_quantity_'.$val, 1 );

									}
									else {
										update_post_meta( $post_id, 'bundle_quantity_'.$val, $_POST['bundle_quantity_'.$val] );
									}
								}
								else
									$woocommerce_errors[] = sprintf( __('The quantity you entered for \'%s%s\' (#%s) was not valid and has been reset. Please enter a positive integer value.', 'woo-bundles'), get_the_title( $id ), ( $id != $val ? ' #' . $times[$id] : '' ), $id );
							}
						} else {
							// if its not there, it means the product was just added
							update_post_meta( $post_id, 'bundle_quantity_'.$val, 1 );
						}

						// Save data related to variable items

						if ( $product_type == 'variable' ) {

							// Save variation filtering options
							if ( isset( $_POST['filter_variations_'.$val] ) ) {

								if ( isset( $_POST['allowed_variations'][$val] ) && count( $_POST['allowed_variations'][$val] ) > 0 ) {
									update_post_meta( $post_id, 'filter_variations_'.$val, 'yes' );

									if ( isset( $_POST['hide_filtered_variations_'.$val] ) )
										update_post_meta( $post_id, 'hide_filtered_variations_'.$val, 'yes' );
									else
										update_post_meta( $post_id, 'hide_filtered_variations_'.$val, 'no' );
								}
								else {
									update_post_meta( $post_id, 'filter_variations_'.$val, 'no' );
									delete_post_meta( $post_id, 'hide_filtered_variations_'.$val );
									$woocommerce_errors[] = __('Please select at least one variation for each bundled product you want to filter.', 'woo-bundles');
								}
							} else {
								update_post_meta( $post_id, 'filter_variations_'.$val, 'no' );
							}

							// Save defaults options
							if ( isset( $_POST['override_defaults_'.$val] ) ) {

								if ( isset( $_POST['default_attributes'][$val] ) ) {

									// if filters are set, check that the selections are valid

									if ( isset( $_POST['filter_variations_'.$val] ) && isset( $_POST['allowed_variations'][$val] ) ) {

										$allowed_variations = $_POST['allowed_variations'][$val];

										// the array to store all valid attribute options of the iterated product
										$filtered_attributes = array();

										// populate array with valid attributes
										foreach ( $allowed_variations as $variation ) {

											$product_custom_fields = get_post_custom( $variation );

											foreach ( $product_custom_fields as $name => $value ) :

												if ( ! strstr( $name, 'attribute_' ) ) continue;
												$attribute_name = substr( $name, strlen('attribute_') );

												// ( populate array )
												if ( !isset( $filtered_attributes[$attribute_name] ) ) {
													$filtered_attributes[$attribute_name][] = $value[0];
												} elseif ( !in_array( $value[0], $filtered_attributes[$attribute_name] ) ) {
													$filtered_attributes[$attribute_name][] = $value[0];
												}

											endforeach;

										}
										// Debug
										//$_SESSION['filtered'][$product_id] = $filtered_attributes;
										//$_SESSION['defaults'][$product_id] = $default_attributes[$product_id];

										// check validity
										foreach ( $_POST['default_attributes'][$val] as $name => $value ) {
											if ($value == '') continue;
											if ( !in_array( $value, $filtered_attributes[sanitize_title($name)] ) && !in_array( '', $filtered_attributes[sanitize_title($name)] ) ) {

												// set option to "Any"
												$_POST['default_attributes'][$val][sanitize_title($name)] = '';

												// throw an error
												$woocommerce_errors[] = sprintf( __('The \'%s\' default option that you selected for \'%s%s\' (#%s) is inconsistent with the set of active variations. Always double-check your preferences before saving, and always save any changes made to the variation filters before choosing new defaults.', 'woo-bundles'), ucwords( $woocommerce->attribute_label($name) ), get_the_title( $id ), ( $id != $val ? ' #' . $times[$id] : '' ), $id );

												continue;
											}
										}

									}

									update_post_meta( $post_id, 'override_defaults_'.$val, 'yes' );

								}
							} else {
								update_post_meta( $post_id, 'override_defaults_'.$val, 'no' );
							}
						}

						// Save visibility preferences
						if ( isset( $_POST['visibility_'.$val] ) ) {

							if ( $_POST['visibility_'.$val] == 'visible' ) {

								update_post_meta( $post_id, 'visibility_'.$val, 'visible' );

							} elseif ( $_POST['visibility_'.$val] == 'hidden' ) {

								if ( $product_type == 'variable' ) {

									if ( isset( $_POST['default_attributes'][$val] ) ) {

										foreach ( $_POST['default_attributes'][$val] as $default_name => $default_value ) {
											if ( !$default_value ) {
												$_POST['visibility_'.$val] = 'visible';
												$woocommerce_errors[] = sprintf( __('\'%s%s\' (#%s) cannot be hidden unless all default options of the product are defined.', 'woo-bundles'), get_the_title( $id ), ( $id != $val ? ' #' . $times[$id] : '' ), $id );
											}
										}

										update_post_meta( $post_id, 'visibility_'.$val, $_POST['visibility_'.$val] );

									} else {
										update_post_meta( $post_id, 'visibility_'.$val, 'visible' );
									}


								} else {
									update_post_meta( $post_id, 'visibility_'.$val, 'hidden' );
								}

							}

						}

					}

				endforeach;

				update_post_meta( $post_id, '_bundled_ids', $bundled_ids );

				if ( isset( $_POST['allowed_variations'] ) ) {
					update_post_meta( $post_id, '_allowed_variations', $_POST['allowed_variations'] );
				}

				if ( isset( $_POST['default_attributes'] ) ) {
					// take out empty attributes (any set) to prepare for saving

					foreach ( $_POST['default_attributes'] as $item_id => $defaults ) {
						$save_defaults[$item_id] = array();
						foreach ($defaults as $default_name => $default_value) {
							if ( $default_value ) {
								$save_defaults[$item_id][ sanitize_title($default_name) ] = $default_value;
							}
						}
					}
					update_post_meta( $post_id, '_bundle_defaults', $save_defaults );
				}


			} else {
				delete_post_meta( $post_id, '_bundled_ids' );

				$woocommerce_errors[] = __('Please add at least one product to the bundle before publishing. To add products, click on the Bundled Products tab.', 'woo-bundles');

				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );

				return;

			}


		}


		/**
		 * Add 'bundle' type to the menu
		 **/
		function woo_bundles_product_selector_filter( $options ) {
			$options['bundle'] = __('Product bundle', 'woo-bundles');
			return $options;
		}


		/**
		 * Aadd Bundled Products write panel tab
		 **/
		function woo_bundles_product_write_panel_tab() {
			echo '<li class="bundled_product_tab show_if_bundle related_product_options linked_product_options"><a href="#bundled_product_data">'.__('Bundled Products', 'woo-bundles').'</a></li>';
		}


		/**
		 * Aadd Bundled Products stock note
		 **/
		function woo_bundles_stock_group() {
			global $woocommerce, $post; ?>

			<p class="form-field show_if_bundle bundle_stock_msg">
				<?php _e('Note', 'woo-bundles'); echo '<img class="help_tip" data-tip="' . __( 'By default, the sale of a product within a bundle has the same effect on its stock as an individual sale. There are no separate inventory settings for bundled items. However, this pane can be used to enable stock management on a bundle level. This can be very useful for allocating bundle stock quota, or for keeping track of bundled item sales.', 'woo-bundles' ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />'; ?>
			</p><?php

		}


		/**
		 * Product bundle options for post-1.6.2 product data section
		 **/
		function woo_bundles_type_options( $options ) {

			$options['per_product_shipping_active'] = array(
				'id' => '_per_product_shipping_active',
				'wrapper_class' => 'show_if_bundle',
				'label' => __('Non-Bundled Shipping', 'woo-bundles'),
				'description' => __('If your bundle consists of items that are assembled or packaged together, leave the box un-checked and just define the shipping properties of the product bundle below. If, however, the bundled items are shipped individually, their shipping properties must be retained. In this case, the box must be checked. \'Non-Bundled Shipping\' should also be selected when the bundle consists of virtual items, which are not shipped.', 'woo-bundles')
			);

			$options['per_product_pricing_active'] = array(
				'id' => '_per_product_pricing_active',
				'wrapper_class' => 'show_if_bundle',
				'label' => __('Per-Item Pricing', 'woo-bundles'),
				'description' => __('When enabled, the bundle is priced per-item, based on standalone item prices.', 'woo-bundles')
			);

			return $options;
		}


		/**
		 * Write panel for Product Bundles
		 **/
		function woo_bundles_product_write_panel() {
			global $woocommerce, $post, $wpdb;

			?>
				<div id="bundled_product_data" class="panel woocommerce_options_panel">

					<div class="options_group">

						<p><label for="bundled_ids"><?php _e('Bundled Products', 'woo-bundles'); ?></label>
						</p>

						<div class="wc-bundled_products">

							<div class="bundled_products_info">

							<?php _e('Note', 'woo-bundles'); echo '<img class="help_tip" data-tip="' . __( 'Select the products that you want to include in your bundle, kit, or assembly. Any simple or variable product can be added - physical, or downloadable.<br/><strong>Important</strong>: v2.0 has introduced the ability to bundle <strong>multiple instances of the same variable product</strong> and configure each instance separately - for details, check out the online documentation.', 'woo-bundles' ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />'; ?>

							</div>

							<div class="bundled_products_selector">

								<select id="bundled_ids" name="bundled_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e('Search for a product&hellip;', 'woo-bundles'); ?>">
									<?php
										$item_ids = get_post_meta( $post->ID, '_bundled_ids', true );
										$bundled_variable_num = 0;

										if ( $item_ids ) {
											foreach ( $item_ids as $item_id ) {

												// remove suffix
												$sep = explode( '_', $item_id );
												$product_id = $sep[0];

												if ( $terms = wp_get_object_terms( $product_id, 'product_type' ) ) { $product_type = current($terms)->slug; }
												if ( $product_type == 'variable' ) { $bundled_variable_num++; }

												$title 	= get_the_title( $product_id ) . ( $product_id != $item_id ? ' #' . $sep[1] : '' );
												$sku 	= get_post_meta( $product_id, '_sku', true );

												if ( !$title ) continue;

												if ( isset($sku) && $sku ) $sku = ' (SKU: ' . $sku . ')';
												echo '<option value="'.$product_id.'" selected="selected">'. $title . $sku . '</option>';
											}
										}
									?>
								</select>
							</div>
						</div>

						<?php
						if ( $item_ids ) { ?>

							<p><label for="bundled_ids_notice"><?php _e('Bundle Configuration', 'woo-bundles'); ?></label></p> <?php


							$allowed_variations = get_post_meta( $post->ID, '_allowed_variations', true );
							$default_attributes = (array) maybe_unserialize( get_post_meta( $post->ID, '_bundle_defaults', true ) );


							foreach ( $item_ids as $item_id ) {

								// remove suffix
								$sep = explode( '_', $item_id );
								$product_id = $sep[0];

								$title 	= get_the_title( $product_id ) . ( $product_id != $item_id ? ' #' . $sep[1] : '' );
								$sku 	= get_post_meta( $product_id, '_sku', true );

								if ( isset($sku) && $sku ) $sku = ' (SKU: ' . $sku . ')';

								if (!$title) continue;
								?>

								<div class="wc-bundled-item">
									<div class="item-description">
										<?php echo $title . ' &ndash; #'. $product_id; ?><br/><?php echo $sku; ?>
									</div>
									<div class="item-data">

										<?php
											if ( $this->is_wc_v2() ) {
												$bundled_product = get_product( $product_id );
											} else {
												$bundled_product = new WC_Product( $product_id );
											}

											if ( $bundled_product->is_type('variable') ) : ?>

												<div class="filtering">

												<?php woocommerce_wp_checkbox( array( 'id' => 'filter_variations_'.$item_id, 'wrapper_class' => 'filter_variations', 'label' => __('Filter Variations', 'woo-bundles'), 'description' => '<img class="help_tip" data-tip="' . __('Check to enable only a subset of the available variations.', 'woo-bundles') .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />' ) ); ?>

												</div>


												<div class="bundle_variation_filters indented">

													<select multiple="multiple" name="allowed_variations[<?php echo $item_id; ?>][]" style="width: 450px; display: none; " data-placeholder="Choose variations…" title="Variations" class="chosen_select" > <?php

													$args = array(
														'post_type'	=> 'product_variation',
														'post_status' => array('private', 'publish'),
														'numberposts' => -1,
														'orderby' => 'menu_order',
														'order' => 'asc',
														'post_parent' => $product_id,
														'fields' => 'ids'
													);

													$variations = get_posts($args);

													foreach ( $variations as $variation ) {

														$product_custom_fields = get_post_custom( $variation );
														ksort($product_custom_fields);
														$description = '';

														foreach ( $product_custom_fields as $name => $value ) :

															if ( ! strstr( $name, 'attribute_' ) ) continue;
															$attribute_name = substr( $name, strlen('attribute_') );

															$description_name = ucwords( $woocommerce->attribute_label( $attribute_name ) );
															if( !$value[0] ) {
																$description_value = __('Any', 'woocommerce') . ' ' . $description_name;
															}
															else {
																$term = get_term_by( 'slug', $value[0], $attribute_name );
																$description_value = ( $term == false ) ? '' : $term->name;
															}

															if ( !$description_value )
																$description_value = $value[0];

															$description .= $description_name . ': ' . apply_filters( 'woocommerce_variation_option_name', $description_value ) . ', ';
														endforeach;

														if ( is_array($allowed_variations[$item_id]) && in_array($variation, $allowed_variations[$item_id]) )
															$selected = 'selected="selected"';
														else $selected = '';

														echo '<option value="'.$variation .'" '.$selected.'>#'.$variation . ' - ' . rtrim( $description, ', ') . '</option>';
													} ?>

													</select>

													<?php woocommerce_wp_checkbox( array( 'id' => 'hide_filtered_variations_'.$item_id, 'wrapper_class' => 'hide_filtered_variations', 'label' => __('Hide Filtered-Out Options', 'woo-bundles'), 'description' => '<img class="help_tip" data-tip="' . __('Check to remove any filtered-out variation options from this item\'s drop-downs. If you leave the box unchecked, the options corresponding to filtered-out variations will be disabled but still visible.', 'woo-bundles') .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />' ) ); ?>

												</div>


												<div class="defaults">

													<?php woocommerce_wp_checkbox( array( 'id' => 'override_defaults_'.$item_id, 'wrapper_class' => 'override_defaults', 'label' => __('Override Default Selections', 'woo-bundles'), 'description' => '<img class="help_tip" data-tip="' . __('In effect for this bundle only. The available options are in sync with the filtering settings above. Always save any changes made above before configuring this section.', 'woo-bundles') .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />' ) ); ?>

												</div>

												<div class="bundle_selection_defaults indented"> <?php

														// get normal variation attributes
														$attributes = $bundled_product->get_variation_attributes();

														// filtered variation attributes
														$filtered_attributes = array();

														// get filter-active setting
														$filtered = ( get_post_meta( $post->ID, 'filter_variations_'.$item_id, true ) == 'yes' ) ? true : false;

														// if filter is active, show only the attributes of the selected variations
														if ( $filtered ) {

															foreach ( $allowed_variations[$item_id] as $allowed_variation_id ) {

																// get variation meta of allowed variations
																$product_custom_fields = get_post_custom( $allowed_variation_id );

																foreach ( $product_custom_fields as $name => $value ) :

																	if ( ! strstr( $name, 'attribute_' ) ) continue;

																	$attribute_name = substr( $name, strlen('attribute_') );

																	if ( !isset( $filtered_attributes[$attribute_name] ) ) {
																		$filtered_attributes[$attribute_name][] = $value[0];
																	} elseif ( !in_array( $value[0], $filtered_attributes[$attribute_name] ) ) {
																		$filtered_attributes[$attribute_name][] = $value[0];
																	}
																endforeach;
															}

														}

														foreach ( $attributes as $name => $values ) :
															sort( $values );
															// Get current value for variation (if set)
															$variation_selected_value = ( isset( $default_attributes[$item_id][sanitize_title($name)] ) ) ? $default_attributes[$item_id][sanitize_title($name)] : '';

															// Name will be something like attribute_pa_color
															echo '<select name="default_attributes[' . $item_id . '][' . sanitize_title($name).']"><option value="">'.__('No default', 'woocommerce') . ' ' . $woocommerce->attribute_label($name).'&hellip;</option>';

															foreach ( $values as $value ) :

																// if filters exist, only show the entries available in the filtered_attributes array
																if ( $filtered && isset( $filtered_attributes[sanitize_title($name)] ) ) {
																	if ( !in_array( $value, $filtered_attributes[sanitize_title($name)] ) && !in_array( '', $filtered_attributes[sanitize_title($name)] ) ) continue;
																}

																$term = get_term_by( 'slug', $value, $name );
																$value_name = ( $term == false ) ? '' : $term->name;

																if ( !$value_name )
																	$value_name = ucwords( $value );
																echo '<option '.selected($variation_selected_value, $value, false) . ' value="' . $value . '">' . apply_filters( 'woocommerce_variation_option_name', $value_name) . '</option>';
															endforeach;

															echo '</select>';

														endforeach; ?>

												</div>
											<?php
											endif;

											$item_quantity = get_post_meta( $post->ID, 'bundle_quantity_'.$item_id, true );
											if ( !isset( $item_quantity ) || $item_quantity < 1 || ( get_post_meta($product_id, '_downloadable', true) == 'yes' && get_post_meta( $product_id, '_virtual', true ) == 'yes' && get_option('woocommerce_limit_downloadable_product_qty') == 'yes' ) )
												$item_quantity = 1;
										?>

										<div class="quantity">

											<?php woocommerce_wp_text_input( array( 'id' => 'bundle_quantity_' . $item_id, 'class' => 'bundle_quantity', 'label' => __('Quantity', 'woocommerce') ) ); ?>

										</div>

										<div class="item_visibility">

											<label for="item_visibility"><?php _e('Front-End Visibility', 'woo-bundles'); ?></label>
											<select name="visibility_<?php echo $item_id; ?>">
												<?php
												$visible = ( get_post_meta( $post->ID, 'visibility_'.$item_id, true ) == 'hidden' ) ? false : true;
												echo '<option '.selected($visible, true, false).' value="visible">' . __( 'Visible', 'woo-bundles' ) . '</option>';
												echo '<option '.selected($visible, false, false).' value="hidden">' . __( 'Hidden', 'woo-bundles' ) . '</option>';
												echo '<img class="help_tip" data-tip="' . __( 'Check this option to hide the thumbnail of this bundled product.', 'woo-bundles' ) .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />';
												?>
											</select>
										</div>

										<div class="images">

											<?php woocommerce_wp_checkbox( array( 'id' => 'hide_thumbnail_'.$item_id, 'wrapper_class' => 'hide_thumbnail', 'label' => __('Hide Product Thumbnail', 'woo-bundles'), 'description' => '' ) ); ?>

										</div>

										<div class="override_title">

											<?php woocommerce_wp_checkbox( array( 'id' => 'override_title_'.$item_id, 'wrapper_class' => 'override_title', 'label' => __('Override Title', 'woo-bundles'), 'description' => '<img class="help_tip" data-tip="' . __('Check this option to override the default product title.', 'woo-bundles') .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />' ) ); ?>

										<?php
										$item_title = get_post_meta( $post->ID, 'product_title_'.$item_id, true );
										?>

											<div class="custom_title indented">

												<?php woocommerce_wp_text_input( array( 'id' => 'product_title_' . $item_id, 'class' => 'product_title', 'label' => __('Product Title', 'woo-bundles') ) ); ?>

											</div>

										</div>


										<div class="override_description">

											<?php woocommerce_wp_checkbox( array( 'id' => 'override_description_' . $item_id, 'wrapper_class' => 'override_description', 'label' => __('Override Short Description', 'woo-bundles'), 'description' => '<img class="help_tip" data-tip="' . __('Check this option to override the default short product description.', 'woo-bundles') .'" src="'.$woocommerce->plugin_url().'/assets/images/help.png" />' ) ); ?>

										<?php
										$item_description = get_post_meta( $post->ID, 'product_description_'.$item_id, true );
										?>

											<div class="custom_description indented">

												<?php woocommerce_wp_textarea_input(  array( 'id' => 'product_description_' . $item_id, 'class' => 'product_description', 'label' => __('Product Short Description', 'woo-bundles') ) ); ?>

											</div>

										</div>


									</div>
								</div>
							<?php
							}
						} else { ?>
							<p><label for="bundled_ids_notice"><?php _e('Bundle Configuration', 'woo-bundles'); ?></label></p>
							<p><em><?php _e( 'To configure additional options, first select some products and then save your changes.', 'woo-bundles' ); ?></em></p>
							<?php
						}
						?>
					</div> <!-- options group -->
				</div>
				<?php
		}


		function woo_bundles_locate_template( $template_name, $template_path = '' ) {
			global $woocommerce;

			$template = ( ! empty( $template_path ) ) ? locate_template( array( $template_path . $template_name , $template_name ) ) : '';

			// Look in yourtheme/woocommerce/template-name and yourtheme/template-name
			if ( ! $template ) $template = locate_template( array( $woocommerce->template_url . $template_name , $template_name ) );

			// Get default template
			if ( ! $template ) $template = $this->woo_bundles_plugin_path() . '/templates/' . $template_name;

			return apply_filters('woo_bundles_locate_template', $template, $template_name, $template_path);
		}

		function woo_bundles_get_template( $template_name, $args = array(), $template_path = '' ) {

			if ( $args && is_array($args) )
				extract( $args );

			$located = $this->woo_bundles_locate_template( $template_name, $template_path );

			do_action( 'woo_bundles_before_template_part', $template_name, $template_path, $located );

			include( $located );

			do_action( 'woo_bundles_after_template_part', $template_name, $template_path, $located );
		}


		/**
		 * Check stock before attempting to call the add_to_cart function
		 * Some double checking happens, but it's better than partially adding items to the cart
		 **/
		function validate_stock( $product_id, $variation_id, $quantity, $exclude_cart, $silent ) {

			global $woocommerce;

			if ( $variation_id > 0 ) {
				if ( $this->is_wc_v2() )
					$product_data = get_product( $variation_id, array( 'product_type' => 'variation') );
				else
					$product_data = new WC_Product_Variation( $variation_id );
			} else {
				if ( $this->is_wc_v2() )
					$product_data = get_product( $product_id, array( 'product_type' => 'simple') );
				else
					$product_data = new WC_Product( $product_id );
			}

			// Stock check - only check if we're managing stock and backorders are not allowed.
			if ( ! $product_data->is_in_stock() ) {
				if ( ! $silent )
					$woocommerce->add_error( sprintf( __('You cannot add this product to the cart since "%s" is out of stock.', 'woo-bundles'), $product_data->get_title() ) );
				return false;
			}
			elseif ( ! $product_data->has_enough_stock( $quantity ) ) {
				if ( ! $silent )
					$woocommerce->add_error( sprintf(__('You cannot add that amount to the cart since there is not enough stock of "%s". We have %s in stock.', 'woo-bundles'), $product_data->get_title(), $product_data->get_stock_quantity() ));
				return false;
			}



			// Stock check - this time accounting for whats already in-cart.
			if ( $exclude_cart )
				return true;

			$product_qty_in_cart = $woocommerce->cart->get_cart_item_quantities();

			if ( $product_data->managing_stock() ) {

				// Variations
				if ( $variation_id && $product_data->variation_has_stock ) {

					if ( isset( $product_qty_in_cart[ $variation_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $variation_id ] + $quantity ) ) {
						if ( ! $silent )
							$woocommerce->add_error( sprintf(__('<a href="%s" class="button">%s</a>You cannot add that amount to the cart since there is not enough stock of "%s" &mdash; we have %s in stock and you already have %s in your cart.', 'woo-bundles'), get_permalink(woocommerce_get_page_id('cart')), __('View Cart &rarr;', 'woocommerce'), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $variation_id ] ));
						return false;
					}

				// Products
				} else {

					if ( isset( $product_qty_in_cart[ $product_id ] ) && ! $product_data->has_enough_stock( $product_qty_in_cart[ $product_id ] + $quantity ) ) {
						if ( ! $silent )
							$woocommerce->add_error( sprintf(__('<a href="%s" class="button">%s</a>You cannot add that amount to the cart since there is not enough stock of "%s" &mdash; we have %s in stock and you already have %s in your cart.', 'woo-bundles'), get_permalink(woocommerce_get_page_id('cart')), __('View Cart &rarr;', 'woocommerce'), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $product_id ] ));
						return false;
					}

				}

			}

			return true;

		}


		// debugging only
		function woo_bundles_before_cart() {

			global $woocommerce;

			$cart = $woocommerce->cart->get_cart();

			print_r( $_SESSION['stock_check_data'] );
			echo '<br/>';
			echo '<br/>';

			echo 'Cart Contents Total: ' . $woocommerce->cart->cart_contents_total . '<br/>';
			echo 'Cart Tax Total: ' . $woocommerce->cart->tax_total . '<br/>';
			echo 'Cart Total: ' . $woocommerce->cart->get_cart_total() . '<br/>';

			foreach ( $cart as $key => $data ) {
				echo '<br/>Cart Item - '.$key.' ('.count($data).' items):<br/>';

				echo 'Price: ' . $data['data']->get_price();
				echo '<br/>';

				foreach ( $data as $datakey => $value ) {
					print_r ( $datakey ); if (is_numeric($value) || is_string($value)) echo ': '.$value; echo ' | ';
				}
			}
		}


	}

	$GLOBALS['woocommerce_bundles'] = new WC_Product_Bundles();
}
