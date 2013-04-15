<?php

class woocommerce_product_pricing_rules_admin {
    
    public function __construct() {
        add_action('woocommerce_product_write_panel_tabs', array(&$this, 'on_product_write_panel_tabs'), 99);
        add_action('woocommerce_product_write_panels', array(&$this, 'product_data_panel'), 99);
        add_action('woocommerce_process_product_meta', array(&$this, 'process_meta_box'), 1, 2);
    }

    public function on_product_write_panel_tabs() {
        ?>
        <li class="pricing_tab dynamic_pricing_options"><a href="#dynamic_pricing_data"><?php _e('Dynamic Pricing', 'wc_pricing'); ?></a></li>
        <?php
    }

    public function product_data_panel() {
        global $woocommerce_pricing,$post;
        $pricing_rule_sets = get_post_meta($post->ID, '_pricing_rules', true);
        ?>
        <div id="dynamic_pricing_data" class="panel woocommerce_options_panel">
            <div id="woocommerce-pricing-rules-wrap" data-setindex="<?php echo count($pricing_rule_sets); ?>">
                <?php $this->meta_box_javascript(); ?>
                <?php $this->meta_box_css(); ?>  
                <?php if ($pricing_rule_sets && is_array($pricing_rule_sets) && sizeof($pricing_rule_sets) > 0) : ?>

                    <?php $this->create_rulesets($pricing_rule_sets); ?>


                <?php endif; ?>        
            </div>   
            <button title="Allows you to configure another Price Adjustment.  Useful if you have different sets of conditions and pricing adjustments which need to be applied to this product. " id="woocommerce-pricing-add-ruleset" type="button" class="button button-primary">Add Pricing Group</button>
            <div class="clear"></div>
        </div>
        <?php
    }

    public function create_rulesets($pricing_rule_sets) {
        global $woocommerce_pricing;

        foreach ($pricing_rule_sets as $pricing_rule_set) {
            $name = uniqid('set_');
            $pricing_rules = isset($pricing_rule_set['rules']) ? $pricing_rule_set['rules'] : null;
            $pricing_conditions = isset($pricing_rule_set['conditions']) ? $pricing_rule_set['conditions'] : null;
            $collector = isset($pricing_rule_set['collector']) ? $pricing_rule_set['collector'] : null;
            $variation_rules = isset($pricing_rule_set['variation_rules']) ? $pricing_rule_set['variation_rules'] : null;
            ?>
            <div id="woocommerce-pricing-ruleset-<?php echo $name; ?>" class="woocommerce_pricing_ruleset">
                <div id="woocommerce-pricing-conditions-<?php echo $name; ?>" class="section    ">
                    <h4 class="first">Pricing Group<a href="#" data-name="<?php echo $name; ?>" class="delete_pricing_ruleset" ><img  src="<?php echo $woocommerce_pricing->plugin_url(); ?>/assets/images/delete.png" title="Delete this Price Adjustment" alt="Delete this Price Adjustment" style="cursor:pointer; margin:0 3px;float:right;" /></a></h4>    
                    <?php
                    $condition_index = 0;
                    if (is_array($pricing_conditions) && sizeof($pricing_conditions) > 0):
                        ?>
                        <input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions_type]" value="all" />
                        <?php
                        foreach ($pricing_conditions as $condition) :
                            $condition_index++;
                            $this->create_condition($condition, $name, $condition_index);
                        endforeach;
                    else :
                        ?>
                        <input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions_type]" value="all" />
                        <?php
                        $this->create_condition(array('type' => 'apply_to', 'args' => array('applies_to' => 'everyone', 'roles' => array())), $name, 1);
                    endif;
                    ?>
                </div>

                <div id="woocommerce-pricing-collector-<?php echo $name; ?>" class="section">
                    <?php
                    if (is_array($collector) && count($collector) > 0) {
                        $this->create_collector($collector, $name);
                    } else {
                        $product_cats = array();
                        $this->create_collector(array('type' => 'product', 'args' => array('cats' => $product_cats)), $name);
                    }
                    ?>
                </div>
                
