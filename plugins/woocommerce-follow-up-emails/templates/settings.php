    <form action="admin-post.php" method="post">
        <h3><?php _e('Unsubscribe Page', 'wc_followup_emails'); ?></h3>
        
        <p>
            <label for="unsubscribe_page"><?php _e('Select Unsubscribe Page', 'wc_followup_emails'); ?></label>
            <select name="unsubscribe_page" id="unsubscribe_page">
                <?php
                foreach ($pages as $p):
                    $sel = ($p->ID == $page) ? 'selected' : '';
                ?>
                <option value="<?php echo esc_attr($p->ID); ?>" <?php echo $sel; ?>><?php echo esc_html($p->post_title); ?></option>
                <?php endforeach; ?>
            </select>
            <a href="post.php?post=<?php echo $page; ?>&action=edit"><?php _e('Edit Unsubscribe Page', 'wc_followup_emails'); ?></a>
        </p>
        
        <h3><?php _e('Extend Follow-up Emails', 'wc_followup_emails'); ?></h3>
        
        <p><?php _e('Reminder emails will send to a buyer within your store based upon the criteria you define when creating your emails based upon the quantity they have purchased. For example, if a user buys four widgets, you can set up a reminder email that will send every 30 days as a <strong>reminder</strong> to change their widget, for a total of four emails over 90 days (Day 1, 30, 60, 90). Reminder emails utilize three new variables to allow you to define text for the first email, the subsequent emails in the series, and then for the final email.', 'wc_followup_emails'); ?></p>
        <p><a href="http://www.75nineteen.com/woocommerce/follow-up-email-autoresponder/?utm_source=WooCommerce&utm_medium=Link&utm_campaign=FUE" target="_blank"><?php _e('Get Reminder Emails', 'wc_followup_emails'); ?></a>
        </p> 
                
        <h3><?php _e('Ensure Emails are Delivered on Schedule', 'wc_followup_emails'); ?></h3>
        
        <p><?php _e('Follow-up Emails rely on a function called WP-Cron, and this function only runs when there is a page requested. So, if there are no visits to your website, then the scheduled jobs are not run. WP-Cron is not the same as the Unix cron scheduler. The key distinction lies in how it is run; unlike a scheduled background process, WP-Cron kicks in every time a visitor opens your WordPress-powered site. As such, it will remain imprecise in terms of timing the sending of your email follow-ups.', 'wc_followup_emails'); ?></p>
        <p><?php _e('If you have a schedule of emails to be sent, and no visits are made to your website, the queue will not be processed, and no emails will be sent. But, there are solutions. The link below contains the best set of instructions for fixing this issue, and ensuring your emails send as scheduled.', 'wc_followup_emails'); ?></p>
        <p><a href="http://wpdailybits.com/blog/replace-wordpress-cron-with-real-cron-job/74" target="_blank"><?php _e('Replace WPCron with real cron jobs.', 'wc_followup_emails'); ?></a>
        </p>       
        
        <?php do_action('fue_settings_form'); ?>
        
        <p class="submit">
            <input type="hidden" name="action" value="sfn_followup_save_settings" />
            <input type="submit" name="save" value="<?php _e('Save Settings', 'wc_followup_emails'); ?>" class="button-primary" />
        </p>
    </form>