<?php

class woocommerce_gpf_common {



    private $settings = Array();
    private $category_cache = Array();
    public $product_fields = Array();



    /**
     * Constructor - set up the available product fields
     *
     * @access public
     */
    function __construct() {

        $this->settings = get_option ( 'woocommerce_gpf_config' );
        $this->product_fields = array (
                                    'availability' => array (
                                        'desc' => __( 'Availability', 'woocommerce_gpf' ),
                                        'full_desc' => __( 'Availability status of items', 'woocommerce_gpf' ),
                                        'callback' => 'render_availability',
                                        'can_default' => true,
                                        'feed_types' => array ( 'google', 'bing' ),
                                         ),

                                    'condition' => array (
                                        'desc' => __( 'Condition', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( 'Condition or state of items', 'woocommerce_gpf' ),
                                        'callback' => 'render_condition' ,
                                        'can_default' => true,
                                        'feed_types' => array ( 'google', 'bing' ),
                                         ),

                                    'brand' => array (
                                        'desc' => __( 'Brand', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( 'Brand of the items', 'woocommerce_gpf' ),
                                        'can_default' => TRUE,
                                        'feed_types' => array ( 'google', 'bing' ),
                                        ),

                                    'mpn' => array (
                                        'desc' => __( 'Manufacturer Part Number (MPN)', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "This code uniquely identifies the product to its manufacturer", 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'google', 'bing' ),
                                        ),

                                    'product_type' => array (
                                        'desc' => __( 'Product Type', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( 'Your category of the items', 'woocommerce_gpf' ),
                                        'callback' => 'render_product_type',
                                        'can_default' => true,
                                        'feed_types' => array ( 'google' ),
                                        ),

                                    'google_product_category' => array (
                                        'desc' => __( 'Google Product Category', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Google's category of the item", 'woocommerce_gpf' ),
                                        'callback' => 'render_product_type' ,
                                        'can_default' => true,
                                        'feed_types' => array ( 'google' ),
                                        ),

                                    'gtin' => array (
                                        'desc' => __( 'Global Trade Item Number (GTIN)', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( 'Global Trade Item Numbers (GTINs) for your items. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)', 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'google' ),
                                        ),

                                    'gender' => array (
                                        'desc' => __( 'Gender', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Target gender for the item", 'woocommerce_gpf' ),
                                        'callback' => 'render_gender' ,
                                        'can_default' => true,
                                        'feed_types' => array ( 'google' ),
                                        ),

                                    'age_group' => array (
                                        'desc' => __( 'Age Group', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Target age group for the item", 'woocommerce_gpf' ),
                                        'callback' => 'render_age_group' ,
                                        'can_default' => true,
                                        'feed_types' => array ( 'google' ),
                                        ),

                                    'color' => array (
                                        'desc' => __( 'Colour', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Items' Colour", 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'google' ),
                                         ),

                                    'size' => array (
                                        'desc' => __( 'Size', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Size of the items", 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'google' ),
                                         ),

                                    'bing_category' => array (
                                        'desc' => __( 'Bing Category', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Bing's category of the item", 'woocommerce_gpf' ),
                                        'callback' => 'render_b_category' ,
                                        'can_default' => true,
                                        'feed_types' => array ( 'bing' ),
                                        ),

                                    'upc' => array (
                                        'desc' => __( 'Universal Product Code', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "Universal Product Code. Only 8 and 12 digit codes are supported.", 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'bing' ),
                                        ),

                                    'isbn' => array (
                                        'desc' => __( 'International Standard Book Number', 'woocommerce_gpf' ),
                                        'full_desc' => __ ( "10 or 13 digit ISBNs. The ISBN is matched to other offers with the identical ISBN - significantly improving your customer's ability to locate your product. Use for books, CDs, DVD.", 'woocommerce_gpf' ),
                                        'feed_types' => array ( 'bing' ),
                                        ),

                                    );

    }



    /**
     * Helper function to remove blank array elements
     *
     * @access public
     * @param array $array The array of elements to filter
     * @return array The array with blank elements removed
     */
    private function remove_blanks ( $array ) {

        if ( empty ( $array ) || ! is_array ( $array ) ) {
            return $array;
        }

        foreach ( array_keys ( $array ) as $key ) {

            if ( empty ( $array[$key] ) ) {
                unset ( $array[$key] );
            }
        }

        return $array;

    }



    /**
     * Helper function to remove items not needed in this feed type
     *
     * @access public
     * @param array $array The list of fields to be filtered
     * @param string $feed_format The feed format that should have its fields maintained
     * @return array The list of fields filtered to only contain elements that apply to the selectedd $feed_format
     */
    private function remove_other_feeds ( $array, $feed_format ) {

        if ( empty ( $array ) || ! is_array ( $array ) ) {
            return $array;
        }

        foreach ( array_keys ( $array ) as $key ) {

            if ( ! in_array( $feed_format, $this->product_fields[$key]['feed_types'] ) ) {
                unset ( $array[$key] );
            }

        }

        return $array;

    }



    /**
     * Retrieve the values that should be output for a particular product
     * Takes into account store defaults, category defaults, and per-product
     * settings
     * 
     * @access public
     * @param  int  $product_id       The ID of the product to retrieve info for
     * @param  string  $feed_format   The feed format being generated
     * @param  boolean $defaults_only Whether to retrieve the
                            *         store/category defaults only
     * @return array                  The values for the product
     */
    public function get_values_for_product ( $product_id = null, $feed_format = 'all', $defaults_only = false ) {

        if ( ! $product_id )
            return false;

        // Get Store defaults
        $settings = $this->remove_blanks ( $this->settings['product_defaults'] );

        if ( $feed_format != 'all' ) {
            $settings = $this->remove_other_feeds ( $settings, $feed_format );
        }

        // Merge category settings
        $categories = wp_get_object_terms ( $product_id, 'product_cat', array ( 'fields'=>'ids' ) );

        foreach ( $categories as $category_id ) {

            $category_settings = $this->get_values_for_category ( $category_id );
            $category_settings = $this->remove_blanks ( $category_settings );
            if ( $feed_format != 'all' ) {
               $category_settings = $this->remove_other_feeds ( $category_settings, $feed_format );
            }

            if ( $category_settings )
                $settings = array_merge ( $settings, $category_settings );

        }

        if ( $defaults_only )
            return $settings;

        // Merge product settings
        $product_settings = get_post_meta ( $product_id, '_woocommerce_gpf_data', true );
        if ( $product_settings ) {
            $product_settings = $this->remove_blanks ( $product_settings );
            $settings = array_merge ( $settings, $product_settings );
        }

        return $settings;

    }



    /**
     * Retrieve category defaults for a specific category
     *
     * @access public
     * @param  int $category_id The category ID to retrieve information for
     * @return array            The category data
     */
    private function get_values_for_category ( $category_id ) {

        if ( ! $category_id )
            return false;

        if ( isset ( $this->category_cache[$category_id] ) )
            return $this->category_cache[$category_id];

        $values = get_metadata( 'woocommerce_term', $category_id, '_woocommerce_gpf_data', true );
        $this->category_cache[$category_id] = &$values;

        return $this->category_cache[$category_id];

    }

}

global $woocommerce_gpf_common;
$woocommerce_gpf_common = new woocommerce_gpf_common();
