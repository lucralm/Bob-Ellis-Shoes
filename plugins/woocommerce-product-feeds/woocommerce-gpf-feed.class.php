<?php
/**
 * woocommerce-gpf-frontend.php
 *
 * @package default
 */


class woocommerce_gpf_feed {



    protected $settings = array();
    protected $store_info = null;



    /**
     * Constructor.
     * Grab the settings, and set up the store info object
     *
     * @access public
     */
    function __construct() {

        $this->settings = get_option ( 'woocommerce_gpf_config' );

        $this->store_info = new stdClass();
        $this->store_info->site_url = home_url('/');
        $this->store_info->feed_url_base = home_url("/index.php?action=woocommerce_gpf");
        $this->store_info->blog_name = get_option('blogname');
        $this->store_info->currency = get_option ( 'woocommerce_currency' );
        $this->store_info->weight_units = get_option ( 'woocommerce_weight_unit' );
        $this->store_info->base_country = get_option ( 'woocommerce_base_country' );

    }



    /**
     * Helper function used to output an escaped value for use in a CSV
     *
     * @access protected
     * @param  string $string The string to be escaped
     * @return string         The escaped string
     */
    protected function csvescape($string) {

        $doneescape = false;
        if (stristr($string,'"')) {
            $string = str_replace('"','""',$string);
            $string = "\"$string\"";
            $doneescape = true;
        }

        $string = str_replace("\n",' ',$string);
        $string = str_replace("\r",' ',$string);

        if (stristr($string,apply_filters('ses_wpscd_csv_separator', ',')) && !$doneescape) {
            $string = "\"$string\"";
        }

        return apply_filters('ses_wpscd_csv_escape_string', $string);

    }



    /**
     * Override this to generate output at the start of the file
     * Opening XML declarations, CSV header rows etc.
     *
     * @access public
     */
    function render_header() {

    }



    /**
     * Override this to generate the output for an individual item
     *
     * @access public
     * @param $item object Item object
     */
    function render_item($item) {

    }



    /**
     * Override this to generate output at the start of the file
     * Opening XML declarations, CSV header rows etc.
     *
     * @access public
     * @param  $store_info object Object containing information about the store
     */
    function render_footer() {

    }


}
