<?php
/**
 * Index Template
 *
 * Here we setup all logic and XHTML that is required for the index template, used as both the homepage
 * and as a fallback template, if a more appropriate template file doesn't exist for a specific context.
 *
 * @package WooFramework
 * @subpackage Template
 */
	get_header();
	global $woo_options;
	
?>
	
    <div id="homepage-slider" class="col-full">
    	<?php echo get_new_royalslider(1);?>
    </div>

    <div id="homepage-promo" class="two-columns col-full">
		<div class="col-half">
			<div class="offer">
				<h1>Offer 1</h1>
			</div>
		</div>
		<div class="col-half">
			<div class="offer">
				<h1>Offer 2</h1>
			</div>
		</div>
    </div>

    <div id="featured-products-homepage" class="col-full">
    	<hr>
    	<?php mystile_featured_products();?>
    </div>
		
<?php get_footer(); ?>