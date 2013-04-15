<?php
/*
Plugin Name: WooCommerce Bulk Stock Management
Plugin URI: http://woothemes.com/woocommerce
Description: Bulk edit stock levels and print out stock reports right from WooCommerce admin.
Version: 1.8.0
Author: Mike Jolley
Author URI: http://mikejolley.com

Copyright: © 2009-2012 WooThemes.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

List_Table class based on "Custom List Table Example" by Matt Van Andel (http://www.mattvanandel.com/)
*/

/**
 * Required functions
 **/
if ( ! class_exists( 'WP_List_Table' ) ) require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
if ( ! class_exists( 'WC_Stock_Management_List_Table' ) ) require_once( 'classes/class-wc-stock-management-list-table.php' );

if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '02f4328d52f324ebe06a78eaaae7934f', '18670' );

if (is_woocommerce_active()) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'wc_stock_management', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * WC_Advanced_Stock_Management class
	 **/
	if ( ! class_exists( 'WC_Advanced_Stock_Management' ) ) {

		class WC_Advanced_Stock_Management {

			var $messages = array();

			/**
			 * Constructor
			 **/
			function __construct() {

				add_action( 'admin_menu', array( &$this, 'register_menu' ) );
				add_action( 'init', array( &$this, 'print_stock_report') );
				add_action( 'init', array( &$this, 'process_qty') );

			}

			function admin_css() {
				global $woocommerce;

				wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
				wp_enqueue_style( 'woocommerce_stock_management_css', plugins_url(basename(dirname(__FILE__))) . '/css/admin.css' );
			}

			function register_menu() {
				$page = add_submenu_page( 'edit.php?post_type=product', __( 'Stock Management', 'wc_stock_management' ), __( 'Stock Management', 'wc_stock_management' ), 'manage_woocommerce_products', 'wc_stock_management', array( &$this, 'stock_management_page' ) );

				add_action( 'admin_print_styles-' . $page, array( &$this, 'admin_css' ) );
			}

			function stock_management_page() {
				global $woocommerce;

			    $WC_Stock_Management_List_Table = new WC_Stock_Management_List_Table();
			    $WC_Stock_Management_List_Table->prepare_items();

			    ?>
			    <div class="wrap">

			        <div id="icon-woocommerce" class="icon32 icon32-posts-product"><br/></div>
			        <h2><?php _e('Stock Management', 'wc_stock_management'); ?> <a href="<?php echo wp_nonce_url( add_query_arg( 'print', 'stock_report' ), 'print-stock' ) ?>" class="add-new-h2"><?php _e('View stock report', 'wc_stock_management'); ?></a></h2>
			        <form id="stock-management" method="post">

			        	<?php
			        		if ( $this->messages ) {

			        			echo '<div class="updated">';

			        			foreach ( $this->messages as $message ) {
			        				echo '<p>' . $message . '</p>';
			        			}

			        			echo '</div>';

			        		}

			        		wp_nonce_field( 'save', 'wc-stock-management' );
			        	?>

			            <input type="hidden" name="post_type" value="product" />
			            <input type="hidden" name="page" value="wc_stock_management" />
			            <?php $WC_Stock_Management_List_Table->display() ?>
			        </form>

			    </div>
			    <?php
			}

			function print_stock_report() {
				global $woocommerce;

				// Save quantities
				if ( ! empty( $_GET['print'] ) && $_GET['print'] == 'stock_report' ) {

					check_admin_referer( 'print-stock' );

					ob_start();

			  		include( apply_filters( 'wc_stock_report_template', plugin_dir_path( __FILE__ ) . 'templates/stock-report.php' ) );

			  		$content = ob_get_clean();

			  		echo $content;

			  		die();
				}
			}

			function process_qty() {
				global $woocommerce;

				// Save quantities
				if ( ! empty( $_POST['stock_quantity'] ) && ! empty( $_POST['save_stock'] ) ) {

					check_admin_referer( 'save', 'wc-stock-management' );

					$quantities 		= $_POST['stock_quantity'];
					$current_quantities = $_POST['current_stock_quantity'];

					foreach ( $quantities as $id => $qty ) {

						if ( $qty == '' ) continue;

						if ( isset( $current_quantities[$id] ) ) {

							// Check the qty has not changed since showing the form
							$current_stock = (int) get_post_meta( $id, '_stock', true );

							if ( $current_stock == $current_quantities[$id] ) {

								$post = get_post( $id );

								// Format $qty
								$qty = (int) $qty;

								// Update stock amount
								update_post_meta( $id, '_stock', $qty );

								// Update stock status
								if ( $post->post_type == 'product' ) {

									// Update manage stock variable for products
									update_post_meta( $id, '_manage_stock', 'yes' );

									if ( function_exists( 'get_product' ) )
										$product = get_product( $post->ID );
									else
										$product = new WC_Product( $post->ID );

									if ( $product->managing_stock() && ! $product->backorders_allowed() && $product->get_total_stock() <= 0 )
										update_post_meta( $post->ID, '_stock_status', 'outofstock' );
									elseif ( $product->managing_stock() && ( $product->backorders_allowed() || $product->get_total_stock() > 0 ) )
										update_post_meta( $post->ID, '_stock_status', 'instock' );

									$woocommerce->clear_product_transients( $post->ID ); // Clear transient

								} else {

									if ( function_exists( 'get_product' ) )
										$product = get_product( $post->post_parent );
									else
										$product = new WC_Product( $post->post_parent );

									if ( $product->managing_stock() && ! $product->backorders_allowed() && $product->get_total_stock() <= 0 )
										update_post_meta( $post->post_parent, '_stock_status', 'outofstock');
									elseif ( $product->managing_stock() && ( $product->backorders_allowed() || $product->get_total_stock() > 0 ) )
										update_post_meta( $post->post_parent, '_stock_status', 'instock' );

									$woocommerce->clear_product_transients( $post->post_parent ); // Clear transient

								}

							} else {

								$this->messages[] = sprintf( __('Product # %s was not updated - the stock amount has changed since posting.', 'wc_stock_management'), $id );

							}

						}

					}

					$this->messages[] = __('Stock quantities saved.', 'wc_stock_management');

				}
			}

		}

	}

	$GLOBALS['WC_Advanced_Stock_Management'] = new WC_Advanced_Stock_Management();

}