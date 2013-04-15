<?php

class woocommerce_gpf_admin {



    private $settings = array();
    private $product_fields = array ();



    /**
     * Constructor - set up the relevant hooks actions, and load the 
     * settings
     *
     * @access public
     */
    function __construct() {

        global $woocommerce_gpf_common;

        $this->settings = get_option ( 'woocommerce_gpf_config' );
        $this->product_fields = $woocommerce_gpf_common->product_fields;

        add_action ( 'init', array ( &$this, 'init' ) );
        add_action ( 'admin_init', array ( &$this, 'admin_init' ), 11 );
        add_action ( 'admin_print_styles', array ( &$this, 'enqueue_styles' ) );
        add_action ( 'admin_print_scripts', array ( &$this, 'enqueue_scripts' ) );

        // Extend Category Admin Page
        add_action( 'product_cat_add_form_fields', array ( &$this, 'category_meta_box' ), 99, 2 ); // After left-col
        add_action( 'product_cat_edit_form_fields', array ( &$this, 'category_meta_box' ), 99, 2 ); // After left-col
        add_action( 'created_product_cat', array ( &$this, 'save_category' ), 15 , 2 ); //After created
        add_action( 'edited_product_cat', array ( &$this, 'save_category' ), 15 , 2 ); //After saved

        add_filter ( 'woocommerce_settings_tabs_array', array ( &$this, 'add_woocommerce_settings_tab' ) );
        add_action ( 'woocommerce_settings_tabs_gpf', array ( &$this, 'config_page' ) );
        add_action ( 'woocommerce_update_options_gpf', array ( &$this, 'save_settings' ) );

    }



    /**
     * Handle ajax callbacks for Google andd bing category lookups
     * Set up localisation
     *
     * @access public
     */
    function init() {

        // Handle ajax requests for the google taxonomy search
        if ( isset ( $_GET['woocommerce_gpf_search'] ) ) {
            $this->ajax_handler($_GET['query']);
            exit();
        }

        // Handle ajax requests for the bing taxonomy search
        if ( isset ( $_GET['woocommerce_gpf_bing_search'] ) ) {
            $this->bing_ajax_handler($_GET['query']);
            exit();
        }

        $this->product_fields = apply_filters ( 'woocommerce_wpf_product_fields', $this->product_fields );

        $locale = apply_filters('plugin_locale', get_locale(), "woocommerce_gpf");
        load_textdomain('woocommerce_gpf', WP_LANG_DIR.'/woocommerce-google-product-feed/woocommerce_gpf-'.$locale.'.mo');
        load_plugin_textdomain ( 'woocommerce_gpf', false, basename( dirname( __FILE__ ) ) . '/languages/' );

    }




    /**
     * Extend Product Edit Page
     *
     * @access public
     */
    function admin_init() {

        add_action ( 'save_post', array ( &$this, 'save_product' ) );

        if ( isset ( $this->settings['product_fields'] ) && count( $this->settings['product_fields'] ) ) {
            add_meta_box ( 'woocommerce-gpf-product-fields', __('Product Feed Information', 'woocommerce_gpf'), array(&$this, 'product_meta_box'), 'product', 'advanced', 'high' ) ;
        }

    }



    /**
     * Handle ajax requests for the google taxonomy search. Returns a JSON encoded list of matches
     * and terminates execution.
     *
     * @access public
     * @param  string $query The user input to search for
     * @return json          
     */
    function ajax_handler( $query ) {

        global $wpdb, $table_prefix;

        // Make sure the taxonomy is up to date
        $taxonomy = $this->refresh_google_taxonomy();

        $sql = "SELECT taxonomy_term FROM ${table_prefix}woocommerce_gpf_google_taxonomy WHERE search_term LIKE %s";
        $results = $wpdb->get_results ( $wpdb->prepare ( $sql, "%".strtolower($query)."%" ) );

        $suggestions = array ();

        foreach ( $results as $match ) {
            $suggestions[] = $match->taxonomy_term;
        }

        $results = array ( 'query' => $query, 'suggestions' => $suggestions, 'data' => $suggestions );
        echo json_encode( $results );
        exit();
    }



