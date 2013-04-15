<?php
/**
 * Product Bundle Class
 *
 * This class prepares variation & pricing data for all bundled products, which are passed on to the bundle.php add-to-cart template
 * It also includes a new version of get_price_html() which takes into account "per-product pricing" and possibly disabled variations
 *
 * @class 		WC_Product_Bundle
 */

class WC_Product_Bundle extends WC_Product {

	var $bundled_item_ids;
	var $bundled_products;

	var $min_bundle_price;
	var $max_bundle_price;
	var $min_bundle_regular_price;
	var $max_bundle_regular_price;

	var $current_bundle_price;
	var $current_bundle_regular_price;

	var $bundle_attributes;
	var $available_bundle_variations;
	var $selected_bundle_attributes;

	var $allowed_variations;
	var $variation_filters_active = array();
	var $has_filters;

	var $filtered_variation_attributes = array();

	var $bundle_defaults;
	var $bundle_defaults_active = array();
	var $has_overrides;

	var $bundled_item_quantities = array();

	var $per_product_pricing_active;
	var $per_product_shipping_active;

	var $sold_individually;

	var $availability;

	var $bundle_price_data;

	var $is_wc_v2 = false;

	function __construct( $bundle_id ) {

		global $woocommerce_bundles;

		$this->product_type = 'bundle';

		parent::__construct( $bundle_id );

		if ( $woocommerce_bundles->is_wc_v2() ) {

			$this->is_wc_v2 = true;

		}

		$this->bundled_item_ids = get_post_meta( $this->id, '_bundled_ids', true );

		$this->has_filters = false;
		$this->has_overrides = false;

		$this->sold_individually = true;

		if ( $this->bundled_item_ids ) {
			foreach( $this->bundled_item_ids as $bundled_id ) {

				// Store 'variation filtering' boolean variables
				if ( get_post_meta( $this->id, 'filter_variations_'.$bundled_id, 'true' ) == 'yes' ) {
					$this->variation_filters_active[$bundled_id] = true;
					$this->has_filters = true;
				} else {
					$this->variation_filters_active[$bundled_id] = false;
				}

				// Store 'override defaults' boolean variables
				if ( get_post_meta( $this->id, 'override_defaults_'.$bundled_id, 'true' ) == 'yes' ) {
					$this->bundle_defaults_active[$bundled_id] = true;
					$this->has_overrides = true;
				} else {
					$this->bundle_defaults_active[$bundled_id] = false;
				}

				// Store bundled item quantities
				$this->bundled_item_quantities[$bundled_id] = get_post_meta( $this->id, 'bundle_quantity_'.$bundled_id, 'true' );
			}
		}

		if ($this->has_filters) {
			$this->allowed_variations = maybe_unserialize( get_post_meta( $this->id, '_allowed_variations', true ) );

			// create array of attributes based on active variations

			foreach ( $this->allowed_variations as $item_id => $allowed_variations ) {

				if ( !$this->variation_filters_active[$item_id] )
					continue;

				$sep = explode( '_', $item_id );
				$product_id = $sep[0];

				$attributes = ( array ) maybe_unserialize( get_post_meta( $product_id, '_product_attributes', true ) );

				// filtered variation attributes (stores attributes of active variations)
				$filtered_attributes = array();

				$this->filtered_variation_attributes[$item_id] = array();

				// make array of active variation attributes
				foreach ( $this->allowed_variations[$item_id] as $allowed_variation_id ) {

					// get variation meta of allowed variations
					$product_custom_fields = get_post_custom( $allowed_variation_id );

					foreach ( $product_custom_fields as $name => $value ) :

						if ( ! strstr( $name, 'attribute_' ) ) continue;
 						$attribute_name = substr( $name, strlen('attribute_') );

						if( !$value[0] ) {
							$description = 'Any';
						}
						else {
							$term = get_term_by( 'slug', $value[0], $attribute_name );
							$description = ( $term == false ) ? '' : $term->name;
						}

						if ( !$description )
							$description = $value[0];


						if ( !isset( $filtered_attributes[$attribute_name] ) ) {
							$filtered_attributes[$attribute_name]['descriptions'][] = $description;
							$filtered_attributes[$attribute_name]['slugs'][] = $value[0];
						} elseif ( !in_array( $description, $filtered_attributes[$attribute_name] ) ) {
							$filtered_attributes[$attribute_name]['descriptions'][] = $description;
							$filtered_attributes[$attribute_name]['slugs'][] = $value[0];
						}

					endforeach;


					// clean up product attributes
			        foreach ( $attributes as $attribute ) {

			            if ( ! $attribute['is_variation'] )
			            	continue;

						$attribute_name = sanitize_title( $attribute['name'] );

						if ( array_key_exists( $attribute_name, $filtered_attributes ) && !in_array('Any', $filtered_attributes[$attribute_name]['descriptions'] ) )
							$this->filtered_variation_attributes[$item_id][$attribute_name] = $filtered_attributes[$attribute_name];

					}

				}

			}

		}

		if ($this->has_overrides)
			$this->bundle_defaults = get_post_meta( $this->id, '_bundle_defaults', true );

		$this->per_product_pricing_active = ( get_post_meta( $this->id, '_per_product_pricing_active', true ) == 'yes' ) ? true : false;

		if ($this->per_product_pricing_active) {
			$this->price = 0;
		}

		$this->per_product_shipping_active = ( get_post_meta( $this->id, '_per_product_shipping_active', true ) == 'yes' ) ? true : false;

		if ($this->bundled_item_ids) {
			$this->load_bundle_data();
			$this->availability = $this->bundle_availability();
		}

	}


