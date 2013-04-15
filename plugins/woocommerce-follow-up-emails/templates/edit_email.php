<?php
$defaults = apply_filters( 'fue_edit_email_defaults', array(
    'type'              => $email->email_type,
    'always_send'       => $email->always_send,
    'name'              => $email->name,
    'interval'          => $email->interval_num,
    'interval_duration' => $email->interval_duration,
    'interval_type'     => $email->interval_type,
    'send_date'         => $email->send_date,
    'product_id'        => $email->product_id,
    'category_id'       => $email->category_id,
    'subject'           => $email->subject,
    'message'           => $email->message,
    'tracking_on'       => (!empty($email->tracking_code)) ? 1 : 0,
    'tracking'          => $email->tracking_code,
), $email);

// if type is date, switch columns
if ( $defaults['interval_type'] == 'date' ) {
    $defaults['interval_type'] = $defaults['interval_duration'];
    $defaults['interval_duration'] = 'date';
}

if ( isset($_POST) && !empty($_POST) ) {
    $defaults = array_merge( $defaults, $_POST );
}
?>
    <form action="admin-post.php" method="post" id="sfn_form">
        <h3><?php _e('Edit Follow-Up Email', 'wc_followup_emails'); ?></h3>
        
        <table class="form-table">
            <tbody>
                <tr valign="top" class="email_type_tr hideable">
                    <th scope="row" style="width:250px;" class="email_type_th">
                        <label for="email_type"><?php _e('Email Type:', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="email_type_td">
                        <select name="email_type" id="email_type" class="email_type_select hideable">
                            <?php
                            $types = SFN_FollowUpEmails::get_email_types();
                            
                            foreach ( $types as $key => $value ):
                                $selected = ($defaults['type'] == $key) ? 'selected' : '';
                            ?>
                            <option class="email_type_option email_type_option_<?php echo $key; ?>" value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr valign="top" class="always_send_tr hideable non-signup generic <?php do_action('fue_form_always_send_tr_class', $defaults); ?>">
                    <th scope="row" class="always_send_th">
                        <label for="always_send"><?php _e('Always Send', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="always_send_td">
                        <input type="checkbox" name="always_send" id="always_send" value="1" <?php if ($defaults['always_send'] == 1) echo 'checked'; ?> /> (<em>Use this setting carefully, as this setting could result in multiple emails being sent per order</em>)
                    </td>
                </tr>
                
                <tr valign="top" class="name_tr">
                    <th scope="row" class="name_th">
                        <label for="name"><?php _e('Name', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="name_td">
                        <input type="text" name="name" id="name" value="<?php echo esc_attr($defaults['name']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr valign="top" class="interval_tr hideable">
                    <th scope="row" class="interval_th">
                        <label for="interval_type"><?php _e('Interval', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="interval_td">
                        <span class="hide-if-date interval_span hideable">
                            <input type="text" name="interval" id="interval" value="<?php echo esc_attr($defaults['interval']); ?>" size="2" />
                        </span>
                        <select name="interval_duration" id="interval_duration" class="interval_duration hideable">
                            <?php
                            $durations = SFN_FollowUpEmails::get_durations();
                            
                            foreach ( $durations as $key => $value ):
                                $selected = ($defaults['interval_duration'] == $key) ? 'selected' : '';
                            ?>
                            <option class="interval_duration_<?php echo $key; ?> hideable" value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="description signup signup_description hideable"><?php _e('after user signs up', 'wc_followup_emails'); ?></span>
                        <span class="hide-if-date non-signup interval_type_span hideable">
                            &nbsp;
                            <span class="description interval_type_after_span hideable"><?php _e('after', 'wc_followup_emails'); ?></span>
                            &nbsp;
                            <select name="interval_type" id="interval_type" class="interval_type hideable">
                                <?php
                                $triggers = SFN_FollowUpEmails::get_trigger_types();
                                
                                foreach ( $triggers as $key => $value ):
                                    $selected = ($defaults['interval_type'] == $key) ? 'selected' : '';
                                ?>
                                <option class="interval_type_option interval_type_<?php echo $key; ?> hideable <?php do_action('fue_form_interval_type', $key); ?>" value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                        <span class="show-if-date interval_date_span hideable">
                            <input type="text" name="send_date" class="date" value="<?php echo esc_attr($defaults['send_date']); ?>" readonly />
                        </span>
                    </td>
                </tr>
                
                <tr valign="top" class="non-generic non-signup hideable <?php do_action('fue_form_product_description_tr_class', $defaults); ?> product_description_tr">
                    <th scope="row" colspan="2" class="product_description_th">
                    <strong><?php _e('Select the product that, when bought or added to the cart, will trigger this follow-up email.', 'wc_followup_emails'); ?></strong>
                    </th>
                </tr>
                
                <tr valign="top" class="non-generic non-signup hideable <?php do_action('fue_form_product_tr_class', $defaults); ?> product_tr">
                    <th scope="row" class="product_th">
                        <label for="product_ids"><?php _e('Product', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="product_td">
                        <select id="product_id" name="product_id" class="ajax_chosen_select_products_and_variations" multiple data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" style="width: 400px">
                            <?php if ($product !== false): ?>
                            <option selected value="<?php echo $product->id; ?>"><?php echo esc_attr(get_the_title($product->id)) .' &ndash; #'. $product->id; ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                
                <tr valign="top" class="non-generic non-signup hideable <?php do_action('fue_form_category_tr_class', $defaults); ?> category_tr">
                    <th scope="row" class="category_th">
                        <label for="category_id"><?php _e('Category', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="category_td">
                        <select id="category_id" name="category_id" class="chzn-select" data-placeholder="<?php _e('Search for a category&hellip;', 'wc_followup_emails'); ?>" style="width: 400px;">
                            <option value="0" <?php echo ($email->category_id == 0) ? 'selected' : ''; ?>><?php _e('Select a category', 'wc_followup_emails'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php _e($category->term_id); ?>" <?php echo ($defaults['category_id'] == $category->term_id) ? 'selected' : ''; ?>><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr valign="top" class="non-generic non-signup hideable <?php do_action('fue_form_custom_field_tr_class', $defaults); ?> use_custom_field_tr">
                    <th scope="row" class="use_custom_field_th">
                        <label for="use_custom_field"><?php _e('Use Custom Field', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="use_custom_field_td">
                        <input type="checkbox" name="use_custom_field" value="1" id="use_custom_field" />
                    </td>
                </tr>
                
                <tr valign="top" class="show-if-custom-field custom_field_tr">
                    <th scope="row" class="custom_field_th">
                        <label for="cf_product"><?php _e('Select the product and custom field to use', 'wc_followup_emails'); ?></label>
                    </th>
                    <td class="custom_field_td">
                        <div class="if-product-selected custom_field_select_div">
                            <select name="custom_fields" id="custom_fields">
                                <option><?php _e('Select a product first.', 'wc_followup_emails'); ?></option>
                            </select>
                            <span class="show-if-cf-selected"><input type="text" readonly onclick="jQuery(this).select();" value="" size="25" id="custom_field" /></span>
                        </div>
                        <div class="if-no-product-selected custom_field_error_div">
                            <p><?php _e('Please select a product first', 'wc_followup_emails'); ?></p>
                        </div>
                    </td>
                </tr>
                
                <?php do_action( 'fue_edit_email_form_before_message', $defaults, $email ); ?>
                
                <tr valign="top">
                    <th scope="row">
                        <label for="subject"><?php _e('Email Subject', 'wc_followup_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="subject" id="subject" value="<?php echo esc_attr($defaults['subject']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">
                        <label for="message"><?php _e('Email Body', 'wc_followup_emails'); ?></label>
                        <br />
                        <span class="description">
                            <?php _e('You may use the following variables in the Email Subject and Body', 'wc_followup_emails'); ?>
                            <ul>
                                <?php do_action('fue_email_variables_list'); ?>
                                <li class="var hideable var_customer_first_name"><strong>{customer_first_name}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_name"><strong>{customer_name}</strong> <img class="help_tip" title="<?php _e('The full name of the customer who purchased from your store.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_url"><strong>{store_url}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_name"><strong>{store_name}</strong> <img class="help_tip" title="<?php _e('The name of your store.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_item_name non-generic non-signup"><strong>{item_name}</strong> <img class="help_tip" title="<?php _e('The name of the purchased item.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_item_category non-generic non-signup"><strong>{item_category}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased item is under.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_item_names generic non-signup"><strong>{item_names}</strong> <img class="help_tip" title="<?php _e('Displays a list of purchased items.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_item_categories generic non-signup"><strong>{item_categories}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased items are under.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_order_number not-cart non-signup"><strong>{order_number}</strong> <img class="help_tip" title="<?php _e('The generated Order Number for the puchase', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_order_datetime not-cart non-signup"><strong>{order_datetime}</strong> <img class="help_tip" title="<?php _e('The date and time that the order was made', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_unsubscribe_url"><strong>{unsubscribe_url}</strong> <img class="help_tip" title="<?php _e('URL where users will be able to opt-out of the email list.', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
                            </ul>
                        </span>
                    </th>
                    <td>
                        <div id="poststuff">
                        <?php wp_editor($defaults['message'], 'message', array('textarea_rows' => 10, 'teeny' => true)); ?>
                        </div>
                    </td>
                </tr>
                
                <?php do_action('fue_edit_email_form_after_message', $defaults); ?>
                
                <tr>
                    <th scope="row">
                        <label for="tracking_on"><?php _e('Add Google Analytics tracking to links', 'wc_followup_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="tracking_on" id="tracking_on" value="1" <?php if ($defaults['tracking_on'] == 1) echo 'checked'; ?> />
                    </td>
                </tr>
                <tr class="tracking_on">
                    <th scope="row">
                        <label for="tracking"><?php _e('Link Tracking', 'wc_followup_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="tracking" id="tracking" value="<?php echo esc_attr($defaults['tracking']); ?>" placeholder="e.g. utm_campaign=Follow-up-Emails-by-75nineteen" size="40" />
                        <p class="description">
                            <?php _e('The value inserted here will be appended to all URLs in the Email Body', 'wc_followup_emails'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="test_email"><strong>Send a test email</strong></label>
                    </th>
                    <td>
                        <input type="text" id="test_email" placeholder="Email Address" value="" />
                        <input type="button" id="test_send" value="<?php _e('Send Email', 'wc_followup_emails'); ?>" class="button" />
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="hidden" name="action" value="sfn_followup_edit" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
            <input type="submit" name="save" id="save" value="<?php _e('Update Follow-Up Email', 'wc_followup_emails'); ?>" class="button-primary" />
        </p>
    </form>
    <script type="text/javascript">
    var interval_types = <?php echo json_encode(SFN_FollowUpEmails::get_trigger_types()); ?>;
    jQuery(document).ready(function() {
        var sfn_checked = false;
        jQuery("select.ajax_chosen_select_products_and_variations").change(function() {
            // remove the first option to limit to only 1 product per email
            if (jQuery(this).find("option:selected").length > 1) {
                while (jQuery(this).find("option:selected").length > 1) {
                    jQuery(jQuery(this).find("option:selected")[0]).remove();
                }
                
                jQuery(this).trigger("liszt:updated");
            }
            jQuery("#use_custom_field").change();
        });
        jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
            method: 	'GET',
            url: 		ajaxurl,
            dataType: 	'json',
            afterTypeDelay: 100,
            data:		{
                action: 		'woocommerce_json_search_products_and_variations',
                security: 		'<?php echo wp_create_nonce("search-products"); ?>'
            }
        }, function (data) {
            var terms = {};
            
            jQuery.each(data, function (i, val) {
                terms[i] = val;
            });
        
            return terms;
        });
        jQuery("select.chzn-select").chosen();
        jQuery("#test_send").click(function() {
            var data = {
                'action':       'sfn_test_email',
                'type':         jQuery("#email_type").val(),
                'subject':      jQuery("#subject").val(),
                'message':      (tinyMCE.get('message')) ? tinyMCE.get('message').getContent() : jQuery("#message").val(),
                'email':        jQuery("#test_email").val(),
                'tracking':     jQuery("#tracking").val()
            };
            
            jQuery.post(ajaxurl, data, function() {
                alert("Email sent!");
            });
        });
        jQuery(".help_tip").tipTip();
        
        jQuery("#email_type").live("change", function() {
            var val = jQuery(this).val();
            reset_elements();
            
            if (val == "generic") {
                var show = ['.always_send_tr', '.var_item_names', '.var_item_categories', '.interval_type_option', '.interval_type_span', '.var'];
                var hide = ['.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category'];
                
                <?php do_action('fue_form_email_generic_show_js'); ?>
                
                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }
                
                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
            
            if (val == "normal") {
                var show = ['.always_send_tr', '.interval_type_option', '.interval_type_span', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var'];
                var hide = ['.var_item_names', '.var_item_categories', '.signup_description'];
                <?php do_action('fue_form_email_normal_show_js'); ?>
                
                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }
                
                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
            
            if (val == "signup") {
                var show = ['.interval_type_option', '.signup_description', '.var_item_name', '.var_item_category', '.var'];
                var hide = ['.always_send_tr', '.interval_type_span', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_order_number', '.var_order_datetime'];
                <?php do_action('fue_form_email_signup_show_js'); ?>
                
                
                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            } 
            
            if (jQuery("#interval_duration").val() == "date") {
                jQuery(".hide-if-date").hide(); 
                jQuery(".show-if-date").show();
            } else {
                jQuery(".hide-if-date").show();
                jQuery(".show-if-date").hide();
                
                if (val == "signup") {
                    jQuery(".interval_type_span").hide();
                }
            }
            
            <?php do_action('fue_email_type_change', $defaults); ?>
        }).change();
        
        jQuery("#tracking_on").change(function() {
            if (jQuery(this).attr("checked")) {
                jQuery(".tracking_on").show();
            } else {
                jQuery(".tracking_on").hide();
            }
        }).change();
        
        jQuery("#interval_type").change(function() {
            if (jQuery(this).val() != "cart") {
                jQuery(".not-cart").show();
            } else {
                jQuery(".not-cart").hide();
            }
        }).change();
        
        jQuery("#interval_duration").change(function() {
            if (jQuery(this).val() == "date") {
                jQuery(".hide-if-date").hide(); 
                jQuery(".show-if-date").show();
            } else {
                jQuery(".hide-if-date").show();
                jQuery(".show-if-date").hide();
            }
            
            jQuery("#email_type").change();
        }).change();
        
        jQuery(".date").datepicker();
        
        jQuery("#use_custom_field").change(function() {
            if (jQuery(this).attr("checked")) {
                jQuery(".show-if-custom-field").show();
                
                if (jQuery("#product_id option:selected").length == 1) {
                    jQuery(".if-product-selected").show();
                    jQuery(".if-no-product-selected").hide();
                    
                    var selected = jQuery("#product_id option:selected");
                    if (selected.length == 1) {
                        // load custom fields
                        var select  = jQuery("#custom_fields");
                        
                        jQuery(".show-if-cf-product-selected").show();
                        jQuery(select).html("<option>Loading data...</option>");
                        
                        var data = {
                            'action'    : 'fue_get_custom_fields',
                            'id'        : jQuery(selected).val()
                        };
                        jQuery.post(ajaxurl, data, function(resp) {
                            var json    = jQuery.parseJSON(resp);
                            jQuery(select).html("");
                            
                            var options = '';
                            for (obj in json) {
                                options += '<option value="'+ obj +'">'+ obj +'</option>';
                            }
                            jQuery(select).html(options);
                            jQuery("#custom_fields").change();
                        });
                    } else {
                        jQuery(".show-if-cf-product-selected").hide();
                    }
                } else {
                    jQuery(".if-product-selected").hide();
                    jQuery(".if-no-product-selected").show();
                }
            } else {
                jQuery(".show-if-custom-field").hide();
            }
        }).change();
        
        jQuery("#custom_fields").change(function() {
            if (jQuery(this).val() == "Select a product first.") return;
            jQuery(".show-if-cf-selected").show();
            jQuery("#custom_field").val("{cf "+ jQuery("#product_id option:selected").val() +" "+ jQuery(this).val() +"}");
        }).change();
        
        jQuery("#sfn_form").submit(function(e) {
            if (sfn_checked == false) {
                jQuery("#save")
                    .val("<?php _e('Processing request...', 'wc_followup_emails'); ?>")
                    .attr("disabled", true);
                
                var data = {
                    'action'            : 'sfn_fe_find_dupes',
                    'id'                : jQuery("#id").val(),
                    'type'              : jQuery("#email_type").val(),
                    'interval'          : jQuery("#interval").val(),
                    'interval_duration' : jQuery("#interval_duration").val(),
                    'interval_type'     : jQuery("#interval_type").val(),
                    'product_id'        : jQuery("#product_id").val(),
                    'category_id'       : jQuery("#category_id").val()
                };
                jQuery.post(ajaxurl, data, function(resp) {
                    jQuery(".sfn-error").remove();
                    if (resp == "DUPE") {
                        jQuery('<div class="message error sfn-error"><p><?php _e('A follow-up email with the same settings already exists.', 'wc_followup_emails'); ?></p></div>').insertAfter("#sfn_form h3");
                        
                        jQuery('html, body').animate({
                             scrollTop: jQuery(jQuery(".sfn-error")[0]).offset().top-50
                         }, 1000);
                    } else if (resp == "SIMILAR") {
                        //jQuery('<div class="message error sfn-error"><p><?php _e('A similar follow-up email already exists. Do you wish to continue?', 'wc_followup_emails'); ?></p></div>').insertAfter("#sfn_form h3");
                        
                        if (confirm("<?php _e('A similar follow-up email already exists. Do you wish to continue?', 'wc_followup_emails'); ?>")) {
                            sfn_checked = true;
                            jQuery("#sfn_form").submit();
                        }
                    } else {
                        sfn_checked = true;
                        jQuery("#sfn_form").submit();
                    }
                    
                    jQuery("#save")
                        .val("<?php _e('Update Follow-Up Email', 'wc_followup_emails'); ?>")
                        .attr("disabled", false)
                });
                return false;
            }
            return true;
        });
        <?php do_action('fue_edit_email_form_script', $email); ?>
    });
    function reset_elements() {
        jQuery(".hideable").show();

        var trigger = jQuery("#interval_type").val();

        jQuery("#interval_type option").remove();
        for (key in interval_types) {
            jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ key +'" id="interval_type_option_'+ key +'" value="'+ key +'">'+ interval_types[key] +'</option>');
        }

        if (trigger) {
            jQuery("#interval_type_option_"+trigger).attr("selected", true);
        }
    }
    </script>