    /**
     * Handle ajax requests for the Bing taxonomy search. eturns a JSON encoded list of matches
     * and terminates execution.
     *
     * @access public
     * @param  string $query The user input to search for
     */
    function bing_ajax_handler( $query ) {

        $taxonomy = Array ( 'Arts & Crafts', 'Baby & Nursery', 'Beauty & Fragrance', 'Books & Magazines', 'Cameras & Optics',
                            'Car & Garage', 'Clothing & Shoes', 'Collectibles & Memorabilia', 'Computing', 'Electronics'.
                            'Flowers', 'Gourmet Food & Chocolate', 'Health & Wellness', 'Home Furnishings',
                            'Jewelry & Watches', 'Kitchen & Housewares', 'Lawn & Garden', 'Miscellaneous', 'Movies',
                            'Music', 'Musical Instruments', 'Office Products', 'Pet Supplies', 'Software',
                            'Sports & Outdoors', 'Tools & Hardware', 'Toys', 'Travel', 'Vehicles', 'Video Games');

        $suggestions = array ();

        foreach ( $taxonomy as $b_cat ) {
            if ( stristr($b_cat, $query) ) {
                $suggestions[] = $b_cat;
            }
        }

        $results = array ( 'query' => $query, 'suggestions' => $suggestions, 'data' => $suggestions );
        echo json_encode( $results );
        exit();
    }



    /*
     * Enqueue CSS needed for product pages
     *
     * @access public
     */
    function enqueue_styles() {
        wp_enqueue_style ( 'woocommerce_gpf_admin', plugins_url(basename(dirname(__FILE__))) . '/css/woocommerce-gpf.css' );
        wp_enqueue_style ( 'wooautocomplete', plugins_url(basename(dirname(__FILE__))) . '/js/jquery.autocomplete.css' );
    }



    /**
     * Enqueue javascript for product_type / google_product_category selector
     *
     * @access public
     */
    function enqueue_scripts() {
        wp_enqueue_script ( 'wooautocomplete', plugins_url(basename(dirname(__FILE__))) . '/js/jquery.autocomplete.js', array ('jquery', 'jquery-ui-core') );
    }



    /**
     * Render the form to allow users to set defaults per-category
     *
     * @access public
     * @param unknown $termortax
     * @param unknown $taxonomy  (optional)
     */
    function category_meta_box( $termortax, $taxonomy = null ) {

        // So we can use the same function for add and edit forms
        if ( $taxonomy === null ) {
            $taxonomy = $termortax;
            $term = null;
        } else {
            $term = $termortax;
        }
?>
        <tr>
          <td colspan="2">
            <h3><?php _e('Product Feed Information', 'woocommerce_gpf'); ?></h3>
            <p><?php _e ( 'Only enter values here if you want to over-ride the store defaults for products in this category. You can still override the values here against individual products if you want to.', 'woocommerce_gpf'); ?></p>
          </td>
        </tr>
<?php

        if ( $term )
            $current_data = get_metadata ( 'woocommerce_term', $term->term_id, '_woocommerce_gpf_data', true);
        else
            $current_data = array();

        foreach ( $this->product_fields as $key => $fieldinfo ) {

            if ( ! isset ( $this->{"settings"}['product_fields'][$key] ) )
                continue;

            $config = &$this->settings['product_fields'][$key];

            echo '<tr><th>';
            echo '<label for="_woocommerce_gpf_data['.$key.']">'.esc_html($fieldinfo['desc'])."<br/>";
            if ( isset ( $fieldinfo['can_default'] ) && ! empty ( $this->settings['product_defaults'][$key] ) ) {
                echo ' <span class="woocommerce_gpf_default_label">('.__( 'Default: ', 'woocommerce_gpf' ).esc_html($this->settings['product_defaults'][$key]).')</span>';
            }
            echo '</label></th><td>';

            if ( ! isset ( $fieldinfo['callback'] ) || ! is_callable( array ( &$this, $fieldinfo['callback'] ) ) ) {

                echo '<input type="textbox" name="_woocommerce_gpf_data['.$key.']" ';
                if ( !empty ( $current_data[$key] ) )
                    echo ' value="'.esc_attr($current_data[$key]).'"';
                echo '>';

            } else {

                if ( isset ( $current_data[$key] ) ) {
                    call_user_func( array ( &$this, $fieldinfo['callback']), $key, $current_data[$key] );
                } else {
                    call_user_func( array ( &$this, $fieldinfo['callback']), $key );
                }

            }
            echo '</td></tr>';

        }

    }



