<?php

class woocommerce_pricing_by_store_category extends woocommerce_pricing_base {

    private $applied_rules = array();
    private static $been_here = false;

    public function __construct($priority) {
        parent::__construct('category');

        add_action('init', array(&$this, 'on_init'));
        add_action('woocommerce_after_cart_item_quantity_update', array(&$this, 'on_update_cart_item_quantity'), $priority, 2);
        add_action('woocommerce_before_calculate_totals', array(&$this, 'on_calculate_totals'), $priority);
    }

    public function on_update_cart_item_quantity($cart_item, $quantity) {
        
    }

    public function is_applied_to($_product, $cat_id = false) {
        if (is_admin() && !is_ajax()) {
            return false;
        }

        $process_discounts = false;
        if (isset($this->applied_rules) && count($this->applied_rules) > 0) {
            if ($cat_id) {
                $process_discounts = is_object_in_term($_product->id, 'product_cat', $cat_id);
            } else {
                $process_discounts = is_object_in_term($_product->id, 'product_cat', array_keys($this->applied_rules));
            }
        }

        return apply_filters('woocommerce_dynamic_pricing_process_product_discounts', $process_discounts, $_product, $this->discounter, $this);
    }

    public function on_init() {
        $pricing_rule_sets = get_option('_s_category_pricing_rules', array());

        if (is_array($pricing_rule_sets) && sizeof($pricing_rule_sets) > 0) {
            foreach ($pricing_rule_sets as $pricing_rule_set) {
                $execute_rules = false;
                $conditions_met = 0;
                $pricing_conditions = $pricing_rule_set['conditions'];
                if (is_array($pricing_conditions) && sizeof($pricing_conditions) > 0) {
                    foreach ($pricing_conditions as $condition) {
                        $conditions_met += $this->handle_condition($condition);
                    }
                    if ($pricing_rule_set['conditions_type'] == 'all') {
                        $execute_rules = $conditions_met == count($pricing_conditions);
                    } elseif ($pricing_rule_set['conditions_type'] == 'any') {
                        $execute_rules = $conditions_met > 0;
                    }
                } else {
                    //empty conditions - default match, process price adjustment rules
                    $execute_rules = true;
                }

                if ($execute_rules && isset($pricing_rule_set['collector']['args']['cats'][0])) {
                    $this->applied_rules[$pricing_rule_set['collector']['args']['cats'][0]] = $pricing_rule_set;
                }
            }
        }
    }

    public function on_calculate_totals($_cart) {
        global $woocommerce, $woocommerce_pricing;

        if (sizeof($_cart->cart_contents) > 0) {
            foreach ($_cart->cart_contents as $cart_item_key => &$cart_item) {

                if (!$this->is_cumulative($cart_item)) {

                    if ($this->is_item_discounted($cart_item)) {
                        continue;
                    }

                    $this->reset_cart_item_price($cart_item);
                }

                $original_price = $this->get_price_to_discount($cart_item);
                $original_price_ex_tax = $this->get_price_excluding_tax_to_discount($cart_item);

                $_product = $cart_item['data'];
                $price_adjusted = false;
                $applied_rule = false;
                $applied_rule_set = false;

                foreach ($this->applied_rules as $pricing_rule_set) {

                    if ($this->is_applied_to($_product, $pricing_rule_set['collector']['args']['cats'][0])) {
                        $rule = $pricing_rule_set['rules'][0];

                        $temp = $this->get_adjusted_price($rule, $original_price);

                        if (!$price_adjusted || $temp < $price_adjusted) {
                            $price_adjusted = $temp;
                            $applied_rule = $rule;
                            $applied_rule_set = $pricing_rule_set;
                        }
                    }
                }

                if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
                    $this->add_adjustment($cart_item, $price_adjusted, $pricing_rule_set);
                    $this->add_discount_info($cart_item, $original_price, $original_price_ex_tax, $price_adjusted);
                    $this->track_cart_item($cart_item_key, $cart_item);
                } else {
                    //Reset discount data
                    $this->remove_discount_info($cart_item);

                    //Should we be tracking the variation?  
                    $tracking_variation = isset($cart_item['variation_id']);
                    //Remove the tracked item
                    $woocommerce_pricing->remove_discounted_cart_item($cart_item_key, $cart_item, $tracking_variation);
                }
            }
        }
    }

    private function get_adjusted_price($rule, $price) {

        $this->discount_data['rule'] = $rule;
        $result = false;

        switch ($rule['type']) {
            case 'fixed_product':
                $adjusted = floatval($price) - floatval($rule['amount']);
                $result = $adjusted >= 0 ? $adjusted : 0;
                break;
            case 'percent_product':
                if ($rule['amount'] > 1) {
                    $rule['amount'] = $rule['amount'] / 100;
                }
                $result = round(floatval($price) - ( floatval($rule['amount']) * $price), 2);
                break;
            case 'fixed_price':
                $result = round($rule['amount'], 2);
                break;
            default:
                $result = false;
                break;
        }


        return $result;
    }

    private function handle_condition($condition) {
        $result = 0;
        switch ($condition['type']) {
            case 'apply_to':
                if (is_array($condition['args']) && isset($condition['args']['applies_to'])) {
                    if ($condition['args']['applies_to'] == 'everyone') {
                        $result = 1;
                    } elseif ($condition['args']['applies_to'] == 'unauthenticated') {
                        if (!is_user_logged_in()) {
                            $result = 1;
                        }
                    } elseif ($condition['args']['applies_to'] == 'authenticated') {
                        if (is_user_logged_in()) {
                            $result = 1;
                        }
                    } elseif ($condition['args']['applies_to'] == 'roles' && isset($condition['args']['roles']) && is_array($condition['args']['roles'])) {
                        if (is_user_logged_in()) {
                            foreach ($condition['args']['roles'] as $role) {
                                if (current_user_can($role)) {
                                    $result = 1;
                                    break;
                                }
                            }
                        }
                    }
                }
                break;
            default:
                break;
        }

        if ($result) {
            $this->discount_data['condition'] = $condition;
        }

        return $result;
    }

    public function get_price($_product, $working_price) {
        $price_adjusted = false;
        $applied_rule = false;
        $applied_rule_set = false;

        foreach ($this->applied_rules as $pricing_rule_set) {
            if ($this->is_applied_to($_product, $pricing_rule_set['collector']['args']['cats'][0])) {
                $rule = $pricing_rule_set['rules'][0];

                $temp = $this->get_adjusted_price($rule, $working_price);

                if (!$price_adjusted || $temp < $price_adjusted) {
                    $price_adjusted = $temp;
                    $applied_rule = $rule;
                    $applied_rule_set = $pricing_rule_set;
                }
            }
        }

        if ($price_adjusted !== false && floatval($working_price) != floatval($price_adjusted)) {
            return $price_adjusted;
        }

        return $working_price;
    }

}

