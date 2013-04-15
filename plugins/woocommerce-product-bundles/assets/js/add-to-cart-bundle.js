jQuery(document).ready(function($) {

	$('form.variations_form')

		.on( 'found_variation', function( event, variation ) {
			var bundle_id 			= $(this).attr('data-bundle-id');
			var product_id			= $(this).attr('data-product_id');
			var bundled_item_id 	= $(this).attr('data-bundled-item-id');

			var bundle_variations 	= window[ "bundle_variations_" + bundle_id ];
			var bundle_price_data 	= window[ "bundle_price_data_" + bundle_id ];

			if ( bundle_price_data['per_product_pricing'] == true ) {
				// put variation price in price table
				bundle_price_data['prices'][bundled_item_id] = variation.price;
				bundle_price_data['regular_prices'][bundled_item_id] = variation.regular_price;
			}

			$( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find('input[name="variation_id['+ bundled_item_id +']"]').val( variation.variation_id ).change();

			for ( attribute in variation.attributes ) {
				$( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find('input[name="' + attribute + '['+ bundled_item_id +']"]').val( $(this).find('.attribute-options select[name="' + attribute + '"]').val() );
			}

			attempt_show_bundle( bundle_id );

		} )


		.on( 'woocommerce_update_variation_values', function() {
			var bundle_id = $(this).attr('data-bundle-id');
			var bundled_item_id 	= $(this).attr('data-bundled-item-id');

			$(this).find( '.bundled_item_wrap input[name="variation_id"]').each(function(){
				if ( $(this).val() == '' ) {
					$( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find('input[name="variation_id['+ bundled_item_id +']"]').val('');
					$( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).slideUp('200');
				}
			});


		} );

	function attempt_show_bundle( bundle_id ) {
		var all_set = true;

		$('#product-' + bundle_id + ' .variations select').each(function(){
			if ($(this).val().length == 0) {
				all_set = false;
			}
		});

		if (all_set) {

			var bundle_price_data = window[ "bundle_price_data_" + bundle_id ];
			var bundled_item_quantities = window[ "bundled_item_quantities_" + bundle_id ];

			if ( (bundle_price_data['per_product_pricing'] == false) && (bundle_price_data['total'] == -1) ) return;

			if ( bundle_price_data['per_product_pricing'] == true ) {
				bundle_price_data['total'] = 0;
				bundle_price_data['regular_total'] = 0;
				for ( prod_id in bundle_price_data['prices'] ) {
					bundle_price_data['total'] += bundle_price_data['prices'][prod_id] * bundled_item_quantities[prod_id];
					bundle_price_data['regular_total'] += bundle_price_data['regular_prices'][prod_id] * bundled_item_quantities[prod_id];
				}
			}

			if ( bundle_price_data['total'] == 0 )
				$('.bundle_form_' + bundle_id + ' .bundle_price').html('<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span>'+ bundle_price_data['free'] +'</p>');
			else {

				var sales_price = number_format ( bundle_price_data['total'], bundle_price_data['woocommerce_price_num_decimals'], bundle_price_data['woocommerce_price_decimal_sep'], bundle_price_data['woocommerce_price_thousand_sep'] );

				var regular_price = number_format ( bundle_price_data['regular_total'], bundle_price_data['woocommerce_price_num_decimals'], bundle_price_data['woocommerce_price_decimal_sep'], bundle_price_data['woocommerce_price_thousand_sep'] );

				var remove = bundle_price_data['woocommerce_price_decimal_sep'];

				if ( bundle_price_data['woocommerce_price_trim_zeros'] == 'yes' && bundle_price_data['woocommerce_price_num_decimals'] > 0 ) {
					for (var i = 0; i < bundle_price_data['woocommerce_price_num_decimals']; i++) { remove = remove + '0'; }
					sales_price = sales_price.replace(remove, '');
					regular_price = regular_price.replace(remove, '');
				}

				var sales_price_format = '';
				var regular_price_format = '';

				if ( bundle_price_data['woocommerce_currency_pos'] == 'left' ) {
					sales_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + sales_price + '</span>';
					regular_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + regular_price + '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'right' ) {
					sales_price_format = '<span class="amount">' + sales_price + bundle_price_data['currency_symbol'] +  '</span>';
					regular_price_format = '<span class="amount">' + regular_price + bundle_price_data['currency_symbol'] +  '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'left_space' ) {
					sales_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + '&nbsp;' + sales_price + '</span>';
					regular_price_format = '<span class="amount">' + bundle_price_data['currency_symbol'] + '&nbsp;' + regular_price + '</span>'; }
				else if ( bundle_price_data['woocommerce_currency_pos'] == 'right_space' ) {
					sales_price_format = '<span class="amount">' + sales_price + '&nbsp;' + bundle_price_data['currency_symbol'] +  '</span>';
					regular_price_format = '<span class="amount">' + regular_price + '&nbsp;' + bundle_price_data['currency_symbol'] +  '</span>'; }

				if ( bundle_price_data['regular_total'] > bundle_price_data['total'] ) {
					$('.bundle_form_' + bundle_id + ' .bundle_price').html('<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span><del>' + regular_price_format +'</del> <ins>'+ sales_price_format +'</ins></p>');
				} else {
					$('.bundle_form_' + bundle_id + ' .bundle_price').html('<p class="price"><span class="total">' + bundle_price_data['total_description'] + '</span>'+ sales_price_format +'</p>');
				}
			}

			// reset bundle stock status
			$('.bundle_form_' + bundle_id + ' .bundle_wrap p.stock').replaceWith( bundle_stock_status[bundle_id] );

			// set bundle stock status as out of stock if any selected variation is out of stock
			$('#product-' + bundle_id + ' .variations_form').each(function(){

				if ( $(this).find('.variations').length > 0 ) {

					var $item_stock_p = $(this).find('p.stock');

					if ( $item_stock_p.hasClass('out-of-stock') ) {
						if ( $('.bundle_form_' + bundle_id + ' .bundle_wrap p.stock').length > 0 ) {
							$('.bundle_form_' + bundle_id + ' .bundle_wrap p.stock').replaceWith( $item_stock_p.clone() );
						} else {
							$('.bundle_form_' + bundle_id + ' .bundle_wrap .bundle_price').after( $item_stock_p.clone() );
						}
					}

				}
			});

			$('.bundle_form_' + bundle_id + ' .bundle_wrap').slideDown('200').trigger('show_bundle');
		}
	}


	function check_all_simple( bundle_id ) {

		var bundle_price_data = window[ "bundle_price_data_" + bundle_id ];
		var bundle_variations = window[ "bundle_variations_" + bundle_id ];

		if ( typeof bundle_price_data == 'undefined' ) { return false; }
		if ( bundle_price_data['prices'].length < 1 ) { return false; }
		if ( $( '.bundle_form_' + bundle_id + ' input[value="variable"]' ).length > 0 ) {
			return false;
		}
		return true;
	}


	/**
	 * Initial states and loading
	 */

	var bundle_stock_status = [];

	$('.bundle_form').each( function() {
		var bundle_id = $(this).attr('data-bundle-id');

		if ( $(this).find('.bundle_wrap p.stock').length > 0 )
			bundle_stock_status[bundle_id] = $(this).find('.bundle_wrap p.stock').clone().wrap('<p>').parent().html();

		$('#product-' + bundle_id + ' .variations select').change();

		if ( check_all_simple( bundle_id ) )
			attempt_show_bundle( bundle_id );
	});



	/**
	 * Helper functions for variations
	 */

	function number_format( number, decimals, dec_point, thousands_sep ) {
	    var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
	    var d = dec_point == undefined ? "," : dec_point;
	    var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
	    var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;

	    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}


});