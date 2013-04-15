<?php
if (!is_user_logged_in() || !current_user_can('manage_options')) wp_die('This page is private.');

global $woocommerce;

function show_stock_report_row( $post, $product, $nested = false ) {
	global $woocommerce;
	?>
	<tr>
		<td><?php echo $product->get_sku(); ?></td>
		<td><?php

			if ( ! $nested )
				$post_title = $post->post_title;
			else
				$post_title = '';

			// Get variation data
	        if ( $post->post_type == 'product_variation' ) {
	        	$post_title = trim(current(explode('-', $post_title)));

	        	$post_title .= ' &mdash; <small><em>';

	        	$list_attributes = array();
	        	$attributes = $product->get_variation_attributes();

	        	foreach ( $attributes as $name => $attribute ) {
	        		$list_attributes[] = $woocommerce->attribute_label( str_replace('attribute_', '', $name) ) . ': <strong>' . $attribute . '</strong>';
	        	}

	        	$post_title .= implode(', ', $list_attributes);

	        	$post_title .= '</em></small>';
	        }

	        echo $post_title;

		?></td>
		<td><?php echo $post->ID; ?></td>
		<td><?php echo $post->post_type == 'product' ? 'Product' : 'Variation'; ?></td>
		<td><?php echo woocommerce_price( $product->get_price() ); ?></td>
		<td><?php echo $product->stock; ?></td>
	</tr>
	<?php
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php _e('Stock Report'); ?></title>
		<style>
			body { background:white; color:black; width: 95%; margin: 0 auto; }
			table { border: 1px solid #000; width: 100%; }
			table td, table th { border: 1px solid #000; padding: 6px; }
			p.date { float: right; }
		</style>
	</head>
	<body>
		<header>
			<p class="date"><?php echo date_i18n(get_option('date_format') , current_time('timestamp')); ?></p>
			<h1 class="title"><?php _e('Stock Report', 'wc_stock_management'); ?></h1>
		</header>
		<section>
		<table cellspacing="0" cellpadding="2">
			<thead>
				<tr>
					<th scope="col" style="text-align:left;"><?php _e('SKU', 'wc_stock_management'); ?></th>
					<th scope="col" style="text-align:left;"><?php _e('Product', 'wc_stock_management'); ?></th>
					<th scope="col" style="text-align:left;"><?php _e('ID', 'wc_stock_management'); ?></th>
					<th scope="col" style="text-align:left;"><?php _e('Type', 'wc_stock_management'); ?></th>
					<th scope="col" style="text-align:left;"><?php _e('Unit Cost', 'wc_stock_management'); ?></th>
					<th scope="col" style="text-align:left;"><?php _e('Stock Qty', 'wc_stock_management'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				$post_ids = $variation_ids = array( 0 );

				$meta_query = array();

				$meta_query[] = array(
					'key'	=> '_manage_stock',
					'value'	=> 'yes'
				);

				$orderby 	= 'meta_value title id';
				$meta_key 	= '_sku';

				/**
				 * Find ID's of posts managing stock
				 */
				$product_ids = get_posts(array(
					'post_type' 		=> 'product',
					'posts_per_page' 	=> -1,
					'post_status' 		=> 'publish',
					'fields'			=> 'ids',
					'meta_query'		=> $meta_query,
					'meta_key'			=> $meta_key,
					'orderby'			=> esc_attr( $orderby ),
					'order'				=> 'asc',
				));

				$meta_query = array();

				$meta_query[] = array(
					'key'		=> '_stock',
					'value'		=> array( '', null ),
					'compare'	=> 'NOT IN'
				);

				/**
				 * Find ID's of variations managing stock
				 */
				$variation_ids = get_posts(array(
					'post_type' 		=> 'product_variation',
					'posts_per_page' 	=> -1,
					'post_status' 		=> 'publish',
					'fields'			=> 'id=>parent',
					'meta_query'		=> $meta_query,
					'meta_key'			=> $meta_key,
					'orderby'			=> esc_attr( $orderby ),
					'order'				=> 'asc',
				));

				//$product_ids = array_merge( $post_ids, $variation_ids );

				/*$meta_key 	= '_sku';
				$orderby 	= 'parent meta_value title id';

		        $posts_ids = get_posts(array(
					'post_type' 		=> array( 'product', 'product_variation' ),
					'posts_per_page' 	=> -1,
					'post_status' 		=> 'publish',
					'orderby'			=> esc_attr( $orderby ),
					'order'				=> 'asc',
					'post__in'			=> $product_ids,
					'meta_key'			=> $meta_key,
					'fields'			=> 'id=>parent',
				));*/

				foreach ( $product_ids as $post_id ) {

				    if ( function_exists( 'get_product' ) )
						$product = get_product( $post_id );
					else {
						$product = new WC_Product( $post_id );
					}

					$product_post = $product->get_post_data();

					show_stock_report_row( $product_post, $product );

					foreach ( $variation_ids as $var_id => $parent ) {
						if ( $parent == $product_post->ID ) {

							if ( function_exists( 'get_product' ) ) {
								$variation = get_product( $var_id );
								$variation_post = $product->get_post_data();
							} else {
								$variation = new WC_Product_Variation( $var_id );
								$variation_post = get_post( $var_id );
							}

							unset( $variation_ids[ $var_id ] );

							show_stock_report_row( $variation_post, $variation, true );
						}
					}
				}

				foreach ( $variation_ids as $var_id => $parent ) {
					if ( function_exists( 'get_product' ) ) {
						$variation = get_product( $var_id );
						$variation_post = $product->get_post_data();
					} else {
						$variation = new WC_Product_Variation( $var_id );
						$variation_post = get_post( $var_id );
					}

					show_stock_report_row( $variation_post, $variation );
				}
			?>
			</tbody>
		</table>
	</body>
</html>