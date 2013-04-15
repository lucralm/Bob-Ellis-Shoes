<?php
/**
 * woocommerce-gpf-feed-google.php
 *
 * @package default
 */
class woocommerce_gpf_feed_bing extends woocommerce_gpf_feed {

    private $US_feed = false;

    /**
     * Constructor. Grab the settings, and add filters if we have stuff to do
     *
     * @access public
     */
    function __construct() {

        parent::__construct();
        $this->store_info->feed_url = add_query_arg ( 'feed_format', 'bing', $this->store_info->feed_url_base);

    }


    /**
     * Render the feed header information
     *
     * @access public
     */
    function render_header() {

        if ( isset ( $_REQUEST['feeddownload'] ) ) {
            header('Content-Disposition: attachment; filename="E-Commerce_Product_List.csv"');
        } else {
            header('Content-Disposition: inline; filename="E-Commerce_Product_List.csv"');
        }
//        header('Content-Type: text/plain');

        // Mandatory fields
        echo 'MerchantProductID,Title,ProductURL,Price,Description,ImageURL,SKU,MerchantCategory,ShippingWeight';

        // Optional fields
        if ( isset ( $this->settings['product_fields']['brand'] ) ) {
            echo ',Brand';
        }
        if ( isset ( $this->settings['product_fields']['mpn'] ) ) {
            echo ',MPN';
        }
        if ( isset ( $this->settings['product_fields']['upc'] ) ) {
            echo ',UPC';
        }
        if ( isset ( $this->settings['product_fields']['isbn'] ) ) {
            echo ',ISBN';
        }
        if ( isset ( $this->settings['product_fields']['availability'] ) ) {
            echo ',Availability';
        }
        if ( isset ( $this->settings['product_fields']['bing_category'] ) ) {
            echo ',B_Category';
        }
        if ( isset ( $this->settings['product_fields']['condition'] ) ) {
            echo ',Condition';
        }
        echo "\n";

    }



    /**
     * Helper function used to output a value in a warnings-safe way
     *
     * @access public
     * @param  object $feed_item The information about the item
     * @param  string $key       The particular attribute to output
     */
    private function output_element ( &$feed_item, $key ) {

        if ( isset ( $this->settings['product_fields'][$key] ) ) {

            if ( isset ( $feed_item->additional_elements[$key] ) ) {

                foreach ( $feed_item->additional_elements[$key] as $data ) {
                    echo ",".$this->csvescape($data);
                }

            } else {

                echo ",";

            }

        }

    }



    /**
     * Generate the output for an individual item
     *
     * @access public
     * @param  object $feed_item The information about the item
     */
    function render_item($feed_item) {

        if ( empty ( $feed_item->price_inc_tax ) )
            return;

//        print_r($feed_item); return;

        // MerchantProductID
        echo "woocommerce_gpf_".$feed_item->ID.",";

        // Title
        echo $this->csvescape(substr($feed_item->title,0,255)).",";

        // ProductURL
        echo $this->csvescape($feed_item->purchase_link).",";

        // Price
        $price = number_format ( $feed_item->price_ex_tax, 2, '.', '' );
        echo $this->csvescape($price).",";

        // Description
        echo $this->csvescape(substr($feed_item->description,0,5000)).",";

        // ImageURL
        if ( ! empty ( $feed_item->image_link ) ) {
            echo $this->csvescape($feed_item->image_link).",";
        } else {
            echo ',';
        }

        // SKU
        if ( ! empty ( $feed_item->sku ) ) {
            echo $this->csvescape($feed_item->sku).",";
        } else {
            echo ',';
        }

        // MerchantCategory
        if ( count($feed_item->categories) ) {

            // Get the hierarchy of the first category
            $category = $feed_item->categories[0];
            $hierarchy = get_ancestors ( $category->term_id, 'product_cat' );
            $hierarchy = array_reverse($hierarchy);
            $hierarchy[] =$category->term_id;

            foreach ( $hierarchy as $cat ) {
                $term = get_term ( $cat, 'product_cat' );
                $merchant_categories[] = $term->name;
            }

            echo $this->csvescape(implode(' > ', $merchant_categories)).",";

        } else {

            echo ',';

        }

        // ShippingWeight - NOTE NO TRAILING COMMA
        if ( $feed_item->shipping_weight ) {
            if ( $this->store_info->weight_units == 'lbs') {
                echo $this->csvescape($feed_item->shipping_weight);
            } else {
                // Convert and output
                $weight = woocommerce_get_weight ( $feed_item->shipping_weight, 'lbs' );
                echo $this->csvescape($weight);
            }
        }

        $this->output_element( $feed_item, 'brand');
        $this->output_element( $feed_item, 'mpn');
        $this->output_element( $feed_item, 'upc');
        $this->output_element( $feed_item, 'isbn');

        if ( isset ( $this->settings['product_fields']['availability'] ) ) {

            if ( ! $feed_item->is_in_stock ) {

                echo ',Out of Stock';

            } elseif ( isset( $feed_item->additional_elements['availability'][0] ) ) {

                //  In Stock; Out of Stock; Pre-Order; Back-Order
                switch ( $feed_item->additional_elements['availability'][0] ) {
                    case 'in stock':
                        echo ',In Stock';
                        break;
                    case 'preorder':
                        echo ',Pre-Order';
                        break;
                    case 'available for order':
                        echo ',Back-Order';
                        break;
                }

            }

        }

        $this->output_element( $feed_item, 'bing_category');

        if ( isset ( $this->settings['product_fields']['condition'] ) ) {

            if ( isset ( $feed_item->additional_elements['condition'][0] ) ) {

                switch ( $feed_item->additional_elements['condition'][0] ) {

                    case 'new':
                        echo ',';
                        break;
                    case 'refurbished':
                        echo ','.$this->csvescape('Refurbished');
                        break;
                    case 'used':
                        echo ','.$this->csvescape('Used');
                        break;

                }

            } else {

                echo ',';
            }

        }

        echo "\n";

    }



    /**
     * Output the feed footer
     *
     * @access public
     */
    function render_footer() {

        exit();

    }



}
