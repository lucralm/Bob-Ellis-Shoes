<?php
/**
 * Bundled Product Short Description
 */

$post = get_post( $product->id );

if ( ! $post->post_excerpt && $custom_description === '' ) return;
?>
<div class="bundled_product_excerpt product_excerpt">
	<?php echo ( ( $custom_description !== '' ) ? $custom_description : __( $post->post_excerpt ) ); ?>
</div>
