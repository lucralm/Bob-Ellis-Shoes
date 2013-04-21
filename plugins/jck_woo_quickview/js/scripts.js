jQuery(document).ready(function($) {
	
	$('.jck_quickview_button').click(function(){
		var $parentLi = $(this).closest('.product');
		$.fancybox({
			content: $parentLi.find('.jck_quickview').clone(),
			onComplete: function(){
				if ($("form.variations_form").length > 0){
				  $("form.variations_form").wc_variation_form();
				}
				$('.thumbnails a:not(.firstThumb)').fadeTo(250, 0.5);
			},
			overlayColor: '#111',
			overlayOpacity: 0.8
		});
		return false;
	});
	
	$('.product a').has('.jck_quickview_button').hover(function(){
		$(this).find('.jck_quickview_button').css('display','block');
	}, function(){
		$(this).find('.jck_quickview_button').hide();
	});
	
	$('.jck_quickview .single_add_to_cart_button').live('click', function(){
		$(this).addClass('loading');		
	});
	
/* 	=============================
   	Inline Gallery 
   	============================= */
   	
   	$('.jck_quickview_thumb').live("click",function(){
   		$(this).fadeTo(250, 1).siblings().fadeTo(250, 0.5);
   		$(this).closest('.thumbnails').siblings('.attachment-shop_single').attr('src',$(this).attr('href')).load(function(){
	   		$.fancybox.center(true);
   		});
	   	return false;
   	});
});