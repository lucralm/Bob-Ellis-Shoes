<?php

class woocommerce_pricing_base {

    protected $discounter = 'base';
    protected $discount_data = array();

    public function __construct($name) {
        $this->discounter = $name;
    }

    public function add_adjustment(&$cart_item, $price_adjusted, $applied_rule) {
        $cart_item['data']->price = $price_adjusted;
    }

    protected function add_discount_info(&$cart_item, $original_price, $original_price_ex_tax, $adjusted_price) {
        $discounters = isset($cart_item['discounts']['discounters']) ? $cart_item['discounts']['discounters'] : array();
        $discounters[$this->discounter] = array('price' => $original_price, 'price_excluding_tax' => $original_price_ex_tax, 'discounted' => $adjusted_price, 'by' => $this->discounter, 'data' => $this->discount_data);
        if (isset($cart_item['discounts'])) {
            $cart_item['discounts'] = array_merge($cart_item['discounts'], array('discounted' => $adjusted_price, 'by' => $this->discounter, 'data' => $this->discount_data, 'discounters' => $discounters));
        } else {
            $cart_item['discounts'] = array('price' => $original_price, 'price_excluding_tax' => $original_price_ex_tax, 'discounted' => $adjusted_price, 'by' => $this->discounter, 'data' => $this->discount_data, 'discounters' => $discounters);
        }
    }

    protected function remove_discount_info(&$cart_item) {
        if (isset($cart_item['discounts']) && isset($cart_item['discounts']['by']) && $cart_item['discounts']['by'] == $this->discounter) {
            unset($cart_item['discounts']);
        }
    }

    protected function reset_cart_item_price(&$cart_item) {
        if (isset($cart_item['discounts']) && isset($cart_item['discounts']['by']) && $cart_item['discounts']['by'] == $this->discounter) {
            $cart_item['data']->price = $cart_item['discounts']['price'];
        }
    }

    protected function track_cart_item(&$cart_item_key, $cart_item) {
        global $woocommerce_pricing;

        $tracking_variation = isset($cart_item['variation_id']);
        $woocommerce_pricing->add_discounted_cart_item($cart_item_key, $cart_item, $tracking_variation);
    }

    protected function is_item_discounted($cart_item) {
        return isset($cart_item['discounts']);
    }

    protected function is_cumulative($cart_item, $default = false) {
        return apply_filters('woocommerce_dynamic_pricing_is_cumulative', $default, $this->discounter, $cart_item);
    }

    protected function get_price_to_discount($cart_item) {
        return apply_filters('woocommerce_dyanmic_pricing_working_price', $cart_item['data']->get_price(), $this->discounter, $cart_item);
    }

    protected function get_price_excluding_tax_to_discount($cart_item) {
        return apply_filters('woocommerce_dyanmic_pricing_working_price_excluding_tax', $cart_item['data']->get_price_excluding_tax(), $this->discounter, $cart_item);
    }

}

?>
