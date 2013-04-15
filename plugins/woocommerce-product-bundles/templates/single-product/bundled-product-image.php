<?php
/**
 * Bundled Product Image
 */

global $woocommerce;

?>
<div class="images">

	<?php if ( has_post_thumbnail( $post_id ) ) : ?>

		<a itemprop="image" href="<?php echo wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ); ?>" class="zoom" rel="thumbnails" title="<?php echo get_the_title( get_post_thumbnail_id( $post_id ) ); ?>"><?php echo get_the_post_thumbnail( $post_id, apply_filters( 'bundled_product_large_thumbnail_size', 'shop_thumbnail' ), array(
			'title'	=> get_the_title( get_post_thumbnail_id( $post_id ) ),
		) ); ?></a>

	<?php else : ?>

		<img class="placeholder" src="<?php echo woocommerce_placeholder_img_src(); ?>" alt="Placeholder" />

	<?php endif; ?>

</div>
