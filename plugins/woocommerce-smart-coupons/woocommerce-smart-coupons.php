<?php
/*
Plugin Name: WooCommerce Smart Coupons
Plugin URI: http://woothemes.com/woocommerce
Description: <strong>WooCommerce Smart Coupons</strong> lets customers buy gift certificates, store credits or coupons easily. They can use purchased credits themselves or gift to someone else.
Version: 1.2.6
Author: Store Apps
Author URI: http://www.storeapps.org/
Copyright (c) 2012 Store Apps All rights reserved.
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '05c45f2aa466106a466de4402fff9dde', '18729' );

//
register_activation_hook ( __FILE__, 'smart_coupon_activate' );

// Function to have by default auto generation for smart coupon on activation of plugin.
function smart_coupon_activate() {
    global $wpdb, $blog_id;

    if (is_multisite()) {
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}", 0);
    } else {
        $blog_ids = array($blog_id);
    }

    if ( !get_option( 'smart_coupon_email_subject' ) ) {
        add_option( 'smart_coupon_email_subject' );
    }

    foreach ($blog_ids as $blog_id) {

        if (( file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php') ) && ( is_plugin_active('woocommerce/woocommerce.php') )) {

            $wpdb_obj = clone $wpdb;
            $wpdb->blogid = $blog_id;
            $wpdb->set_prefix($wpdb->base_prefix);

            $query = "SELECT postmeta.post_id FROM {$wpdb->prefix}postmeta as postmeta WHERE postmeta.meta_key = 'discount_type' AND postmeta.meta_value LIKE 'smart_coupon' AND postmeta.post_id IN
                    (SELECT p.post_id FROM {$wpdb->prefix}postmeta AS p WHERE p.meta_key = 'customer_email' AND p.meta_value LIKE 'a:0:{}') ";

            $results = $wpdb->get_col($query);

            foreach ($results as $result) {
                update_post_meta($result, 'auto_generate_coupon', 'yes');
            }
            // To disable apply_before_tax option for Gift Certificates / Store Credit.
            $post_id_tax_query = "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'discount_type' AND meta_value LIKE 'smart_coupon'";

            $tax_post_ids = $wpdb->get_col($post_id_tax_query);

            foreach ( $tax_post_ids as $tax_post_id ) {
                update_post_meta($tax_post_id, 'apply_before_tax', 'no');
            }

            $wpdb = clone $wpdb_obj;
        }
    }
}

if ( is_woocommerce_active() ) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain('wc_smart_coupons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

        if ( ! class_exists( 'WC_Smart_Coupons' ) ) {

		class WC_Smart_Coupons {

			var $credit_settings;
			
			public function __construct() {
                                
				// Action to display coupons field on product edit page
				add_action( 'woocommerce_product_options_general_product_data', array(&$this, 'woocommerce_product_options_coupons') );
				add_action( 'woocommerce_process_product_meta_simple', array(&$this, 'woocommerce_process_product_meta_coupons') );
				add_action( 'woocommerce_process_product_meta_variable', array(&$this, 'woocommerce_process_product_meta_coupons') );
				add_action( 'wp_ajax_woocommerce_json_search_coupons', array(&$this, 'woocommerce_json_search_coupons') );

				// Actions on order status change
				add_action( 'woocommerce_order_status_completed', array(&$this, 'sa_add_coupons'), 19 );
				add_action( 'woocommerce_order_status_completed', array(&$this, 'coupons_used'), 19 );
				add_action( 'woocommerce_order_status_processing', array(&$this, 'sa_add_coupons'), 19 );
                                add_action( 'woocommerce_order_status_processing', array(&$this, 'coupons_used'), 19 );
				add_action( 'woocommerce_order_status_refunded', array(&$this, 'sa_remove_coupons'), 19 );
				add_action( 'woocommerce_order_status_cancelled', array(&$this, 'sa_remove_coupons'), 19 );

				// Default settings for Store Credit / Gift Certificate
				add_option('woocommerce_delete_smart_coupon_after_usage', 'yes');
				add_option('woocommerce_smart_coupon_apply_before_tax', 'no');
				add_option('woocommerce_smart_coupon_individual_use', 'no');
				add_option('woocommerce_smart_coupon_show_my_account', 'yes');

				// Gift Certificate Settings to be displayed under WooCommerce->Settings
				$this->credit_settings = array(
					array(
						'name' 				=> __( 'Store Credit / Gift Certificate', 'wc_smart_coupons' ),
						'type' 				=> 'title',
						'desc' 				=> __('The following options are specific to Gift / Credit.', 'wc_smart_coupons'),
						'id' 				=> 'smart_coupon_options'
					),
					array(
						'name' 				=> __('Default Gift / Credit options', 'wc_smart_coupons'),
						'desc' 				=> __('Show Credit on My Account page.', 'wc_smart_coupons'),
						'id' 				=> 'woocommerce_smart_coupon_show_my_account',
						'type' 				=> 'checkbox',
						'default'			=> 'yes',
						'checkboxgroup'		=> 'start'
					),
					array(
						'desc' 				=> __('Delete Gift / Credit, when credit is used up.', 'wc_smart_coupons'),
						'id' 				=> 'woocommerce_delete_smart_coupon_after_usage',
						'type' 				=> 'checkbox',
						'default'			=> 'yes',
						'checkboxgroup'		=> ''
					),
					array(
						'desc' 				=> __('Individual use', 'wc_smart_coupons'),
						'id' 				=> 'woocommerce_smart_coupon_individual_use',
						'type' 				=> 'checkbox',
						'default'			=> 'no',
						'checkboxgroup'                 => ''
					),
                                        array(
                                                'name'                          => __( "E-mail's subject", 'wc_smart_coupons' ),
                                                'desc'                          => __( "This text will be used as subject for e-mails to be send to customers. Default: Congratulations! You've received a coupon", 'wc_smart_coupons' ),
                                                'id'                            => 'smart_coupon_email_subject',
                                                'type'                          => 'textarea',
                                                'desc_tip'                      =>  true,
                                                'args'                          => 'cols="50" rows="2"'
                                        ),
					array(
						'type' 				=> 'sectionend',
						'id' 				=> 'smart_coupon_options'
					)
				);

				// Filters for handling coupon types & checking its validity
				add_filter( 'woocommerce_coupon_discount_types', array(&$this, 'add_smart_coupon_discount_type') );
				add_filter( 'woocommerce_coupon_is_valid', array(&$this, 'is_smart_coupon_valid'), 10, 2 );

				// Actions for handling processing of Gift Certificate
				add_action( 'woocommerce_new_order', array(&$this, 'update_smart_coupon_balance'), 10 );
				add_action( 'woocommerce_calculate_totals', array(&$this, 'apply_smart_coupon_to_cart') );
				add_action( 'woocommerce_before_my_account', array(&$this, 'show_smart_coupon_balance') );
				add_action( 'woocommerce_email_after_order_table', array(&$this, 'show_store_credit_balance'), 10, 5 );

				// Actions for Gift Certificate settings
				add_action( 'woocommerce_settings_digital_download_options_after', array(&$this, 'smart_coupon_admin_settings'));
				add_action( 'woocommerce_update_options_general', array(&$this, 'save_smart_coupon_admin_settings'));

				// Actions to show gift certificate & receiver's details form
				add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'show_attached_gift_certificates' ) );
				add_action( 'woocommerce_checkout_shipping', array( &$this, 'gift_certificate_receiver_detail_form' ), 1000 );
				add_action( 'woocommerce_before_checkout_process', array( &$this, 'verify_gift_certificate_receiver_details' ) );
				add_action( 'woocommerce_new_order', array( &$this, 'add_gift_certificate_receiver_details_in_order' ) );

				// Action to show available coupons
				add_action( 'woocommerce_after_cart_table', array( &$this, 'show_available_coupons_after_cart_table' ) );
				add_action( 'woocommerce_before_checkout_form', array( &$this, 'show_available_coupons_before_checkout_form' ), 11 );

                                // Action to show duplicate icon for coupons
                                add_filter( 'post_row_actions', array( &$this,'woocommerce_duplicate_coupon_link_row'), 1, 2 );

                                // Action to create duplicate coupon
                                add_action( 'admin_action_duplicate_coupon', array( &$this,'woocommerce_duplicate_coupon_action') );

                                // Action to search coupon based on email ids in customer email postmeta key
                                add_action( 'parse_request', array( &$this,'woocommerce_admin_coupon_search' ) );
                                add_filter( 'get_search_query', array( &$this,'woocommerce_admin_coupon_search_label' ) );

                                // Action for importing coupon csv file
                                add_action( 'admin_menu', array(&$this, 'woocommerce_coupon_admin_menu') );
                                add_action( 'admin_init', array(&$this, 'woocommerce_coupon_admin_init') );

                                // Action for settings on coupon page
                                add_action( 'woocommerce_coupon_options', array(&$this, 'woocommerce_smart_coupon_options') );
                                add_action( 'save_post', array(&$this, 'woocommerce_process_smart_coupon_meta'), 10, 2 );

                                add_action( 'woocommerce_single_product_summary', array(&$this, 'call_for_credit_form') );
                                add_filter( 'woocommerce_is_purchasable', array(&$this, 'make_product_purchasable'), 10, 2 );
                                add_action( 'woocommerce_before_calculate_totals', array(&$this, 'override_price_before_calculate_totals') );
                                add_filter( 'woocommerce_add_to_cart_validation', array(&$this, 'pass_products_price_to_cart'), 10, 3 );
                                
                                add_action( 'woocommerce_after_shop_loop_item', array(&$this, 'remove_add_to_cart_button_from_shop_page') );
			}
                        
                        //
                        function remove_add_to_cart_button_from_shop_page() {
                            global $product;
                            
                            $coupons = get_post_meta( $product->id, '_coupon_title', true );

                            if ( !empty( $coupons ) && $this->is_coupon_amount_pick_from_product_price( $coupons ) && !( $product->get_price() > 0 ) ) {
                                ?>
                                <script type="text/javascript">
                                    jQuery(function(){
                                        jQuery('a[data-product_id="'+<?php echo $product->id; ?>+'"]').remove();
                                    });
                                </script>
                                <?php
                            }
                        }
                        
                        //
                        function pass_products_price_to_cart( $validation, $product_id, $quantity ) {
                            global $woocommerce;
                            
                            // NEWLY ADDED CODE TO MAKE WC 2.0 COMPATIBLE.
                            if( function_exists( 'get_product' ) ){
                                $_product = get_product( $product_id ) ;
                            } else {
                                $_product = new WC_Product( $product_id ) ;
                            }
                            
                            $coupons = get_post_meta( $product_id, '_coupon_title', true );

                            if ( !empty( $coupons ) && $this->is_coupon_amount_pick_from_product_price( $coupons ) && !( $_product->get_price() > 0 ) ) {
                               
                                // NEWLY ADDED CODE TO MAKE COMPATIBLE.
                                if( function_exists( 'get_product' ) ) {
                                    $woocommerce->session->customer_price = $_REQUEST['credit_called'][$_REQUEST['add-to-cart']];
                                } else {
                                    $_SESSION['customer_price'] = $_REQUEST['credit_called'][$_REQUEST['add-to-cart']];
                                }
                                
                            }
                            return $validation;
                        }

                        //
                        function override_price_before_calculate_totals( $cart_object ) {
                            global $woocommerce;
                            
                            foreach ( $cart_object->cart_contents as $key => $value ) {

                                $coupons = get_post_meta( $value['data']->id, '_coupon_title', true );

                                if ( !empty( $coupons ) && $this->is_coupon_amount_pick_from_product_price( $coupons ) && !( $value['data']->price > 0 ) ) {
                                  
                                    // NEWLY ADDED CODE TO MAKE COMPATIBLE.
                                    if( function_exists( 'get_product' ) ) {
                                        $price = ( isset( $woocommerce->session->customer_price ) ) ? $woocommerce->session->customer_price: '';
                                    } else {                         
                                        $price = ( isset( $_SESSION['customer_price'] ) ) ? $_SESSION['customer_price']: '';
                                    }

                                    $value['data']->price = $price;
                                }

                            }

                        }

                        //
                        function make_product_purchasable( $purchasable, $product ) {

                            $coupons = get_post_meta( $product->id, '_coupon_title', true );

                            if ( !empty( $coupons ) && $product instanceof WC_Product && $product->get_price() === '' && $this->is_coupon_amount_pick_from_product_price( $coupons ) && !( $product->get_price() > 0 ) ) {
                                return true;
                            }

                            return $purchasable;
                        }

                        //
                        function is_coupon_amount_pick_from_product_price( $coupons ) {
                            global $woocommerce;

                            foreach ( $coupons as $coupon_code ) {
                                $coupon = new WC_Coupon( $coupon_code );
                                if ( $coupon->discount_type == 'smart_coupon' && get_post_meta( $coupon->id, 'is_pick_price_of_product', true ) == 'yes' ) {
                                    return true;
                                }
                            }
                            return false;
                        }

                        //
                        function call_for_credit_form() {
                            global $product, $woocommerce;

                            if ( $product instanceof WC_Product_Variation ) return;

                            $coupons = get_post_meta( $product->id, '_coupon_title', true );

                            if ( !function_exists( 'is_plugin_active' ) ) {
                                if ( ! defined('ABSPATH') ) {
                                    include_once ('../../../wp-load.php');
                                }
                                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                            }

                            // MADE CHANGES IN THE CONDITION TO SHOW INPUT FIELDFOR PRICE ONLY FOR COUPON AS A PRODUCT
                            if ( !empty( $coupons ) && $this->is_coupon_amount_pick_from_product_price( $coupons ) && ( !( $product->get_price() != '' || ( is_plugin_active( 'woocommerce-name-your-price/woocommerce-name-your-price.php' ) && ( get_post_meta( $product->id, '_nyp', true ) == 'yes' ) ) ) ) ) {
                                ?>
                                <script type="text/javascript">
                                    jQuery(function(){
                                        var validateCreditCalled = function(){
                                            var enteredCreditAmount = jQuery('input#credit_called').val();
                                            if ( enteredCreditAmount < 0.01 ) {
                                                jQuery('p#error_message').text('<?php _e('Invalid amount', 'wc_smart_coupons'); ?>');
                                                jQuery('input#credit_called').css('border-color', 'red');
                                                return false;
                                            } else {
                                                jQuery('p#error_message').text('');
                                                jQuery('input#credit_called').css('border-color', '');
                                                return true;
                                            }
                                        };

                                        jQuery('input#credit_called').change(function(){
                                            validateCreditCalled();
                                        });

                                        jQuery('input#credit_called').live('keyup', function(){
                                            jQuery('input#hidden_credit').remove();
                                            jQuery('div.quantity').append('<input type="hidden" id="hidden_credit" name="credit_called[<?php echo $product->id; ?>]" value="'+jQuery('input#credit_called').val()+'" />');
                                        });

                                        jQuery('form').submit(function(e){
                                            if ( validateCreditCalled() == false ) {
                                                e.preventDefault();
                                            }
                                        });
                                        
                                    });
                                </script>
                                <br /><br />
                                <div id="call_for_credit">
                                    <?php

                                        $currency_pos = get_option( 'woocommerce_currency_pos' );
                                        $currency_symbol = get_woocommerce_currency_symbol();
                                        $input_price = "<input id='credit_called' type='text' name='credit_called' value='' autocomplete='off' />";

                                        switch ( $currency_pos ) {
                                                case 'left' :
                                                        $echo = $currency_symbol . $input_price;
                                                break;
                                                case 'right' :
                                                        $echo = $input_price . $currency_symbol;
                                                break;
                                                case 'left_space' :
                                                        $echo = $currency_symbol . '&nbsp;' . $input_price;
                                                break;
                                                case 'right_space' :
                                                        $echo = $input_price . '&nbsp;' . $currency_symbol;
                                                break;
                                        }

                                    ?>
                                    <?php _e('Purchase Credit worth ', 'wc_smart_coupons'); echo '<br /><br />' . $echo; ?>
                                    <p id="error_message" style="color: red;"></p>
                                </div><br />
                                <?php

                            }
                        }

                        // Function to notifiy user about remaining balance in Store Credit in "Order Complete" email
                        function show_store_credit_balance( $order, $send_to_admin ) {
                                global $woocommerce;

                                if ( $send_to_admin ) return;

                                if ( sizeof( $order->get_used_coupons() ) > 0 ) {
                                        $store_credit_balance = '';
                                        foreach ( $order->get_used_coupons() as $code ) {
                                                if ( ! $code ) continue;
                                                $coupon = new WC_Coupon( $code );

                                                if ( $coupon->type == 'smart_coupon' && $coupon->amount > 0 ) {
                                                        $store_credit_balance .= '<li><strong>'. $coupon->code .'</strong> &mdash; '. woocommerce_price( $coupon->amount ) .'</li>';
                                                }
                                        }

                                        if ( !empty( $store_credit_balance ) ) {
                                                echo "<br /><h3>" . __( 'Store Credit / Gift Certificate Balance', 'wc_smart_coupons' ) . ": </h3>";
                                                echo "<ul>" . $store_credit_balance . "</ul><br />";
                                        }
                                }
                        }

			// Function to show available coupons after cart table
			function show_available_coupons_after_cart_table() {

				if ( $this->show_available_coupons() ) {

					global $woocommerce;

				?>

					<script type="text/javascript">

						// Apply Coupon through Ajax
						jQuery("a.apply_coupons_credits").click( function() {

							var coupon_code = jQuery(this).attr('name');

							jQuery.ajax({
								url:	'<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>',
								type:	'post',
								data: 	{
									'coupon_code': coupon_code,
									'apply_coupon': jQuery('input[name=apply_coupon]').val(),
									'_n': jQuery('#_n').val(),
									'_wp_http_referer': jQuery('input[name=_wp_http_referer]').val()
								},
								dataType: 'html',
								success: function( response ) {
									jQuery('body').html( response );
								}
							});
							return false;

						});

					</script>

				<?php

				}

			}

			// Function to show available coupons before checkout form
			function show_available_coupons_before_checkout_form() {

				if ( $this->show_available_coupons() ) {

				?>

					<script type="text/javascript">
						jQuery('#coupons_list').hide();

						jQuery('a.showcoupon').click( function() {
							jQuery('#coupons_list').slideToggle();
							return false;
						});

						// Apply Coupon through Ajax
						jQuery("a.apply_coupons_credits").click( function() {
						    var coupon_code = jQuery(this).attr('name');
							jQuery.ajax({
							     type: 		'POST',
							     url: 		woocommerce_params.ajax_url,
							     dataType: 	'html',
							     data: {
							     	action: 			'woocommerce_apply_coupon',
									security: 			woocommerce_params.apply_coupon_nonce,
									coupon_code:		coupon_code
							     },
							     success: function( code ) {
							     	jQuery('.woocommerce_error, .woocommerce_message').remove();
									jQuery('form.checkout_coupon').removeClass('processing').unblock();

									if ( code ) {
										jQuery('form.checkout_coupon').before( code );
										jQuery('form.checkout_coupon').slideUp();
										jQuery('#coupons_list').slideToggle();
										jQuery('li[name='+coupon_code+']').remove();

										jQuery('body').trigger('update_checkout');
									}
								 }
							});
							return false;

						});

					</script>

				<?php

				}

			}

			// Function to show available coupons
			function show_available_coupons() {
                                global $woocommerce;
                                
				if ( !is_user_logged_in() ) return false;

				$coupons = $this->get_customer_credit();

				if ( empty( $coupons ) ) return false;

					?>
					<div id='coupons_list'><h3><?php _e( 'Available Coupons (Click on the coupon to use it)', 'wc_smart_coupons' ) ?></h3>
					<ul>
						<?php
                                                
                                                // NEWLY ADDED CODE TO MAKE COMPATIBLE.
                                                if( function_exists( 'get_product' ) ){
                                                    $coupons_applied = $woocommerce->cart->get_applied_coupons();
                                                } else {
                                                    $coupons_applied = $_SESSION['coupons'];
                                                }
                                                
						foreach ( $coupons as $code ) {

							if ( in_array( $code->post_title, $coupons_applied ) ) continue;

							$coupon = new WC_Coupon( $code->post_title );

                                                        if ( empty( $coupon->discount_type ) ) continue;

							switch ( $coupon->discount_type ) {

								case 'smart_coupon':
									$coupon_type = 'Store Credit';
									$coupon_amount = woocommerce_price( $coupon->amount );
									break;

								case 'fixed_cart':
									$coupon_type = 'Cart Discount';
									$coupon_amount = woocommerce_price( $coupon->amount );
									break;

								case 'fixed_product':
									$coupon_type = 'Product Discount';
									$coupon_amount = woocommerce_price( $coupon->amount );
									break;

								case 'percent_product':
									$coupon_type = 'Product Discount';
									$coupon_amount = $coupon->amount . '%';
									break;

								case 'percent':
									$coupon_type = 'Cart Discount';
									$coupon_amount = $coupon->amount . '%';
									break;

							}

							echo '<li name="' . $coupon->code . '"><strong><a href="" class="apply_coupons_credits" name="' . $coupon->code . '">' . $coupon->code . ' (' . $coupon_type  . ')</a></strong> &mdash;'. $coupon_amount .'</li>';

						}
						?>
					</ul></div>
					<?php

					return true;

			}

			// Function to add gift certificate receiver's details in order itself
			function add_gift_certificate_receiver_details_in_order( $order_id ) {

				if ( !isset( $_POST['gift_receiver_email'] ) || count( $_POST['gift_receiver_email'] ) <= 0 ) return;

                                if ( $_POST['billing_email'] != $_POST['gift_receiver_email'] ) {

					update_post_meta( $order_id, 'gift_receiver_email', $_POST['gift_receiver_email'] );

					if ( isset( $_POST['gift_receiver_name'] ) && $_POST['gift_receiver_name'] != '' ) {
						update_post_meta( $order_id, 'gift_receiver_name', $_POST['gift_receiver_name'] );
					}

					if ( isset( $_POST['gift_receiver_message'] ) && $_POST['gift_receiver_message'] != '' ) {
						update_post_meta( $order_id, 'gift_receiver_message', $_POST['gift_receiver_message'] );
					}

				}
			}

			// Function to verify gift certificate form details
			function verify_gift_certificate_receiver_details() {
				global $woocommerce;

                                if ( !isset( $_POST['gift_receiver_email'] ) || count( $_POST['gift_receiver_email'] ) <= 0 ) return;

                                foreach ( $_POST['gift_receiver_email'] as $key => $emails ) {
                                    foreach ( $emails as $index => $email ) {
                                        if ( empty( $email ) ) {
                                            $_POST['gift_receiver_email'][$key][$index] = $_POST['billing_email'];
                                        } elseif ( !empty( $email ) && !is_email( $email ) ) {
                                            $woocommerce->add_error( __( 'Error: Gift Certificate Receiver&#146;s E-mail address is invalid.', 'wc_smart_coupons' ) );
                                            return;
                                        }
                                    }
                                }

                                /** This session value not used anywhere.
                                    $_SESSION['gift_receiver_email'] = $_POST['gift_receiver_email'];

                                    if( function_exists( 'get_product' ) ) {
                                        $woocommerce->session->gift_receiver_email = $_POST['gift_receiver_email'];
                                    } else {
                                        $_SESSION['gift_receiver_email'] = $_POST['gift_receiver_email'];
                                    }
                                * 
                                */
			}

                        //
                        function add_text_field_for_email( $coupon = '', $product = '' ) {
                            global $woocommerce;

                            if ( empty( $coupon ) ) return;

                            for ( $i = 0; $i < $product['quantity']; $i++ ) {

                                $coupon_amount = ( $this->is_coupon_amount_pick_from_product_price( $coupon ) ) ? $product['data']->price: $coupon->amount;

                                // NEWLY ADDED CONDITION TO NOT TO SHOW TEXTFIELD IF COUPON AMOUNT IS "0"
                                if($coupon_amount != '' || $coupon_amount > 0) {
                                    ?>

                                    <tr>
                                        <td><input class="gift_receiver_email" type="text" name="gift_receiver_email[<?php echo $coupon->id; ?>][]" value="" /></td>
                                        <td><p class="coupon_amount_label"><?php echo $coupon_amount; ?></p></td>
                                    </tr>

                                    <?php
                                }

                            }

                        }

			// Function to display form for entering details of the gift certificate's receiver
			function gift_certificate_receiver_detail_form() {
				global $woocommerce;

                                $form_started = false;

				foreach ( $woocommerce->cart->cart_contents as $product ) {

					$coupon_titles = get_post_meta( $product['product_id'], '_coupon_title', true );

                                        // NEWLY ADDED CONDITION TO MAKE COMPATIBLE
                                        if( function_exists( 'get_product' ) ){
                                            $_product = get_product( $product['product_id'] ) ;
                                        } else {
                                            $_product = new WC_Product( $product['product_id'] ) ;
                                        }
                                        
                                        $price = $_product->get_price();
                                        
					if ( $coupon_titles ) {

                                            foreach ( $coupon_titles as $coupon_title ) {

                                                    $coupon = new WC_Coupon( $coupon_title );

                                                    $pick_price_of_prod = get_post_meta( $coupon->id, 'is_pick_price_of_product', true ) ;
                                                    
                                                    // MADE CHANGES IN THE CONDITION TO SHOW FORM
                                                    if ( $coupon->type == 'smart_coupon' || ( $pick_price_of_prod == 'yes' &&  $price == '' ) || ( $pick_price_of_prod == 'yes' &&  $price != '' && $coupon->amount > 0)  ) {

                                                            if ( !$form_started ) {

                                                                    ?>

                                                                    </div></div>

                                                                    <div class="gift-certificate">
                                                                        <style type="text/css">
                                                                            input.gift_receiver_email {
                                                                                min-width: 100%;
                                                                            }
                                                                            p.coupon_amount_label {
                                                                                text-align: center;
                                                                            }
                                                                            table#gift-certificate-receiver-form thead th {
                                                                                text-align: center;
                                                                            }
                                                                            input#deliver_on_date {
                                                                                text-align: center;
                                                                            }
                                                                        </style>
                                                                        <div class="gift-certificate-receiver-detail-form">
                                                                            <h3><?php _e( 'Store Credit / Gift Certificate receiver&#146;s Details', 'wc_smart_coupons' ); ?></h3>
                                                                            <p><?php _e( '(Enter details to gift to someone. Coupon amount with Blank E-mail ID will be send to you.)', 'wc_smart_coupons' ); ?></p>
                                                                            <table id="gift-certificate-receiver-form">
                                                                                <thead >
                                                                                    <th><?php _e('E-mail IDs', 'wc_smart_coupons'); ?></th>
                                                                                    <th><?php _e('Coupon amount', 'wc_smart_coupons'); ?></th>
                                                                                </thead>

                                                                    <?php

                                                                    $form_started = true;

                                                                }

                                                                $this->add_text_field_for_email( $coupon, $product );

                                                    }

                                            }

					}

				}

                                if ( $form_started ) {
                                    ?>
                                    <tr>
                                        <td colspan="2"><textarea placeholder="<?php _e('Message', 'wc_smart_coupons'); ?>..." id="gift_receiver_message" name="gift_receiver_message" cols="50" rows="5"></textarea></td>
                                    </tr>
                                    </table>
                                    <?php
                                }

			}

			// Function to show gift certificates that are attached with the product
			function show_attached_gift_certificates() {
				global $post, $woocommerce, $wp_rewrite;

				$coupon_titles = get_post_meta( $post->ID, '_coupon_title', true );

                                //NEWLY ADDED TO EVENSHOW COUPON THAT HAS "is_pick_price_of_product" : TRUE
                                if( function_exists( 'get_product' ) ){
                                    $_product = get_product( $post->ID ) ;
                                } else {
                                    $_product = new WC_Product( $post->ID ) ;
                                }
                                
                                $price = $_product->get_price();

				if ( $coupon_titles && count( $coupon_titles ) > 0 && !empty( $price ) ) {

					$all_discount_types = $woocommerce->get_coupon_discount_types();

					echo '<div class="clear"></div>';
		    		echo '<div class="gift-certificates">';
		    		echo '<br /><p>' . __( 'By purchasing this product, you will get the following coupon(s):', 'wc_smart_coupons' ) . '';
		    		echo '<ul>';

		    		foreach ( $coupon_titles as $coupon_title ) {

						$coupon = new WC_Coupon( $coupon_title );

						switch ( $coupon->discount_type ) {

							case 'smart_coupon':
                                                            
                                                                //NEWLY ADDED TO EVENSHOW COUPON THAT HAS "is_pick_price_of_product" : TRUE
                                                                if( get_post_meta( $coupon->id, 'is_pick_price_of_product', true ) == 'yes' ){
                                                                    $amount = ($_product->price > 0) ? __( 'Store Credit of ', 'wc_smart_coupons' ) . $_product->price : "" ;
                                                                } else {
                                                                    $amount = __( 'Store Credit of ', 'wc_smart_coupons' ) . woocommerce_price( $coupon->amount );
                                                                }
								
								break;

							case 'fixed_cart':
								$amount = woocommerce_price( $coupon->amount ).__( ' discount on your entire purchase.', 'wc_smart_coupons' );
								break;

							case 'fixed_product':
								$amount = woocommerce_price( $coupon->amount ).__( ' discount on this product.', 'wc_smart_coupons' );
								break;

							case 'percent_product':
								$amount = $coupon->amount.'%'.__( ' discount on this product.', 'wc_smart_coupons' );
								break;

							case 'percent':
								$amount = $coupon->amount.'%'.__( ' discount on your entire purchase.', 'wc_smart_coupons' );
								break;
						}
						if(!empty($amount)) echo '<li>' . $amount . '</li>';
					}
					echo '</ul></p></div>';
				}
			}

			// Function for saving settings for Gift Certificate
			function save_smart_coupon_admin_settings() {
				woocommerce_update_options( $this->credit_settings );
			}

			// Function to display fields for configuring settings for Gift Certificate
			function smart_coupon_admin_settings() {
				woocommerce_admin_fields( $this->credit_settings );
			}

			// Function to display current balance associated with Gift Certificate
			function show_smart_coupon_balance() {
				$coupons = $this->get_customer_credit();

				if ( $coupons ) {
					?>
					<h2><?php _e('Store Credit Available', 'wc_smart_coupons'); ?></h2>
					<ul class="gift-certificate">
						<?php
						foreach ( $coupons as $code ) {

							$coupon = new WC_Coupon( $code->post_title );

							if ( $coupon->type == 'smart_coupon' ) {

								echo '<li><strong>'. $coupon->code .'</strong> &mdash;'. woocommerce_price( $coupon->amount ) .'</li>';

							}
						}
						?>
					</ul>
					<?php
				}

			}

			// Function to apply Gift Certificate's credit to cart
			function apply_smart_coupon_to_cart() {
				global $woocommerce;

				$woocommerce->cart->smart_coupon_credit_used = array();

				if ($woocommerce->cart->applied_coupons) {

					foreach ($woocommerce->cart->applied_coupons as $code) {

						$smart_coupon = new WC_Coupon( $code );

						if ( $smart_coupon->is_valid() && $smart_coupon->type=='smart_coupon' ) {

							$order_total = $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total + $woocommerce->cart->shipping_total;

							if ( $woocommerce->cart->discount_total != 0 && ( $woocommerce->cart->discount_total + $smart_coupon->amount ) > $order_total ) {
								$smart_coupon->amount = $order_total - $woocommerce->cart->discount_total;
							} elseif( $smart_coupon->amount > $order_total ) {
								$smart_coupon->amount = $order_total;
							}

							$woocommerce->cart->discount_total 		= $woocommerce->cart->discount_total + $smart_coupon->amount;
							$woocommerce->cart->smart_coupon_credit_used[$code] 	= $smart_coupon->amount;
						}
					}
				}
			}

			// Function to update Store Credit / Gift Ceritficate balance
			function update_smart_coupon_balance() {
				global $woocommerce;

				if( $woocommerce->cart->applied_coupons ) {

					foreach( $woocommerce->cart->applied_coupons as $code ) {

						$smart_coupon = new WC_Coupon( $code );

						if($smart_coupon->type == 'smart_coupon' ) {

							$credit_remaining = max( 0, ( $smart_coupon->amount - $woocommerce->cart->smart_coupon_credit_used[$code] ) );

							if ( $credit_remaining <= 0 && get_option( 'woocommerce_delete_smart_coupon_after_usage' ) == 'yes' ) {
								wp_delete_post( $smart_coupon->id );
							} else {
								update_post_meta( $smart_coupon->id, 'coupon_amount', $credit_remaining );
							}

						}

					}

				}
			}

			// Function to return validity of Store Credit / Gift Certificate
			function is_smart_coupon_valid( $valid, $coupon ) {
				global $woocommerce;

				if ( $valid && $coupon->type == 'smart_coupon' && $coupon->amount <= 0 ) {
					$woocommerce->add_error( __('There is no credit remaining on this coupon.', 'wc_smart_coupons') );
					return false;
				}

				return $valid;
			}

			// Function to add new discount type 'smart_coupon'
			function add_smart_coupon_discount_type( $discount_types ) {
				$discount_types['smart_coupon'] = __('Store Credit / Gift Certificate', 'wc_smart_coupons');
				return $discount_types;
			}

			// Function to search coupons
			function woocommerce_json_search_coupons( $x = '', $post_types = array( 'shop_coupon' ) ) {
				global $woocommerce, $wpdb;

				check_ajax_referer( 'search-coupons', 'security' );

				$term = (string) urldecode(stripslashes(strip_tags($_GET['term'])));

				if (empty($term)) die();

					$args = array(
						'post_type'		=> $post_types,
						'post_status' 		=> 'publish',
						'posts_per_page' 	=> -1,
						's' 			=> $term,
						'fields'			=> 'all'
					);

//                              $posts = get_posts( $args );        // In some cases get_post is returning posts instead of coupons

                                $posts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type LIKE 'shop_coupon' AND post_title LIKE '$term%' AND post_status = 'publish'");

				$found_products = array();

				$all_discount_types = $woocommerce->get_coupon_discount_types();

				if ($posts) foreach ($posts as $post) {

					$discount_type = get_post_meta($post->ID, 'discount_type', true);

					if ( !empty( $all_discount_types[$discount_type] ) ) {
                                            $discount_type = ' (Type: ' . $all_discount_types[$discount_type] . ')';
                                            $found_products[get_the_title( $post->ID )] = get_the_title( $post->ID ) . $discount_type;
                                        }

				}

				echo json_encode( $found_products );

				die();
			}

			// Function to provide area for entering coupon code
			function woocommerce_product_options_coupons() {
				global $post, $woocommerce;

				?>
				<p class="form-field"><label for="_coupon_title"><?php _e('Coupons', 'wc_smart_coupons'); ?></label>

				<select id="_coupon_title" name="_coupon_title[]" class="ajax_chosen_select_coupons" multiple="multiple" data-placeholder="<?php _e('Search for a coupon...', 'wc_smart_coupons'); ?>">

				<?php
						if ( ! class_exists( 'WC_Coupon' ) ) {
							require_once( WP_PLUGIN_DIR . '/woocommerce/classes/class-wc-coupon.php' );
						}

						$all_discount_types = $woocommerce->get_coupon_discount_types();

						$coupon_titles = get_post_meta( $post->ID, '_coupon_title', true );

						if ($coupon_titles) {

							foreach ($coupon_titles as $coupon_title) {

								$coupon = new WC_Coupon( $coupon_title );

								$discount_type = $coupon->discount_type;

								if (isset($discount_type) && $discount_type) $discount_type = ' ( Type: ' . $all_discount_types[$discount_type] . ' )';

								echo '<option value="'.$coupon_title.'" selected="selected">'. $coupon_title . $discount_type .'</option>';

							}
						}
					?>
				</select>

					<script type="text/javascript">

						// Ajax Chosen Coupon Selectors
						jQuery("select.ajax_chosen_select_coupons").ajaxChosen({
						    method: 	'GET',
						    url: 		'<?php echo admin_url('admin-ajax.php'); ?>',
						    dataType: 	'json',
						    afterTypeDelay: 100,
						    data:		{
						    	action: 		'woocommerce_json_search_coupons',
								security: 		'<?php echo wp_create_nonce("search-coupons"); ?>'
						    }
						}, function (data) {

							var terms = {};

						    jQuery.each(data, function (i, val) {
						        terms[i] = val;
						    });

						    return terms;
						});

					</script>

				<img class="help_tip" data-tip='<?php _e('These coupon/s will be given to customers who buy this product. The coupon code will be automatically sent to their email address on purchase.', 'wc_smart_coupons'); ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/tip.png" /></p>

				<?php

			}

			// Function to save coupon code to database
			function woocommerce_process_product_meta_coupons( $post_id ) {
				if (isset($_POST['_coupon_title'])) :
					update_post_meta( $post_id, '_coupon_title', $_POST['_coupon_title'] );
				else :
					update_post_meta( $post_id, '_coupon_title', array() );
				endif;
			}

			// Function to track whether coupon is used or not
			function coupons_used( $order_id ) {
				$order = new WC_Order( $order_id );

				$email = get_post_meta( $order_id, 'gift_receiver_email', true );


                                /** this validation is not required
                                 * 
                                 * 
                                    if ( empty( $email ) || ! is_email( $email ) ) {
                                            $email = $order->billing_email;
                                    }
                                 * 
                                 * 
                                 * **/

				if ( $order->get_used_coupons() ) {
					$this->update_coupons( $order->get_used_coupons(), $email, '', 'remove' );
				}
			}

			// Function to update details related to coupons
			function update_coupons( $coupon_titles = array(), $email, $product_ids = '', $operation, $order_item = null, $gift_certificate_receiver = false, $gift_certificate_receiver_name = '', $message_from_sender = '', $gift_certificate_sender_name = '', $gift_certificate_sender_email = '', $order_id = '' ) {

                            global $smart_coupon_codes;

                                $prices_include_tax = (get_option('woocommerce_prices_include_tax')=='yes') ? true : false;

				if ( !empty( $coupon_titles ) ) {

					if ( ! class_exists( 'WC_Coupon' ) ) {
						require_once( WP_PLUGIN_DIR . '/woocommerce/classes/class-wc-coupon.php' );
					}

					if ( isset( $order_item['qty'] ) && $order_item['qty'] > 1 ) {
						$qty = $order_item['qty'];
					} else {
						$qty = 1;
					}

					foreach ( $coupon_titles as $coupon_title ) {

						$coupon = new WC_Coupon( $coupon_title );

                                                $auto_generation_of_code = get_post_meta( $coupon->id, 'auto_generate_coupon', true);

						if ( ( $auto_generation_of_code == 'yes' || $coupon->discount_type == 'smart_coupon' ) && $operation == 'add' ) {

                                                        if ( get_post_meta( $coupon->id, 'is_pick_price_of_product', true ) == 'yes' && $coupon->discount_type == 'smart_coupon' ) {
//                                                            $products_price = ( $prices_include_tax ) ? $order_item['line_total'] : $order_item['line_total'] + $order_item['line_tax'];
                                                            $products_price = $order_item['line_total'];
                                                            $amount = $products_price / $qty;
                                                        } else {
                                                            if ( $coupon->discount_type == 'fixed_cart' || $coupon->discount_type == 'fixed_product' ) {
                                                                $amount = $coupon->amount * $qty;
                                                            } else {
                                                                $amount = $coupon->amount;
                                                            }
                                                        }

                                                        $email_id = ( $auto_generation_of_code == 'yes' && $coupon->discount_type != 'smart_coupon' && !empty( $gift_certificate_sender_email ) ) ? $gift_certificate_sender_email : $email;

                                                        if( $amount > 0 ) {
                                                            $coupon_title =  $this->generate_smart_coupon( $email_id, $amount, $order_id, $coupon, $coupon->discount_type, $gift_certificate_receiver_name, $message_from_sender, $gift_certificate_sender_name, $gift_certificate_sender_email );
                                                        }

						} else {

							$coupon_receiver_email = ( $gift_certificate_sender_email != '' ) ? $gift_certificate_sender_email : $email;

							$old_customers_email_ids = (array) maybe_unserialize( get_post_meta( $coupon->id, 'customer_email', true ) );

							if ( $operation == 'add' && $auto_generation_of_code != 'yes' && $coupon->discount_type != 'smart_coupon') {

								if ( $qty && $operation == 'add' && ! ( $coupon->discount_type == 'percent_product' || $coupon->discount_type == 'percent' ) ) {
									$amount = $coupon->amount * $qty;
								} else {
									$amount = $coupon->amount;
								}

                                                                if ( $qty > 0 ) {
                                                                    for ( $i = 0; $i < $qty; $i++ ) 
                                                                        $old_customers_email_ids[] = $coupon_receiver_email;
                                                                }

                                                                $coupon_details = array(
                                                                    $coupon_receiver_email  =>  array(
                                                                        'parent'    => $coupon->id,
                                                                        'code'      => $coupon_title,
                                                                        'amount'    => $amount
                                                                    )
                                                                );

								$this->sa_email_coupon( $coupon_details, $coupon->discount_type );

							} elseif ( $operation == 'remove' && $coupon->discount_type != 'smart_coupon' ) {

								$key = array_search( $coupon_receiver_email, $old_customers_email_ids );

								if ($key !== false) {
									unset( $old_customers_email_ids[$key] );
								}

							}

							update_post_meta( $coupon->id, 'customer_email', $old_customers_email_ids );

						}

					}

				}

			}

                        //
                        function get_receivers_detail( $coupon_details = array(), $gift_certificate_sender_email = '' ) {

                            if ( count( $coupon_details ) <= 0 ) return 0;

                            global $woocommerce;

                            $receivers_email = array();

                            foreach ( $coupon_details as $coupon_id => $emails ) {
                                $discount_type = get_post_meta( $coupon_id, 'discount_type', true );
                                if ( $discount_type == 'smart_coupon' ) {
                                    $receivers_email = array_merge( $receivers_email, array_diff( $emails, array( $gift_certificate_sender_email ) ) );
                                }
                            }

                            return $receivers_email;
                        }

			// Function to process coupons based on change in order status
			function process_coupons( $order_id, $operation ) {
                            global $smart_coupon_codes;

                            $smart_coupon_codes = array();

                            if (get_post_meta( $order_id, 'coupon_sent', true)== 'yes') return;

				$order = new WC_Order( $order_id );
				$order_items = (array) $order->get_items();

				$receivers_emails = get_post_meta( $order_id, 'gift_receiver_email', true );
                                $email = $receivers_emails;

                                $gift_certificate_receiver = true;
                                $gift_certificate_sender_name = $order->billing_first_name . ' ' . $order->billing_last_name;
                                $gift_certificate_sender_email = $order->billing_email;
                                $gift_certificate_receiver_name = '';
                                $message_from_sender = get_post_meta( $order_id, 'gift_receiver_message', true );

                                $receivers_detail = array();

				foreach( $order_items as $item ) {

					$product = $order->get_product_from_item( $item );

					$coupon_titles = get_post_meta( $product->id, '_coupon_title', true );

					if ( $coupon_titles ) {

                                                $this->update_coupons( $coupon_titles, $email, '', $operation, $item, $gift_certificate_receiver, $gift_certificate_receiver_name, $message_from_sender, $gift_certificate_sender_name, $gift_certificate_sender_email, $order_id );

                                                if ( $operation == 'add' ) {
							$receivers_detail = $this->get_receivers_detail( $receivers_emails, $gift_certificate_sender_email );
						}

                                        }
				}

                                if ( count( $receivers_detail ) > 0 ) {
                                        update_post_meta($order_id, 'coupon_sent', 'yes');// to know whether coupon has sent or not
					$this->acknowledge_gift_certificate_sender( $receivers_detail, $gift_certificate_receiver_name, $email, $gift_certificate_sender_email );
				}

                                unset( $smart_coupon_codes );
			}

			// Function to acknowledge sender of gift credit
			function acknowledge_gift_certificate_sender( $receivers_detail = array(), $gift_certificate_receiver_name = '', $email = '', $gift_certificate_sender_email = '' ) {

                                if ( count( $receivers_detail ) <= 0 ) return;

				// Start collecting content for e-mail
				ob_start();

				$subject = __( 'Gift Certificate sent successfully!', 'wc_smart_coupons' );

				do_action('woocommerce_email_header', $subject);

				echo sprintf(__('You have successfully sent %d %s to %s (%s)', 'wc_smart_coupons'), count( $receivers_detail ), _n( 'Gift Certificate', 'Gift Certificates', count( $receivers_detail ), 'wc_smart_coupons'), $gift_certificate_receiver_name, implode( ', ', array_unique( $receivers_detail ) ) );

				do_action('woocommerce_email_footer');

				// Get contents of the e-mail to be sent
				$message = ob_get_clean();
				woocommerce_mail( $gift_certificate_sender_email, $subject, $message );

			}

			// Function to add details to coupons
			function sa_add_coupons( $order_id ) {
				$this->process_coupons( $order_id, 'add' );
			}

			// Function to remove details from coupons
			function sa_remove_coupons( $order_id ) {
				$this->process_coupons( $order_id, 'remove' );
			}

			//Function to send e-mail containing coupon code to customer
			function sa_email_coupon( $coupon_title, $discount_type, $gift_certificate_receiver_name = '', $message_from_sender = '', $gift_certificate_sender_name = '', $gift_certificate_sender_email = '' ) {
				global $woocommerce;

				$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                                $subject_string = __("Congratulations! You've received a coupon", 'wc_smart_coupons');

                                $url = ( get_option('permalink_structure') ) ? get_permalink( woocommerce_get_page_id('shop') ) : get_post_type_archive_link('product');

				if ( ( $discount_type == 'smart_coupon' ) && ( $gift_certificate_sender_name != '' || $gift_certificate_sender_email != '' ) ) {
					$from = ( $gift_certificate_sender_name != '' ) ? $gift_certificate_sender_name . ' ( ' . $gift_certificate_sender_email . ' )' : substr( $gift_certificate_sender_email, 0, strpos( $gift_certificate_sender_email, '@' ) );
					$subject_string .= ' ' . __( 'from', 'wc_smart_coupons' ) . ' ' . $from;
				}

                                $subject_string = ( get_option( 'smart_coupon_email_subject' ) && get_option( 'smart_coupon_email_subject' ) != '' ) ? __( get_option( 'smart_coupon_email_subject' ), 'wc_smart_coupons' ): $subject_string;

                                $subject = apply_filters( 'woocommerce_email_subject_gift_certificate', sprintf( '[%s] %s', $blogname, $subject_string ) );

                                foreach ( $coupon_title as $email => $coupon ) {

                                    $amount = $coupon['amount'];
                                    $coupon_code = $coupon['code'];

                                    switch ( $discount_type ) {

                                            case 'smart_coupon':
                                                    $email_heading 	=  sprintf(__('You have received credit worth %s ', 'wc_smart_coupons'), woocommerce_price($amount) );
                                                    break;

                                            case 'fixed_cart':
                                                    $email_heading 	=  sprintf(__('You have received a coupon worth %s (on entire purchase) ', 'wc_smart_coupons'), woocommerce_price($amount) );
                                                    break;

                                            case 'fixed_product':
                                                    $email_heading 	=  sprintf(__('You have received a coupon worth %s (for a product) ', 'wc_smart_coupons'), woocommerce_price($amount) );
                                                    break;

                                            case 'percent_product':
                                                    $email_heading 	=  sprintf(__('You have received a coupon worth %s%% (for a product) ', 'wc_smart_coupons'), $amount );
                                                    break;

                                            case 'percent':
                                                    $email_heading 	=  sprintf(__('You have received a coupon worth %s%% (on entire purchase) ', 'wc_smart_coupons'), $amount );
                                                    break;

                                    }

                                    // Buffer
                                    ob_start();

                                    include(apply_filters('woocommerce_gift_certificates_email_template', 'templates/email.php'));

                                    // Get contents of the e-mail to be sent
                                    $message = ob_get_clean();

                                    woocommerce_mail( $email, $subject, $message );

                                }

			}

                        //
                        function is_credit_sent( $email_id, $coupon ) {

                            global $smart_coupon_codes;

                            if ( ! empty( $smart_coupon_codes[$email_id] ) ) {
	                            foreach ( $smart_coupon_codes[$email_id] as $generated_coupon_details ) {
	                                if ( $generated_coupon_details['parent'] == $coupon->id ) return true;
	                            }
                            }

                            return false;

                        }

                        //
                        function generate_unique_code( $email = '', $coupon = '' ) {
                                $unique_code = ( !empty( $email ) ) ? strtoupper( uniqid( substr( preg_replace('/[^a-z0-9]/i', '', sanitize_title( $email ) ), 0, 5 ) ) ) : strtoupper( uniqid() );

                                if ( !empty( $coupon ) && get_post_meta( $coupon->id, 'auto_generate_coupon', true) == 'yes' ) {
                                     $prefix = get_post_meta( $coupon->id, 'coupon_title_prefix', true);
                                     $suffix = get_post_meta( $coupon->id, 'coupon_title_suffix', true);
                                     $unique_code = $prefix . $unique_code . $suffix;
                                }

                                return $unique_code;
                        }

			// Function for generating Gift Certificate
			function generate_smart_coupon( $email, $amount, $order_id = '', $coupon = '', $discount_type = 'smart_coupon', $gift_certificate_receiver_name = '', $message_from_sender = '', $gift_certificate_sender_name = '', $gift_certificate_sender_email = '' ) {

                            if ( $email == '' ) return false;

                            global $smart_coupon_codes;

                            if ( !is_array( $email ) ) {
                                $emails = array( $email => 1 );
                            } else {
                                $emails = array_count_values( $email[$coupon->id] );
                            }

                            foreach ( $emails as $email_id => $qty ) {

                                if ( $this->is_credit_sent( $email_id, $coupon ) ) continue;

                                $smart_coupon_code = $this->generate_unique_code( $email_id, $coupon );

                                $smart_coupon_args = array(
					'post_title' 	=> $smart_coupon_code,
					'post_content' 	=> '',
					'post_status' 	=> 'publish',
					'post_author' 	=> 1,
					'post_type'     => 'shop_coupon'
				);

				$smart_coupon_id = wp_insert_post( $smart_coupon_args );

                                $type                           = ( !empty( $coupon ) && !empty( $coupon->type ) ) ?  $coupon->type: 'smart_coupon';
                                $individual_use                 = ( !empty( $coupon ) ) ?  $coupon->individual_use: get_option('woocommerce_smart_coupon_individual_use');
                                $product_ids                    = ( !empty( $coupon ) ) ?  implode( ',', $coupon->product_ids ): '';
                                $exclude_product_ids            = ( !empty( $coupon ) ) ?  implode( ',', $coupon->exclude_product_ids ): '';
                                $usage_limit                    = ( !empty( $coupon ) ) ?  $coupon->usage_limit: '';
                                $expiry_date                    = ( !empty( $coupon ) && !empty( $coupon->expiry_date ) ) ?  date( 'Y-m-d', intval( $coupon->expiry_date ) ): '';
                                $apply_before_tax               = ( !empty( $coupon ) ) ?  $coupon->apply_before_tax: 'no';
                                $free_shipping                  = ( !empty( $coupon ) ) ?  $coupon->free_shipping: 'no';
                                $product_categories             = ( !empty( $coupon ) ) ?  $coupon->product_categories: '';
                                $exclude_product_categories     = ( !empty( $coupon ) ) ?  $coupon->exclude_product_categories: '';

				// Add meta for Gift Certificate
				update_post_meta( $smart_coupon_id, 'discount_type', $type );
				update_post_meta( $smart_coupon_id, 'coupon_amount', ( $amount * $qty ) );
				update_post_meta( $smart_coupon_id, 'individual_use', $individual_use );
				update_post_meta( $smart_coupon_id, 'product_ids', $product_ids );
				update_post_meta( $smart_coupon_id, 'exclude_product_ids', $exclude_product_ids );
				update_post_meta( $smart_coupon_id, 'usage_limit', $usage_limit );
				update_post_meta( $smart_coupon_id, 'expiry_date', $expiry_date );
				update_post_meta( $smart_coupon_id, 'customer_email', array( $email_id ) );
				update_post_meta( $smart_coupon_id, 'apply_before_tax', $apply_before_tax  );
				update_post_meta( $smart_coupon_id, 'free_shipping', $free_shipping );
                                update_post_meta( $smart_coupon_id, 'product_categories', $product_categories  );
                                update_post_meta( $smart_coupon_id, 'exclude_product_categories', $exclude_product_categories );
				update_post_meta( $smart_coupon_id, 'generated_from_order_id', $order_id );

                                $generated_coupon_details = array(
                                    'parent'    => $coupon->id,
                                    'code'      => $smart_coupon_code,
                                    'amount'    => ( $amount * $qty )
                                );

                                $smart_coupon_codes[$email_id][] = $generated_coupon_details;

                                $this->sa_email_coupon( array( $email_id => $generated_coupon_details ), $discount_type, $gift_certificate_receiver_name, $message_from_sender, $gift_certificate_sender_name, $gift_certificate_sender_email );

                            }

                            return $smart_coupon_codes;

			}

			// Function to get current user's Credit amount
			function get_customer_credit() {

				if ( get_option( 'woocommerce_smart_coupon_show_my_account' ) == 'no' ) return;

				global $current_user;
      			get_currentuserinfo();

      			$args = array(
					'post_type'			=> 'shop_coupon',
					'post_status'		=> 'publish',
					'posts_per_page' 	=> -1,
					'meta_query' 		=> array(
						array(
							'key'		=> 'customer_email',
							'value' 	=> $current_user->user_email,
							'compare'	=> 'LIKE'
						),
						array(
							'key'		=> 'coupon_amount',
							'value' 	=> '0',
							'compare'	=> '>=',
							'type'		=> 'NUMERIC'
						)
					)
				);

				$coupons = get_posts( $args );

      			return $coupons;
			}

                        //Funtion to add "duplicate" icon for coupons
                        function woocommerce_duplicate_coupon_link_row($actions, $post){

                                if ( function_exists( 'duplicate_post_plugin_activation' ) )
                                return $actions;

                                if ( ! current_user_can( 'manage_woocommerce' ) ) return $actions;

                                if ( $post->post_type != 'shop_coupon' )
                                return $actions;

                                $actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_coupon&amp;post=' . $post->ID ), 'woocommerce-duplicate-coupon_' . $post->ID ) . '" title="' . __("Make a duplicate from this coupon", 'wc_smart_coupons')
                                . '" rel="permalink">' .  __("Duplicate", 'wc_smart_coupons') . '</a>';

                                return $actions;
                        }

                        // function to insert post meta values for duplicate coupon
                        function woocommerce_duplicate_coupon_post_meta($id, $new_id){
                                global $wpdb;
                                $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$id");

                                if (count($post_meta_infos)!=0) {
                                        $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                                        foreach ($post_meta_infos as $meta_info) {
                                                $meta_key = $meta_info->meta_key;
                                                $meta_value = addslashes($meta_info->meta_value);
                                                $sql_query_sel[]= "SELECT $new_id, '$meta_key', '$meta_value'";
                                        }
                                        $sql_query.= implode(" UNION ALL ", $sql_query_sel);
                                        $wpdb->query($sql_query);
                                }
                        }


                        // Function to duplicate post taxonomies for the duplicate coupon
                        function woocommerce_duplicate_coupon_post_taxonomies($id, $new_id, $post_type){
                                global $wpdb;
                                $taxonomies = get_object_taxonomies($post_type);
                                foreach ($taxonomies as $taxonomy) {
                                        $post_terms = wp_get_object_terms($id, $taxonomy);
                                        $post_terms_count = sizeof( $post_terms );
                                        for ($i=0; $i<$post_terms_count; $i++) {
                                                wp_set_object_terms($new_id, $post_terms[$i]->slug, $taxonomy, true);
                                        }
                                }
                        }

                        // Function to create duplicate coupon and copy all properties of the coupon to duplicate coupon
                        function woocommerce_create_duplicate_from_coupon( $post, $parent = 0, $post_status = '' ){
                                global $wpdb;

                                $new_post_author 	= wp_get_current_user();
                                $new_post_date 		= current_time('mysql');
                                $new_post_date_gmt 	= get_gmt_from_date($new_post_date);

                                if ( $parent > 0 ) {
                                        $post_parent		= $parent;
                                        $post_status 		= $post_status ? $post_status : 'publish';
                                        $suffix 			= '';
                                } else {
                                        $post_parent		= $post->post_parent;
                                        $post_status 		= $post_status ? $post_status : 'draft';
                                        $suffix 			= __("(Copy)", 'wc_smart_coupons');
                                }

                                $new_post_type 			= $post->post_type;
                                $post_content    		= str_replace("'", "''", $post->post_content);
                                $post_content_filtered 	= str_replace("'", "''", $post->post_content_filtered);
                                $post_excerpt    		= str_replace("'", "''", $post->post_excerpt);
                                $post_title      		= str_replace("'", "''", $post->post_title).$suffix;
                                $post_name       		= str_replace("'", "''", $post->post_name);
                                $comment_status  		= str_replace("'", "''", $post->comment_status);
                                $ping_status     		= str_replace("'", "''", $post->ping_status);

                                // Insert the new template in the post table
                                $wpdb->query(
                                                "INSERT INTO $wpdb->posts
                                                (post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, post_type, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_mime_type)
                                                VALUES
                                                ('$new_post_author->ID', '$new_post_date', '$new_post_date_gmt', '$post_content', '$post_content_filtered', '$post_title', '$post_excerpt', '$post_status', '$new_post_type', '$comment_status', '$ping_status', '$post->post_password', '$post->to_ping', '$post->pinged', '$new_post_date', '$new_post_date_gmt', '$post_parent', '$post->menu_order', '$post->post_mime_type')");

                                $new_post_id = $wpdb->insert_id;

                                // Copy the taxonomies
                                $this->woocommerce_duplicate_coupon_post_taxonomies( $post->ID, $new_post_id, $post->post_type );

                                // Copy the meta information
                                $this->woocommerce_duplicate_coupon_post_meta( $post->ID, $new_post_id );

                                return $new_post_id;
                        }

                        // Functionto return post id of the duplicate coupon to be created
                        function woocommerce_get_coupon_to_duplicate( $id ){
                        	global $wpdb;
                                $post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
                                if (isset($post->post_type) && $post->post_type == "revision"){
                                        $id = $post->post_parent;
                                        $post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$id");
                                }
                                return $post[0];
                        }

                        // Function to validate condition and create duplicate coupon
                        function woocommerce_duplicate_coupon(){

                                if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'duplicate_post_save_as_new_page' == $_REQUEST['action'] ) ) ) {
                                    wp_die(__('No coupon to duplicate has been supplied!', 'wc_smart_coupons'));
                                }

                                // Get the original page
                                $id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
                                check_admin_referer( 'woocommerce-duplicate-coupon_' . $id );
                                $post = $this->woocommerce_get_coupon_to_duplicate($id);

                                if (isset($post) && $post!=null) {
                                    $new_id = $this->woocommerce_create_duplicate_from_coupon($post);

                                    // If you have written a plugin which uses non-WP database tables to save
                                    // information about a page you can hook this action to dupe that data.
                                    do_action( 'woocommerce_duplicate_coupon', $new_id, $post );

                                    // Redirect to the edit screen for the new draft page
                                    wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
                                    exit;
                                } else {
                                    wp_die(__('Coupon creation failed, could not find original product:', 'wc_smart_coupons') . ' ' . $id);
                                }

                        }

                        // Function to call function to create duplicate coupon
                        function woocommerce_duplicate_coupon_action(){
                            $this->woocommerce_duplicate_coupon();
                        }


                        // Funtion to show search result based on email id included in customer email
                        function woocommerce_admin_coupon_search( $wp ){
                                global $pagenow, $wpdb;

                                if( 'edit.php' != $pagenow ) return;
                                if( !isset( $wp->query_vars['s'] ) ) return;
                                if ($wp->query_vars['post_type']!='shop_coupon') return;

                                $e = substr( $wp->query_vars['s'], 0, 6 );

                                if( 'Email:' == substr( $wp->query_vars['s'], 0, 6 ) ) {

                                    $email = trim( substr( $wp->query_vars['s'], 6 ) );

                                    if( !$email ) return;

                                    $post_ids = $wpdb->get_col( 'SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="customer_email" AND meta_value LIKE "%'.$email.'%"; ' );

                                    if( !$post_ids ) return;

                                    unset( $wp->query_vars['s'] );

                                    $wp->query_vars['post__in'] = $post_ids;

                                    $wp->query_vars['email'] = $email;
                                }

                        }

                        // Function to show label of the search result on email
                        function woocommerce_admin_coupon_search_label( $query ){
                                global $pagenow, $typenow, $wp;

                                if ( 'edit.php' != $pagenow ) return $query;
                                if ( $typenow!='shop_coupon' ) return $query;

                                $s = get_query_var( 's' );
                                if ($s) return $query;

                                $email = get_query_var( 'email' );

                                if( $email ) {

                                    $post_type = get_post_type_object($wp->query_vars['post_type']);
                                    return sprintf(__("[%s with email of %s]", 'wc_smart_coupons'), $post_type->labels->singular_name, $email);
                                }

                                return $query;
                        }

                        // funtion to register the coupon importer
                        function woocommerce_coupon_admin_init(){
                                global $wpdb;

                                register_importer( 'woocommerce_coupon_csv', 'Import WooCommerce Coupons', __('Import <strong>coupons</strong> to your store using CSV file.', 'wc_smart_coupons'), array( &$this, 'coupon_importer')  );

                                if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page']=='woocommerce_coupon_csv_import' ) {

                                    if( $_GET['action']=='sent_gift_certificate'){

                                        $email = $_POST['smart_coupon_email'];
                                        $amount = $_POST['smart_coupon_amount'];
                                        $this->send_gift_certificate($email, $amount);
                                    }
				}
                        }

                        //
                        function send_gift_certificate( $email, $amount ){
                                global $woocommerce;

                                if ( !$email || !is_email($email) ) {

                                    $location = admin_url('admin.php?page=woocommerce_coupon_csv_import&tab=send_certificate&email_error=yes');

                                } elseif ( !$amount || !is_numeric($amount) ) {

                                    $location = admin_url('admin.php?page=woocommerce_coupon_csv_import&tab=send_certificate&amount_error=yes');

                                } else {

                                    $coupon_title = $this->generate_smart_coupon( $email, $amount );

                                    $location = admin_url('admin.php?page=woocommerce_coupon_csv_import&tab=send_certificate&sent=yes');

                                }

                                wp_safe_redirect($location);
                        }

                        // Function to add submenu page for Coupon CSV Import
                        function woocommerce_coupon_admin_menu(){
                                $page = add_submenu_page('woocommerce', __( 'Smart Coupon', 'wc_smart_coupons' ), __( '&lfloor; Smart Coupon', 'wc_smart_coupons' ), 'manage_woocommerce', 'woocommerce_coupon_csv_import', array(&$this, 'admin_page') );
                        }

                        // funtion to show content on the Coupon CSV Importer page
                        function admin_page(){
                                global $woocommerce;

				$tab = ( !empty($_GET['tab']) && $_GET['tab'] == 'send_certificate' ) ? 'send_certificate' : 'import';

                                ?>

				<div class="wrap woocommerce">

				    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				        <a href="<?php echo admin_url('admin.php?page=woocommerce_coupon_csv_import') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('Import Coupons', 'wc_smart_coupons'); ?></a><a href="<?php echo admin_url('admin.php?page=woocommerce_coupon_csv_import&tab=send_certificate') ?>" class="nav-tab <?php echo ($tab == 'send_certificate') ? 'nav-tab-active' : ''; ?>"><?php _e('Send Store Credit', 'wc_smart_coupons'); ?></a>
				    </h2>

					<?php
						switch ($tab) {
							case "send_certificate" :
								$this->admin_send_certificate();
							break;
							default :
								$this->admin_import_page();
							break;
						}
					?>

				</div>
				<?php

                        }

                        //
                        function admin_import_page() {
                                global $woocommerce;
				?>
				<div class="tool-box">
                                    <h3 class="title"><?php _e('Bulk Upload / Import Coupons using CSV file', 'wc_smart_coupons'); ?></h3>
                                    <p class="description"><?php _e('Upload a CSV file & click \'Import\' (existing coupons will be skipped) . Importing requires <code>post_title</code> column.', 'wc_smart_coupons'); ?></p>
                                    <p class="submit"><a class="button" href="<?php echo admin_url('admin.php?import=woocommerce_coupon_csv'); ?>"><?php _e('Import Coupons', 'wc_csv_import'); ?></a> </p>
                                </div>
                                <?php
                        }

                        //
                        function admin_send_certificate() {
                                global $woocommerce;

                                if( !empty($_GET['sent']) && $_GET['sent']=='yes' ){
                                    echo '<div id="message" class="updated fade"><p><strong>' . __( 'Store Credit / Gift Certificate sent successfully.', 'wc_smart_coupons' ) . '</strong></p></div>';
                                }

                                ?>
				<div class="tool-box">

					<h3 class="title"><?php _e('Send Store Credit / Gift Certificate', 'wc_csv_import'); ?></h3>
					<p class="description"><?php _e('Click "Send" to send Store Credit / Gift Certificate. *All field are compulsary.', 'wc_smart_coupons'); ?></p>

					<form action="<?php echo admin_url('admin.php?page=woocommerce_coupon_csv_import&action=sent_gift_certificate'); ?>" method="post">

						<table class="form-table">
							<tr>
								<th>
									<label for="smart_coupon_email"><?php _e( 'Email ID', 'wc_smart_coupons' ); ?> *</label>
								</th>
								<td>
									<input type="text" name="smart_coupon_email" id="email" class="input-text" />
								</td>
                                                                <td>
                                                                    <?php
                                                                        if( !empty($_GET['email_error']) && $_GET['email_error']=='yes' ){
                                                                          echo '<div id="message" class="error fade"><p><strong>' . __( 'Invalid email address.', 'wc_store_credit' ) . '</strong></p></div>';
                                                                        }
                                                                    ?>
                                                                </td>
							</tr>

                                                        <tr>
								<th>
									<label for="smart_coupon_amount"><?php _e( 'Coupon Amount', 'wc_smart_coupons' ); ?> *</label>
								</th>
								<td>
									<input type="text" name="smart_coupon_amount" id="amount" placeholder="<?php _e('0.00', 'wc_smart_coupons'); ?>" class="input-text" />
								</td>
                                                                <td>
                                                                    <?php
                                                                        if( !empty($_GET['amount_error']) && $_GET['amount_error']=='yes' ){
                                                                              echo '<div id="message" class="error fade"><p><strong>' . __( 'Invalid amount.', 'wc_store_credit' ) . '</strong></p></div>';
                                                                        }
                                                                    ?>
                                                                </td>
							</tr>

						</table>

						<p class="submit"><input type="submit" class="button" value="<?php _e('Send', 'wc_smart_coupons'); ?>" /></p>

					</form>
				</div>
                                <?php
                        }


                        // Funtion to perform importing of coupon from csv file
                        function coupon_importer(){

                                if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) return;

                                // Load Importer API
                                require_once ABSPATH . 'wp-admin/includes/import.php';


                                if ( ! class_exists( 'WP_Importer' ) ) {

                                        $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

                                        if ( file_exists( $class_wp_importer ) ){
                                            require $class_wp_importer;

                                        }
                                }

                                // includes
                                require dirname(__FILE__) . '/classes/class-wc-csv-coupon-import.php' ;
                                require dirname(__FILE__) . '/classes/class-wc-coupon-parser.php' ;

                                $wc_csv_coupon_import = new WC_CSV_Coupon_Import();

                                $wc_csv_coupon_import->dispatch();

                        }

                        // function to display the coupon data meta box.
                        function woocommerce_smart_coupon_options(){
                                global $woocommerce, $post;

                                ?>
                                    <script type="text/javascript">
                                        jQuery(function(){
                                            var showHideApplyBeforeTax = function() {
                                                if ( jQuery('select#discount_type').val() == 'smart_coupon' ) {
                                                    jQuery('p.apply_before_tax_field').hide();
                                                    jQuery('div.pick_price_of_product').show();
                                                    jQuery('input#auto_generate_coupon').attr('checked', 'checked');
                                                    jQuery('div#for_prefix_sufix').show();
                                                } else {
                                                    jQuery('p.apply_before_tax_field').show();
                                                    jQuery('div.pick_price_of_product').hide();
                                                }
                                            };

                                            jQuery(document).ready(function(){
                                                showHideApplyBeforeTax();
                                                if (jQuery("#auto_generate_coupon").is(":checked")){
                                                        //show the hidden div
                                                        jQuery("#for_prefix_sufix").show("fast");
                                                } else {
                                                        //otherwise, hide it
                                                        jQuery("#for_prefix_sufix").hide("fast");
                                                }

                                                jQuery("#auto_generate_coupon").click(function(){
                                                    if (jQuery("#auto_generate_coupon").is(":checked")) {
                                                            //show the hidden div
                                                            jQuery("#for_prefix_sufix").show("fast");
                                                    } else {
                                                            //otherwise, hide it
                                                            jQuery("#for_prefix_sufix").hide("fast");
                                                    }
                                                });
                                            });

                                            jQuery('select#discount_type').change(function(){
                                                showHideApplyBeforeTax();
                                            });
                                        });
                                    </script>
                                    <style type="text/css">
                                            #edit-slug-box { display:none }
                                    </style>
                                    <div id="coupon_options" class="panel woocommerce_options_panel">
                                        <div class="options_group pick_price_of_product">
                                            <?php woocommerce_wp_checkbox( array( 'id' => 'is_pick_price_of_product', 'label' => __('Pick Product\'s Price?', 'wc_smart_coupons'), 'description' => __('Check this box to allow overwriting coupon\'s amount with Product\'s Price.', 'wc_smart_coupons') ) ); ?>
                                        </div>
                                    </div>
                                    <div id="coupon_options" class="panel woocommerce_options_panel">

                                        <?php
                                            echo '<div class="options_group">';

                                            // autogeneration of coupon for store credit/gift certificate
                                            woocommerce_wp_checkbox( array( 'id' => 'auto_generate_coupon', 'label' => __('Auto Generation of Coupon', 'wc_smart_coupons'), 'description' => __('Check this box if the coupon needs to be auto generated', 'wc_smart_coupons') ) );

                                            echo '<div id="for_prefix_sufix">';
                                            // text field for coupon prefix
                                            woocommerce_wp_text_input( array( 'id' => 'coupon_title_prefix', 'label' => __('Prefix for Coupon Title', 'wc_smart_coupons'), 'placeholder' => _x('Prefix', 'placeholder', 'wc_smart_coupons'), 'description' => __('Adding prefix to the coupon title', 'wc_smart_coupons') ) );

                                            // text field for coupon suffix
                                            woocommerce_wp_text_input( array( 'id' => 'coupon_title_suffix', 'label' => __('Suffix for Coupon Title', 'wc_smart_coupons'), 'placeholder' => _x('Suffix', 'placeholder', 'wc_smart_coupons'), 'description' => __('Adding suffix to the coupon title', 'wc_smart_coupons') ) );

                                            echo '</div>';
                                            echo '</div>';
                                       ?>

                                    </div>

                                <?php

                        }

                        // Function to save the coupon data meta box.
                        function woocommerce_process_smart_coupon_meta( $post_id, $post ){
                                if ( empty($post_id) || empty($post) || empty($_POST) ) return;
                                if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
                                if ( is_int( wp_is_post_revision( $post ) ) ) return;
                                if ( is_int( wp_is_post_autosave( $post ) ) ) return;
                                if ( empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' )) return;
                                if ( !current_user_can( 'edit_post', $post_id )) return;
                                if ( $post->post_type != 'shop_coupon' ) return;

                                if ( isset( $_POST['auto_generate_coupon'] ) ) {
                                    update_post_meta( $post_id, 'auto_generate_coupon', $_POST['auto_generate_coupon'] );
                                } else {
                                    if ( get_post_meta( $post_id, 'discount_type', true ) == 'smart_coupon' ) {
                                        update_post_meta( $post_id, 'auto_generate_coupon', 'yes' );
                                    } else {
                                        update_post_meta( $post_id, 'auto_generate_coupon', 'no' );
                                    }
                                }

                                if ( get_post_meta( $post_id, 'discount_type', true ) == 'smart_coupon' ) {
                                    update_post_meta( $post_id, 'apply_before_tax', 'no' );
                                }

                                if( isset($_POST['coupon_title_prefix']) || isset( $_POST['coupon_title_suffix'] ) ) {

                                    $prefix = isset($_POST['coupon_title_prefix']) ? $_POST['coupon_title_prefix'] : '';
                                    $suffix = isset($_POST['coupon_title_suffix']) ? $_POST['coupon_title_suffix'] : '';

                                    update_post_meta($post_id, 'coupon_title_prefix', $prefix);
                                    update_post_meta($post_id, 'coupon_title_suffix', $suffix);
                                }

                                if ( isset( $_POST['is_pick_price_of_product'] ) ) {
                                    update_post_meta( $post_id, 'is_pick_price_of_product', $_POST['is_pick_price_of_product'] );
                                } else {
                                    update_post_meta( $post_id, 'is_pick_price_of_product', 'no' );
                                }

                        }

		}// End of class WC_Smart_Coupons

		$GLOBALS['woocommerce_smart_coupon'] = new WC_Smart_Coupons();

	} // End class exists check

} // End woocommerce active check