    /**
     * Store the per-category defaults
     *
     * @access public
     * @param unknown $term_id
     */
    function save_category( $term_id ) {

        foreach ( $_POST['_woocommerce_gpf_data'] as $key => $value ) {
            $_POST['_woocommerce_gpf_data'][$key] = stripslashes($value);
        }

        if ( isset ( $_POST['_woocommerce_gpf_data'] ) ) {
            update_metadata('woocommerce_term', $term_id, '_woocommerce_gpf_data', $_POST['_woocommerce_gpf_data']);
        }

    }



    /**
     * Meta box on product pages for setting per-product information
     *
     * @access public
     */
    function product_meta_box() {

        global $post, $woocommerce_gpf_common;

        $current_data = get_post_meta ( $post->ID, '_woocommerce_gpf_data', true );
        $product_defaults = $woocommerce_gpf_common->get_values_for_product ( $post->ID, 'all', true );

        echo '<p>';
        echo '<input type="checkbox" id="woocommerce_gpf_excluded" name="_woocommerce_gpf_data[exclude_product]" '. ((isset($current_data['exclude_product'])) ? checked ( $current_data['exclude_product'], 'on', false ) : '') .'>';
        echo '<label for="_woocommerce_gpf_data[exclude_product]">'.__("Hide this product from the feed", 'woocommerce_gpf');
        echo '</p>';

        echo '<div id="woocommerce_gpf_options">';
        foreach ( $this->product_fields as $key => $fieldinfo ) {

            if ( ! isset ( $this->{"settings"}['product_fields'][$key] ) )
                continue;

            $config = &$this->settings['product_fields'][$key];

            echo '<p><label for="_woocommerce_gpf_data['.$key.']">'.esc_html($fieldinfo['desc']);
            if ( isset ( $fieldinfo['can_default'] ) && ! empty ( $product_defaults[$key] ) ) {
                echo ' <span class="woocommerce_gpf_default_label">('.__( 'Default: ', 'woocommerce_gpf' ).esc_html($product_defaults[$key]).')</span>';
            }
            echo '</label><br/>';

            if ( ! isset ( $fieldinfo['callback'] ) || ! is_callable( array( &$this, $fieldinfo['callback'] ) ) ) {

                echo '<input type="textbox" name="_woocommerce_gpf_data['.$key.']" ';
                if ( !empty ( $current_data[$key] ) )
                    echo ' value="'.esc_attr($current_data[$key]).'"';
                echo '>';

            } else {

                if ( isset ( $current_data[$key] ) ) {
                    call_user_func( array ( &$this, $fieldinfo['callback']), $key, $current_data[$key] );
                } else {
                    call_user_func( array ( &$this, $fieldinfo['callback']), $key );
                }

            }
            echo '</p>';

        }
        echo '</div>';
        ?>
        <script type="text/javascript">
            jQuery('#woocommerce_gpf_excluded').change(function() {
                    if (jQuery("#woocommerce_gpf_excluded").is(':checked')) {
                        jQuery('#woocommerce_gpf_options').slideUp('400','swing');
                    } else {
                        jQuery('#woocommerce_gpf_options').slideDown('400','swing');
                    }
                }
            );
            jQuery('#woocommerce_gpf_excluded').change();
        </script>
        <?php
    }



