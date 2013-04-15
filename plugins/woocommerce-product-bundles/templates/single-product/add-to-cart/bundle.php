<?php
/**
 * Bundled Product Add to Cart
 */

global $woocommerce, $product, $post, $woocommerce_bundles;

$per_product_pricing = $product->per_product_pricing_active;

?>

<script type="text/javascript">
	if ( ! product_variations )
			var product_variations = new Array();

	var bundle_price_data_<?php echo $post->ID; ?> = <?php echo json_encode( $bundle_price_data ); ?>;
	var bundled_item_quantities_<?php echo $post->ID; ?> = <?php echo json_encode( $bundled_item_quantities ); ?>;

</script>

<?php do_action('woocommerce_before_add_to_cart_form'); ?>

<?php foreach ( $bundled_products as $bundled_item_id => $bundled_product ) { ?>

	<script type="text/javascript">
		product_variations[<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?>] = <?php echo json_encode( $available_variations[$bundled_item_id] ); ?>;
		// pre WC 2.0 compatibility
		product_variations_<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?> = <?php echo json_encode( $available_variations[$bundled_item_id] ); ?>;
	</script>

	<?php
	// visibility
	$visibility = get_post_meta( $product->id, 'visibility_' . $bundled_item_id, true );
	?>

	<div class="bundled_product_summary product" <?php echo ( $visibility == 'hidden' ? 'style=display:none;' : '' ); ?> >

	<?php
		if ( $bundled_product->product_type == 'simple' ) {

			if ( $bundled_product->get_price() === '') continue;

			$item_quantity = get_post_meta( $product->id, 'bundle_quantity_' . $bundled_item_id, true );

			// title template
			$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-title.php', array(
					'product' => $bundled_product,
					'quantity' => $item_quantity,
					'custom_title' => get_post_meta( $product->id, 'product_title_' . $bundled_item_id, true )
				) );

			// image template
			if ( get_post_meta( $product->id, 'hide_thumbnail_' . $bundled_item_id, true ) != 'yes' )
				$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-image.php', array( 'post_id' => $bundled_product->id ));

			// description template
			$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-short-description.php', array(
					'product' => $bundled_product,
					'custom_description' => get_post_meta( $product->id, 'product_description_' . $bundled_item_id, true )
				) );

			// Availability
			$availability = $bundled_product->get_availability();

			if ( ! $woocommerce_bundles->validate_stock( $bundled_product->id, '', $item_quantity, true, true ) ) {
					$availability = array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
			}

			if ($availability['availability'])
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'.$availability['availability'].'</p>', $availability['availability'] );

			// Compatibility with plugins that normally hook to woocommerce_before_add_to_cart_button
			do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product->id );

			?>
			<div class="bundled_item_wrap">
				<?php
					if ($per_product_pricing)
						$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-price.php', array(
					'product' => $bundled_product
				) ); ?>
			</div>
			<?php

		} elseif ( $bundled_product->product_type == 'variable' ) {

			$item_quantity = get_post_meta( $product->id, 'bundle_quantity_' . $bundled_item_id, true );

			// title template
			$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-title.php', array(
					'product' => $bundled_product,
					'quantity' => $item_quantity,
					'custom_title' => get_post_meta( $product->id, 'product_title_' . $bundled_item_id, true )
				) );

			// image template
			if ( get_post_meta( $product->id, 'hide_thumbnail_' . $bundled_item_id, true ) != 'yes' )
				$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-image.php', array( 'post_id' => $bundled_product->id ));

			// description template
			$woocommerce_bundles->woo_bundles_get_template('single-product/bundled-product-short-description.php', array(
					'product' => $bundled_product,
					'custom_description' => get_post_meta( $product->id, 'product_description_' . $bundled_item_id, true )
				) );

			?>
			<form class="variations_form" data-bundled-item-id="<?php echo $bundled_item_id; ?>" data-product_id="<?php echo $post->ID . str_replace('_', '', $bundled_item_id); ?>" data-bundle-id="<?php echo $post->ID; ?>">
				<div class="variations">
					<?php

					$loop = 0; foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { $loop++; ?>
						<div class="attribute-options">
						<label for="<?php echo sanitize_title($name). '_' . $bundled_item_id; ?>"><?php if ( function_exists('ssc_remove_accents') ) { echo ssc_remove_accents( $woocommerce->attribute_label( $name ) ); } else { echo $woocommerce->attribute_label( $name ); } ?></label>
						<select id="<?php echo esc_attr( sanitize_title($name) . '_' . $bundled_item_id ); ?>" name="attribute_<?php echo sanitize_title($name); ?>">
							<option value=""><?php echo __('Choose an option', 'woocommerce') ?>&hellip;</option>
							<?php
								if( is_array( $options ) ) {
									if ( empty( $_POST ) )
										$selected_value = ( isset( $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ] ) ) ? $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ] : '';
									else
										$selected_value = isset( $_POST[ 'attribute_' . sanitize_title( $name ) ][ $bundled_item_id ] ) ? $_POST[ 'attribute_' . sanitize_title( $name ) ][ $bundled_item_id ] : '';


									// Do not show filtered-out (disabled) options
									if ( get_post_meta( $product->id, 'hide_filtered_variations_' . $bundled_item_id, true ) == 'yes' && $product->variation_filters_active[$bundled_item_id] && is_array( $product->filtered_variation_attributes[$bundled_item_id] ) && array_key_exists( sanitize_title( $name ), $product->filtered_variation_attributes[$bundled_item_id] ) ) {

										$options = $product->filtered_variation_attributes[$bundled_item_id][sanitize_title( $name )]['slugs'];
									}

									if ( taxonomy_exists( sanitize_title( $name ) ) ) {
										$args = array( 'menu_order' => 'ASC' );
										$terms = get_terms( sanitize_title($name), $args );

										foreach ( $terms as $term ) {
											if ( !in_array( $term->slug, $options ) ) continue;
											echo '<option value="'.$term->slug.'" '.selected( $selected_value, $term->slug, false ).'>'. apply_filters( 'woocommerce_variation_option_name', $term->name ) .'</option>';
										}
									}
									else {
										foreach ( $options as $option ) {
											echo '<option value="'.$option.'" '.selected( $selected_value, $option, false ).'>'. apply_filters( 'woocommerce_variation_option_name', $option ) .'</option>';
										}
									}
								}
							?>
						</select></div><?php

						if ( sizeof($attributes[ $bundled_item_id ]) == $loop ) {
							echo '<a class="reset_variations" href="#reset_' . $bundled_item_id .'">'.__('Clear selection', 'woocommerce').'</a>';
						}

					}
				?>

				</div>

				<?php
				// Compatibility with plugins that normally hook to woocommerce_before_add_to_cart_button
				do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product->id );
				?>

				<div class="single_variation_wrap bundled_item_wrap" style="display:none;">
					<div class="single_variation"></div>
					<div class="variations_button">
						<input type="hidden" name="variation_id" value="" />
					</div>
				</div>

			</form>
		<?php
		}
	?>

	</div>