	function load_bundle_data() {

		global $woocommerce_bundles;

		// stores bundle pricing strategy info and price table
		$this->bundle_price_data = array();

		$this->bundle_price_data['currency_symbol'] = get_woocommerce_currency_symbol();
		$this->bundle_price_data['woocommerce_price_num_decimals'] = (int) get_option('woocommerce_price_num_decimals');
		$this->bundle_price_data['woocommerce_currency_pos'] = get_option('woocommerce_currency_pos');
		$this->bundle_price_data['woocommerce_price_decimal_sep'] = stripslashes(get_option('woocommerce_price_decimal_sep'));
		$this->bundle_price_data['woocommerce_price_thousand_sep'] = stripslashes(get_option('woocommerce_price_thousand_sep'));
		$this->bundle_price_data['woocommerce_price_trim_zeros'] = get_option('woocommerce_price_trim_zeros');

		$this->bundle_price_data['free'] = __('Free!', 'woocommerce');
		$this->bundle_price_data['per_product_pricing'] = $this->per_product_pricing_active;
		$this->bundle_price_data['prices'] = array();
		$this->bundle_price_data['regular_prices'] = array();
		$this->bundle_price_data['total'] = ( ($this->per_product_pricing_active) ? (float) 0 : (float) ( $this->get_price()=='' ? -1 : $this->get_price() ) );
		$this->bundle_price_data['regular_total'] = ( ($this->per_product_pricing_active) ? (float) 0 : (float) $this->regular_price );
		$this->bundle_price_data['total_description'] = __('Total', 'woo-bundles') . ': ';

		$this->bundle_attributes = array();
		$this->available_bundle_variations = array();
		$this->selected_bundle_attributes = array();
		$this->bundled_products = array();

		foreach ($this->bundled_item_ids as $bundled_item_id) :

			// remove suffix
			$sep = explode( '_', $bundled_item_id );
			$product_id = $sep[0];

			$bundled_product_post = get_post( $product_id );

			if ( get_post_status( $product_id ) != 'publish' ) continue;

			if ( $this->is_wc_v2 )
				$bundled_product = get_product( $product_id );
			else
				$bundled_product = new WC_Product( $product_id );

			$this->bundled_products[$bundled_item_id] = $bundled_product;

			if ( $bundled_product->product_type == 'simple' ) {

				if ( !$bundled_product->is_sold_individually() )
					$this->sold_individually = false;

				// price for simple products gets stored now, for variable products jquery gets the job done
				$this->bundle_price_data['prices'][$bundled_product->id] = (float) $bundled_product->get_price();
				$this->bundle_price_data['regular_prices'][$bundled_product->id] = (float) $bundled_product->regular_price;

				// no variation data to load - product is simple

				$this->min_bundle_price = $this->min_bundle_price + $this->bundled_item_quantities[$bundled_item_id] * $bundled_product->get_price();
				$this->min_bundle_regular_price = $this->min_bundle_regular_price + $this->bundled_item_quantities[$bundled_item_id] *$bundled_product->regular_price;

				$this->max_bundle_price = $this->max_bundle_price + $this->bundled_item_quantities[$bundled_item_id] * $bundled_product->get_price();
				$this->max_bundle_regular_price = $this->max_bundle_regular_price + $this->bundled_item_quantities[$bundled_item_id] * $bundled_product->regular_price;
			}

			//
			// woocommerce-template -> woocommerce_variable_add_to_cart
			//

			elseif ( $bundled_product -> product_type == 'variable' ) {

				// prepare price variable for jquery
				$this->bundle_price_data['prices'][$bundled_item_id] = 0;
				$this->bundle_price_data['regular_prices'][$bundled_item_id] = 0;

				// get all available attributes and settings
				$this->bundle_attributes[$bundled_item_id] = $bundled_product->get_variation_attributes();

				$default_product_attributes = array();

				if ( $this->bundle_defaults_active[$bundled_item_id] ) {
					$default_product_attributes = $this->bundle_defaults[$bundled_item_id];
				} else {
					$default_product_attributes = ( array ) maybe_unserialize( get_post_meta( $bundled_product_post->ID, '_default_attributes', true  ) );
				}

				$this->selected_bundle_attributes[$bundled_item_id] = apply_filters( 'woocommerce_product_default_attributes', $default_product_attributes  );

				// calculate min-max variation prices

				$min_variation_regular_price 	= '';
				$min_variation_sale_price 		= '';
				$max_variation_regular_price 	= '';
				$max_variation_sale_price 		= '';

				foreach ( $bundled_product->get_children() as $child_id ) {

					$variation = $bundled_product->get_child( $child_id  );

					// stop here if this variation is not within the active set (prices will not include this variation)
					if ( $this->variation_filters_active[$bundled_item_id] ) {
						if ( !is_array($this->allowed_variations[$bundled_item_id]) ) continue;
						if ( !in_array($child_id, $this->allowed_variations[$bundled_item_id]) ) continue;
					}

					if ( $variation instanceof WC_Product_Variation ) {

						if ( get_post_status( $variation->get_variation_id() ) != 'publish' ) continue; // Disabled

						if ( !$variation->is_sold_individually() )
							$this->sold_individually = false;

						// variation min-max price calculation

						$variation_price = get_post_meta($child_id, '_price', true);
						$variation_sale_price = get_post_meta($child_id, '_sale_price', true);

						// Low price
						if ( !is_numeric($min_variation_regular_price) || $variation_price < $min_variation_regular_price )
							$min_variation_regular_price = $variation_price;
						if ( $variation_sale_price !== '' && (!is_numeric($min_variation_sale_price) || $variation_sale_price < $min_variation_sale_price ))
							$min_variation_sale_price = $variation_sale_price;

						// High price
						if ( !is_numeric($max_variation_regular_price) || $variation_price > $max_variation_regular_price)
							$max_variation_regular_price = $variation_price;
						if ( $variation_sale_price !== '' && (!is_numeric($max_variation_sale_price) || $variation_sale_price >
							$max_variation_sale_price)) $max_variation_sale_price = $variation_sale_price;


						// prepare available options

						$variation_attributes = $variation->get_variation_attributes();
						$availability = $variation->get_availability();

						if ( ! is_admin() && ! $woocommerce_bundles->validate_stock( $product_id, $variation->variation_id, get_post_meta( $this->id, 'bundle_quantity_'.$bundled_item_id, true ), true, true ) ) {
							$availability = array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
						}

						$availability_html = ( ! empty( $availability['availability'] ) ) ? apply_filters( 'woocommerce_stock_html', '<p class="stock ' . $availability['class'] . '">'. $availability['availability'].'</p>', 	$availability['availability']  ) : '';

						if ( has_post_thumbnail( $variation->get_variation_id() ) ) {
							$attachment_id = get_post_thumbnail_id( $variation->get_variation_id() );

							$attachment = wp_get_attachment_image_src( $attachment_id, apply_filters( 'bundled_product_large_thumbnail_size', 'shop_single' )  );
							$image = $attachment ? current( $attachment ) : '';

							$attachment = wp_get_attachment_image_src( $attachment_id, 'full'  );
							$image_link = $attachment ? current( $attachment ) : '';

							$image_title = get_the_title( $attachment_id );
						} else {
							$image = $image_link = $image_title = '';
						}

						$this->available_bundle_variations[$bundled_item_id][] = apply_filters( 'woocommerce_available_variation', array(
							'variation_id'				=> $variation->get_variation_id(),
							'product_id'				=> $bundled_item_id,
							'attributes'				=> $variation_attributes,
							'image_src'					=> $image,
							'image_link'				=> $image_link,
							'image_title'				=> $image_title,
							'price'						=> (float) $variation->get_price(),
							'regular_price'				=> (float) $variation->regular_price,
							'price_html'				=> ( ( $this->per_product_pricing_active ) ? '<span class="price">' . $variation->get_price_html() . '</span>' : '' ),
							'availability_html'			=> $availability_html
							//'sku'						=> __( 'SKU:', 'woocommerce' ) . ' ' . $variation->sku,
							//'min_qty'					=> 1,
							//'max_qty'					=> $variation->stock,
							//'is_downloadable'			=> $variation->is_downloadable() ,
							//'is_virtual'				=> $variation->is_virtual(),
							//'is_sold_individually' 	=> $variation->is_sold_individually() ? 'yes' : 'no'
					) , $bundled_product, $variation );
					}

				}

				$add = ($min_variation_sale_price==='' || $min_variation_regular_price < $min_variation_sale_price) ? $min_variation_regular_price : $min_variation_sale_price;

				$this->min_bundle_price 		= $this->min_bundle_price + $this->bundled_item_quantities[$bundled_item_id] * $add ;

				$this->min_bundle_regular_price = $this->min_bundle_regular_price + $this->bundled_item_quantities[$bundled_item_id] * $min_variation_regular_price;

				$add = ($max_variation_sale_price==='' || $max_variation_regular_price < $max_variation_sale_price) ? $max_variation_regular_price : $max_variation_sale_price;

				$this->max_bundle_price 		= $this->max_bundle_price + $this->bundled_item_quantities[$bundled_item_id] * $add;

				$this->max_bundle_regular_price = $this->max_bundle_regular_price + $this->bundled_item_quantities[$bundled_item_id] * $max_variation_regular_price;
			}

		endforeach;

	}


