<?php

class FUE_Reports {

    public $license_product = 'reports';
    public $platform        = 'woocommerce';
    
    public function __construct() {
        add_action( 'fue_activate_'. basename(__FILE__), array(&$this, 'activated') );
        add_action( 'fue_deactivate_'. basename(__FILE__), array(&$this, 'deactivated') );
        $this->init();
    }
    
    public function init() {
        add_action( 'fue_settings_tabs', array(&$this, 'settings_tab') );
        add_action( 'fue_settings_tab_controller', array(&$this, 'settings_tab_controller') );
        
        // email open tracking
        add_action( 'init', array(&$this, 'pixel_tracker') );
        
        // email tracking
        add_filter('query_vars', array(&$this, 'query_vars'));
        add_action('template_redirect', array(&$this, 'template_redirect'));
        
        // forms
        add_action('fue_new_email_form_after_message', array(&$this, 'form_after_message'));
        add_action('fue_edit_email_form_after_message', array(&$this, 'form_after_message'));
        
        // sending
        add_filter('fue_email_message', array(&$this, 'email_message'), 10, 3);
        
        // log emails sent
        add_action( 'fue_email_sent_details', array(&$this, 'emails_sent'), 10, 6 );
    }
    
    public function activated() {
        global $wpdb;
        
    }
    
    public function deactivated() {
        
    }
    
    public static function is_installed() {
        return true;
    }
    
    public static function is_licensed() {
        return true;
    }
    
    public function settings_tab($tab = '') {
        $tabs   = array('reports', 'reportview', 'reportuser_view', 'emailopen_view', 'linkclick_view');
        $class  = (in_array($tab, $tabs)) ? 'nav-tab-active' : '';
        echo '<a href="admin.php?page=wc-followup-emails&amp;tab=reports" class="nav-tab '. $class .'">'. __('Reporting', 'wc_followup_emails') .'</a>';
    }
    
    public function settings_tab_controller($tab) {
        if ($tab == 'reports') {
            fue_settings_header($tab);
            echo FUE_Reports::settings_main();
            fue_settings_footer();
        } elseif ($tab == 'reportview') {
            fue_settings_header($tab);
            echo FUE_Reports::reportview_html();
            fue_settings_footer();
        } elseif ($tab == 'reportuser_view') {
            fue_settings_header($tab);
            echo FUE_Reports::user_view_html();
            fue_settings_footer();
        } elseif ($tab == 'emailopen_view') {
            fue_settings_header($tab);
            echo FUE_Reports::email_open_html($_GET['eid'], $_GET['ename']);
            fue_settings_footer();
        } elseif ($tab == 'linkclick_view') {
            fue_settings_header($tab);
            echo FUE_Reports::link_click_html($_GET['eid'], $_GET['ename']);
            fue_settings_footer();
        }
    }
    
    public function emails_sent( $email_order, $user_id, $email, $email_to, $cname, $trigger ) {
        global $wpdb;
        
        // load order and email row
        $email  = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );
        