<?php } ?>

<?php do_action('woocommerce_before_add_to_cart_button'); ?>

<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="bundle_form bundle_form_<?php echo $post->ID; ?> cart" method="post" enctype='multipart/form-data' data-bundle-id="<?php echo $post->ID; ?>">

	<div class="bundle_wrap" style="display:none;">
		<div class="bundle_price"></div>
		<?php
			// Bundle Availability
			$availability = $product->get_availability();

			if ($availability['availability'])
				echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'.$availability['availability'].'</p>', $availability['availability'] );
		?>
		<div class="bundle_button">
			<?php
			foreach ( $bundled_products as $bundled_item_id => $bundled_product ) {
				if ( $bundled_product->product_type == 'variable' ) {
					?><input type="hidden" name="variation_id[<?php echo $bundled_item_id; ?>]" value="" /><?php
					foreach ( $attributes[ $bundled_item_id ] as $name => $options ) { ?>
						<input type="hidden" name="attribute_<?php echo sanitize_title($name) . '[' . $bundled_item_id . ']'; ?>" value=""><?php
					}
				}
				?>
				<input type="hidden" name="add-product-to-cart[<?php echo $bundled_item_id; ?>]" value="<?php echo $bundled_product->product_type; ?>" />
			<?php
			}
			if ( !$product->is_sold_individually() ) woocommerce_quantity_input( array ( 'min_value' => 1 ) ); ?>
			<button type="submit" class="button alt"><?php echo apply_filters('single_add_to_cart_text', __('Add to cart', 'woocommerce'), $product->product_type); ?></button>
		</div>
	</div>

	<?php do_action('woocommerce_after_add_to_cart_button'); ?>

</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>
