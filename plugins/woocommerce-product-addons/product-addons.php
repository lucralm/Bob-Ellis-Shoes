<?php
/*
Plugin Name: WooCommerce Product Add-ons
Plugin URI: http://woothemes.com/woocommerce
Description: WooCommerce Product Add-ons lets you add extra options to products which the user can select. Add-ons can be checkboxes, a select box, or custom input. Each option can optionally be given a price which is added to the cost of the product.
Version: 1.2.0
Author: WooThemes
Author URI: http://woothemes.com
Requires at least: 3.1
Tested up to: 3.2

	Copyright: © 2009-2011 WooThemes.
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
woothemes_queue_update( plugin_basename( __FILE__ ), '147d0077e591e16db9d0d67daeb8c484', '18618' );

if (is_woocommerce_active()) {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain('wc_product_addons', false, dirname( plugin_basename( __FILE__ ) ) . '/');

	/**
	 * woocommerce_product_addons class
	 **/
	if (!class_exists('woocommerce_product_addons')) {

		class woocommerce_product_addons {

			var $settings;

			public function __construct() {

				// Addon display
				add_action( 'woocommerce_before_add_to_cart_button', array(&$this, 'product_addons'), 10 );

				// Filters for cart actions
				add_filter( 'woocommerce_add_cart_item_data', array(&$this, 'add_cart_item_data'), 10, 2 );
				add_filter( 'woocommerce_get_cart_item_from_session', array(&$this, 'get_cart_item_from_session'), 10, 2 );
				add_filter( 'woocommerce_get_item_data', array(&$this, 'get_item_data'), 10, 2 );
				add_filter( 'woocommerce_add_cart_item', array(&$this, 'add_cart_item'), 10, 1 );
				add_filter( 'woocommerce_add_to_cart_validation', array(&$this, 'validate_add_cart_item'), 10, 3 );

				// Add meta to order
				add_action( 'woocommerce_order_item_meta', array(&$this, 'order_item_meta'), 10, 2 );

				// Add meta to order 2.0
				add_action( 'woocommerce_add_order_item_meta', array( $this, 'order_item_meta_2' ), 10, 2 );

				// Write Panel
				add_action('woocommerce_admin_css', array(&$this, 'meta_box_css'));
				add_action('add_meta_boxes', array(&$this, 'add_meta_box'));
				add_action('woocommerce_process_product_meta', array(&$this, 'process_meta_box'), 1, 2);

		    }

		    /*-----------------------------------------------------------------------------------*/
			/* Write Panel */
			/*-----------------------------------------------------------------------------------*/

		    function add_meta_box() {
		    	add_meta_box( 'woocommerce-product-addons', __('Product Add-ons', 'wc_product_addons'), array(&$this, 'meta_box'), 'product', 'side', 'default' );
		    }

		    function meta_box_css() {
		    	global $typenow;

		    	if ($typenow=='product') wp_enqueue_style( 'woocommerce_product_addons_css', plugins_url(basename(dirname(__FILE__))) . '/css/admin.css' );
		    }

		    function meta_box( $post ) {
				?>
				<div id="product_addons" class="panel">
					<div class="woocommerce_addons">

						<?php
						$product_addons = get_post_meta( $post->ID, '_product_addons', true );

						$loop = 0;

						if (is_array($product_addons) && sizeof($product_addons)>0) foreach ($product_addons as $addon) :

							if (!$addon['name']) continue;
							if (!isset($addon['required'])) $addon['required'] = 0;

							?><div class="woocommerce_addon">
								<p class="addon_name">
									<label class="hidden"><?php _e('Name:', 'wc_product_addons'); ?></label>
									<input type="text" name="addon_name[<?php echo $loop; ?>]" placeholder="<?php _e('Name', 'wc_product_addons'); ?>" value="<?php echo esc_attr($addon['name']); ?>" />
									<input type="hidden" name="addon_position[<?php echo $loop; ?>]" class="addon_position" value="<?php echo $loop; ?>" />
								</p>
								<p class="addon_type">
									<label class="hidden"><?php _e('Type:', 'wc_product_addons'); ?></label>
									<select name="addon_type[<?php echo $loop; ?>]">
										<option <?php selected('checkbox', $addon['type']); ?> value="checkbox"><?php _e('Checkboxes', 'wc_product_addons'); ?></option>
										<option <?php selected('radiobutton', $addon['type']); ?> value="radiobutton"><?php _e('Radio buttons', 'wc_product_addons'); ?></option>
										<option <?php selected('select', $addon['type']); ?> value="select"><?php _e('Select box', 'wc_product_addons'); ?></option>
										<option <?php selected('custom', $addon['type']); ?> value="custom"><?php _e('Custom input (text)', 'wc_product_addons'); ?></option>
										<option <?php selected('custom_textarea', $addon['type']); ?> value="custom_textarea"><?php _e('Custom input (textarea)', 'wc_product_addons'); ?></option>
										<option <?php selected('file_upload', $addon['type']); ?> value="file_upload"><?php _e('File upload', 'wc_product_addons'); ?></option>
									</select>
								</p>
								<p class="addon_description">
									<label class="hidden"><?php _e('Description:', 'wc_product_addons'); ?></label>
									<input type="text" name="addon_description[<?php echo $loop; ?>]" placeholder="<?php _e('Description', 'wc_product_addons'); ?>" value="<?php echo esc_attr($addon['description']); ?>" />
								</p>
								<p class="addon_required">
									<label><input type="checkbox" name="addon_required[<?php echo $loop; ?>]" <?php checked($addon['required'], 1) ?> /> <?php _e('Required field', 'wc_product_addons'); ?></label>
								</p>
								<table cellpadding="0" cellspacing="0" class="woocommerce_addon_options">
									<thead>
										<tr>
											<th><?php _e('Label/Value:', 'wc_product_addons'); ?></th>
											<th><?php _e('Price:', 'wc_product_addons'); ?></th>
											<th width="1%" class="actions"><button type="button" class="add_addon_option button"><?php _e('Add', 'wc_product_addons'); ?></button></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ($addon['options'] as $option) :
											?>
											<tr>
												<td><input type="text" name="addon_option_label[<?php echo $loop; ?>][]" value="<?php echo esc_attr($option['label']) ?>" placeholder="<?php _e('Label', 'wc_product_addons'); ?>" /></td>
												<td><input type="text" name="addon_option_price[<?php echo $loop; ?>][]" value="<?php echo esc_attr($option['price']) ?>" placeholder="0.00" /></td>
												<td class="actions"><button type="button" class="remove_addon_option button">x</button></td>
											</tr>
											<?php
										endforeach;
										?>
									</tbody>
								</table>
								<span class="handle"><?php _e('&varr; Move', 'wc_product_addons'); ?></span>
								<a href="#" class="delete_addon"><?php _e('Delete add-on', 'wc_product_addons'); ?></a>
							</div><?php

							$loop++;

						endforeach;
						?>

					</div>

					<h4>
						<a href="#" class="import tips" tip="<?php _e('Import', 'wc_product_addons'); ?>"><?php _e('Import', 'wc_product_addons'); ?></a>
						<a href="#" class="export tips" tip="<?php _e('Export', 'wc_product_addons'); ?>"><?php _e('Export', 'wc_product_addons'); ?></a>
						<a href="#" class="add_new_addon"><?php _e('+ Add New Product Add-on', 'wc_product_addons'); ?></a>
					</h4>

					<textarea name="export_product_addon" class="export" cols="20" rows="5" readonly="readonly"><?php echo esc_textarea( serialize($product_addons) ); ?></textarea>
					<textarea name="import_product_addon" class="import" cols="20" rows="5" placeholder="<?php _e('Paste form data to import here then save the product. The imported fields will be appended.', 'wc_product_addons'); ?>"></textarea>
				</div>
				<script type="text/javascript">
				jQuery(function(){

					jQuery('#product_addons').on('click', 'a.add_new_addon', function(){

						var loop = jQuery('.woocommerce_addons .woocommerce_addon').size();

						jQuery('.woocommerce_addons').append('<div class="woocommerce_addon">\
							<p class="addon_name">\
								<label class="hidden"><?php _e('Name:', 'wc_product_addons'); ?></label>\
								<input type="text" name="addon_name[' + loop + ']" placeholder="<?php _e('Name', 'wc_product_addons'); ?>" />\
								<input type="hidden" name="addon_position[' + loop + ']" class="addon_position" value="' + loop + '" />\
							</p>\
							<p class="addon_type">\
								<label class="hidden"><?php _e('Type:', 'wc_product_addons'); ?></label>\
								<select name="addon_type[' + loop + ']">\
									<option value="checkbox"><?php _e('Checkboxes', 'wc_product_addons'); ?></option>\
									<option value="radiobutton"><?php _e('Radio buttons', 'wc_product_addons'); ?></option>\
									<option value="select"><?php _e('Select box', 'wc_product_addons'); ?></option>\
									<option value="custom"><?php _e('Custom input (text)', 'wc_product_addons'); ?></option>\
									<option value="custom_textarea"><?php _e('Custom input (textarea)', 'wc_product_addons'); ?></option>\
									<option value="file_upload"><?php _e('File upload', 'wc_product_addons'); ?></option>\
								</select>\
							</p>\
							<p class="addon_description">\
								<label class="hidden"><?php _e('Description:', 'wc_product_addons'); ?></label>\
								<input type="text" name="addon_description[' + loop + ']" placeholder="<?php _e('Description', 'wc_product_addons'); ?>" />\
							</p>\
							<p class="addon_required">\
								<label><input type="checkbox" name="addon_required[' + loop + ']" /> <?php _e('Required field', 'wc_product_addons'); ?></label>\
							</p>\
							<table cellpadding="0" cellspacing="0" class="woocommerce_addon_options">\
								<thead>\
									<tr>\
										<th><?php _e('Option:', 'wc_product_addons'); ?></th>\
										<th><?php _e('Price:', 'wc_product_addons'); ?></th>\
										<th width="1%" class="actions"><button type="button" class="add_addon_option button"><?php _e('Add', 'wc_product_addons'); ?></button></th>\
									</tr>\
								</thead>\
								<tbody>\
									<tr>\
										<td><input type="text" name="addon_option_label[' + loop + '][]" value="<?php ?>" placeholder="<?php _e('Label', 'wc_product_addons'); ?>" /></td>\
										<td><input type="text" name="addon_option_price[' + loop + '][]" value="<?php ?>" placeholder="0.00" /></td>\
										<td class="actions"><button type="button" class="remove_addon_option button">x</button></td>\
									</tr>\
								</tbody>\
							</table>\
							<span class="handle"><?php _e('&varr; Move', 'wc_product_addons'); ?></span>\
							<a href="#" class="delete_addon"><?php _e('Delete add-on', 'wc_product_addons'); ?></a>\
						</div>');

						return false;

					});

					jQuery('button.add_addon_option').live('click', function(){

						var loop = jQuery(this).closest('.woocommerce_addon').index('.woocommerce_addon');

						jQuery(this).closest('.woocommerce_addon_options').find('tbody').append('<tr>\
							<td><input type="text" name="addon_option_label[' + loop + '][]" placeholder="<?php _e('Label', 'wc_product_addons'); ?>" /></td>\
							<td><input type="text" name="addon_option_price[' + loop + '][]" placeholder="0.00" /></td>\
							<td class="actions"><button type="button" class="remove_addon_option button">x</button></td>\
						</tr>');

						return false;

					});

					jQuery('button.remove_addon_option').live('click', function(){

						var answer = confirm('<?php _e('Are you sure you want delete this add-on item?', 'wc_product_addons'); ?>');

						if (answer) {
							jQuery(this).closest('tr').remove();
						}

						return false;

					});

					jQuery('a.delete_addon').live('click', function(){

						var answer = confirm('<?php _e('Are you sure you want delete this add-on?', 'wc_product_addons'); ?>');

						if (answer) {
							var addon = jQuery(this).closest('.woocommerce_addon');
							jQuery(addon).find('input').val('');
							jQuery(addon).hide();
						}

						return false;

					});

					jQuery('.woocommerce_addon table.woocommerce_addon_options tbody').sortable({
						items:'tr',
						cursor:'move',
						axis:'y',
						scrollSensitivity:40,
						helper:function(e,ui){
							ui.children().each(function(){
								jQuery(this).width(jQuery(this).width());
							});
							return ui;
						},
						start:function(event,ui){
							ui.item.css('background-color','#f6f6f6');
						},
						stop:function(event,ui){
							ui.item.removeAttr('style');
						}
					});

					jQuery('.woocommerce_addons').sortable({
						items:'.woocommerce_addon',
						cursor:'move',
						axis:'y',
						handle:'.handle',
						scrollSensitivity:40,
						helper:function(e,ui){
							ui.children().each(function(){
								jQuery(this).width(jQuery(this).width());
							});
							return ui;
						},
						start:function(event,ui){
							ui.item.css('border-style','dashed');
						},
						stop:function(event,ui){
							ui.item.removeAttr('style');
							addon_row_indexes();
						}
					});

					function addon_row_indexes() {
						jQuery('.woocommerce_addons .woocommerce_addon').each(function(index, el){ jQuery('.addon_position', el).val( parseInt( jQuery(el).index('.woocommerce_addons .woocommerce_addon') ) ); });
					};

					jQuery('#product_addons').on('click', 'a.export', function() {

						jQuery('#product_addons textarea.import').hide();
						jQuery('#product_addons textarea.export').slideToggle('500', function() {
							jQuery(this).select();
						});

						return false;
					});

					jQuery('#product_addons').on('click', 'a.import', function() {

						jQuery('#product_addons textarea.export').hide();
						jQuery('#product_addons textarea.import').slideToggle('500', function() {
							jQuery(this).val('');
						});

						return false;
					});

				});
				</script>
		    	<?php
		    }

		    function process_meta_box( $post_id, $post ) {

		    	// Save addons as serialised array
				$product_addons = array();

				if (isset($_POST['addon_name'])) :
					 $addon_name			= $_POST['addon_name'];
					 $addon_description		= $_POST['addon_description'];
					 $addon_type 			= $_POST['addon_type'];
					 $addon_option_label	= $_POST['addon_option_label'];
					 $addon_option_price	= $_POST['addon_option_price'];
					 $addon_position 		= $_POST['addon_position'];
					 $addon_required		= $_POST['addon_required'];

					 for ($i=0; $i<sizeof($addon_name); $i++) :

					 	if (!isset($addon_name[$i]) || ('' == $addon_name[$i])) continue;

					 	// Meta
					 	$addon_options 			= array();
					 	$option_label 			= $addon_option_label[$i];
					 	$option_price 			= $addon_option_price[$i];

					 	for ($ii=0; $ii<sizeof($option_label); $ii++) :
					 		$label = esc_attr(stripslashes($option_label[$ii]));
					 		$price = esc_attr(stripslashes($option_price[$ii]));

				 			$addon_options[] = array(
				 				'label' => $label,
				 				'price' => $price
				 			);

					 	endfor;

					 	if (sizeof($addon_options)==0) continue; // Needs options

					 	// Add to array
					 	$product_addons[] = array(
					 		'name' 			=> esc_attr(stripslashes($addon_name[$i])),
					 		'description' 	=> esc_attr(stripslashes($addon_description[$i])),
					 		'type' 			=> esc_attr(stripslashes($addon_type[$i])),
					 		'position'		=> (int) $addon_position[$i],
					 		'options' 		=> $addon_options,
					 		'required'		=> (isset($addon_required[$i])) ? 1 : 0
					 	);

					 endfor;
				endif;

				if (!function_exists('addons_cmp')) {
					function addons_cmp($a, $b) {
					    if ($a['position'] == $b['position']) {
					        return 0;
					    }
					    return ($a['position'] < $b['position']) ? -1 : 1;
					}
				}
				uasort($product_addons, 'addons_cmp');

				if ($_POST['import_product_addon']) {
					$import_addons = maybe_unserialize(maybe_unserialize(stripslashes(trim( $_POST['import_product_addon'] ))));
					if (is_array($import_addons) && sizeof($import_addons)>0) {
						$valid = true;
						foreach ($import_addons as $addon) {
							if (!isset($addon['name']) || !$addon['name']) $valid = false;
							if (!isset($addon['description'])) $valid = false;
							if (!isset($addon['type'])) $valid = false;
							if (!isset($addon['position'])) $valid = false;
							if (!isset($addon['options'])) $valid = false;
							if (!isset($addon['required'])) $valid = false;
						}
						if ($valid) {
							// Append data
							$product_addons = array_merge( $product_addons, $import_addons );
						}
					}
				}

				update_post_meta( $post_id, '_product_addons', $product_addons );

		    }


	        /*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/

			function product_addons() {
				global $post;

				$product_addons = get_post_meta( $post->ID, '_product_addons', true );

				if (is_array($product_addons) && sizeof($product_addons)>0) foreach ($product_addons as $addon) :

					if (!isset($addon['name'])) continue;

					?>
					<div class="product-addon product-addon-<?php echo sanitize_title($addon['name']); ?>">
						<?php if ($addon['name']) : ?><h3><?php echo wptexturize($addon['name']); ?> <?php if ($addon['type']=='file_upload') echo sprintf(__('(max size %s)', 'wc_product_addons'), $this->max_upload_size()); ?></h3><?php endif; ?>
						<?php if ($addon['description']) : ?><p><?php echo wptexturize($addon['description']); ?></p><?php endif; ?>
						<?php
						switch ($addon['type']) :
							case "checkbox" :
								foreach ($addon['options'] as $option) :

									$current_value = (
										isset($_POST['addon-'. sanitize_title( $addon['name'] )]) &&
										in_array(sanitize_title( $option['label'] ), $_POST['addon-'. sanitize_title( $addon['name'] )])
										) ? 1 : 0;

									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';
									echo '<p class="form-row form-row-wide"><label><input type="checkbox" name="addon-'. sanitize_title( $addon['name'] ) .'[]" value="'. sanitize_title( $option['label'] ) .'" '.checked($current_value, 1, false).' /> '. wptexturize($option['label']) . $price .'</label></p>';

								endforeach;
							break;
							case "radiobutton" :
								foreach ($addon['options'] as $option) :

									$current_value = (
										isset($_POST['addon-'. sanitize_title( $addon['name'] )]) &&
										in_array(sanitize_title( $option['label'] ), $_POST['addon-'. sanitize_title( $addon['name'] )])
										) ? 1 : 0;

									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';
									echo '<p class="form-row form-row-wide"><label><input type="radio" name="addon-'. sanitize_title( $addon['name'] ) .'[]" value="'. sanitize_title( $option['label'] ) .'" '.checked($current_value, 1, false).' /> '. wptexturize($option['label']) . $price .'</label></p>';

								endforeach;
							break;
							case "select" :

								$current_value = (isset($_POST['addon-'. sanitize_title( $addon['name'] )])) ? $_POST['addon-'. sanitize_title( $addon['name'] )] : '';

								echo '<p class="form-row form-row-wide"><select name="addon-'. sanitize_title( $addon['name'] ) .'">';

								if ( ! isset( $addon['required'] ) )
									echo '<option value="">'. __('None', 'wc_product_addons') .'</option>';
								else
									echo '<option value="">'. __('Select an option...', 'wc_product_addons') .'</option>';

								$loop = 0;
								foreach ($addon['options'] as $option) : $loop++;
									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';
									echo '<option value="'. sanitize_title( $option['label'] ) .'-'. $loop .'" '.selected($current_value, sanitize_title( $option['label'] ), false).'>'. wptexturize($option['label']) . $price .'</option>';
								endforeach;
								echo '</select></p>';

							break;
							case "custom" :
								foreach ($addon['options'] as $option) :

									$current_value = (isset($_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )])) ? $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] : '';

									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';
									echo '<p class="form-row form-row-wide"><label>'. wptexturize($option['label']) . $price .': <input type="text" class="input-text" name="addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] ) .'" value="'.$current_value.'" /></label></p>';

								endforeach;
							break;
							case "custom_textarea" :
								foreach ($addon['options'] as $option) :

									$current_value = (isset($_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )])) ? $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] : '';

									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';
									echo '<p class="form-row form-row-wide"><label>'. wptexturize($option['label']) . $price .': <textarea class="textarea input-text" name="addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] ) .'" rows="4" cols="20">'. esc_textarea($current_value) .'</textarea></label></p>';

								endforeach;
							break;
							case "file_upload" :
								foreach ($addon['options'] as $option) :

									$price = ($option['price']>0) ? ' (' . woocommerce_price($option['price']) . ')' : '';

									echo '<p class="form-row form-row-wide"><label>'. wptexturize($option['label']) . $price .': <input type="file" class="input-text" name="addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] ) .'" /></label></p>';

								endforeach;
							break;
						endswitch;
						?>
						<div class="clear"></div>
					</div>
					<?php
				endforeach;

			}

			function add_cart_item_data( $cart_item_meta, $product_id ) {
				global $woocommerce;

				$product_addons = get_post_meta( $product_id, '_product_addons', true );

				$cart_item_meta['addons'] = array();

				if (is_array($product_addons) && sizeof($product_addons)>0) foreach ($product_addons as $addon) :

					if (!isset($addon['name'])) continue;

					switch ($addon['type']) :
						case "checkbox" :
						case "radiobutton" :

							// Posted var = name, value = label
							$posted = (isset( $_POST['addon-' . sanitize_title( $addon['name'] )] )) ? $_POST['addon-' . sanitize_title( $addon['name'] )] : '';

							if (!$posted || sizeof($posted)==0) continue;

							foreach ($addon['options'] as $option) :

								if (array_search(sanitize_title($option['label']), $posted)!==FALSE) :

									// Set
									$cart_item_meta['addons'][] = array(
										'name' 		=> esc_attr( $addon['name'] ),
										'value'		=> esc_attr( $option['label'] ),
										'price' 	=> esc_attr( $option['price'] )
									);

								endif;

							endforeach;

						break;
						case "select" :

							// Posted var = name, value = label
							$posted = (isset( $_POST['addon-' . sanitize_title( $addon['name'] )] )) ? $_POST['addon-' . sanitize_title( $addon['name'] )] : '';

							if (!$posted) continue;

							$chosen_option = '';

							$loop = 0;
							foreach ( $addon['options'] as $option ) : $loop ++;
								if ( sanitize_title( $option['label'] . '-' . $loop )==$posted) :
									$chosen_option = $option;
									break;
								endif;
							endforeach;

							if (!$chosen_option) continue;

							$cart_item_meta['addons'][] = array(
								'name' 		=> esc_attr( $addon['name'] ),
								'value'		=> esc_attr( $chosen_option['label'] ),
								'price' 	=> esc_attr( $chosen_option['price'] )
							);

						break;
						case "custom" :
						case "custom_textarea" :

							// Posted var = label, value = custom
							foreach ($addon['options'] as $option) :

								$posted = (isset( $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] )) ? $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] : '';

								if (!$posted) continue;

								$cart_item_meta['addons'][] = array(
									'name' 		=> esc_attr( $option['label'] ),
									'value'		=> esc_attr( stripslashes( trim( $posted ) ) ),
									'price' 	=> esc_attr( $option['price'] )
								);

							endforeach;

						break;
						case "file_upload" :

							/** WordPress Administration File API */
							include_once(ABSPATH . 'wp-admin/includes/file.php');
							/** WordPress Media Administration API */
							include_once(ABSPATH . 'wp-admin/includes/media.php');

							add_filter('upload_dir',  array(&$this, 'upload_dir'));

							foreach ($addon['options'] as $option) {

								$field_name = 'addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] );

								if (isset( $_FILES[$field_name] ) && !empty( $_FILES[$field_name]) && !empty($_FILES[$field_name]['name'])) {
									$file   = $_FILES[$field_name];
									$upload = wp_handle_upload($file, array('test_form' => false));
									if(!isset($upload['error']) && isset($upload['file'])) {

							            $file_path = $upload['url'];

							            $cart_item_meta['addons'][] = array(
											'name' 		=> esc_attr( $option['label'] ),
											'value'		=> esc_attr( stripslashes( trim( $file_path ) ) ),
											'display'	=> basename( esc_attr( stripslashes( trim( $file_path ) ) ) ),
											'price' 	=> esc_attr( $option['price'] )
										);

							    	} else {

							    		$woocommerce->add_error( $upload['error'] );

							    	}
								}

							}

							remove_filter('upload_dir',  array(&$this, 'upload_dir'));
						break;
					endswitch;

				endforeach;

				return $cart_item_meta;

			}

			function get_cart_item_from_session( $cart_item, $values ) {

				if (isset($values['addons'])) :
					$cart_item['addons'] = $values['addons'];

					$cart_item = $this->add_cart_item( $cart_item );
				endif;

				return $cart_item;

			}

			function get_item_data( $other_data, $cart_item ) {

				if (isset($cart_item['addons'])) :

					foreach ($cart_item['addons'] as $addon) :

						$name = $addon['name'];
						if ($addon['price']>0) $name .= ' (' . woocommerce_price($addon['price']) . ')';

						$other_data[] = array(
							'name' => $name,
							'value' => $addon['value'],
							'display' => isset($addon['display']) ? $addon['display'] : ''
						);

					endforeach;

				endif;

				return $other_data;

			}

			function add_cart_item( $cart_item ) {

				// Adjust price if addons are set
				if (isset($cart_item['addons'])) :

					$extra_cost = 0;

					foreach ($cart_item['addons'] as $addon) :

						if ($addon['price']>0) $extra_cost += $addon['price'];

					endforeach;

					$cart_item['data']->adjust_price( $extra_cost );

				endif;

				return $cart_item;

			}

			function order_item_meta( $item_meta, $cart_item ) {

				// Add the fields
				if (isset($cart_item['addons'])) :

					foreach ($cart_item['addons'] as $addon) :

						$name = $addon['name'];

						if ($addon['price']>0)
							$name .= ' (' . strip_tags(woocommerce_price($addon['price'])) . ')';

						$item_meta->add( $name, $addon['value'] );

					endforeach;

				endif;

			}

			function order_item_meta_2( $item_id, $values ) {
				if ( function_exists('woocommerce_add_order_item_meta') ) {

					if ( ! empty( $values['addons'] ) ) {
						foreach ( $values['addons'] as $addon ) {

							$name = $addon['name'];

							if ($addon['price']>0)
								$name .= ' (' . strip_tags(woocommerce_price($addon['price'])) . ')';

							woocommerce_add_order_item_meta( $item_id, $name, $addon['value'] );
						}
					}

				}
			}

			function validate_add_cart_item( $passed, $product_id, $qty ) {
				global $woocommerce;

				$product_addons = get_post_meta( $product_id, '_product_addons', true );

				if (is_array($product_addons) && sizeof($product_addons)>0) foreach ($product_addons as $addon) {

					if (!isset($addon['name'])) continue;
					if (!isset($addon['required'])) continue;

					if ($addon['required']) {

						switch ($addon['type']) :
							case "checkbox" :
							case "radiobutton" :
							case "select" :

								$posted = (isset( $_POST['addon-' . sanitize_title( $addon['name'] )] )) ? $_POST['addon-' . sanitize_title( $addon['name'] )] : '';

								if (!$posted || sizeof($posted)==0) $passed = false;

							break;
							case "custom" :
							case "custom_textarea" :

								foreach ($addon['options'] as $option) {

									$posted = (isset( $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] )) ? $_POST['addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] )] : '';

									if (!$posted || sizeof($posted)==0) {
										$passed = false;
										break;
									}
								}

							break;
							case "file_upload" :

								foreach ($addon['options'] as $option) {

									$field_name = 'addon-' . sanitize_title( $addon['name'] ) . '-' . sanitize_title( $option['label'] );

									if (!isset( $_FILES[$field_name] ) || empty( $_FILES[$field_name]) || empty($_FILES[$field_name]['name'])) {
										$passed = false;
										break;
									}

								}

							break;
						endswitch;

						if (!$passed) {
							$woocommerce->add_error( sprintf( __('"%s" is a required field.', 'woocommerce'), $addon['name']) );
							break;
						}
					}

					do_action('woocommerce_validate_posted_addon_data', $addon);

				}

				return $passed;
			}

			function max_upload_size() {
				$u_bytes = $this->convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
				$p_bytes = $this->convert_hr_to_bytes( ini_get( 'post_max_size' ) );
				$bytes = apply_filters( 'upload_size_limit', min($u_bytes, $p_bytes), $u_bytes, $p_bytes );
				return $this->convert_bytes_to_hr( $bytes );
			}

			function convert_hr_to_bytes( $size ) {
				$size = strtolower($size);
				$bytes = (int) $size;
				if ( strpos($size, 'k') !== false )
					$bytes = intval($size) * 1024;
				elseif ( strpos($size, 'm') !== false )
					$bytes = intval($size) * 1024 * 1024;
				elseif ( strpos($size, 'g') !== false )
					$bytes = intval($size) * 1024 * 1024 * 1024;
				return $bytes;
			}

			function convert_bytes_to_hr( $bytes ) {
				$units = array( 0 => 'B', 1 => 'kB', 2 => 'MB', 3 => 'GB' );
				$log = log( $bytes, 1024 );
				$power = (int) $log;
				$size = pow(1024, $log - $power);
				return $size . $units[$power];
			}

			function upload_dir( $pathdata ) {
				$subdir = '/product_addons_uploads/'.md5(session_id());
			 	$pathdata['path'] = str_replace($pathdata['subdir'], $subdir, $pathdata['path']);
			 	$pathdata['url'] = str_replace($pathdata['subdir'], $subdir, $pathdata['url']);
				$pathdata['subdir'] = str_replace($pathdata['subdir'], $subdir, $pathdata['subdir']);
				return $pathdata;
			}

		}

		$woocommerce_product_addons = new woocommerce_product_addons();
	}
}