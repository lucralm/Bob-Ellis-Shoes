<?php
/**
* @package jck_woo_quickview
* @version 1.3
*/

/*
Plugin Name: WooCommerce Quickview
Plugin URI: http://wordpress.org/extend/plugins/admin-quick-jump/
Description: 
Author: James Kemp
Version: 1.0
Author URI: http://www.jckemp.com/
*/

class jck_woo_quickview
{

/* 	=============================
   	Front-end Scripts & Styles 
   	============================= */
  
	function jck_sands() {
		/** Scripts **/
		wp_enqueue_style('fancybox', plugins_url( '/js/fancybox/jquery.fancybox-1.3.4.css' , __FILE__ ));
		
		wp_register_script('fancybox', plugins_url( '/js/fancybox/jquery.fancybox-1.3.4.pack.js' , __FILE__ ), 'jquery');
		wp_register_script('jck_quickview', plugins_url( '/js/scripts.js' , __FILE__ ), 'jquery');
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('fancybox');
		wp_enqueue_script('jck_quickview');
		
		/** Styles **/
		wp_register_style( 'jck_quickview', plugins_url('css/style.css', __FILE__) );
        
        wp_enqueue_style( 'jck_quickview' );
	} 

/* 	=============================
   	Quickview Functions 
   	============================= */
   	
   	/** The Quickview Button **/
   	function jck_quickview_button() {
		echo '<span class="jck_quickview_button"><!-- Quickview --></span>';
	}
	
	/** The Quickview Output **/
	function jck_quickview() {
		global $post, $woocommerce;
		
		ob_start();
		$this->jck_quickview_content();
		$output = ob_get_contents();
		ob_end_clean();
		
		echo $output;
	}
	
	//** The Quickview Content **/
	function jck_quickview_content() {
		global $post, $product, $woocommerce;
		
		echo '<div class="hide">';
			echo '<div itemscope itemtype="http://schema.org/Product" id="product-'.get_the_ID().'" class="jck_quickview product">';
				
				echo '<div class="images">';
			
					if ( has_post_thumbnail() ) :
						echo get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) ) ;
					else :
						echo '<img src="'.woocommerce_placeholder_img_src().'" alt="Placeholder" class="attachment-shop_single wp-post-image" />';
					endif;
					
					$attachment_ids = $product->get_gallery_attachment_ids();
					
					if ( $attachment_ids ) {
						?>
						<div class="thumbnails"><?php
						
							if(has_post_thumbnail()) {
								array_unshift($attachment_ids, get_post_thumbnail_id($post->ID));
							}
					
							$loop = 0;
							$columns = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );
					
							foreach ( $attachment_ids as $attachment_id ) {
								
								$wrapClasses = array('quickviewThumbs-'.$columns.'col', 'jck_quickview_thumb');
					
								$classes = array('attachment-shop_thumbnail');
					
								if ( $loop == 0 || $loop % $columns == 0 )
									$wrapClasses[] = 'first';
									
								if( $loop == 0 ) {
									$wrapClasses[] = 'firstThumb';
								}
					
								if ( ( $loop + 1 ) % $columns == 0 )
									$wrapClasses[] = 'last';
								
								$image_class = esc_attr( implode( ' ', $classes ) );
								
								$lrgImg = wp_get_attachment_image_src($attachment_id, 'shop_single');
								
								echo '<a href="'.$lrgImg[0].'" class="'.esc_attr( implode( ' ', $wrapClasses ) ).'">'.wp_get_attachment_image( $attachment_id, apply_filters( 'single_product_small_thumbnail_size', 'shop_thumbnail' ), false, array('class' => $image_class) ).'</a>';
					
								$loop++;
							}
					
						?></div>
						<?php
					}
				
				echo '</div>';
		
				echo '<div class="summary entry-summary">';
		
					do_action( 'jck_quickview_single_product_summary' );
		
				echo '</div><!-- .summary -->';
			
			echo '</div><!-- #product-'.get_the_ID().' -->';
		echo '</div>';
	}  
  
/* 	=============================
   	PHP 4 Compatible Constructor 
   	============================= */
	
	function jck_woo_quickview() {
		$this->__construct();
	}

/* 	=============================
   	PHP 5 Constructor 
   	============================= */
   	
	function __construct() {		
		add_action( 'wp_enqueue_scripts', array( &$this, 'jck_sands') );
		add_action('woocommerce_before_shop_loop_item_title', array( &$this, 'jck_quickview_button'), 11);
		add_action( 'woocommerce_after_shop_loop_item', array( &$this, 'jck_quickview'), 6);
		
		/** Build jck_quickview_single_product_summary **/
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_title', 5 );
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_price', 10 );
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_meta', 40 );
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		add_action( 'jck_quickview_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );  
	}
  
} // End jck_woo_quickview Class

$jck_woo_quickview = new jck_woo_quickview; // Start an instance of the plugin class