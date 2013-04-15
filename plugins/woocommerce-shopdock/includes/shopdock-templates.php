<?php
/**
 * Shopdock Templates Functions
 *
 * Override woocommerce templates
 *
 * @author    Themify
 * @category  Core
 * @package   Shopdock
 */

/**
 * Shopdock dock bar
 **/
if ( ! function_exists( 'shopdock_dock_bar' ) ) {
	function shopdock_dock_bar() {
		global $woocommerce;

		$placeholder_width = 65;
		$placeholder_height = 65;
?>

    <div id="addon-shopdock" class="shopdock_cart" <?php if ( sizeof( $woocommerce->cart->get_cart() ) == 0 ) : ?>style="display:none;"<?php endif; ?>>
    <div class="shopdock-inner pagewidth">

      <?php
		$qty = 0;
		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) { ?>

      <div id="cart-slider">
        <ul class="cart-slides">
      <?php
			$carts = array_reverse($woocommerce->cart->get_cart());
			foreach ($carts as $cart_item_key => $values) {
				$_product = $values['data'];
				if ($_product->exists() && $values['quantity']>0) {

					// store qty
					$qty += $values['quantity'];

?>
            <li>
              <div class="product">
                <div class="product-imagewrap">
                  <?php if ($values['quantity'] > 1): ?>
                    <div class="quantity-tip"><?php echo $values['quantity']; ?></div>
                  <?php endif; ?>
                  <a href="<?php echo esc_url( $woocommerce->cart->get_remove_url($cart_item_key) ); ?>" class="remove-item remove-item-js">Remove</a>
                    <a href="<?php echo get_permalink($values['product_id']); ?>" title="<?php echo get_the_title($values['product_id']); ?>">
                      <?php if (has_post_thumbnail($values['product_id'])) { ?>

                        	<?php echo get_the_post_thumbnail( $values['product_id'], 'cart_thumbnail' ); ?>

                      <?php } else {
?>
                      		<img src="<?php echo woocommerce_placeholder_img_src(); ?>" alt="Placeholder" width="<?php echo $placeholder_width; ?>" height="<?php echo $placeholder_height; ?>" />

                      <?php } ?>
                    </a>
                </div>
              </div>
            </li>

            <?php
				}
			}
?>
        </ul>
      </div>
        <?php
		}

		do_action( 'woocommerce_cart_contents' );
?>

      <div class="checkout-wrap clearfix">
        <p class="checkout-button">
          <button type="submit" class="button checkout" onclick="location.href='<?php echo esc_url( $woocommerce->cart->get_checkout_url() ); ?>'"><?php _e('Checkout', 'wc_shopdock')?></button>
        </p>
        <p class="cart-total">
          <span id="cart-loader" class="hide"></span>
          <span class="total-item"><?php echo sprintf(__('%d items', 'wc_shopdock'), $qty); ?></span>
          <?php
		if (sizeof($woocommerce->cart->get_cart()) > 0) {
			echo '('.$woocommerce->cart->get_cart_total().')';
		}
?>
        </p>
      </div>
      <!-- /.cart-checkout -->

    </div>
    <!-- /.pagewidth -->
  </div>
  <!-- /#shopdock -->


  <?php
	} // end shopdock_bar
}

/**
 * Hook shopdock template loop product thumbnail
 **/
if (!function_exists('shopdock_template_loop_product_thumbnail')) {
	function shopdock_template_loop_product_thumbnail() {
		echo shopdock_get_product_thumbnail();
	}
}

/**
 * Shopdock Product Thumbnail function
 * addition: wrapping the image
 **/
if (!function_exists('shopdock_get_product_thumbnail')) {
	function shopdock_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0 ) {
		global $post, $woocommerce;

		if ( ! $placeholder_width )
			$placeholder_width = $woocommerce->get_image_size( $size );
		if ( ! $placeholder_height )
			$placeholder_height = $woocommerce->get_image_size( $size );

		$html = '<div class="product-image">';
		$html .= '<a href="'.get_permalink().'">';

		if ( has_post_thumbnail() ) {
			$html .= get_the_post_thumbnail($post->ID, $size);
		}
		else {
			$html .= '<img src="'. woocommerce_placeholder_img_src().'" alt="Placeholder" width="' . $placeholder_width . '" height="' . $placeholder_height . '" />';
		}

		$html .= '</a>';
		$html .= '</div>';

		return $html;
	}
}

/** Loop ******************************************************************/

/**
 * Products Loop add to cart
 **/
if ( ! function_exists( 'shopdock_template_loop_add_to_cart' ) ) {
	function shopdock_template_loop_add_to_cart() {
		woocommerce_get_template( 'loop/add-to-cart.php', '', 'woocommerce-shopdock', SHOPDOCK_DIR . '/templates/' );
	}
}