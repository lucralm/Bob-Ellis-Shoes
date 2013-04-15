jQuery(document).ready(function($){

    /////////////////////////////////////////////
    // Check is_mobile
	/////////////////////////////////////////////
	$('body').addClass( (document.body.clientWidth < 600 ) ? 'is_mobile' : 'is_desktop');

	/////////////////////////////////////////////
	// Cart slider
	/////////////////////////////////////////////
	cart_slider();

	function cart_slider(){
		$('.cart-slides').jcarousel({
			visible: 6,
			//auto: 0,
			scroll: 6,
			animation: 500,
			//initCallback: carousel_callback
		});
	}

});