	function get_bundle_price_data() {
		return $this->bundle_price_data;
	}

	function get_bundle_attributes() {
		return $this->bundle_attributes;
	}

	function get_bundled_item_quantities() {
		return $this->bundled_item_quantities;
	}

	function get_selected_bundle_attributes() {
		return $this->selected_bundle_attributes;
	}

	function get_available_bundle_variations() {
		return $this->available_bundle_variations;
	}

	function get_bundled_products() {
		return $this->bundled_products;
	}


	function get_price_html( $price = '' ) {
		if ( $this->per_product_pricing_active ) {

			// Get the price
			if ($this->min_bundle_price > 0) :
				if ( $this->is_on_sale() && $this->min_bundle_regular_price !== $this->min_bundle_price ) :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, $this->min_bundle_price );

					$price = apply_filters('woocommerce_bundle_sale_price_html', $price, $this);

				else :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price )
						$price .= $this->get_price_html_from_text();

					$price .= woocommerce_price( $this->min_bundle_price );

					$price = apply_filters('woocommerce_bundle_price_html', $price, $this);

				endif;
			elseif ($this->min_bundle_price === '' ) :

				$price = apply_filters('woocommerce_bundle_empty_price_html', '', $this);

			elseif ($this->min_bundle_price == 0 ) :