    /**
     * Store the per-product meta information. Called from wpsc_edit_product which has already checked we're not in an AUTOSAVE
     *
     * @access public
     * @param unknown $product_id
     */
    function save_product( $product_id ) {

        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        if ( empty ( $_POST['_woocommerce_gpf_data'] ) )
            return;

        $current_data = get_post_meta ( $product_id, '_woocommerce_gpf_data', true );

        if ( ! $current_data )
            $current_data = array();

        // Remove entries that are blanked out
        foreach ( $_POST['_woocommerce_gpf_data'] as $key => $value) {
            if ( empty ( $value ) ) {
                unset ( $_POST['_woocommerce_gpf_data'][$key] );
                if ( isset ( $current_data[$key] ) )
                    unset ( $current_data[$key] );
            } else {
                $_POST['_woocommerce_gpf_data'][$key] = stripslashes($value);
            }
        }
        // Including missing checkboxes
        if ( ! isset ( $_POST['_woocommerce_gpf_data']['exclude_product'] ) ) {
            unset ( $current_data['exclude_product'] );
        }

        $current_data = array_merge( $current_data, $_POST['_woocommerce_gpf_data'] );

        update_post_meta ( $product_id, '_woocommerce_gpf_data', $current_data );

    }



    /**
     * Used to render the drop-down of valid gender options
     * PS. Excellent function name
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_gender( $key, $current_data = NULL ) {
?>
        <select name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]">
            <option value=""> <?php if ( isset ( $_REQUEST['post'] ) || isset ( $_REQUEST['taxonomy'] ) ) { _e ( 'Use default', 'woocommerce_gpf' ); } else { _e ( 'No default', 'woocommerce_gpf'); }; ?></option>
            <option value="male" <?php if ( $current_data == 'male' ) echo 'selected'; ?>><?php _e ( 'Male', 'woocommerce_gpf'); ?></option>
            <option value="female" <?php if ( $current_data == 'female' ) echo 'selected'; ?>><?php _e ( 'Female', 'woocommerce_gpf'); ?></option>
            <option value="unisex" <?php if ( $current_data == 'unisex' ) echo 'selected'; ?>><?php _e ( 'Unisex', 'woocommerce_gpf'); ?></option>
        </select>
        <?php
    }



    /**
     * Used to render the drop-down of valid conditions
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_condition( $key, $current_data = NULL ) {
?>
        <select name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]">
            <option value=""> <?php if ( isset ( $_REQUEST['post'] ) || isset ( $_REQUEST['taxonomy'] ) ) { _e ( 'Use default', 'woocommerce_gpf' ); } else { _e ( 'No default', 'woocommerce_gpf'); }; ?></option>
            <option value="new" <?php if ( $current_data == 'new' ) echo 'selected'; ?>><?php _e ( 'New', 'woocommerce_gpf'); ?></option>
            <option value="refurbished" <?php if ( $current_data == 'refurbished' ) echo 'selected'; ?>><?php _e ( 'Refurbished', 'woocommerce_gpf'); ?></option>
            <option value="used" <?php if ( $current_data == 'used' ) echo 'selected'; ?>><?php _e ( 'Used', 'woocommerce_gpf'); ?></option>
        </select>
        <?php
    }



    /**
     * Used to render the drop-down of valid availability
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_availability( $key, $current_data = NULL ) {
?>
        <select name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]">
            <option value=""> <?php if ( isset ( $_REQUEST['post'] ) || isset ( $_REQUEST['taxonomy'] ) ) { _e ( 'Use default', 'woocommerce_gpf' ); } else { _e ( 'No default', 'woocommerce_gpf'); }; ?></option>
            <option value="in stock" <?php if ( $current_data == 'in stock' ) echo 'selected'; ?>><?php _e ( 'In Stock', 'woocommerce_gpf'); ?></option>
            <option value="available for order" <?php if ( $current_data == 'available for order' ) echo 'selected'; ?>><?php _e ( 'Available for order', 'woocommerce_gpf'); ?></option>
            <option value="preorder" <?php if ( $current_data == 'preorder' ) echo 'selected'; ?>><?php _e ( 'Pre-Order', 'woocommerce_gpf'); ?></option>
            <option value="out of stock" <?php if ( $current_data == 'out of stock' ) echo 'selected'; ?>><?php _e ( 'Out of stock', 'woocommerce_gpf'); ?></option>
        </select>
        <?php
    }



    /**
     * Used to render the drop-down of valid age groups
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_age_group( $key, $current_data = NULL ) {
?>
        <select name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]">
            <option value=""> <?php if ( isset ( $_REQUEST['post'] ) || isset ( $_REQUEST['taxonomy'] ) ) { _e ( 'Use default', 'woocommerce_gpf' ); } else { _e ( 'No default', 'woocommerce_gpf'); }; ?></option>
            <option value="adult" <?php if ( $current_data == 'adult' ) echo 'selected'; ?>><?php _e ( 'Adults', 'woocommerce_gpf'); ?></option>
            <option value="kids" <?php if ( $current_data == 'kids' ) echo 'selected'; ?>><?php _e ( 'Children', 'woocommerce_gpf'); ?></option>
        </select>
        <?php
    }



    /**
     * Retrieve the Google taxonomy list to allow users to choose from it
     *
     * @access private
     * @return unknown
     */
    private function refresh_google_taxonomy() {

        global $wpdb, $table_prefix;

        // Retrieve from cache - avoid hitting Google.com too much because you know they might mind :)
        $taxonomies_cached = get_transient ( 'woocommerce_gpf_taxonomy' );
        if ( $taxonomies_cached )
            return true;
        set_transient ( 'woocommerce_gpf_taxonomy', true, time() + 60*60*24*14 );

        $request = wp_remote_get ('http://www.google.com/basepages/producttype/taxonomy.en-US.txt');

        if ( is_wp_error ( $request ) || ! isset ( $request['response']['code'] ) || '200' != $request['response']['code'] )
            return array();

        $taxonomies = explode( "\n", $request['body'] );
        // Strip the comment at the top
        array_shift( $taxonomies );
        // Strip the extra newline at the end
        array_pop( $taxonomies ) ;

        $sql = "DELETE FROM ${table_prefix}woocommerce_gpf_google_taxonomy";
        $wpdb->query ( $sql );

        foreach  ( $taxonomies as $term ) {

            $sql = "INSERT INTO ${table_prefix}woocommerce_gpf_google_taxonomy VALUES ( %s, %s )";
            $wpdb->query ( $wpdb->prepare ( $sql, $term, strtolower( $term ) ) );
        }

        return true;
    }