        FUE_Reports::email_log($email->id, $email_order->id, $user_id, $email->name, $cname, $email_to, $email_order->order_id, $email_order->product_id, $trigger);
    }
    
    function query_vars($vars) {
        $vars[] = 'sfn_trk';
        $vars[] = 'sfn_payload';
        return $vars;
    }
    
    function template_redirect() {
        global $wpdb;
        
        if ( intval(get_query_var('sfn_trk')) == 1 && get_query_var('sfn_payload') ) {
            $payload = base64_decode(get_query_var('sfn_payload'));
            
            $parsed = array();
            parse_str($payload, $parsed);
            
            if ( ! is_array($parsed) || count($parsed) < 3 ) return;
            
            // log this
            $insert = array(
                'event_type'    => 'click',
                'email_order_id'=> isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'      => $parsed['eid'],
                'user_id'       => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'    => $parsed['user_email'],
                'target_url'    => $parsed['next'],
                'date_added'    => date('Y-m-d H:i:s')
            );
            $wpdb->insert($wpdb->prefix .'followup_email_tracking', $insert);
            
            wp_redirect( add_query_arg('fueid', $parsed['eid'], $parsed['next']) );
            exit;
        }
    }
    
    public function pixel_tracker() {
        global $wpdb;
        
        if ( isset($_GET['fuepx']) && $_GET['fuepx'] == 1 ) {
            if (!isset($_GET['data'])||empty($_GET['data'])) return;
            
            header("Content-Type: image/gif");

            $data   = base64_decode($_GET['data']);
            $parsed = array();
            parse_str($data, $parsed);
            
            // log this
            $insert = array(
                'event_type'    => 'open',
                'email_order_id'=> isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'      => (int)$parsed['eid'],
                'user_id'       => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'    => $parsed['user_email'],
                'target_url'    => '',
                'date_added'    => date('Y-m-d H:i:s')
            );
            $wpdb->insert($wpdb->prefix .'followup_email_tracking', $insert);
        }
    }
    
    public function form_after_message() {
        
    }
    
    public function email_message( $message, $email, $email_order ) {
        global $wpdb;
        
        $user_id = 0;
        if ( $email_order->order_id != 0 ) {
            // order
            $order = new WC_Order($email_order->order_id);
            
            if ( isset($order->user_id) && $order->user_id > 0 ) {
                $user_id    = $order->user_id;
                $wp_user    = new WP_User( $order->user_id );
                $email_to   = $wp_user->user_email;
            } else {
                $email_to   = $order->billing_email;
            }
        } else {
            $order      = false;
            $wp_user    = new WP_User( $email_order->user_id );
            $user_id    = $email_order->user_id;
            $email_to   = $wp_user->user_email;
        }
        
        $email  = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );
        
        $qstring    = base64_encode('oid='. $email_order->id .'&eid='. $email->id .'&user_email='. $email_to .'&user_id='. $user_id);
        $px_url     = add_query_arg('fuepx', 1, add_query_arg('data', $qstring, site_url()));
        $message    .= '<img src="'. $px_url .'" height="1" width="1" />';
        
        return $message;
    }
    
    public static function email_log($id, $email_order_id, $user_id, $name, $cname, $mail_to, $order_id, $product_id, $trigger = '') {
        global $wpdb;
        
        $log = array(
            'email_id'      => $id,
            'email_order_id'=> $email_order_id,
            'user_id'       => $user_id,
            'email_name'    => $name,
            'customer_name' => $cname,
            'email_address' => $mail_to,
            'date_sent'     => date('Y-m-d H:i:s'),
            'order_id'      => $order_id,
            'user_id'       => $user_id,
            'product_id'    => $product_id,
            'email_trigger' => $trigger
        );
        $wpdb->insert( $wpdb->prefix .'followup_email_logs', $log );
    }
    
    static function settings_main() {
        echo FUE_Reports::reports_html();
    }
    
    static function reports_html() {
        global $wpdb, $woocommerce;
        
        // coupons sorting
        $sort['sortby'] = 'date_sent';
        $sort['sort']   = 'desc';
        
        if ( isset($_GET['sortby']) && !empty($_GET['sortby']) ) {
            $valid = array('date_sent', 'email_address', 'coupon_used');
            if ( in_array($_GET['sortby'], $valid) ) {
                $sort['sortby'] = $_GET['sortby'];
                $sort['sort']   = (isset($_GET['sort']) && $_GET['sort'] == 'asc') ? 'asc' : 'desc';
            }
        }
        
        $email_reports  = FUE_Reports::get_reports(array('type' => 'emails'));
        $user_reports   = FUE_Reports::get_reports(array('type' => 'users'));
        $coupon_reports = FUE_Reports::get_reports(array('type' => 'coupons', 'sort' => $sort));
        $exclude_reports= FUE_Reports::get_reports(array('type' => 'excludes'));
        
        $emails_block   = '';
        $coupons_block  = '';
        $users_block    = '';
        
        if (empty($email_reports)) {
            $emails_block = '
            <tr scope="row">
                <th colspan="4">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($email_reports as $report) {
                $opened     = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `email_id` = %d AND `event_type` = 'open'", $report->email_id) );
                $clicked    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `email_id` = %d AND `event_type` = 'click'", $report->email_id) );
                
                $emails_block .= '
                <tr scope="row">
                    <td class="post-title column-title">
                        <strong>'. stripslashes($report->email_name) .'</strong>
                        <a href="admin.php?page=wc-followup-emails&tab=reportview&eid='. urlencode($report->email_id) .'">View Report</a>
                    </td>
                    <td>
                        <a class="row-title" href="admin.php?page=wc-followup-emails&tab=reportview&eid='. urlencode($report->email_id) .'">'. $report->sent .'</a>
                    </td>
                    <td><a class="row-title" href="admin.php?page=wc-followup-emails&tab=emailopen_view&eid='. urlencode($report->email_id) .'&ename='. urlencode($report->email_name) .'">'. $opened .'</a></td>
                    <td><a class="row-title" href="admin.php?page=wc-followup-emails&tab=linkclick_view&eid='. urlencode($report->email_id) .'&ename='. urlencode($report->email_name) .'">'. $clicked .'</a></td>
                </tr>
                ';
            }
        }
        
        if (empty($coupon_reports)) {
            $coupons_block = '
            <tr scope="row">
                <th colspan="6">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($coupon_reports as $report) {
                $used = __('No', 'wc_followup_emails');
                
                if ( $report->coupon_used == 1 ) {
                    $date = date('M d, Y h:i A', strtotime($report->date_used));
                    $used = sprintf(__('Yes (%s)', 'wc_followup_emails'), $date);
                }
                
                $coupons_block .= '
                <tr scope="row">
                    <td class="post-title column-title">
                        <strong>'. stripslashes($report->coupon_name) .'</strong>
                    </td>
                    <td>'. esc_html($report->email_address) .'</td>
                    <td>'. esc_html($report->coupon_code) .'</td>
                    <td>'. esc_html($report->email_name) .'</td>
                    <td>'. $used .'</td>
                    <td>'. date('M d,Y h:i A', strtotime($report->date_sent)) .'</td>
                </tr>
                ';
            }
        }
        
        if (empty($exclude_reports)) {
            $excludes_block = '
            <tr scope="row">
                <th colspan="3">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($exclude_reports as $report) {
                $excludes_block .= '
                <tr scope="row">
                    <td class="post-title column-title">
                        <strong>'. stripslashes($report->email_name) .'</strong>
                    </td>
                    <td>'. esc_html($report->email) .'</td>
                    <td>'. date('M d,Y h:i A', strtotime($report->date_added)) .'</td>
                </tr>
                ';
            }
        }
        
        if (empty($user_reports)) {
            $users_block = '
            <tr scope="row">
                <th scope="3">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($user_reports as $report) {
                if ( empty($report->email_address) ) continue;
                
                $sent       = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s", $report->email_address) );
                $opened     = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'open'", $report->email_address) );
                $clicked    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'click'", $report->email_address) );
                
                $users_block .= '
                <tr scope="row">
                    <td class="post-title column-title">
                        <strong>'. $report->customer_name .'</strong>
                        <a href="admin.php?page=wc-followup-emails&tab=reportuser_view&email='. urlencode($report->email_address) .'">View Report</a>
                    </td>
                    <td>'. esc_html($sent) .'</td>
                    <td>'. esc_html($opened) .'</td>
                    <td>'. esc_html($clicked) .'</td>
                </tr>
                ';
            }
        }
        
        $html = '
    <div class="subsubsub_section">
        <ul class="subsubsub">
            <li><a href="#emails" class="current">'. __('Emails', 'wc_followup_emails') .'</a> | </li>
            <li><a href="#users">'. __('Users', 'wc_followup_emails') .'</a> | </li>
            <li><a href="#coupons">'. __('Coupons', 'wc_followup_emails') .'</a> | </li>
            <li><a href="#excludes">'. __('Opt-Outs', 'wc_followup_emails') .'</a></li>
        </ul>
        <br class="clear">
        
        <div class="section" id="emails">
            <h3>'. __('Emails', 'wc_followup_emails') .'</h3>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="type" class="manage-column column-type" style="">'. __('Email Name', 'wc_followup_emails') .'</th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style="">'. __('Emails Sent', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of individual emails sent using this follow-up email', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style="">'. __('Emails Opened', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of times the this specific follow-up emails has been opened', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style="">'. __('Links Clicked', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of times links in this follow-up email have been clicked', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    '. $emails_block .'
                </tbody>
            </table>
        </div>
        <div class="section" id="users">
            <h3>'. __('Users', 'wc_followup_emails') .'</h3>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="type" class="manage-column column-type" style="">'. __('Customer', 'wc_followup_emails') .'</th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style="">'. __('Emails Sent', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of individual emails sent using this follow-up email', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style="">'. __('Emails Opened', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of times the this specific follow-up emails has been opened', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style="">'. __('Links Clicked', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The number of times links in this follow-up email have been clicked', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    '. $users_block .'
                </tbody>
            </table>
        </div>';
        
        $email_address_class    = ($sort['sortby'] != 'email_address') ? 'sortable' : 'sorted';
        $email_address_sort     = ($email_address_class == 'sorted') ? $sort['sort'] : 'asc';
        $email_address_dir      = ($email_address_sort == 'asc') ? 'desc' : 'asc';
        
        $used_class     = ($sort['sortby'] != 'coupon_used') ? 'sortable' : 'sorted';
        $used_sort      = ($used_class == 'sorted') ? $sort['sort'] : 'asc';
        $used_dir       = ($used_sort == 'asc') ? 'desc' : 'asc';
        
        $sent_class     = ($sort['sortby'] != 'date_sent') ? 'sortable' : 'sorted';
        $sent_sort      = ($sent_class == 'sorted') ? $sort['sort'] : 'asc';
        $sent_dir       = ($sent_sort == 'asc') ? 'desc' : 'asc';
        $html .= '
        <div class="section" id="coupons">
            <h3>'. __('Coupons', 'wc_followup_emails') .'</h3>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="coupon_name" class="manage-column column-type" style="">'. __('Coupon Name', 'wc_followup_emails') .'</th>
                        <th scope="col" id="email_address" class="manage-column column-usage_count '. $email_address_class .' '. $email_address_sort .'" style="">
                            <a href="admin.php?page=wc-followup-emails&tab=reports&sortby=email_address&sort='. $email_address_dir .'&v=coupons">
                                <span>'. __('Email Address', 'wc_followup_emails') .'</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="coupon_code" class="manage-column column-usage_count" style="">'. __('Coupon Code', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('This is the unique coupon code generated by the follow-up email for this specific email address', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="email_name" class="manage-column column-usage_count" style="">'. __('Email Name', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('This is the name of the follow-up email that generated the coupon that was sent to this specific email address', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="used" class="manage-column column-used '. $used_class .' '. $used_sort .'" style="">
                            <a href="admin.php?page=wc-followup-emails&tab=reports&sortby=coupon_used&sort='. $used_dir .'&v=coupons">
                                <span>'. __('Used', 'wc_followup_emails') .'  <img class="help_tip" width="16" height="16" title="'. __('This tells you if this specific coupon code generated and sent via follow-up emails has been used, and if it has, it includes the date and time', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="date_sent" class="manage-column column-date_sent '. $sent_class .' '. $sent_sort .'" style="">
                            <a href="admin.php?page=wc-followup-emails&tab=reports&sortby=date_sent&sort='. $sent_dir .'&v=coupons">
                                <span>'. __('Date Sent', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('This is the date and time that this specific coupon code was sent to this email address', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    '. $coupons_block .'
                </tbody>
            </table>
        </div>
        <div class="section" id="excludes">
            <h3>'. __('Opt-Outs', 'wc_followup_emails') .'</h3>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="coupon_name" class="manage-column column-type" style="">'. __('Email Name', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The name of the follow-up email that a customer has opted out of', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="coupon_name" class="manage-column column-type" style="">'. __('Email Address', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The email address of the customer that opted out', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                        <th scope="col" id="coupon_name" class="manage-column column-type" style="">'. __('Date', 'wc_followup_emails') .' <img class="help_tip" width="16" height="16" title="'. __('The date and time that the email address was opted out this follow-up email', 'wc_followup_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    '. $excludes_block .'
                </tbody>
            </table>
        </div>
    </div>
    <script type="text/javascript">
    jQuery(window).load(function(){
        jQuery(".help_tip").tipTip();
        // Subsubsub tabs
        jQuery(\'div.subsubsub_section ul.subsubsub li a:eq(0)\').addClass(\'current\');
        jQuery(\'div.subsubsub_section .section:gt(0)\').hide();
        
        jQuery(\'div.subsubsub_section ul.subsubsub li a\').click(function(){
            var $clicked = jQuery(this);
            var $section = $clicked.closest(\'.subsubsub_section\');
            var $target  = $clicked.attr(\'href\');
            
            $section.find(\'a\').removeClass(\'current\');
            
            if ( $section.find(\'.section:visible\').size() > 0 ) {
                $section.find(\'.section:visible\').fadeOut( 100, function() {
                    $section.find( $target ).fadeIn(\'fast\');
                });
            } else {
                $section.find( $target ).fadeIn(\'fast\');
            }
            
            $clicked.addClass(\'current\');
            jQuery(\'#last_tab\').val( $target );

            return false;
        });
        ';
    
        if ( isset($_GET['v']) ) {
            if ( $_GET['v'] == 'coupons' ) {
                $html .= '
            jQuery("ul.subsubsub a[href=#coupons]").click()
            ';
            }
        } else {
            $html .= '
            var hash = location.hash;
            if (hash.length > 0) {
                jQuery("ul.subsubsub a[href="+ hash +"]").click()
            }
            ';
    }
    
    $html .= '
    });
    </script>';
        return $html;
    }
    
    public static function reportview_html() {
        $id         = urldecode($_GET['eid']);
        $reports    = FUE_Reports::get_reports(array('id' => $id, 'type' => 'emails'));
        $block  = '';
        
        if (empty($reports)) {
            $block = '
            <tr scope="row">
                <th colspan="2">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($reports as $report) {
                $block .= '
                <tr scope="row">
                    <td class="post-title column-title">';
                    
                if ($report->user_id != 0) {
                    $block .= '<strong><a href="edit.php?post_status=all&post_type=shop_order&_customer_user='. $report->user_id .'">'. stripslashes($report->customer_name) .'</a></strong>';
                } else {
                    $block .= '<strong>'. stripslashes($report->customer_name) .'</strong>';
                }
                
                $block .= '
                    </td>
                    <td>'. stripslashes($report->email_address) .'</td>
                    <td>';
                        
                if ( $report->product_id != 0 ) {
                    $block .= '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
                }
                
                $block .= '
                    </td>
                    <td>'. $report->email_trigger .'</td>
                    <td>';
                        
                if ($report->order_id != 0) {
                    $block .= '<a href="post.php?post='. $report->order_id .'&action=edit">View Order</a>';
                } else {
                    $block .= '-';
                }
                
                $block .= '
                    </td>
                    <td>'. date('M d, Y h:i a', strtotime($report->date_sent)) .'</td>
                </tr>
                ';
            }
        }
        
        $html   = '
        <h3>'. sprintf(__('Report for %s', 'wc_folloup_emails'), $report->email_name) .'</h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="type" class="manage-column column-type" style="">'. __('Customer Name', 'wc_followup_emails') .'</th>
                    <th scope="col" id="user_email" class="manage-column column-user_email" style="">'. __('Email', 'wc_followup_emails') .'</th>
                    <th scope="col" id="product" class="manage-column column-product" style="">'. __('Product', 'wc_folloup_emails') .'</th>
                    <th scope="col" id="trigger" class="manage-column column-trigger" style="">'. __('Trigger', 'wc_folloup_emails') .'</th>
                    <th scope="col" id="order" class="manage-column column-order" style="">&nbsp;</th>
                    <th scope="col" id="date_sent" class="manage-column column-date_sent" style="">'. __('Date Sent', 'wc_followup_emails') .'</th>
                </tr>
            </thead>
            <tbody id="the_list">
                '. $block .'
            </tbody>
        </table>
        ';
        
        return $html;
    }
    
    public static function user_view_html() {
        $email      = urldecode($_GET['email']);
        $reports    = FUE_Reports::get_reports(array('email' => $email, 'type' => 'users'));
        $block  = '';
        //echo '<pre>'. print_r($reports,true) .'</pre>';
        if (empty($reports)) {
            $block = '
            <tr scope="row">
                <th colspan="2">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($reports as $report) {
                $block .= '
                <tr scope="row">
                    <td class="post-title column-title">';
                    
                if ($report->user_id != 0) {
                    $block .= '<strong><a href="edit.php?post_status=all&post_type=shop_order&_customer_user='. $report->user_id .'">'. stripslashes($report->customer_name) .'</a></strong>';
                } else {
                    $block .= '<strong>'. stripslashes($report->customer_name) .'</strong>';
                }
                
                $block .= '
                    </td>
                    <td>'. stripslashes($report->email_address) .'</td>
                    <td>';
                        
                if ( $report->product_id != 0 ) {
                    $block .= '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
                }
                
                $block .= '
                    </td>
                    <td>'. $report->email_name .'</td>
                    <td>'. $report->email_trigger .'</td>';
                        
                $block .= '
                    <td>'. date('M d, Y h:i a', strtotime($report->date_sent)) .'</td>
                ';
                
                if ($report->order_id != 0) {
                    $block .= '<td><a href="post.php?post='. $report->order_id .'&action=edit">View Order</a></td>';
                } else {
                    $block .= '<td>-</td>';
                }
                
                $block .= '</tr>';
            }
        }
        
        $html   = '
        <h3>'. sprintf(__('Report for %s (%s)', 'wc_folloup_emails'), $report->customer_name, $report->email_address) .'</h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="type" class="manage-column column-type" style="">'. __('Customer Name', 'wc_followup_emails') .'</th>
                    <th scope="col" id="user_email" class="manage-column column-user_email" style="">'. __('Customer Email', 'wc_followup_emails') .'</th>
                    <th scope="col" id="product" class="manage-column column-product" style="">'. __('Product', 'wc_folloup_emails') .'</th>
                    <th scope="col" id="email_name" class="manage-column column-email_name" style="">'. __('Email', 'wc_folloup_emails') .'</th>
                    <th scope="col" id="trigger" class="manage-column column-trigger" style="">'. __('Trigger', 'wc_folloup_emails') .'</th>
                    <th scope="col" id="date_sent" class="manage-column column-date_sent" style="">'. __('Date Sent', 'wc_followup_emails') .'</th>
                    <th scope="col" id="order" class="manage-column column-order" style="">&nbsp;</th>
                </tr>
            </thead>
            <tbody id="the_list">
                '. $block .'
            </tbody>
        </table>
        ';
        
        return $html;
    }
    
    public static function email_open_html($id, $name) {
        global $wpdb;
        
        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'open' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );
        $block  = '';
        
        if (empty($reports)) {
            $block = '
            <tr scope="row">
                <th colspan="2">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($reports as $report) {
                //$count_clicked = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `event_type` = 'click' AND `email_id` = %d AND `user_email` = %s", $report->email_id, $report->user_email) );
                //$clicked = ( $count_clicked > 0 ) ? __('Yes', 'wc_followup_emails') : __('No', 'wc_followup_emails');
                
                $block .= '
                <tr scope="row">
                    <td>'. esc_html($report->user_email) .'</td>
                    <td>'. date('M d, Y h:i a', strtotime($report->date_added)) .'</td>
                </tr>
                ';
            }
        }
        
        $html   = '
        <h3>'. sprintf(__('Email Opened Report for %s', 'wc_folloup_emails'), $name) .'</h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="user_email" class="manage-column column-user_email" style="">'. __('Email', 'wc_followup_emails') .'</th>
                    <th scope="col" id="date_sent" class="manage-column column-date_sent" style="">'. __('Date Opened', 'wc_followup_emails') .'</th>
                </tr>
            </thead>
            <tbody id="the_list">
                '. $block .'
            </tbody>
        </table>
        ';
        
        return $html;
    }
    
    public static function link_click_html($id, $name) {
        global $wpdb;
        
        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'click' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );
        $block  = '';
        
        if (empty($reports)) {
            $block = '
            <tr scope="row">
                <th colspan="3">'. __('No reports available', 'wc_followup_emails') .'</th>
            </tr>';
        } else {
            foreach ($reports as $report) {
                $block .= '
                <tr scope="row">
                    <td>'. esc_html($report->user_email) .'</td>
                    <td><a href="'. esc_attr($report->target_url) .'" target="_blank">'. esc_html($report->target_url) .'</a></td>
                    <td>'. date('M d, Y h:i a', strtotime($report->date_added)) .'</td>
                </tr>
                ';
            }
        }
        
        $html   = '
        <h3>'. sprintf(__('Email Link Clicks Report for %s', 'wc_folloup_emails'), $name) .'</h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="user_email" class="manage-column column-user_email" style="">'. __('Email', 'wc_followup_emails') .'</th>
                    <th scope="col" id="user_email" class="manage-column column-user_email" style="">'. __('Target URL', 'wc_followup_emails') .'</th>
                    <th scope="col" id="date_sent" class="manage-column column-date_sent" style="">'. __('Date Clicked', 'wc_followup_emails') .'</th>
                </tr>
            </thead>
            <tbody id="the_list">
                '. $block .'
            </tbody>
        </table>
        ';
        
        return $html;
    }
    
    public function send_summary() {
        global $wpdb, $woocommerce;
        
        $last_send  = get_option('fue_last_summary', 0);
        $next_send  = get_option('fue_next_summary', 0);
        $now        = time();
        $reports    = '';
        
        if ( $now < $next_send ) return;
        
        $sfn_reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 1 AND `date_sent` >= %s", date('Y-m-d H:i:s', $last_send)) );
        
        if ( empty($sfn_reports) ) return;
        
        foreach ( $sfn_reports as $report ) {
            $product_str    = 'n/a';
            $order_str      = 'n/a';
            $coupon_str     = '-';
            $order          = false;
            $email          = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = {$report->email_id}");
            
            if ( $report->product_id != 0 ) {
                $product_str = '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
            }
            
            if ( $report->order_id != 0 ) {
                $order      = new WC_Order( $report->order_id );
                $order_str  = '<a href="'. get_admin_url() .'post.php?post='. $report->order_id .'&action=edit">View Order</a>';
            }
            
            if (! empty($report->coupon_name) && ! empty($report->coupon_code )) {
                $coupon_str = $report->coupon_name .' ('. $report->coupon_code .')';
            }
            
            $email_address = '';
            if ( $order === false ) {
                $user = new WP_User( $report->user_id );
                $email_address = $user->user_email;
            } else {
                $email_address = $order->billing_email;
            }
            
            $reports .= '
            <tr>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email->name .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email_address .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $product_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $order_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $report->email_trigger.'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $coupon_str .'</td>
            </tr>';
        }
        
        $subject    = __('Follow-up emails summary', 'wc_followup_emails');
        $body       = '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Name', 'woocommerce') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Address', 'woocommerce') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Product', 'woocommerce') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Order', 'woocommerce') .'</th>
            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Trigger', 'woocommerce') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Sent Coupon', 'woocommerce') .'</th>
		</tr>
	</thead>
	<tbody>
		'. $reports .'
	</tbody>
</table>';
        
        // send the email
        $mailer     = $woocommerce->mailer();
        $message    = $mailer->wrap_message( sprintf(__('Follow-up emails summary - %s', 'woocommerce'), date('M d, Y h:i a')), $body );
        
        $recipient  = get_option('fue_daily_emails', false);
        
        if (! $recipient) {
            $recipient = get_bloginfo('admin_email');
        }
        
        $mailer->send($recipient, $subject, $message);
        
        update_option( 'fue_last_summary', time() );
        update_option( 'fue_next_summary', time() + 86400 );
    }
    
    public static function get_reports( $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'id'    => '',
            'email' => '',
            'type'  => 'emails',
            'sort'  => array()
        );
        $args = array_merge($defaults, $args);
        
        if ( $args['type'] == 'emails' ) {
            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT email_id, email_name, COUNT( email_name ) AS sent FROM  `{$wpdb->prefix}followup_email_logs` GROUP BY email_id ORDER BY date_sent DESC" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_id` = %d ORDER BY `date_sent` DESC", $args['id']) );
            }
        } elseif ( $args['type'] == 'users' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';
            
            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }
            
            if ( empty($args['email']) ) {
                return $wpdb->get_results( "SELECT customer_name, email_address, user_id FROM `{$wpdb->prefix}followup_email_logs` GROUP BY email_address ORDER BY $sortby $sort" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s ORDER BY $sortby $sort", $args['email']) );
            }
        } elseif ( $args['type'] == 'coupons' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';
            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }
            
            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT * FROM  `{$wpdb->prefix}followup_coupon_logs` ORDER BY $sortby $sort" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_coupon_logs` WHERE `coupon_id` = %d ORDER BY $sortby $sort", $args['id']) );
            }
        } elseif ( $args['type'] == 'excludes' ) {
            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}followup_email_excludes` ORDER BY `date_added` DESC" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_excludes` WHERE `email_id` = %d ORDER BY `date_added` DESC", $args['id']) );
            }
        }
    }
    
}

if (! wp_next_scheduled( 'fue_send_summary' ) ) {
    wp_schedule_event( current_time('timestamp'), 'daily', 'fue_send_summary' );
}
add_action('fue_send_summary', array('FUE_Reports', 'send_summary'));

$GLOBALS['fue_reports'] = new FUE_Reports();