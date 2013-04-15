<?php
/**
 * woocommerce-gpf-frontend.php
 *
 * @package default
 */



class woocommerce_gpf_frontend {



    protected $feed = null;
    protected $feed_format = '';



    /*
     * Constructor. Grab the settings, and add filters if we have stuff to do
     *
     * @access public
     */
    function __construct() {

        add_action ( 'template_redirect', array ( &$this, 'render_product_feed' ), 15 );

        if ( ! isset ( $_REQUEST['feed_format'] ) || $_REQUEST['feed_format'] == 'google' ) {

            
            $this->feed = new woocommerce_gpf_feed_google();
            $this->feed_format = 'google';

        } else if ( $_REQUEST['feed_format'] == 'bing' ) {

            $this->feed = new woocommerce_gpf_feed_bing();
            $this->feed_format = 'bing';

        }

        add_action ( 'woocommerce_gpf_elements', array ( &$this, 'general_elements' ), 10, 2 );

    }



    /**
     * Retrieve Post Thumbnail URL
     *
     * @param int     $post_id (optional) Optional. Post ID.
     * @param string  $size    (optional) Optional. Image size.  Defaults to 'post-thumbnail'.
     * @return string|bool Image src, or false if the post does not have a thumbnail.
     */
    protected function get_the_post_thumbnail_src( $post_id = null, $size = 'post-thumbnail' ) {

        $post_thumbnail_id = get_post_thumbnail_id( $post_id );

        if ( ! $post_thumbnail_id ) {
            return false;
        }

        list( $src ) = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );

