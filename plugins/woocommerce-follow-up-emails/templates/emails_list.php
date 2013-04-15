    <style type="text/css">
    span.priority {
        display: inline;
        padding: 4px 7px;
        background: #EAF2FA;
        border-radius: 10px;
        border: 1px solid #ddd;
    }
    
    .fue_table_footer {
        position: relative;
        overflow: hidden;
        padding: 8px;
        background: #EAF2FA;
        border: #c7d7e2 solid 1px;
        border-top:0 none;
    }

    .fue_table_footer .order_message {
        background: url(<?php echo FUE_TEMPLATES_URL; ?>/images/drag_and_drop_to_reorder.png);
        width: 161px;
        height: 23px;
        float: left;
        margin-left: 20px;
    }
    </style>
    <form action="admin-post.php" method="post" id="update_priorities">
    <div class="subsubsub_section">
        <ul class="subsubsub">
            <li><a href="#generic" class="current"><?php _e('Storewide Emails', 'wc_followup_emails'); ?></a> | </li>
            <li><a href="#category_mails"><?php _e('Category Emails', 'wc_followup_emails'); ?></a> | </li>
            <li><a href="#product_mails"><?php _e('Product Emails', 'wc_followup_emails'); ?></a> | </li>
            <li><a href="#signup_mails"><?php _e('Sign Up Emails', 'wc_followup_emails'); ?></a></li>
            <?php do_action( 'fue_email_types_sub' ); ?>
        </ul>
        <br class="clear">
        
        <div class="section" id="generic">
            <h3><?php _e('Storewide Emails', 'wc_followup_emails'); ?></h3>
            
            <p class="description">Storewide emails will send to a buyer of any product within your store based upon the criteria you define when creating your emails. Below are the existing Storewide emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.</p><br />
    
            <table class="wp-list-table widefat fixed posts generic-table">
                <thead>
                    <tr>
                        <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="generic_always_send"><?php _e('Always Send', 'wc_followup_emails'); ?></th>
                        <!--<th scope="col" id="priority" class="manage-column column-priority" style=""><?php _e('Priority', 'wc_followup_emails'); ?></th>-->
                        <?php do_action( 'fue_table_all_products_head' ); ?>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php if (empty($generics)): ?>
                    <tr scope="row">
                        <th colspan="4"><?php _e('No emails available', 'wc_followup_emails'); ?></th>
                    </tr>
                    <?php
                    else:
                        $p = 0;
                        foreach ($generics as $email):
                        $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="generic_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php _e('Edit', 'wc_followup_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this entry?');" href="admin.php?page=wc-followup-emails&tab=delete&id=<?php echo $email->id; ?>"><?php _e('Delete', 'wc_followup_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            if ( $email->interval_duration != 'date' ) {
                                printf( __('%d %s after %s'), $email->interval_num, SFN_FollowUpEmails::get_duration($email->interval_duration), SFN_FollowUpEmails::get_trigger_name($email->interval_type) ); 
                            } else {
                                printf( __('Send on %s'), $email->send_date) ;
                            }
                            ?>
                        </td>
                        <td>
                        <?php echo $email->usage_count; ?>
                        </td>
                        <td>
                            <?php echo ($email->always_send == 1) ? __('Yes', 'wc_followup_emails') : __('No', 'wc_followup_emails'); ?>
                        </td>
                        <!--<td><input type="hidden" class="generic_priorities" name="priority[<?php echo $email->id; ?>]" value="<?php echo $email->priority; ?>" size="3" /></td>-->
                        <?php do_action( 'fue_table_all_products_body' ); ?>
                    </tr>
                    <?php
                        endforeach;
                    ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="fue_table_footer">
                <div class="order_message"></div>
            </div>
        </div>
        
        <div class="section" id="category_mails" style="display:none;">
            <h3><?php _e('Category Emails', 'wc_followup_emails'); ?></h3>
            
            <p class="description">Category emails will send to a buyer of products within the specific categories from your store based upon the criteria you define when creating your emails. Below are the existing Category emails set up for your store.  Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.</p><br />
            
            <table class="wp-list-table widefat fixed posts category-table">
                <thead>
                    <tr>
                        <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="products" class="manage-column column-products" style=""><?php _e('Category', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="category_always_send"><?php _e('Always Send', 'wc_followup_emails'); ?></th>
                        <!--<th scope="col" id="priority" class="manage-column column-priority" style=""><?php _e('Priority', 'wc_followup_emails'); ?></th>-->
                        <?php do_action( 'fue_table_category_head' ); ?>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php if (empty($categories)): ?>
                    <tr scope="row">
                        <th colspan="5"><?php _e('No emails available', 'wc_followup_emails'); ?></th>
                    </tr>
                    <?php
                    else:
                        $p = 0;
                        foreach ($categories as $email):
                        $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="category_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php _e('Edit', 'wc_followup_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this entry?');" href="admin.php?page=wc-followup-emails&tab=delete&id=<?php echo $email->id; ?>"><?php _e('Delete', 'wc_followup_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ( $email->interval_duration != 'date' ) {
                                printf( __('%d %s after %s'), $email->interval_num, SFN_FollowUpEmails::get_duration($email->interval_duration), SFN_FollowUpEmails::get_trigger_name($email->interval_type) ); 
                            } else {
                                printf( __('Send on %s'), $email->send_date) ;
                            }
                            ?>
                        </td>
                        <td>
                        <?php if (empty($email->category_id)): ?>
                        -
                        <?php 
                        else: 
                            $cat = get_term_by('id', $email->category_id, 'product_cat');
                            echo '<a href="edit.php?s&post_status=all&post_type=product&action=-1&m=0&product_cat='. $cat->slug .'&product_type&product_subtype&paged=1&mode=list&action2=-1">'. $cat->name .'</a>';
                        endif;
                        ?>
                        </td>
                        <td>
                        <?php echo $email->usage_count; ?>
                        </td>
                        <td>
                            <?php echo ($email->always_send == 1) ? __('Yes', 'wc_followup_emails') : __('No', 'wc_followup_emails'); ?>
                        </td>
                        <?php do_action( 'fue_table_category_body' ); ?>
                    </tr>
                    <?php
                        endforeach;
                    ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="fue_table_footer">
                <div class="order_message"></div>
            </div>
        </div>
        
        <div class="section" id="product_mails" style="display:none;">
            <h3><?php _e('Product Emails', 'wc_followup_emails'); ?></h3>
            
            <p class="description">Product emails will send to a buyer of products from your store based upon the criteria you define when creating your emails. Below are the existing Product emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.</p><br />
            
            <table class="wp-list-table widefat fixed posts product-table">
                <thead>
                    <tr>
                        <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="products" class="manage-column column-products" style=""><?php _e('Product', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="product_always_send"><?php _e('Always Send', 'wc_followup_emails'); ?></th>
                        <!--<th scope="col" id="priority" class="manage-column column-priority" style=""><?php _e('Priority', 'wc_followup_emails'); ?></th>-->
                        <?php do_action( 'fue_table_product_head' ); ?>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php if (empty($products)): ?>
                    <tr scope="row">
                        <th colspan="5"><?php _e('No emails available', 'wc_followup_emails'); ?></th>
                    </tr>
                    <?php
                    else:
                        $p = 0;
                        foreach ($products as $email):
                        $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="product_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php _e('Edit', 'wc_followup_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this entry?');" href="admin.php?page=wc-followup-emails&tab=delete&id=<?php echo $email->id; ?>"><?php _e('Delete', 'wc_followup_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            if ( $email->interval_duration != 'date' ) {
                                printf( __('%d %s after %s'), $email->interval_num, SFN_FollowUpEmails::get_duration($email->interval_duration), SFN_FollowUpEmails::get_trigger_name($email->interval_type) ); 
                            } else {
                                printf( __('Send on %s'), $email->send_date) ;
                            }
                            ?>
                        </td>
                        <td>
                        <?php if (empty($email->product_id)): ?>
                        -
                        <?php 
                        else: 
                            $product = get_the_title($email->product_id);
                            echo '<a href="post.php?post='. $email->product_id .'&action=edit">'. $product .'</a>';
                        endif;
                        ?>
                        </td>
                        <td>
                        <?php echo $email->usage_count; ?>
                        </td>
                        <td>
                            <?php echo ($email->always_send == 1) ? __('Yes', 'wc_followup_emails') : __('No', 'wc_followup_emails'); ?>
                        </td>
                        <?php do_action( 'fue_table_product_body' ); ?>
                    </tr>
                    <?php
                        endforeach;
                    ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="fue_table_footer">
                <div class="order_message"></div>
            </div>
        </div>
        
        <div class="section" id="signup_mails" style="display:none;">
            <h3><?php _e('Sign Up Emails', 'wc_followup_emails'); ?></h3>
            
            <p class="description">Sign up emails will send to a new user in your store based upon the criteria you define when creating your emails. Below are the existing Sign up emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.</p><br />
    
            <table class="wp-list-table widefat fixed posts signup-table">
                <thead>
                    <tr>
                        <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'wc_followup_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'wc_followup_emails'); ?></th>
                        <?php do_action( 'fue_table_signups_head' ); ?>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php if (empty($signups)): ?>
                    <tr scope="row">
                        <th colspan="4"><?php _e('No emails available', 'wc_followup_emails'); ?></th>
                    </tr>
                    <?php
                    else:
                        $p = 0;
                        foreach ($signups as $email):
                        $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="signup_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=wc-followup-emails&tab=edit&id=<?php echo $email->id; ?>"><?php _e('Edit', 'wc_followup_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this entry?');" href="admin.php?page=wc-followup-emails&tab=delete&id=<?php echo $email->id; ?>"><?php _e('Delete', 'wc_followup_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            if ( $email->interval_duration != 'date' ) {
                                printf( __('%d %s after user signs up'), $email->interval_num, SFN_FollowUpEmails::get_duration($email->interval_duration) ); 
                            } else {
                                printf( __('Send on %s'), $email->send_date) ;
                            }
                            ?>
                        </td>
                        <td>
                        <?php echo $email->usage_count; ?>
                        </td>
                        <?php do_action( 'fue_table_signups_body' ); ?>
                    </tr>
                    <?php
                        endforeach;
                    ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="fue_table_footer">
                <div class="order_message"></div>
            </div>
        </div>
        
        <?php do_action('fue_email_types_section'); ?>
    </div>
    
    <p class="submit">
        <input type="hidden" name="action" value="sfn_followup_save_priorities" />
        <input type="submit" name="save" value="<?php _e('Update Priorities', 'wc_followup_emails'); ?>" class="button-primary" />
    </p>
    </form>
    
    <script type="text/javascript">
    jQuery(window).load(function(){
        // Subsubsub tabs
        jQuery('div.subsubsub_section ul.subsubsub li a:eq(0)').addClass('current');
        jQuery('div.subsubsub_section .section:gt(0)').hide();
        
        jQuery('div.subsubsub_section ul.subsubsub li a').click(function(){
            var $clicked = jQuery(this);
            var $section = $clicked.closest('.subsubsub_section');
            var $target  = $clicked.attr('href');
            
            $section.find('a').removeClass('current');
            
            if ( $section.find('.section:visible').size() > 0 ) {
                $section.find('.section:visible').fadeOut( 100, function() {
                    $section.find( $target ).fadeIn('fast');
                });
            } else {
                $section.find( $target ).fadeIn('fast');
            }
            
            $clicked.addClass('current');
            jQuery('#last_tab').val( $target );

            return false;
        });
        
        var url_hash = window.location.hash;
        if (url_hash != "") {
            jQuery("a[href="+ url_hash +"]").click();
        }
        
        // Sorting
        jQuery('table.product-table tbody, table.category-table tbody, table.generic-table tbody, table.signup-table tbody').sortable({
            items:'tr',
            cursor:'move',
            axis:'y',
            handle: 'td',
            scrollSensitivity:40,
            helper:function(e,ui){
                ui.children().each(function(){
                    jQuery(this).width(jQuery(this).width());
                });
                ui.css('left', '0');
                return ui;
            },
            start:function(event,ui){
                ui.item.css('background-color','#f6f6f6');
            },
            stop:function(event,ui){
                ui.item.removeAttr('style');
                update_priorities();
            }
        });
        <?php do_action('email_types_script'); ?>
    });
    function update_priorities() {
        jQuery('.product-table tbody tr').each(function(x){
            jQuery(this).find('td .priority').html(x+1);
        });
        
        jQuery('.category-table tbody tr').each(function(x){
            jQuery(this).find('td .priority').html(x+1);
        });
        
        jQuery('.generic-table tbody tr').each(function(x){
            jQuery(this).find('td .priority').html(x+1);
        });
        
        jQuery('.signup-table tbody tr').each(function(x){
            jQuery(this).find('td .priority').html(x+1);
        });
        <?php do_action('email_types_update_priorities_script'); ?>
    }
    </script>