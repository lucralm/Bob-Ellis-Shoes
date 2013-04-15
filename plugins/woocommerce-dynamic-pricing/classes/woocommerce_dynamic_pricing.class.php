<?php

class woocommerce_dynamic_pricing {

    private $discounted_products = array();
    public static $init_count = 0;
    public $variation_counts = array();
    
    public $product_counts = array();
    public $category_counts = array();
    
    public $_discounted;
    public $_discounted_cart;
    public $pricing_by_category;
    public $pricing_by_store_category;
    public $pricing_by_product;
    public $pricing_by_membership;
    public $pricing_by_totals;
    private $plugin_url;
    private $plugin_path;

    public function __construct() {
        self::$init_count++;

        // Start a PHP session
        if (!session_id())
            session_start();

        $this->_discounted = array();
        $this->_discounted_cart = array();

        $this->pricing_by_product = new woocommerce_pricing_by_product(1);

        $this->pricing_by_category = new woocommerce_pricing_by_category(2);
        $this->pricing_by_store_category = new woocommerce_pricing_by_store_category(3);

        $this->pricing_by_membership = new woocommerce_pricing_by_membership(4);

        $this->pricing_by_totals = new woocommerce_pricing_by_totals(5);

        add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'update_counts'), 1, 2);
        add_action('woocommerce_after_cart_item_quantity_update', array(&$this, 'on_update_cart_item_quantity'), 1, 2);

        add_filter('woocommerce_cart_item_price_html', array(&$this, 'on_display_cart_item_price_html'), 10, 3);

        add_filter('woocommerce_grouped_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_variable_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_sale_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_variation_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_variation_sale_price_html', array(&$this, 'on_price_html'), 10, 2);
        add_filter('woocommerce_empty_price_html', array(&$this, 'on_price_html'), 10, 2);
    }

    public function on_display_cart_item_price_html($html, $cart_item, $cart_item_key) {
        if ($this->is_cart_item_discounted($cart_item_key)) {

            if (get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes') :
                $price = $this->_discounted_cart[$cart_item_key]['discounts']['price_excluding_tax'];
                $discounted_price = $cart_item['data']->get_price_excluding_tax();
            else :
                $price = $this->_discounted_cart[$cart_item_key]['discounts']['price'];
                $discounted_price = $cart_item['data']->get_price();
            endif;

            $html = '<del>' . woocommerce_price($price) . '</del><ins> ' . woocommerce_price($discounted_price) . '</ins>';

            if (defined('WP_DEBUG') && WP_DEBUG) :
                $html .= '<br /><strong>Debug Info - Discounted By: ' . $this->_discounted_cart[$cart_item_key]['discounts']['by'] . '</strong><br /><pre>' . print_r($this->_discounted_cart[$cart_item_key]['discounts']['data'], true) . '</pre>';
            endif;
        }

        return $html;
    }

    public function on_price_html($html, $_product) {

        $from = strstr($html, 'From') !== false ? ' From ' : ' ';

        $discount_price = false;
        $id = isset($_product->variation_id) ? $_product->variation_id : $_product->id;
        $working_price = isset($this->discounted_products[$id]) ? $this->discounted_products[$id] : $_product->get_price();

        $base_price = $_product->get_price();

        if ($this->pricing_by_store_category->is_applied_to($_product)) {
            if (floatval($working_price)) {
                $discount_price = $this->pricing_by_store_category->get_price($_product, $working_price);
                if ($discount_price && $discount_price != $base_price) {
                    $html = '<del>' . woocommerce_price($base_price) . '</del><ins>' . $from . woocommerce_price($discount_price) . '</ins>';
                }
            }
        }

        //Make sure we are using the price that was just discounted. 
        $working_price = $discount_price ? $discount_price : $base_price;

        if ($this->pricing_by_membership->is_applied_to($_product)) {
            $discount_price = $this->pricing_by_membership->get_price($_product, $working_price);
            if (floatval($working_price)) {
                if ($discount_price && $discount_price != $base_price) {
                    $html = '<del>' . woocommerce_price($base_price) . '</del><ins>' . $from . woocommerce_price($discount_price) . '</ins>';
                }
            }
        }

        $this->discounted_products[$id] = $discount_price ? $discount_price : $base_price;

        return $html;
    }

    /* Helper getter methods */

    public function get_product_ids($variations = false) {
        if ($variations) {
            return array_merge(array_keys($this->product_counts), array_keys($this->variation_counts));
        } else {
            return array_keys($this->product_counts);
        }
    }

    public function reset_counts() {
        global $woocommerce_pricing;

        $this->variation_counts = array();
        $this->product_counts = array();
        $this->category_counts = array();
    }

    public function reset_products() {
        global $woocommerce;

        foreach ($woocommerce->cart->cart_contents as $cart_item) {
            if (isset($cart_item['discounts'])) {
                $cart_item['data']->price = $cart_item['discounts']['price'];

                unset($cart_item['discounts']);
            }
        }
    }

    public function update_counts($cart_item, $values) {
        global $woocommerce;
        $_product = $cart_item['data'];

        //Gather product id counts
        $this->product_counts[$_product->id] = isset($this->product_counts[$_product->id]) ? $this->product_counts[$_product->id] + $cart_item['quantity'] : $cart_item['quantity'];

        //Gather product variation id counts
        if (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) {
            $this->variation_counts[$cart_item['variation_id']] = isset($this->variation_counts[$cart_item['variation_id']]) ? $this->variation_counts[$cart_item['variation_id']] + $cart_item['quantity'] : $cart_item['quantity'];
        }

        //Gather product category counts
        $product_categories = wp_get_post_terms($_product->id, 'product_cat');
        foreach ($product_categories as $category) {
            $this->category_counts[$category->term_id] = isset($this->category_counts[$category->term_id]) ? $this->category_counts[$category->term_id] + $cart_item['quantity'] : $cart_item['quantity'];
        }

        return $cart_item;
    }

    public function on_update_cart_item_quantity($cart_item, $quantity) {
        global $woocommerce;

        $this->reset_counts();
        $this->reset_products();
        if (sizeof($woocommerce->cart->get_cart()) > 0) {
            foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {
                $this->update_counts($values, null);
            }
        }
    }

    public function plugin_url() {
        if ($this->plugin_url)
            return $this->plugin_url;

        if (is_ssl()) :
            return $this->plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL) . "/" . plugin_basename(dirname(dirname(__FILE__)));
        else :
            return $this->plugin_url = WP_PLUGIN_URL . "/" . plugin_basename(dirname(dirname(__FILE__)));
        endif;
    }

    /**
     * Get the plugin path
     */
    public function plugin_path() {
        if ($this->plugin_path)
            return $this->plugin_path;
        return $this->plugin_path = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(dirname(__FILE__)));
    }

    public function reset_totals() {
        $this->_discounted = array();
        $this->_discounted_cart = array();
    }

    public function is_cart_item_discounted($cart_item_key) {
        return isset($this->_discounted_cart[$cart_item_key]) && isset($this->_discounted_cart[$cart_item_key]['discounts']);
    }

    public function add_discounted_cart_item(&$cart_item_key, $cart_item, $track_variation = false) {
        $this->_discounted[$cart_item['product_id']] = $cart_item;
        if ($track_variation) {
            $this->_discounted[$cart_item['variation_id']] = $cart_item;
        }

        $this->_discounted_cart[$cart_item_key] = $cart_item;
    }

    public function remove_discounted_cart_item($cart_item_key, $cart_item, $track_variation = false) {

        //unset($this->_discounted[$cart_item['product_id']]);
        if ($track_variation) {
            //unset($this->_discounted[$cart_item['variation_id']]);
        }

        //unset($this->_discounted_cart[$cart_item_key]);
    }

}

?>