        return $src;
    }



    /**
     * Helper function to retrieve a custom field from a product, compatible
     * both with WC < 2.0 and WC >= 2.0
     *
     * @param WC_Product $product the product object
     * @param string $field_name the field name, without a leading underscore
     *
     * @return mixed the value of the member named $field_name, or null
     */
    private function get_product_meta ( $product, $field_name ) {

      if ( version_compare( WOOCOMMERCE_VERSION, "2.0.0" ) >= 0 ) {

        // even in WC >= 2.0 product variations still use the product_custom_fields array apparently
        if ( $product->variation_id && isset( $product->product_custom_fields[ '_' . $field_name ][0] ) && $product->product_custom_fields[ '_' . $field_name ][0] !== '' ) {
          return $product->product_custom_fields[ '_' . $field_name ][0];
        }

        // use magic __get
        return $product->$field_name;

      } else {

        // use product custom fields array

        // variation support: return the value if it's defined at the variation level
        if ( isset( $product->variation_id ) && $product->variation_id ) {
          if ( ( $value = get_post_meta( $product->variation_id, '_' . $field_name, true ) ) !== '' ) return $value;
          // otherwise return the value from the parent
          return get_post_meta( $product->id, '_' . $field_name, true );
        }
        // regular product
        return isset( $product->product_custom_fields[ '_' . $field_name ][0] ) ? $product->product_custom_fields[ '_' . $field_name ][0] : null;
      }
      
    }



    /**
     * Render the product feed requests - calls the sub-classes according
     * to the feed required.
     *
     * @access public
     */
    function render_product_feed() {

        global $wpdb, $wp_query, $post;

        // Don't cache feed under WP Super-Cache
        define('DONOTCACHEPAGE', TRUE);

        // Cater for large stores
        $wpdb->hide_errors();
        @set_time_limit ( 0 );
        @ob_clean();
        
        // wp_suspend_cache_addition is buggy prior to 3.4
        if ( version_compare ( get_bloginfo('version'), '3.4', '>=' ) ) {
            wp_suspend_cache_addition ( true ) ;
        }

        $this->feed->render_header();

        // Query for the products
        $chunk_size = apply_filters ( 'woocommerce_gpf_chunk_size', 20 );

        $args['post_type'] = 'product';
        $args['numberposts'] = $chunk_size;
        $args['offset'] = 0;

        $products = get_posts ($args);

        while ( count ( $products ) ) {

            foreach ($products as $post) {

                setup_postdata($post);

                // 2.0 compat
                if ( function_exists( 'get_product' ) )
                    $woocommerce_product = get_product( $post );
                else
                    $woocommerce_product = new WC_Product( $post->ID );

                if ( $woocommerce_product->visibility == 'hidden' )
                    continue;

                // Check to see if the product has been excluded
                // Fortunately WooCommerce already fetches the data for us
                if ( $tmp_product_data = $this->get_product_meta ( $woocommerce_product, 'woocommerce_gpf_data' ) ) {
                    $tmp_product_data = maybe_unserialize ( $tmp_product_data );
                } else {
                    $tmp_product_data = array();
                }

                if ( isset ( $tmp_product_data['exclude_product'] ) )
                    continue;

                $feed_item = new stdClass();

                $feed_item->price_ex_tax = $woocommerce_product->get_price_excluding_tax();
                $feed_item->price_inc_tax = $woocommerce_product->get_price();

                if ( $woocommerce_product->has_child() ) {

                    $children = $woocommerce_product->get_children();

                    foreach ( $children as $child ) {
                    
                        $child_product = $woocommerce_product->get_child( $child );

                        $child_price = $child_product->get_price();

                        if (($feed_item->price_inc_tax == 0) && ($child_price > 0)) {

                            $feed_item->price_ex_tax = $child_product->get_price_excluding_tax();
                            $feed_item->price_inc_tax = $child_product->get_price();

                        } else if ( ($child_price > 0) && ($child_price < $feed_item->price_inc_tax) ) {

                                $feed_item->price_inc_tax = $child_product->get_price();
                                $feed_item->price_ex_tax = $child_product->get_price_excluding_tax();

                        }

                    }

                }

                // Get main item information
                $feed_item->ID = $post->ID;
                $feed_item->title = get_the_title();
                $feed_item->description = apply_filters ('the_content', get_the_content());
                $feed_item->purchase_link = get_permalink($post->ID);
                $feed_item->image_link = $this->get_the_post_thumbnail_src ( $post->ID, 'shop_large' );
                $feed_item->shipping_weight = apply_filters ( 'woocommerce_gpf_shipping_weight', $woocommerce_product->get_weight(), $post->ID );
                $feed_item->is_in_stock = $woocommerce_product->is_in_stock();
                $feed_item->sku = $woocommerce_product->get_sku();
                $feed_item->categories = wp_get_object_terms($post->ID, 'product_cat');

                // General, or feed-specific items
                $feed_item->additional_elements = apply_filters ( 'woocommerce_gpf_elements', array(), $post->ID );
                $feed_item->additional_elements = apply_filters ( 'woocommerce_gpf_elements_'.$this->feed_format, $feed_item->additional_elements, $post->ID );

                // Get other images
                $feed_item->additional_images = array();

                $main_thumbnail = get_post_meta ( $post->ID, '_thumbnail_id', true );
                $images = get_children( array ( 'post_parent' => $post->ID,
                                                'post_status' => 'inherit',
                                                'post_type' => 'attachment',
                                                'post_mime_type' => 'image',
                                                'exclude' => isset($main_thumbnail) ? $main_thumbnail : '',
                                                'order' => 'ASC',
                                                'orderby' => 'menu_order' ) );

                if ( is_array ( $images ) && count ( $images ) ) {

                    foreach ( $images as $image ) {

                        $full_image_src = wp_get_attachment_image_src( $image->ID, 'original' );
                        $feed_item->additional_images[] = $full_image_src[0];

                    }

                }

                $this->feed->render_item ( $feed_item );

            }

            $args['offset'] += $chunk_size;
            $products = get_posts ( $args );

        }

        $this->feed->render_footer();

    }



    /**
     * Add the "advanced" information to the field based on either the per-product settings, category settings, or store defaults
     *
     * @access public
     * @param array $elements The current elements for the product
     * @param int $product_id The product ID to retrieve information for
     * @return array The data for the product
     */
    function general_elements( $elements, $product_id ) {

        global $woocommerce_gpf_common;

        // Retrieve the info set against the product by this plugin.
        $product_values = $woocommerce_gpf_common->get_values_for_product ( $product_id, $this->feed_format );

        if ( ! empty ( $product_values ) ) {

            foreach ( $product_values as $key => $value ) {
                $elements[$key] = array ($value);
            }

        }

        return $elements;

    }



}

global $woocommerce_gpf_frontend;
$woocommerce_gpf_frontend = new woocommerce_gpf_frontend();