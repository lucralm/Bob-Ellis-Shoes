<?php
/**
 * woocommerce-gpf-feed-google.php
 *
 * @package default
 */


class woocommerce_gpf_feed_google extends woocommerce_gpf_feed {



    private $US_feed = false;



    /**
     * Constructor. Grab the settings, and add filters if we have stuff to do
     *
     * @access public
     */
    function __construct() {

        parent::__construct();
        $this->store_info->feed_url = add_query_arg ( 'feed_format', 'google', $this->store_info->feed_url_base);

        if ( !empty ( $this->store_info->base_country ) && substr ( $this->store_info->base_country, 0, 2 ) == 'US' ) {
            $this->US_feed = true;
        } else {
            $this->US_feed = false;
        }


    }



    /**
     * Render the feed header information
     *
     * @access public
     */
    function render_header() {

        header("Content-Type: application/xml; charset=UTF-8");
        if ( isset ( $_REQUEST['feeddownload'] ) ) {
            header('Content-Disposition: attachment; filename="E-Commerce_Product_List.xml"');
        } else {
            header('Content-Disposition: inline; filename="E-Commerce_Product_List.xml"');
        }

        // Core feed information
        echo "<?xml version='1.0' encoding='UTF-8' ?>\n";
        echo "<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:g='http://base.google.com/ns/1.0'>\n";
        echo "  <channel>\n";
        echo "    <title><![CDATA[".$this->store_info->blog_name." Products]]></title>\n";
        echo "    <link>".$this->store_info->site_url."</link>\n";
        echo "    <description>This is the WooCommerce Product List RSS feed</description>\n";
        echo "    <generator>WooCommerce Google Product Feed Plugin (http://plugins.leewillis.co.uk/store/plugins/woocommerce-google-product-feed/)</generator>\n";
        echo "    <atom:link href='".htmlspecialchars($this->store_info->feed_url)."' rel='self' type='application/rss+xml' />\n";

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

        echo "    <item>\n";
        echo "      <title><![CDATA[".$feed_item->title."]]></title>\n";
        echo "      <link>".$feed_item->purchase_link."</link>\n";
        echo "      <guid>woocommerce_gpf_".$feed_item->ID."</guid>\n";

        // Google limit the description to 10,000 characters
        // This is basically a hack since we're avoiding using the PHP DOM functions
        // so we don't have to hold the whole doc in memory
        $product_description = $feed_item->description;
        $product_description = str_replace(']]>', ']]]]><![CDATA[>', $product_description);
        echo "      <description><![CDATA[".substr($product_description,0,10000)."]]></description>\n";

        if ( ! empty ( $feed_item->image_link ) ) {
            echo "      <g:image_link>".$feed_item->image_link."</g:image_link>\n";
        }

        if ( $this->US_feed ) {
            // US prices have to be submitted excluding tax
            $price = number_format ( $feed_item->price_ex_tax, 2, '.', '' );
        } else {
            // Non-US prices have to be submitted including tax
            $price = number_format ( $feed_item->price_inc_tax, 2, '.', '' );
        }
        echo "      <g:price>".$price." ".$this->store_info->currency."</g:price>\n";

        $cnt = 0;
        foreach ( $feed_item->additional_images as $image_url ) {

            // Google limit the number of additional images to 10
            if ( $cnt == 10 )
                break;

            echo "      <g:additional_image_link><![CDATA[".$image_url."]]></g:additional_image_link>\n";
            $cnt++;
        }

        $done_condition = FALSE;
        $done_weight = FALSE;

        if ( count( $feed_item->additional_elements ) ) {

            foreach ( $feed_item->additional_elements as $element_name => $element_values ) {

                foreach ( $element_values as $element_value ) {

                    // Special case for stock - only send a value if the product is in stock
                    if ( 'availability' == $element_name ) {
                        if ( ! $feed_item->is_in_stock ) {
                            $element_value = 'out of stock';
                        }
                    }

                    echo "      <g:".$element_name.">";
                    echo "<![CDATA[".$element_value."]]>";
                    echo "</g:".$element_name.">\n";

                }

                if ($element_name == 'shipping_weight')
                    $done_weight = TRUE;

                if ($element_name == 'condition')
                    $done_condition = TRUE;

            }

        }

        if (!$done_condition)
            echo "      <g:condition>new</g:condition>\n";

        if ( ! $done_weight ) {

            $weight = apply_filters ( 'woocommerce_gpf_shipping_weight', $feed_item->shipping_weight, $feed_item->ID );

            if ( $this->store_info->weight_units == 'lbs' ) {
                $weight_units = 'lb';
            } else {
                $weight_units = $this->store_info->weight_units;
            }

            if ( $weight && is_numeric ( $weight ) && $weight > 0 ) {
                echo "      <g:shipping_weight>$weight $weight_units</g:shipping_weight>";
            }
        }

        echo "    </item>\n";

    }



    /**
     * Output the feed footer
     *
     * @access public
     */
    function render_footer() {

        echo "  </channel>\n";
        echo "</rss>";

        exit();

    }



}