class woocommerce_pricing_by_category extends woocommerce_pricing_base {

    private static $been_here = false;

    public function __construct($priority) {
        parent::__construct('advanced_category');
        add_action('woocommerce_after_cart_item_quantity_update', array(&$this, 'on_update_cart_item_quantity'), $priority, 2);
        add_action('woocommerce_before_calculate_totals', array(&$this, 'on_calculate_totals'), $priority);
    }

    public function on_update_cart_item_quantity($cart_item, $quantity) {
        
    }

    public function on_calculate_totals($_cart) {
        global $woocommerce;

        if (sizeof($_cart->cart_contents) > 0) {
            foreach ($_cart->cart_contents as $cart_item_key => &$values) {
                $this->adjust_cart_item($cart_item_key, $values);
            }
        }

        return;
    }

    private function adjust_cart_item($cart_item_key, &$cart_item) {
        global $woocommerce, $woocommerce_pricing;

        $this->discount_data = array();

        $process_discounts = true; //all products are eligibile for the discount.  The role is checked later. 
        $process_discounts = apply_filters('woocommerce_dynamic_pricing_process_product_discounts', $process_discounts, $cart_item['data'], $this->discounter, $this);

        if (!$process_discounts) {
            return false;
        }

        if (!$this->is_cumulative($cart_item)) {
            if ($this->is_item_discounted($cart_item)) {
                return false;
            }
            $this->reset_cart_item_price($cart_item);
        }

        $original_price = $this->get_price_to_discount($cart_item);
        $original_price_ex_tax = $this->get_price_excluding_tax_to_discount($cart_item);

        $pricing_rule_sets = get_option('_a_category_pricing_rules', array());
        if (is_array($pricing_rule_sets) && sizeof($pricing_rule_sets) > 0) {
            foreach ($pricing_rule_sets as $pricing_rule_set) {
                $execute_rules = false;
                $conditions_met = 0;

                $pricing_conditions = $pricing_rule_set['conditions'];

                $collector = $this->get_collector($pricing_rule_set);

                if (is_array($pricing_conditions) && sizeof($pricing_conditions) > 0) {

                    foreach ($pricing_conditions as $condition) {
                        $conditions_met += $this->handle_condition($condition, $cart_item);
                    }

                    if ($pricing_rule_set['conditions_type'] == 'all') {
                        $execute_rules = $conditions_met == count($pricing_conditions);
                    } elseif ($pricing_rule_set['conditions_type'] == 'any') {
                        $execute_rules = $conditions_met > 0;
                    }
                } else {
                    //empty conditions - default match, process price adjustment rules
                    $execute_rules = true;
                }

                if ($execute_rules) {
                    $pricing_rules = $pricing_rule_set['rules'];

                    $price_adjusted = $this->get_adjusted_price($pricing_rules, $original_price, $collector, $cart_item);

                    if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
                        $this->add_adjustment($cart_item, $price_adjusted, $pricing_rule_set);
                        $this->add_discount_info($cart_item, $original_price, $original_price_ex_tax, $price_adjusted);
                        $this->track_cart_item($cart_item_key, $cart_item);
                        break;
                    } else {
                        //Reset discount data
                        $this->remove_discount_info($cart_item);
                        //Should we be tracking the variation?  
                        $tracking_variation = $collector['type'] == 'variation' && isset($cart_item['variation_id']);
                        //Remove the tracked item
                        $woocommerce_pricing->remove_discounted_cart_item($cart_item_key, $cart_item, $tracking_variation);
                    }
                }
            }
        }