                 <div id="woocommerce-pricing-variations-<?php echo $name; ?>" class="section">
                    <?php
                    $variation_index = 0;
                    if (is_array($variation_rules) && count($variation_rules) > 0) {
                        $this->create_variation_selector($variation_rules, $name);
                    } else {
                        $product_cats = array();
                        $this->create_variation_selector(null, $name);
                    }
                    ?>
                </div>
                <div class="clear"></div>
                <div class="section">
                    <table id="woocommerce-pricing-rules-table-<?php echo $name; ?>" data-lastindex="<?php echo (is_array($pricing_rules) && sizeof($pricing_rules) > 0) ? count($pricing_rules) : '1'; ?>">
                        <thead>
                        <th>
                            <?php _e('Minimum Quantity', 'wc_pricing'); ?>
                        </th>
                        <th>
                            <?php _e('Max Quantity', 'wc_pricing'); ?>
                        </th>
                        <th>
                            <?php _e('Type', 'wc_pricing'); ?>
                        </th>
                        <th>
                            <?php _e('Amount', 'wc_pricing'); ?>
                        </th>
                        <th>&nbsp;</th>
                        </thead>
                        <tbody>
                            <?php
                            $index = 0;
                            if (is_array($pricing_rules) && sizeof($pricing_rules) > 0) {
                                foreach ($pricing_rules as $rule) {
                                    $index++;
                                    $this->get_row($rule, $name, $index);
                                }
                            } else {
                                $this->get_row(array('to' => '', 'from' => '', 'amount' => '', 'type' => ''), $name, 1);
                            }
                            ?>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>
            </div><?php
            }
        }

        public function create_empty_ruleset($set_index) {
            $pricing_rule_sets = array();
            $pricing_rule_sets['set_' . $set_index] = array();
            $pricing_rule_sets['set_' . $set_index]['title'] = 'Rule Set ' . $set_index;
            $pricing_rule_sets['set_' . $set_index]['rules'] = array();
            $this->create_rulesets($pricing_rule_sets);
        }

        private function create_condition($condition, $name, $condition_index) {
            global $wp_roles;
            switch ($condition['type']) {
                case 'apply_to':
                    $this->create_condition_apply_to($condition, $name, $condition_index);
                    break;
                default:
                    break;
            }
        }

        private function create_condition_apply_to($condition, $name, $condition_index) {
            if (!isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
            $all_roles = $wp_roles->roles;
            $div_style = ($condition['args']['applies_to'] != 'roles') ? 'display:none;' : '';
                        ?>

        <div>
            <label for="pricing_rule_apply_to_<?php echo $name . '_' . $condition_index; ?>">Applies To:</label>
            <input type="hidden" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][type]" value="apply_to" />

            <select title="Choose if this rule should apply to everyone, or to specific roles.  Useful if you only give discounts to existing customers, or if you have tiered pricing based on the users role." class="pricing_rule_apply_to" id="pricing_rule_apply_to_<?php echo $name . '_' . $condition_index; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][applies_to]">
                <option <?php selected('everyone', $condition['args']['applies_to']); ?> value="everyone">Everyone</option>
                <option <?php selected('roles', $condition['args']['applies_to']); ?> value="roles">Specific Roles</option>
            </select>

            <div class="roles" style="<?php echo $div_style; ?>">
                <?php $chunks = array_chunk($all_roles, ceil(count($all_roles) / 3), true); ?>

                <?php foreach ($chunks as $chunk) : ?>
                    <ul class="list-column">        
                        <?php foreach ($chunk as $role_id => $role) : ?>
                            <?php $role_checked = (isset($condition['args']['roles']) && is_array($condition['args']['roles']) && in_array($role_id, $condition['args']['roles'])) ? 'checked="checked"' : ''; ?>
                            <li>
                                <label for="role_<?php echo $role_id; ?>" class="selectit">
                                    <input <?php echo $role_checked; ?> type="checkbox" id="role_<?php echo $role_id; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][roles][]" value="<?php echo $role_id; ?>" /><?php echo $role['name']; ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>

            </div>
            <div class="clear"></div>
        </div>
        <?php
    }

    private function create_variation_selector($condition, $name) {
        global $post;

        $post_id = isset($_POST['post']) ? intval($_POST['post']) : $post->ID;

        $product = new WC_Product($post_id);
        if (!$product->has_child()) {
            return;
        }

        $all_variations = $product->children;
        $div_style = ($condition['args']['type'] != 'variations') ? 'display:none;' : '';
        ?>

        <div>
            <label for="pricing_rule_variations_<?php echo $name; ?>">Product / Variations:</label>
            <select title="Choose what you would like to apply this pricing rule set to" class="pricing_rule_variations" id="pricing_rule_variations_<?php echo $name; ?>" name="pricing_rules[<?php echo $name; ?>][variation_rules][args][type]">
                <option <?php selected('product', $condition['args']['type']); ?> value="product">All Variations</option>
                <option <?php selected('variations', $condition['args']['type']); ?> value="variations">Specific Variations</option>
            </select>

            <div class="variations" style="<?php echo $div_style; ?>">
                <?php $chunks = array_chunk($all_variations, ceil(count($all_variations) / 3), true); ?>

                <?php foreach ($chunks as $chunk) : ?>

                    <ul class="list-column">        
                        <?php foreach ($chunk as $variation_id) : ?>
                            <?php $variation_object = new WC_Product_Variation($variation_id); ?>
                            <?php $variation_checked = (isset($condition['args']['variations']) && is_array($condition['args']['variations']) && in_array($variation_id, $condition['args']['variations'])) ? 'checked="checked"' : ''; ?>
                            <li>
                                <label for="variation_<?php echo $variation_id; ?>" class="selectit">
                                    <input <?php echo $variation_checked; ?> type="checkbox" id="variation_<?php echo $variation_id; ?>" name="pricing_rules[<?php echo $name; ?>][variation_rules][args][variations][]" value="<?php echo $variation_id; ?>" /><?php echo get_the_title($variation_id); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>

            </div>
            <div class="clear"></div>
        </div>
        <?php
    }

    private function create_collector($collector, $name) {
        $terms = (array) get_terms('product_cat', array('get' => 'all'));
        $div_style = ($collector['type'] != 'cat') ? 'display:none;' : '';
        ?>
        <label for="pricing_rule_when_<?php echo $name; ?>"><?php _e('Quantities based on:', 'wc_pricing'); ?></label>
        <select title="Choose how to calculate the quantity.  This tallied amount is used in determining the min and max quantities used below in the Quantity Pricing section." class="pricing_rule_when" id="pricing_rule_when_<?php echo $name; ?>" name="pricing_rules[<?php echo $name; ?>][collector][type]">
            <option title="Calculate quantity based on the Product ID" <?php selected('product', $collector['type']); ?> value="product"><?php _e('Product Quantity', 'wc_pricing'); ?></option>
            <option title="Calculate quantity based on the Variation ID" <?php selected('variation', $collector['type']); ?> value="variation"><?php _e('Variation Quantity', 'wc_pricing'); ?></option>
            <option title="Calculate quantity based on the Cart Line Item" <?php selected('cart_item', $collector['type']); ?> value="cart_item"><?php _e('Cart Line Item Quantity', 'wc_pricing'); ?></option>
            <option title="Calculate quantity based on total amount of a category in the cart" <?php selected('cat', $collector['type']); ?> value="cat"><?php _e('Quantity of Category', 'wc_pricing'); ?></option>
        </select>
        <br />
        <div class="cats" style="<?php echo $div_style; ?>">
            <?php $chunks = array_chunk($terms, ceil(count($terms) / 3)); ?>
            <?php foreach ($chunks as $chunk) : ?>
                <ul class="list-column">        
                    <?php foreach ($chunk as $term) : ?>
                        <?php $term_checked = (isset($collector['args']['cats']) && is_array($collector['args']['cats']) && in_array($term->term_id, $collector['args']['cats'])) ? 'checked="checked"' : ''; ?> 
                        <li>
                            <label for="<?php echo $name; ?>_term_<?php echo $term->term_id; ?>" class="selectit">
                                <input <?php echo $term_checked; ?> type="checkbox" id="<?php echo $name; ?>_term_<?php echo $term->term_id; ?>" name="pricing_rules[<?php echo $name; ?>][collector][args][cats][]" value="<?php echo $term->term_id; ?>" /><?php echo $term->name; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
            <div class="clear"></div>
        </div>

        <?php
    }

    private function get_row($rule, $name, $index) {
        global $woocommerce_pricing;
        ?>
        <tr id="pricing_rule_row_<?php echo $name . '_' . $index; ?>">
            <td>
                <input title="Apply this adjustment when the quantity in the cart starts at this value.  Use * for any." class="int_pricing_rule" id="pricing_rule_from_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index ?>][from]" value="<?php echo $rule['from']; ?>" />
            </td>
            <td>
                <input title="Apply this adjustment when the quantity in the cart is less than this value.  Use * for any." class="int_pricing_rule" id="pricing_rule_to_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index ?>][to]" value="<?php echo $rule['to']; ?>" />
            </td>
            <td>
                <select title="The type of adjustment to apply" id="pricing_rule_type_value_<?php echo $name . '_' . $index; ?>" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index; ?>][type]">
                    <option <?php selected('price_discount', $rule['type']); ?> value="price_discount">Price Discount</option>
                    <option <?php selected('percentage_discount', $rule['type']); ?> value="percentage_discount">Percentage Discount</option>
                    <option <?php selected('fixed_price', $rule['type']); ?> value="fixed_price">Fixed Price</option>
                </select>
            </td>
            <td>
                <input title="The value of the adjustment. Currency and percentage symbols are not required" class="float_rule_number" id="pricing_rule_amount_input_<?php echo $name . '_' . $index; ?>" type="text" name="pricing_rules[<?php echo $name; ?>][rules][<?php echo $index; ?>][amount]" value="<?php echo $rule['amount']; ?>" /> 
            </td>
            <td width="48"><a class="add_pricing_rule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
                        src="<?php echo $woocommerce_pricing->plugin_url() . '/assets/images/add.png'; ?>" 
                        title="add another rule" alt="add another rule" 
                        style="cursor:pointer; margin:0 3px;" /></a><a <?php echo ($index > 1) ? '' : 'style="display:none;"'; ?> class="delete_pricing_rule" data-index="<?php echo $index; ?>" data-name="<?php echo $name; ?>"><img 
                        src="<?php echo $woocommerce_pricing->plugin_url() . '/assets/images/remove.png'; ?>" 
                        title="add another rule" alt="add another rule" 
                        style="cursor:pointer; margin:0 3px;" /></a>
            </td>
        </tr>
        <?php
    }

    private function meta_box_javascript() {
        global $woocommerce_pricing;
        ?>
        <script type="text/javascript">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
            jQuery(document).ready(function($) {
                var set_index = 0;
                var rule_indexes = new Array();
                                                                                                                                                                                                                                                                                                                                        
                $('.woocommerce_pricing_ruleset').each(function(){
                    var length = $('table tbody tr', $(this)).length;
                    if (length==1) {
                        $('.delete_pricing_rule', $(this)).hide(); 
                    }
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
                $("#woocommerce-pricing-add-ruleset").click(function(event) {
                    event.preventDefault();
                                                                                                                                                                                                                                                                                                                                            
                    var set_index = $("#woocommerce-pricing-rules-wrap").data('setindex') + 1;
                    $("#woocommerce-pricing-rules-wrap").data('setindex', set_index );
                                                                                                                                                                                                                                                                                                                                            
                    var data = {
                        set_index:set_index,
                        post:<?php echo isset($_GET['post']) ? $_GET['post'] : 0; ?>,
                        action:'create_empty_ruleset'
                    }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
                    $.post(ajaxurl, data, function(response) { 
                        $('#woocommerce-pricing-rules-wrap').append(response);
                                                                                                                                                                                                                                                                                                                                                
                    });                                                                                                                                            
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                
                $('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_apply_to', 'change', function(event) {  
                    var value = $(this).val();
                    if (value != 'roles') {
                        $('.roles', $(this).parent()).fadeOut();
                        $('.roles input[type=checkbox]', $(this).closest('div') ).removeAttr('checked');
                    } else {                                                            
                        $('.roles', $(this).parent()).fadeIn();
                    }                                                              
                });
                                
                $('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_variations', 'change', function(event) {  
                    var value = $(this).val();
                    if (value != 'variations') {
                        $('.variations', $(this).parent()).fadeOut();
                        $('.variations input[type=checkbox]', $(this).closest('div') ).removeAttr('checked');
                    } else {                                                            
                        $('.variations', $(this).parent()).fadeIn();
                    }                                                              
                });
                                
                                                                                                                                                                                                                                                               
                $('#woocommerce-pricing-rules-wrap').delegate('.pricing_rule_when', 'change', function(event) {  
                    var value = $(this).val();
                    if (value != 'cat') {
                        $('.cats', $(this).closest('div')).fadeOut();
                        $('.cats input[type=checkbox]', $(this).closest('div') ).removeAttr('checked');
                                                                        
                    } else {                                                            
                        $('.cats', $(this).closest('div')).fadeIn();
                    }                                                              
                });
                                                                                                                                                                                                                                                                                                                                                
                //Remove Pricing Set
                $('#woocommerce-pricing-rules-wrap').delegate('.delete_pricing_ruleset', 'click', function(event) {  
                    event.preventDefault();
                    DeleteRuleSet( $(this).data('name') );
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                
                //Add Button
                $('#woocommerce-pricing-rules-wrap').delegate('.add_pricing_rule', 'click', function(event) {  
                    event.preventDefault();
                    InsertRule($(this).data('index'), $(this).data('name') );
                });
                                                                                                                                                                                                                                                                                                                                                                 
                                                                                                                                                                                                                                                                                                                                                                 
                                                                                                                                                                                                                                                                                                                                                                 
                //Remove Button                
                $('#woocommerce-pricing-rules-wrap').delegate('.delete_pricing_rule', 'click', function(event) {  
                    event.preventDefault();
                    DeleteRule($(this).data('index'), $(this).data('name'));
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                //Validation
                $('#woocommerce-pricing-rules-wrap').delegate('.int_pricing_rule', 'keydown', function(event) {  
                    // Allow only backspace, delete and tab
                    if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 190 ) {
                        // let it happen, don't do anything
                    }
                    else {
                        if (event.shiftKey && event.keyCode == 56){
                            if ($(this).val().length > 0) {
                                event.preventDefault();
                            } else {
                                return true;    
                            }
                        }else if (event.shiftKey){
                            event.preventDefault();
                        } else if ( (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ) ) {
                            event.preventDefault(); 
                        } else {
                            if ($(this).val() == "*") {
                                event.preventDefault();
                            }
                        }
                    }
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
                $('#woocommerce-pricing-rules-wrap').delegate('.float_pricing_rule', 'keydown', function(event) {  
                    // Allow only backspace, delete and tab
                    if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 190) {
                        // let it happen, don't do anything
                    }
                    else {
                        // Ensure that it is a number and stop the keypress
                        if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
                            event.preventDefault(); 
                        }   
                    }
                });
                                                                                                                                                                                                                                                                                                                                
                $("#woocommerce-pricing-rules-wrap").sortable(
                { 
                    handle: 'h4.first',
                    containment: 'parent',
                    axis:'y'
                });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
                function InsertRule(previousRowIndex, name) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                    var $index = $("#woocommerce-pricing-rules-table-" + name).data('lastindex') + 1;
                    $("#woocommerce-pricing-rules-table-" + name).data('lastindex', $index );
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                    var html = '';
                    html += '<tr id="pricing_rule_row_' + name + '_' + $index + '">';
                    html += '<td>';
                    html += '<input class="int_pricing_rule" id="pricing_rule_from_input_'  + name + '_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][from]" value="" /> ';
                    html += '</td>';
                    html += '<td>';
                    html += '<input class="int_pricing_rule" id="pricing_rule_to_input_' + name + '_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][to]" value="" /> ';
                    html += '</td>';
                    html += '<td>';
                    html += '<select id="pricing_rule_type_value_' + name + '_' + $index + '" name="pricing_rules[' + name + '][rules][' + $index + '][type]">';
                    html += '<option value="price_discount">Price Discount</option>';
                    html += '<option value="percentage_discount">Percentage Discount</option>';
                    html += '<option value="fixed_price">Fixed Price</option>';
                    html += '</select>';
                    html += '</td>';
                    html += '<td>';
                    html += '<input class="float_pricing_rule" id="pricing_rule_amount_input_' + $index + '" type="text" name="pricing_rules[' + name + '][rules][' + $index + '][amount]" value="" /> ';
                    html += '</td>';
                    html += '<td width="48">';
                    html += '<a data-index="' + $index + '" data-name="' + name + '" class="add_pricing_rule"><img  src="<?php echo $woocommerce_pricing->plugin_url() . '/assets/images/add.png'; ?>" title="add another rule" alt="add another rule" style="cursor:pointer; margin:0 3px;" /></a>';         
                    html += '<a data-index="' + $index + '" data-name="' + name + '" class="delete_pricing_rule"><img data-index="' + $index + '" src="<?php echo $woocommerce_pricing->plugin_url() . '/assets/images/remove.png'; ?>" title="remove rule" alt="remove rule" style="cursor:pointer; margin:0 3px;" /></a>';         
                    html += '</td>';
                    html += '</tr>';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                    $('#pricing_rule_row_' + name + '_' + previousRowIndex).after(html);
                    $('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).show();
                                                                                                                                                                                                                                                                                                                                            
                } 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
                function DeleteRule(index, name) {
                    if (confirm("Are you sure you would like to remove this price adjustment?")) {
                        $('#pricing_rule_row_' + name + '_' + index).remove();
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                        var $index = $('tbody tr', "#woocommerce-pricing-rules-table-" + name).length;
                        if ($index > 1) {
                            $('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).show();
                        } else {
                            $('.delete_pricing_rule', "#woocommerce-pricing-rules-table-" + name).hide();
                        }
                    }
                }
                                                                                                                                                                                                                                                                                                                                                
                function DeleteRuleSet(name) {
                    if (confirm('Are you sure you would like to remove this dynamic price set?')){
                        $('#woocommerce-pricing-ruleset-' + name ).slideUp().remove();  
                    }
                }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
            });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
        </script>
        <?php
    }

    public function meta_box_css() {
        ?>
        <style>
            #woocommerce-pricing-product div.section {
                margin-bottom: 10px;
            }

            #woocommerce-pricing-product label {
                display:block;
                font-weight: bold;
            }

            #woocommerce-pricing-product .list-column {
                float:left;
                margin-right:25px;
            }

            #dynamic_pricing_data {
                padding: 12px;
                margin: 0;
                overflow: hidden;
                zoom: 1;
            }

            #woocommerce-pricing-rules-wrap h4 {
                border-bottom: 1px solid #E5E5E5;
                padding-bottom: 6px;
                font-size: 1.2em;
                margin: 1em 0 1em;
                text-transform: none;
            }

            #woocommerce-pricing-rules-wrap h4.first {
                margin-top:0px;
                cursor:move;
            }

            #woocommerce-pricing-rules-wrap select {
                width:250px;
            }

            .woocommerce_pricing_ruleset {

                border-color:#dfdfdf;
                border-width:1px;
                border-style:solid;
                -moz-border-radius:3px;
                -khtml-border-radius:3px;
                -webkit-border-radius:3px;
                border-radius:3px;
                padding: 0;
                border-style:solid;
                border-spacing:0;
                background-color:#F9F9F9;
                margin-bottom: 12px;
            }

        </style>
        <?php
    }

    public function process_meta_box($post_id, $post) {
        $pricing_rules = array();
        $valid_rules = array();
        if (isset($_POST['pricing_rules'])) {
            $pricing_rule_sets = $_POST['pricing_rules'];
            foreach ($pricing_rule_sets as $key => $rule_set) {
                $valid = true;
                foreach ($rule_set['rules'] as $rule) {
                    if (isset($rule['to']) && isset($rule['from']) && isset($rule['amount'])) {
                        $valid = $valid & true;
                    } else {
                        $valid = $valid & false;
                    }
                }

                if ($valid) {
                    $valid_rules[$key] = $rule_set;
                }
            }

            update_post_meta($post_id, '_pricing_rules', $valid_rules);
        } else {
            delete_post_meta($post_id, '_pricing_rules');
        }
    }

}
?>
