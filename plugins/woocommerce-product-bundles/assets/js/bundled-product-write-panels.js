/*
// from write-panels.js
*/

jQuery( function($){

	// bundle type move stock msg up
	$('.bundle_stock_msg').insertBefore('._manage_stock_field');

	// bundle type specific options
	$('select#product-type').change(function(){

		// get value
		var select_val = $(this).val();

		$('.hide_if_grouped, .hide_if_external').show();
		$('.show_if_bundle').hide();

		if ( select_val=='bundle' ) {
			$('input#_downloadable').prop('checked', false).change();
			$('input#_virtual').removeAttr('checked').change();

			$('._sku_field').closest('div.options_group').show();
			$('.show_if_bundle').show();
			$('.inventory_options').show();
			$('#inventory_product_data .show_if_simple').show();
			$('input#_manage_stock').change();

			$('input#_per_product_pricing_active').change();
			$('input#_per_product_shipping_active').change();
		}

		$('ul.tabs li:visible').eq(0).find('a').click();

		$('body').trigger('woocommerce-product-type-change', select_val, $(this) );

	}).change();


	// variation filtering options
	$('.filter_variations input').change(function(){
		if ($(this).is(':checked')) $(this).closest('div.item-data').find('div.bundle_variation_filters').show();
		else $(this).closest('div.item-data').find('div.bundle_variation_filters').hide();
	}).change();

	// selection defaults options
	$('.override_defaults input').change(function(){
		if ($(this).is(':checked')) $(this).closest('div.item-data').find('div.bundle_selection_defaults').show();
		else $(this).closest('div.item-data').find('div.bundle_selection_defaults').hide();
	}).change();

	// visibility
	$('.item_visibility select').change(function(){

		if ( $(this).val() == 'visible' ) {
			$(this).closest('div.item-data').find('div.override_title').show();
			$(this).closest('div.item-data').find('div.override_description').show();
			$(this).closest('div.item-data').find('div.images').show();
		} else {
			$(this).closest('div.item-data').find('div.override_title').hide();
			$(this).closest('div.item-data').find('div.override_description').hide();
			$(this).closest('div.item-data').find('div.images').hide();
		}

	}).change();

	// custom title options
	$('.override_title > p input').change(function(){
		if ($(this).is(':checked')) $(this).closest('div.override_title').find('div.custom_title').show();
		else $(this).closest('div.override_title').find('div.custom_title').hide();
	}).change();

	// custom description options
	$('.override_description > p input').change(function(){
		if ($(this).is(':checked')) $(this).closest('div.override_description').find('div.custom_description').show();
		else $(this).closest('div.override_description').find('div.custom_description').hide();
	}).change();

	// non-bundled shipping
	$('input#_per_product_shipping_active').change(function(){

		if ( $('select#product-type').val() == 'bundle' ) {

			if ($('input#_per_product_shipping_active').is(':checked')) {
				$('.show_if_virtual').show();
				$('.hide_if_virtual').hide();
			} else {
				$('.show_if_virtual').hide();
				$('.hide_if_virtual').show();
			}
		}

	}).change();

	// show options if pricing is static
	$('input#_per_product_pricing_active').change(function(){

		if ( $('select#product-type').val() == 'bundle' ) {

			if ( $(this).is(':checked') ) {

				$('#_regular_price').attr('disabled', true);
		        $('#_regular_price').val('');
		        $('#_sale_price').attr('disabled', true);
		        $('#_sale_price').val('');

				$('._tax_class_field').closest('.options_group').hide();
				$('.pricing').hide();
			} else {

				$('#_regular_price').removeAttr('disabled');
		        $('#_sale_price').removeAttr('disabled');

				$('._tax_class_field').closest('.options_group').show();
				$('.pricing').show();
			}
		}

	}).change();

});