        return false;
    }

    private function get_adjusted_price($pricing_rules, $price, $collector, $cart_item) {
        $result = false;

        if (is_array($pricing_rules) && sizeof($pricing_rules) > 0) {
            foreach ($pricing_rules as $rule) {

                $q = $this->get_quantity_to_compare($cart_item, $collector);

                if ($rule['from'] == '*') {
                    $rule['from'] = 0;
                }

                if ($rule['to'] == '*') {
                    $rule['to'] = $q;
                }

                if ($q >= $rule['from'] && $q <= $rule['to']) {
                    $this->discount_data['rule'] = $rule;

                    switch ($rule['type']) {
                        case 'price_discount':
                            $adjusted = floatval($price) - floatval($rule['amount']);
                            $result = $adjusted >= 0 ? $adjusted : 0;
                            break;
                        case 'percentage_discount':

                            if ($rule['amount'] > 1) {
                                $rule['amount'] = $rule['amount'] / 100;
                            }

                            $result = round(floatval($price) - ( floatval($rule['amount']) * $price), 2);
                            break;
                        case 'fixed_price':
                            $result = round($rule['amount'], 2);
                            break;
                        default:
                            $result = false;
                            break;
                    }

                    break; //break out here only the first matched pricing rule will be evaluated.
                }
            }
        }

        return $result;
    }

    private function handle_condition($condition, $cart_item) {
        $result = 0;
        switch ($condition['type']) {
            case 'apply_to':
                if (is_array($condition['args']) && isset($condition['args']['applies_to'])) {
                    if ($condition['args']['applies_to'] == 'everyone') {
                        $result = 1;
                    } elseif ($condition['args']['applies_to'] == 'unauthenticated') {
                        if (!is_user_logged_in()) {
                            $result = 1;
                        }
                    } elseif ($condition['args']['applies_to'] == 'authenticated') {
                        if (is_user_logged_in()) {
                            $result = 1;
                        }
                    } elseif ($condition['args']['applies_to'] == 'roles' && isset($condition['args']['roles']) && is_array($condition['args']['roles'])) {
                        if (is_user_logged_in()) {
                            foreach ($condition['args']['roles'] as $role) {
                                if (current_user_can($role)) {
                                    $result = 1;
                                    break;
                                }
                            }
                        }
                    }
                }
                break;
            default:
                break;
        }

        if ($result) {
            $this->discount_data['condition'] = $condition;
        }

        return $result;
    }

    private function get_collector($pricing_rule_set) {
        $this->discount_data['collector'] = $pricing_rule_set['collector'];
        return $pricing_rule_set['collector'];
    }

    private function get_quantity_to_compare($cart_item, $collector) {
        global $woocommerce_pricing;
        $quantity = 0;

        switch ($collector['type']) {
            case 'cat_product':
                if (isset($collector['args']) && isset($collector['args']['cats']) && is_array($collector['args']['cats'])) {
                    if (is_object_in_term($cart_item['product_id'], 'product_cat', $collector['args']['cats'])) {
                        $quantity = $cart_item['quantity'];
                    }
                }
                break;
            case 'cat' :
                if (isset($collector['args']) && isset($collector['args']['cats']) && is_array($collector['args']['cats'])) {
                    if (is_object_in_term($cart_item['product_id'], 'product_cat', $collector['args']['cats'])) {
                        $terms = wp_get_post_terms($cart_item['product_id'], 'product_cat');

                        $product_ids = array_keys($woocommerce_pricing->product_counts);
                        $quantity = (int) $cart_item['quantity'];

                        if ($terms) {
                            foreach ($terms as $term) {

                                if (!in_array($term->term_id, $collector['args']['cats'])) {
                                    continue;
                                }


                                foreach ($product_ids as $product_id) {
                                    if ($product_id != $cart_item['product_id']) {

                                        if (is_object_in_term($product_id, 'product_cat', $term)) {
                                            $quantity += (int) $woocommerce_pricing->product_counts[$product_id];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }

        $this->discount_data['collected_quantity'] = $quantity;

        return $quantity;
    }

}

?>