    /**
     * Let people choose from the Google taxonomy for the product_type tag
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_product_type( $key, $current_data = NULL) {

        $google_taxonomy = $this->refresh_google_taxonomy();
?>
        <input name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]" class="woocommerce_gpf_product_type_<?php esc_attr_e ( $key ); ?>" <?php echo $current_data ? 'value="'.esc_attr($current_data).'"' : ''; ?> style="width: 800px;">
        <script type="text/javascript">
            jQuery(document).ready(function(){
                    jQuery('.woocommerce_gpf_product_type_<?php esc_attr_e ( $key ); ?>').wooautocomplete( { minChars: 3, deferRequestBy: 5, serviceUrl: 'index.php?woocommerce_gpf_search=true' } );
            });
        </script>
<?php
    }



    /**
     * Let people choose from the Bing taxonomy for the bing_category tag
     *
     * @access private
     * @param unknown $key
     * @param unknown $current_data (optional)
     */
    private function render_b_category( $key, $current_data = NULL) {
?>
        <input name="_woocommerce_gpf_data[<?php esc_attr_e($key); ?>]" class="woocommerce_gpf_product_type_<?php esc_attr_e ( $key ); ?>" <?php echo $current_data ? 'value="'.esc_attr($current_data).'"' : ''; ?> style="width: 800px;">
        <script type="text/javascript">
            jQuery(document).ready(function(){
                    jQuery('.woocommerce_gpf_product_type_<?php esc_attr_e ( $key ); ?>').wooautocomplete( { minChars: 3, deferRequestBy: 5, serviceUrl: 'index.php?woocommerce_gpf_bing_search=true' } );
            });
        </script>
<?php
    }