				if ($this->is_on_sale() && isset($this->min_bundle_regular_price) && $this->min_bundle_regular_price !== $this->min_bundle_price ) :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price )
						$price .= $this->get_price_html_from_text();

					$price .= $this->get_price_html_from_to( $this->min_bundle_regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters('woocommerce_bundle_free_sale_price_html', $price, $this);

				else :

					if ( !$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price )
						$price .= $this->get_price_html_from_text();

					$price .= __('Free!', 'woocommerce');

					$price = apply_filters('woocommerce_bundle_free_price_html', $price, $this);

				endif;

			endif;
		} else {

			if ( $this->price > 0 ) :
				if ($this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, $this->get_price() );

					$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

				else :

					$price .= woocommerce_price( $this->get_price() );

					$price = apply_filters( 'woocommerce_price_html', $price, $this );

				endif;
			elseif ( $this->price === '' ) :

				$price = apply_filters('woocommerce_empty_price_html', '', $this);

			elseif ( $this->price == 0 ) :

				if ( $this->is_on_sale() && isset( $this->regular_price ) ) :

					$price .= $this->get_price_html_from_to( $this->regular_price, __('Free!', 'woocommerce') );

					$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );

				else :

					$price = __('Free!', 'woocommerce');

					$price = apply_filters( 'woocommerce_free_price_html', $price, $this );

				endif;

			endif;
		}

			return $price;
	}



	function is_on_sale() {
		if ( $this->per_product_pricing_active && ! empty( $this->bundled_item_ids ) ) {
			$is_on_sale = false;
			foreach ( $this->bundled_item_ids as $bundled_item_id ) :
				// remove suffix
				$sep = explode( '_', $bundled_item_id );
				$product_id = $sep[0];

				if ( $this->is_wc_v2 )
					$bundled_product = get_product( $product_id );
				else
					$bundled_product = new WC_Product( $product_id );

				if ( $bundled_product->is_on_sale() ) { $is_on_sale = true; break; }
			endforeach;
			return $is_on_sale;
		} else {
			if ( $this->sale_price && $this->sale_price == $this->price ) return true;
		}
	}


	/** Returns whether or not the bundle has any attributes set */
	function has_attributes() {
		// check bundle for attributes
		if (sizeof($this->get_attributes())>0) :
			foreach ($this->get_attributes() as $attribute) :
				if (isset($attribute['is_visible']) && $attribute['is_visible']) return true;
			endforeach;
		endif;
		// check all bundled items for attributes
		if ( $this->get_bundled_products() ) {
			foreach ($this->get_bundled_products() as $bundled_product) {
				if (sizeof($bundled_product->get_attributes())>0) :
					foreach ($bundled_product->get_attributes() as $attribute) :
						if (isset($attribute['is_visible']) && $attribute['is_visible']) return true;
					endforeach;
				endif;
			}
		}
		return false;
	}


	function is_sold_individually() {
		return $this->sold_individually;
	}


	function get_availability() {
		return $this->availability;
	}

	function is_in_stock() {

		if ( ! is_admin() ) {

			if ( $this->availability['class'] == 'out-of-stock' )
				return false;
		}

		return parent::is_in_stock();
	}

	function bundle_availability() {

		if ( ! is_admin() ) {

			global $woocommerce_bundles;

			foreach ($this->get_bundled_products() as $bundled_item_id => $bundled_product) {

				$sep 	= explode( '_', $bundled_item_id );
				$id 	= $sep[0];

				$availability = $bundled_product->get_availability();

				// for both simple & variable items
				if ( $availability['class'] == 'out-of-stock' )
					return array( 'availability' => $availability['availability'], 'class' => $availability['class'] );

				// if any simple item is out of stock, mark bundle as out of stock
				if ( $bundled_product->is_type('simple') && ! $woocommerce_bundles->validate_stock( $id, '', get_post_meta( $this->id, 'bundle_quantity_'.$bundled_item_id, true ), true, true ) ) {
					return array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );
				}

				// if any variable item's active variations are all out of stock, mark bundle as out of stock
				elseif ( $bundled_product->is_type('variable') ) {

					$product_in_stock = false;

					foreach ( $bundled_product->get_children() as $variation_id ) {

						// stop here if this variation is not within the active set
						if ( $this->variation_filters_active[$bundled_item_id] ) {
							if ( !is_array($this->allowed_variations[$bundled_item_id]) ) continue;
							if ( !in_array($variation_id, $this->allowed_variations[$bundled_item_id]) ) continue;
						}

						if ( get_post_status( $variation_id ) != 'publish' ) continue; // Disabled

						if ( $woocommerce_bundles->validate_stock( $id, $variation_id, get_post_meta( $this->id, 'bundle_quantity_'.$bundled_item_id, true ), true, true ) ) {
							$product_in_stock = true;
						}
					}

					if ( !$product_in_stock )
						return array( 'availability' => __( 'Out of stock', 'woocommerce' ), 'class' => 'out-of-stock' );

				}
			}

		}

		return parent::get_availability();
	}


	/** Lists a table of attributes for the bundle page */
	function list_attributes() {

		// show attributes attached to the bundle only
		woocommerce_get_template('single-product/product-attributes.php', array(
			'product' => $this
		));

		foreach ($this->get_bundled_products() as $bundled_item_id => $bundled_product) {
			if (!$this->per_product_shipping_active)
				$bundled_product->length = $bundled_product->width = $bundled_product->weight = '';
			if ( $bundled_product->has_attributes() ) {
				$GLOBALS['listing_attributes_of'] = $bundled_item_id;
				echo '<h3>'.get_the_title($bundled_product->id).'</h3>';
				woocommerce_get_template('single-product/product-attributes.php', array(
					'product' => $bundled_product
				));
			}
		}
		unset( $GLOBALS['listing_attributes_of'] );
	}


}

