jQuery(document).ready(function($) {

	/////////////////////////////////////////////
	// cart slider
	/////////////////////////////////////////////

	function cart_slider() {
		$('.cart-slides').jcarousel({
			visible: 6,
			scroll: 6,
			animation: 500,
		});
	}

	/////////////////////////////////////////////
	// Add to cart ajax
	/////////////////////////////////////////////

	$('body').bind('adding_to_cart', function() {
		$('#cart-loader').removeClass('hide');
	});

	$('body').bind('added_to_cart', function() {
		var shopdock = $('#addon-shopdock');
		shopdock.slideDown();
		if (shopdock.size() > 0) {
			shopdock.load(window.location + ' #addon-shopdock > *', function() {
				// remove class dock-on
				$('body').removeClass('dock-on');
				$('#cart-loader').addClass('hide');
				cart_slider();
			});
		}
	});

	// remove item ajax
	$('.remove-item-js').live('click', function() {
		var href = $(this).attr('href');
		var shopdock = $('#addon-shopdock');
		$('#cart-loader').removeClass('hide');
		$.get(href, function(response) {
			var this_page = window.location.toString();
			this_page = this_page.replace('add-to-cart', 'added-to-cart');
			window.location = this_page;
		});
		return false;
	});
});