    /**
     * Add a tab to the WooCommerce settings pages
     *
     * @access public
     * @param array $tabs The current list of settings tabs
     * @return array The tabs array with the new item added
     */
    function add_woocommerce_settings_tab( $tabs ) {

        $tabs['gpf'] = __('Product Feeds', 'woocommerce_gpf');
        return $tabs;

    }



    /**
     * Show config page, and process form submission
     *
     * @access public
     */
    function config_page() {

        if ( ! empty ( $_POST['woocommerce_gpf_config'] ) ) {
            $this->save_settings();
        }

        $bing_url = add_query_arg ( array ( 'woocommerce_gpf' => 'bing' ), get_home_url( null, '/') );
        $bing_url_download = add_query_arg ( array ( 'woocommerce_gpf' => 'bing', 'feeddownload' => true ), get_home_url( null, '/' ) );
        $google_url = add_query_arg ( array ( 'woocommerce_gpf' => 'google' ), get_home_url( null, '/' ) );
        $google_url_download = add_query_arg ( array ( 'woocommerce_gpf' => 'google', 'feeddownload' => true ), get_home_url( null, '/' ) );
        
?>
        <h3><?php _e ( 'Settings for your store', 'woocommerce_gpf' ); ?></h3>
        <p><?php _e ("This page allows you to control what data is added to your product feeds.", 'woocommerce_gpf'); ?></p>
        <p><?php _e ("Choose the fields you want to include here, and also set store-wide defaults. You can also set defaults against categories, or provide information on each product page. ", 'woocommerce_gpf'); ?></p>
        <h4><?php _e ( "Notes about Google", 'woocommerce_gpf'); ?></h3>
        <p><?php _e ( "Depending on what you sell, and where you are selling it to Google apply different rules as to which information you should supply. You can find Google's list of what information is required on ", 'woocommerce_gpf'); ?><a href="http://www.google.com/support/merchants/bin/answer.py?answer=188494" rel="nofollow"><?php _e ( 'this page', 'woocommerce_gpf' ); ?></a></p>
        <h4><?php _e ( "Getting your feed", 'woocommerce_gpf'); ?></h3>
        <p><?php _e ( "Your feed is available here: ", 'woocommerce_gpf'); ?><br>
            <ul>
                <li><img src="<?php echo plugins_url("images/google.png", __FILE__); ?>" alt="Google Merchant Centre"> <a href="<?php esc_attr_e ( $google_url ); ?>" target="_blank"><?php esc_html_e ( $google_url ); ?></a> or <a href="<?php esc_attr_e ( $google_url_download ); ?>"><?php _e('download a copy of your feed', 'woocommerce_gpf'); ?></a>.</li>
                <li><img src="<?php echo plugins_url("images/bing.png", __FILE__); ?>" alt="Bing"> <a href="<?php esc_attr_e ( $bing_url ); ?>" target="_blank"><?php esc_html_e ( $bing_url ); ?></a> or <a href="<?php esc_attr_e ( $bing_url_download ); ?>"><?php _e('download a copy of your feed', 'woocommerce_gpf'); ?></a>.</li>
            </ul>
        </p>

        <h4><?php _e ( "Settings", 'woocommerce_gpf'); ?></h3>
        <p><?php _e ( 'Choose which fields you want in your feed for each product, and set store-wide defaults below where necessary: ', 'woocommerce_gpf' ); ?><br/></p>
        <table class="form-table">
        <?php
        foreach ( $this->product_fields as $key => $info ) {
            echo '<tr valign="top">';
            echo '  <th scope="row" class="titledesc">'.esc_html ( $info['desc'] ). '<br>';
            foreach ( $this->product_fields[$key]['feed_types'] as $feed_type ) {
                echo '<span class="woocommerce_gpf_feed_type_icon"><img src="';
                echo plugins_url("images/$feed_type.png", __FILE__);
                echo '" alt="<?php echo $feed_type; ?>"></span>';
            }
            echo '</th>';
            echo '  <td class="forminp">';
            echo '    <div class="woocommerce_gpf_field_selector_group">';
            echo '    <input type="checkbox" class="woocommerce_gpf_field_selector" name="woocommerce_gpf_config[product_fields]['.$key.']" ';
            if ( isset ( $this->settings['product_fields'][$key] ) ) {
                echo 'checked="checked"';
            }
            echo '><label for="woocommerce_gpf_config[product_fields]['.$key.']">'.esc_html ( $info['full_desc'] ) . '</label>';
            if ( isset ( $this->product_fields[$key]['can_default'] ) ) {

                echo '<div class="woocommerce_gpf_config_'.$key.'"';
                if ( ! isset ( $this->settings['product_fields'][$key] ) ) {
                    echo ' style="display:none;"';

                }
                echo '>'.__( 'Store default: ', 'woocommerce_gpf' );
                if ( ! isset ( $this->{"product_fields"}[$key]['callback'] ) || ! is_callable( array ( &$this, $this->{"product_fields"}[$key]['callback'] ) ) ) {

                    echo '<input type="textbox" name="_woocommerce_gpf_data['.$key.']" ';
                    if ( !empty ( $this->settings['product_defaults'][$key] ) )
                        echo ' value="'.esc_attr($this->settings['product_defaults'][$key]).'"';
                    echo '>';

                } else {

                    if ( isset ( $this->settings['product_defaults'][$key] ) ) {
                        call_user_func( array ( &$this, $this->{"product_fields"}[$key]['callback']), $key, $this->settings['product_defaults'][$key] );
                    } else {
                        call_user_func( array ( &$this, $this->{"product_fields"}[$key]['callback']), $key );
                    }

                }
                echo "</div></div></td>";
            }
            echo '</tr>';
        }
?>
        </table>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery('.woocommerce_gpf_field_selector').change(function(){
                    group = jQuery(this).parent('.woocommerce_gpf_field_selector_group');
                    defspan = group.children('div');
                    defspan.slideToggle('fast');
                });
            });
        </script>
        <?php
    }



    /**
     * Save the settings from the config page
     *
     * @access public
     */
    function save_settings() {

        // Check nonce
        if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) die( __( 'Action failed. Please refresh the page and retry.', 'woothemes' ) );

        if ( ! $this->settings ) {
            $this->settings = array();
            add_option ( 'woocommerce_gpf_config', $this->settings, '', 'yes' );
        }

        foreach ( $_POST['_woocommerce_gpf_data'] as $key => $value ) {
            $_POST['_woocommerce_gpf_data'][$key] = stripslashes($value);
        }

        // We do these so we can re-use the same form field rendering code for the fields
        if ( isset ( $_POST['_woocommerce_gpf_data'] ) ) {
            $_POST['woocommerce_gpf_config']['product_defaults'] = $_POST['_woocommerce_gpf_data'];
            unset ( $_POST['_woocommerce_gpf_data'] );
        }

        $this->settings = $_POST['woocommerce_gpf_config'];
        update_option ( 'woocommerce_gpf_config', $this->settings );

        echo '<div id="message" class="updated"><p>'.__('Settings saved.').'</p></div>';

    }


}

global $woocommerce_gpf_admin;
$woocommerce_gpf_admin = new woocommerce_gpf_admin();
