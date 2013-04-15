<?php

class FUE {
    
    /**
     * Create email orders after a user registers
     * @static
     * @param int $user_id
     * @param array $triggers
     */
    public static function create_order_from_signup( $user_id, $triggers = array() ) {
        global $woocommerce, $wpdb;
        
        $user = new WP_User( $user_id );
        
        if ( is_wp_error($user) ) return;
        
        $trigger = '';
        foreach ( $triggers as $t ) {
            $trigger .= "'". $wpdb->escape($t) ."',";
        }
        $trigger = rtrim($trigger, ',');
        
        if ( empty($trigger) ) $trigger = "''";
        
        $emails = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'signup' AND `interval_type` IN ($trigger)" );
        
        foreach ( $emails as $email ) {
            $interval   = (int)$email->interval_num;
            
            if ( $email->interval_type == 'date' ) {
                $send_on = strtotime($email->send_date);
            } else {
                $add        = self::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = current_time('timestamp') + $add;
            }
            
            $insert = array(
                'send_on'       => $send_on,
                'email_id'      => $email->id,
                'user_id'       => $user_id,
                'order_id'      => 0,
                'is_cart'       => 0
            );
            self::insert_email_order( $insert );
            break;
        }
    }
    
    /**
     * Create email orders from a shop order
     * @static
     * @param int $order_id
     * @param array $triggers
     */
    public static function create_order_from_triggers( $order_id = 0, $triggers = array() ) {
        global $woocommerce, $wpdb;
        
        $order          = ($order_id > 0) ? new WC_Order($order_id) : false;
        $items          = $order->get_items();
        $order_created  = false;
        //var_dump($triggers);
        $trigger = '';
        foreach ( $triggers as $t ) {
            $trigger .= "'". $wpdb->escape($t) ."',";
        }
        $trigger = rtrim($trigger, ',');
        
        if ( empty($trigger) ) $trigger = "''";
        
        // find a product match
        $emails         = array();
        $always_prods   = array();
        $always_cats    = array();
        foreach ( $items as $item ) {
            $prod_id = (isset($item['id'])) ? $item['id'] : $item['product_id'];
            $email_results = $wpdb->get_results("SELECT DISTINCT `id`, `priority` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND `product_id` = '". $prod_id ."' AND `email_type` <> 'generic' AND `always_send` = 0 ORDER BY `priority` ASC");
            
            if ( $email_results ) {
                foreach ($email_results as $email) {
                    $emails[] = array('id' => $email->id, 'item' => $prod_id, 'priority' => $email->priority);
                }
            }
            
            // always_send product matches
            $results = $wpdb->get_results("SELECT DISTINCT `id` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND `product_id` = '". $prod_id ."' AND `always_send` = 1");
            
            foreach ( $results as $row ) {
                $always_prods[] = array( 'id' => $row->id, 'item' => $prod_id );
            }
            
            // always_send category matches
            $cat_ids    = wp_get_object_terms( $prod_id, 'product_cat', array('fields' => 'ids') );
            $ids        = implode(',', $cat_ids);
            
            if (empty($ids)) $ids = "''";
            
            $results = $wpdb->get_results("SELECT DISTINCT `id` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND `always_send` = 1 AND `category_id` IN (". $ids .")");
            
            foreach ( $results as $row ) {
                $always_cats[] = array('id' => $row->id, 'item' => $prod_id);
            }
        }
        
        if ( !empty($always_prods) ) {
            foreach ( $always_prods as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $row['id']) );
                $interval   = (int)$email->interval_num;
                
                $skip = false;
                do_action('fue_create_order_always_send', $email, $order_id, $row);
                
                if (false == $skip ) {
                    if ( $email->interval_type == 'date' ) {
                        $send_on = strtotime($email->send_date);
                    } else {
                        $add        = self::get_time_to_add( $interval, $email->interval_duration );
                        $send_on    = current_time('timestamp') + $add;
                    }
                    
                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $row['item'],
                        'order_id'      => $order_id
                    );
                    self::insert_email_order( $insert );
                }
            }
        }
        
        if ( !empty($always_cats) ) {
            foreach ( $always_cats as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $row['id']) );
                $interval   = (int)$email->interval_num;
                
                $skip = false;
                do_action('fue_create_order_always_send', $email, $order_id, $row);
                
                if ( false == $skip ) {
                    if ( $email->interval_type == 'date' ) {
                        $send_on = strtotime($email->send_date);
                    } else {
                        $add        = self::get_time_to_add( $interval, $email->interval_duration );
                        $send_on    = current_time('timestamp') + $add;
                    }
                    
                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $row['item'],
                        'order_id'      => $order_id
                    );
                    self::insert_email_order( $insert );
                }
            }
        }
        
        if ( !empty($emails) ) {
            // find the one with the highest priority
            $top        = false;
            $highest    = 1000;
            foreach ( $emails as $email ) {
                if ( $email['priority'] < $highest ) {
                    $highest    = $email['priority'];
                    $top        = $email;
                }
            }
            
            if ( $top !== false ) {
                $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $top['id']) );
                
                $interval   = (int)$email->interval_num;
                
                if ( $email->interval_type == 'date' ) {
                    $send_on = strtotime($email->send_date);
                } else {
                    $add        = self::get_time_to_add( $interval, $email->interval_duration );
                    $send_on    = current_time('timestamp') + $add;
                }
                
                $insert = array(
                    'send_on'       => $send_on,
                    'email_id'      => $email->id,
                    'product_id'    => $top['item'],
                    'order_id'      => $order_id
                );
                self::insert_email_order( $insert );
                $order_created = true;
                
                // look for other emails with the same product id
                foreach ( $emails as $prod_email ) {
                    if ( $prod_email['id'] == $top['id'] ) continue;
                    
                    if ( $prod_email['item'] == $top['item'] ) {
                        $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $prod_email['id']) );
                
                        $interval   = (int)$email->interval_num;
                        
                        if ( $email->interval_type == 'date' ) {
                            $send_on = strtotime($email->send_date);
                        } else {
                            $add        = self::get_time_to_add( $interval, $email->interval_duration );
                            $send_on    = current_time('timestamp') + $add;
                        }
                        
                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $prod_email['item'],
                            'order_id'      => $order_id
                        );
                        self::insert_email_order( $insert );
                    } else {
                        // if schedule is within 60 minutes, add to queue
                        $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $prod_email['id']) );
                        $interval   = (int)$email->interval_num;
                        
                        if ( $email->interval_type == 'date' ) {
                            continue;
                        } else {
                            $add = self::get_time_to_add( $interval, $email->interval_duration );
                            
                            if ( $add > 3600 ) continue;
                            
                            // less than 60 minutes, add to queue
                            $send_on = current_time('timestamp') + $add;
                        }
                        
                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $prod_email['item'],
                            'order_id'      => $order_id
                        );
                        self::insert_email_order( $insert );
                    }
                }
            }
        }
        
        // find a category match
        if ( !$order_created ) {
            $emails = array();
            foreach ( $items as $item ) {
                $prod_id    = (isset($item['id'])) ? $item['id'] : $item['product_id'];
                $cat_ids    = wp_get_object_terms( $prod_id, 'product_cat', array('fields' => 'ids') );
                $ids        = implode(',', $cat_ids);
                
                if (empty($ids)) $ids = "''";
                
                $email = $wpdb->get_results("SELECT DISTINCT `id`, `priority` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND `product_id` = 0 AND `category_id` > 0 AND `category_id` IN (". $ids .") AND `email_type` <> 'generic' AND `always_send` = 0 ORDER BY `priority` ASC");
                
                foreach ( $email as $e ) {
                    $emails[] = array('id' => $e->id, 'item' => $prod_id, 'priority' => $e->priority);
                }
            }
            
            if ( !empty($emails) ) {
                // find the one with the highest priority
                $top        = false;
                $highest    = 1000;
                foreach ( $emails as $email ) {
                    if ( $email['priority'] < $highest ) {
                        $highest    = $email['priority'];
                        $top        = $email;
                    }
                }
                
                if ( $top !== false ) {
                    $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $top['id']) );
                    
                    $interval   = (int)$email->interval_num;
                    
                    if ( $email->interval_type == 'date' ) {
                        $send_on = strtotime($email->send_date);
                    } else {
                        $add        = self::get_time_to_add( $interval, $email->interval_duration );
                        $send_on    = current_time('timestamp') + $add;
                    }
                    
                    $insert = array(
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => $top['item'],
                        'order_id'      => $order_id
                    );
                    self::insert_email_order( $insert );
                    $order_created = true;
                    
                    // look for other emails with the same category id
                    foreach ( $emails as $cat_email ) {
                        if ( $cat_email['id'] == $top['id'] ) continue;
                        
                        if ( $cat_email['item'] == $top['item'] ) {
                            $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $cat_email['id']) );
                    
                            $interval   = (int)$email->interval_num;
                            
                            if ( $email->interval_type == 'date' ) {
                                $send_on = strtotime($email->send_date);
                            } else {
                                $add        = self::get_time_to_add( $interval, $email->interval_duration );
                                $send_on    = current_time('timestamp') + $add;
                            }
                            
                            $insert = array(
                                'send_on'       => $send_on,
                                'email_id'      => $email->id,
                                'product_id'    => $cat_email['item'],
                                'order_id'      => $order_id
                            );
                            self::insert_email_order( $insert );
                        } else {
                            // if schedule is within 60 minutes, add to queue
                            $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $cat_email['id']) );
                            $interval   = (int)$email->interval_num;
                            
                            if ( $email->interval_type == 'date' ) {
                                continue;
                            } else {
                                $add = self::get_time_to_add( $interval, $email->interval_duration );
                                
                                if ( $add > 3600 ) continue;
                                
                                // less than 60 minutes, add to queue
                                $send_on = current_time('timestamp') + $add;
                            }
                            
                            $insert = array(
                                'send_on'       => $send_on,
                                'email_id'      => $email->id,
                                'product_id'    => $cat_email['item'],
                                'order_id'      => $order_id
                            );
                            self::insert_email_order( $insert );
                        }
                    }
                }
            }
        }
        
        if ( !$order_created ) {
            // find a generic mailer
            $emails = $wpdb->get_results("SELECT DISTINCT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'generic' AND `interval_type` IN ($trigger) ORDER BY `priority` ASC");
            //echo '<pre>'. print_r($emails, true) .'</pre>';
            foreach ( $emails as $email ) {
                $interval   = (int)$email->interval_num;
                
                if ( $email->interval_type == 'date' ) {
                    $send_on = strtotime($email->send_date);
                } else {
                    $add        = self::get_time_to_add( $interval, $email->interval_duration );
                    $send_on    = current_time('timestamp') + $add;
                }
                
                $insert = array(
                    'send_on'       => $send_on,
                    'email_id'      => $email->id,
                    'product_id'    => 0,
                    'order_id'      => $order_id
                );
                self::insert_email_order( $insert );
            }
            //exit;
        }
    }
    
    public static function insert_email_order( $data ) {
        global $wpdb;
        
        $defaults = array(
            'user_id'       => 0,
            'order_id'      => 0,
            'product_id'    => 0,
            'email_id'      => '',
            'send_on'       => 0,
            'is_cart'       => 0,
            'is_sent'       => 0,
            'date_sent'     => '',
            'email_trigger' => ''
        );
        
        $insert = array_merge( $defaults, $data );
        $insert = apply_filters( 'fue_insert_email_order', $insert );
        
        $wpdb->insert( $wpdb->prefix .'followup_email_orders', $insert );
    }
    
    /** 
     * Send emails that are in the email queue
     */
    public static function send_emails() {
        global $wpdb, $woocommerce;
        
        // get start and end times
        $to         = current_time('timestamp');
        $results    = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `send_on` <= %s", $to) );
        
        foreach ($results as $email_order) {
            $sfn_report = array();
            $user_id    = 0;
            
            if ( $email_order->order_id != 0 ) {
                // order
                $order      = new WC_Order($email_order->order_id);
                
                if ( isset($order->user_id) && $order->user_id > 0 ) {
                    $user_id    = $order->user_id;
                    $wp_user    = new WP_User( $order->user_id );
                    $email_to   = $wp_user->user_email;
                    $first_name = $wp_user->first_name;
                    $last_name  = $wp_user->last_name;
                    $cname      = $first_name .' '. $last_name;
                } else {
                    $email_to   = $order->billing_email;
                    $first_name = $order->billing_first_name;
                    $last_name  = $order->billing_last_name;
                }
                
                $cname      = $first_name .' '. $last_name;
                $order_date = date('M d, Y h:i A', strtotime($order->order_date));
            } else {
                $order      = false;
                $user_id    = $email_order->user_id;
                $wp_user    = new WP_User( $email_order->user_id );
                $email_to   = $wp_user->user_email;
                $first_name = $wp_user->first_name;
                $last_name  = $wp_user->last_name;  
                $cname      = $first_name .' '. $last_name;
                $order_date = '';
                
                if ( empty($first_name)  && empty($last_name) ) {
                    $first_name = $wp_user->user_nicename;
                    $cname      = $wp_user->user_nicename;
                }
                
                // non-order related email. make sure user is not opted-out
                $opt_out = get_user_meta( $email_order->user_id, 'wcfu_opted_out', true );
                $opt_out = apply_filters( 'fue_user_opt_out', $opt_out, $email_order->user_id );
                
                if ( $opt_out )  {
                    // user opted out, delete this email_order
                    $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `id` = %d", $email_order->id) );
                    continue; 
                }
            }
            
            $email  = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );
            
            // check if the email address is on the excludes list
            $sql = $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_excludes` WHERE `email` = '%s'", $email_to );
            
            if ($wpdb->get_var( $sql ) > 0) {
                // delete and go to the next entry
                do_action( 'fue_email_excluded', $email_to, $email_order->id );
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `id` = %d", $email_order->id) );
                continue;
            }
            
            if ( $email->email_type == 'generic' ) {
                if ( $order ) {
                    $used_cats  = array();
                    $item_list  = '<ul>';
                    $item_cats  = '<ul>';
                    $items      = $order->get_items();
                    
                    foreach ( $items as $item ) {
                        $item_id = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];
                        $item_list .= apply_filters( 'fue_email_item_list', '<li><a href="'. self::create_email_url( $email_order->id, $email->id, $user_id, $email_to, get_permalink($item_id) ) .'">'. get_the_title($item_id) .'</a></li>', $email_order->id, $item );
                        
                        $cats   = get_the_terms($item_id, 'product_cat');
                        
                        if ( is_array($cats) && !empty($cats) ) {
                            foreach ($cats as $cat) {
                                if (!in_array($cat->term_id, $used_cats)) {
                                    $item_cats .= apply_filters( 'fue_email_cat_list', '<li>'. $cat->name .'</li>', $email_order->id, $cat );
                                }
                            }
                        }
                    }
                    
                    $item_list .= '</ul>';
                    $item_cats .= '</ul>';
                } else {
                    $item_list = '';
                    $item_cats = '';
                }
            } else {
                if ( !empty($email_order->product_id) ) {
                    $item   = sfn_get_product($email_order->product_id);
                    $cats   = get_the_terms($item->id, 'product_cat');
                    
                    $categories = '';
                    if (is_array($cats) && !empty($cats)) {
                        foreach ($cats as $cat) {
                            $categories .= $cat->name .', ';
                        }
                        $categories = rtrim($categories, ', ');
                    }
                } else {
                    
                }
            }
            
            // process variable replacements
            $tracking   = $email->tracking_code;
            $codes      = array();
            
            if ( !empty($tracking) ) {
                parse_str( $tracking, $codes );
                
                foreach ( $codes as $key => $val ) {
                    $codes[$key] = urlencode($val);
                }
            }
            
            $store_url      = site_url();
            $store_name     = get_bloginfo('name');
            $page_id        = woocommerce_get_page_id('followup_unsubscribe');
            $unsubscribe    = add_query_arg('wcfu', $email_to, get_permalink($page_id));
            
            // convert urls
            $store_url      = self::create_email_url( $email_order->id, $email->id, $user_id, $email_to, $store_url );
            $unsubscribe    = self::create_email_url( $email_order->id, $email->id, $user_id, $email_to, $unsubscribe );

            if (! empty($codes) ) {
                $store_url      = add_query_arg($codes, $store_url);
                $unsubscribe    = add_query_arg($codes, $unsubscribe);
            }
            
            if ( $email->email_type == 'generic' ) {
                $vars   = array('{order_number}','{order_datetime}', '{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{item_names}', '{item_categories}', '{unsubscribe_url}');
                $reps   = array(
                    (0 == $email_order->order_id) ? '' : $email_order->order_id,
                    $order_date,
                    $store_url,
                    $store_name,
                    $first_name,
                    $first_name .' '. $last_name,
                    $item_list,
                    $item_cats,
                    $unsubscribe
                );
            } elseif ( $email->email_type == 'signup' ) {
                $vars   = array('{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{unsubscribe_url}');
                $reps   = array(
                    $store_url,
                    $store_name,
                    $first_name,
                    $cname,
                    $unsubscribe
                );
            } else {
                $item_url   = self::create_email_url( $email_order->id, $email->id, $user_id, $email_to, get_permalink($item->id) );

                if (! empty($codes) ) add_query_arg($codes, $item_url);

                $vars       = array('{order_number}','{order_datetime}', '{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{item_name}', '{item_category}', '{unsubscribe_url}');
                $reps       = array(
                    (0 == $email_order->order_id) ? '' : $email_order->order_id,
                    $order_date,
                    $store_url,
                    $store_name,
                    $first_name,
                    $first_name .' '. $last_name,
                    '<a href="'. $item_url .'">'. get_the_title($item->id) .'</a>',
                    $categories,
                    $unsubscribe
                );
            }
            
            $subject    = apply_filters('fue_email_subject', $email->subject, $email, $email_order);
            $message    = apply_filters('fue_email_message', $email->message, $email, $email_order);
            
            $subject    = strip_tags(str_replace($vars, $reps, $subject));
            $message    = str_replace($vars, $reps, $message);
            
            // hook to variable replacement
            $subject    = apply_filters( 'fue_send_email_subject', $subject, $email_order );
            $message    = apply_filters( 'fue_send_email_message', $message, $email_order );
            
            // look for custom fields
            $message    = preg_replace_callback('|\{cf ([0-9]+) ([^}]*)\}|', 'fue_add_custom_fields', $message);
            
            do_action( 'fue_before_email_send', $subject, $message, $email_order );
            
            // send the email
            $mailer     = $woocommerce->mailer();
            $message    = $mailer->wrap_message( $subject, $message );
            $mailer->send($email_to, $subject, $message);
            
            $oid = ($order) ? $email_order->order_id : 0;
            
            if ( $email->interval_type == 'date' ) {
                $email_trigger = sprintf( __('Send on %s'), $email->send_date );
            } elseif ( $email->interval_type == 'signup' ) {
                $email_trigger = sprintf( __('%d %s after user signs up', 'wc_followup_emails'), $email->interval_num, $email->interval_duration );
            } else {
                $email_trigger = sprintf( __('%d %s after %s'), $email->interval_num, $email->interval_duration, SFN_FollowUpEmails::get_trigger_name( $email->interval_type ) );
            }
            
            do_action( 'fue_after_email_sent', $subject, $message, $email_order );
            do_action( 'fue_email_sent_details', $email_order, $order->user_id, $email, $email_to, $cname, $email_trigger );
            
            // increment usage count
            $wpdb->query( $wpdb->prepare("UPDATE `{$wpdb->prefix}followup_emails` SET `usage_count` = `usage_count` + 1 WHERE `id` = %d", $email->id) );
            
            // update the email order
            $now = date('Y-m-d H:i:s');
            $wpdb->query( $wpdb->prepare("UPDATE `{$wpdb->prefix}followup_email_orders` SET `is_sent` = 1, `date_sent` = %s, `email_trigger` = %s WHERE `id` = %d", $now, $email_trigger, $email_order->id) );
            do_action( 'fue_email_order_sent', $email_order->id );
        }
    }
    
    function send_test_email() {
        global $woocommerce;
        
        $_POST      = array_map('stripslashes_deep', $_POST);
        
        $type       = $_POST['type'];
        $email      = $_POST['email'];
        $subject    = $_POST['subject'];
        $message    = $_POST['message'];
        $tracking   = $_POST['tracking'];
        $codes      = array();
        
        if ( !empty($tracking) ) {
            parse_str( $tracking, $codes );
            
            foreach ( $codes as $key => $val ) {
                $codes[$key] = urlencode($val);
            }
        }
        
        if ( $type == 'generic' ) {
            $item_list = '<ul><li><a href="#">Item 1</a></li><li><a href="#">Item 2</a></li></ul>';
            $item_cats = '<ul><li>Category 1</li><li>Category 2</li></ul>';
        }
        
        // process variable replacements
        $store_url      = (empty($codes)) ? site_url() : add_query_arg($codes, site_url());
        $store_name     = get_bloginfo('name');
        $page_id        = woocommerce_get_page_id('followup_unsubscribe');
        $unsubscribe    = (empty($codes)) ? add_query_arg('wcfu', $email, get_permalink($page_id)) : add_query_arg($codes, add_query_arg('wcfu', $email, get_permalink($page_id)));
        $order_date     = date('M d, Y h:i A');
        if ( $type == 'generic' ) {
            $vars   = array('{order_number}', '{order_datetime}', '{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{item_names}', '{item_categories}', '{unsubscribe_url}');
            $reps   = array(
                '1100',
                $order_date,
                $store_url,
                $store_name,
                'John',
                'John Doe',
                $item_list,
                $item_cats,
                $unsubscribe
            );
        } elseif ( $type == 'signup' ) {
            $vars   = array('{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{unsubscribe_url}');
            $reps   = array(
                $store_url,
                $store_name,
                'John',
                'John Doe',
                $unsubscribe
            );
        } else {
            $vars   = array('{order_number}', '{order_datetime}', '{store_url}', '{store_name}', '{customer_first_name}', '{customer_name}', '{item_name}', '{item_category}', '{unsubscribe_url}');
            $reps   = array(
                '1100',
                $order_date,
                $store_url,
                $store_name,
                'John',
                'John Doe',
                '<a href="#">Name of Product</a>',
                'Test Category',
                $unsubscribe
            );
        }
        
        $subject    = strip_tags(str_replace($vars, $reps, $subject));
        $message    = str_replace($vars, $reps, $message);
        $message    = do_shortcode($message);
        
        // hook to variable replacement
        $subject    = apply_filters( 'fue_send_test_email_subject', $subject );
        $message    = apply_filters( 'fue_send_test_email_message', $message );
        
        // look for custom fields
        $message    = preg_replace_callback('|\{cf ([0-9]+) ([^}]*)\}|', 'fue_add_custom_fields', $message);
        
        do_action( 'fue_before_test_email_send', $subject, $message );
        
        // send the email
        $mailer     = $woocommerce->mailer();
        $message    = $mailer->wrap_message( $subject, $message );
        $mailer->send($email, $subject, $message);
        
        do_action( 'fue_after_test_email_sent', $subject, $message );
    }
    
    public static function create_email_url( $email_order_id, $email_id, $user_id = 0, $user_email, $target_page ) {
        $args = apply_filters('fue_create_email_url', array(
            'oid'           => $email_order_id,
            'eid'           => $email_id,
            'user_id'       => $user_id,
            'user_email'    => $user_email,
            'next'          => $target_page
        ));
        $payload    = base64_encode(http_build_query($args));
        return add_query_arg( 'sfn_payload', $payload, add_query_arg( 'sfn_trk', 1, get_bloginfo( 'wpurl' ) ) );
    }
    
    public static function get_time_to_add( $interval, $duration ) {
        $add = 0;
        switch ($duration) {
            case 'minutes':
                $add = $interval * 60;
                break;
                
            case 'hours':
                $add = $interval * (60*60);
                break;
                
            case 'days':
                $add = $interval * 86400;
                break;
                
            case 'weeks':
                $add = $interval * (7 * 86400);
                break;
                
            case 'months':
                $add = $interval * (30 * 86400);
                break;
                
            case 'years':
                $add = $interval * (365 * 86400);
                break;
        }
        
        return apply_filters('fue_get_time_to_add', $add, $duration, $interval);
    }
    
}