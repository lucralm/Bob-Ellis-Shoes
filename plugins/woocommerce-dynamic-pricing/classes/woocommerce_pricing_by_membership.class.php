<?php

class woocommerce_pricing_by_membership extends woocommerce_pricing_base {

    private $variation_rules = array();
    private $is_applied_from_product = false;
    private $is_applied_from_variation = false;
    private $applied_rule;
    private static $been_here = false;

    public function __construct($priority) {
        parent::__construct('membership');

        add_action('init', array(&$this, 'on_init'));
        add_action('woocommerce_before_calculate_totals', array(&$this, 'on_calculate_totals'), $priority);
    }

    public function is_applied_to($_product) {
        if (is_admin() && !is_ajax()) {
            return false;
        }
        
        $process = true; //all products are eligibile for the discount.  The role is checked later. 
        return apply_filters('woocommerce_dynamic_pricing_process_product_discounts', $process, $_product, $this->discounter, $this);
    }

    public function on_init() {
        $pricing_rule_sets = get_option('_s_membership_pricing_rules', array());

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

                if ($execute_rules) {
                    $this->applied_rule = $pricing_rule_set['rules'][0];
                }
            }
        }
    }

    public function on_calculate_totals($_cart) {
        global $woocommerce, $woocommerce_pricing;
        if (sizeof($_cart->cart_contents) > 0) {
            foreach ($_cart->cart_contents as $cart_item_key => &$cart_item) {
                $_product = $cart_item['data'];
                if ($this->is_applied_to($_product)) {

                    if (!$this->is_cumulative($cart_item)) {

                        if ($this->is_item_discounted($cart_item)) {
                            continue;
                        }

                        $this->reset_cart_item_price($cart_item);
                    }

                    $original_price = $this->get_price_to_discount($cart_item);
                    $original_price_ex_tax = $this->get_price_excluding_tax_to_discount($cart_item);

                    $price_adjusted = $this->get_adjusted_price($this->applied_rule, $original_price);

                    if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
                        $this->add_adjustment($cart_item, $price_adjusted, $this->applied_rule);
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

        return;
    }

    public function get_price($_product, $working_price) {

        $fake_cart_item = array('data' => $_product);

        $a_working_price = apply_filters('woocommerce_dyanmic_pricing_working_price', $working_price, 'advanced_product', $fake_cart_item);


        $lowest_price = false;
        $applied_rule = null;
        $applied_to_variation = false;

        $pricing_rule_sets = get_post_meta($_product->id, '_pricing_rules', true);
        if (is_array($pricing_rule_sets) && sizeof($pricing_rule_sets) > 0) {
            foreach ($pricing_rule_sets as $pricing_rule_set) {
                $execute_rules = false;
                $conditions_met = 0;
                $variation_id = 0;
                $variation_rules = isset($pricing_rule_set['variation_rules']) ? $pricing_rule_set['variation_rules'] : '';
                $applied_to_variation = $variation_rules && isset($variation_rules['args']['type']) && $variation_rules['args']['type'] == 'variations';

                if ($_product->is_type('variable') && $variation_rules) {
                    if (isset($variation_rules['args']['type']) && $variation_rules['args']['type'] == 'variations' && isset($variation_rules['args']['variations']) && count($variation_rules['args']['variations'])) {
                        if (!isset($_product->variation_id) || !in_array($_product->variation_id, $variation_rules['args']['variations'])) {
                            continue;
                        } else {
                            $variation_id = $_product->variation_id;
                        }
                    }
                }

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

                if ($execute_rules) {
                    $pricing_rules = $pricing_rule_set['rules'];
                    if (is_array($pricing_rules) && sizeof($pricing_rules) > 0) {
                        foreach ($pricing_rules as $rule) {
                            if ($rule['from'] == '0') {

                                //first rule matched takes precedence for the item. 
                                if (!$applied_rule) {
                                    if ($applied_to_variation && $variation_id) {
                                        $applied_rule = $rule;
                                    } elseif (!$applied_to_variation) {
                                        $applied_rule = $rule;
                                    }
                                }

                                //calcualte the lowest price for display
                                $price = $this->get_adjusted_price_by_product_rule($rule, $a_working_price);
                                if ($price && !$lowest_price) {
                                    $lowest_price = $price;
                                } elseif ($price && $price < $lowest_price) {
                                    $lowest_price = $price;
                                }
                            }
                        }
                    }
                }
            }
        }



        if (!$this->is_cumulative($fake_cart_item)) {

            if (get_class($_product) == 'WC_Product' && $_product->is_type('variable') && $lowest_price) {
                return $lowest_price;
            } elseif ($applied_rule) {
                return $this->get_adjusted_price_by_product_rule($applied_rule, $a_working_price);
            } elseif ($this->applied_rule) {
                $s_working_price = apply_filters('woocommerce_dyanmic_pricing_working_price', $working_price, 'membership', $fake_cart_item);
                return $this->get_adjusted_price($this->applied_rule, $s_working_price);
            }
        } else {

            $discounted_price = null;
            if (get_class($_product) == 'WC_Product' && $_product->is_type('variable') && $lowest_price) {
                $discounted_price = $lowest_price;
            } elseif ($applied_rule) {
                $discounted_price = $this->get_adjusted_price_by_product_rule($applied_rule, $a_working_price);
            }

            if ($this->applied_rule) {
                $s_working_price = apply_filters('woocommerce_dyanmic_pricing_working_price', $discounted_price, 'membership', $fake_cart_item);
                return $this->get_adjusted_price($this->applied_rule, $s_working_price);
            } else {
                return $discounted_price;
            }
        }

        return $working_price;
    }

    private function get_adjusted_price($rule, $price) {
        $result = $price;
        $this->discount_data['rule'] = $rule;
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

    private function get_adjusted_price_by_product_rule($rule, $price) {
        $result = false;

        $q = 0;

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

}

function template_get_product_price($_product, $format = true) {
    global $woocommerce, $woocommerce_pricing;

    if ($woocommerce_pricing->pricing_by_membership->applied_rule) {
        $data = $woocommerce_pricing->pricing_by_membership->get_price($_product);
        return $format ? woocommerce_price($data) : $data;
    }

    return $_product->get_price();
}

?>