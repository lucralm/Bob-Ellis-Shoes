<?php

$GLOBALS['fue_subscriptions_product_link'] = 'http://www.75nineteen.com/woocommerce';

class FUE_Subscriptions {

    static $license_product = 'subscriptions';
    static $platform        = 'woocommerce';
    
    public function __construct() {
        if ( self::is_installed() ) {
            // subscriptions integration
            add_filter( 'fue_trigger_types', array(&$this, 'add_triggers') );
            add_action( 'activated_subscription', array(&$this, 'subscription_activated'), 10, 2 );
            add_action( 'cancelled_subscription', array(&$this, 'subscription_cancelled'), 10, 2 );
            add_action( 'subscription_expired', array(&$this, 'subscription_expired'), 10, 2 );
            add_action( 'reactivated_subscription', array(&$this, 'reactivated_subscription'), 10, 2 );
            add_action( 'suspended_subscription', array(&$this, 'suspended_subscription'), 10, 2 );
        }
    }
    
    public static function is_installed() {
        return ( class_exists( 'WC_Subscriptions' ) );
    }
    
    public function add_triggers( $triggers ) {
        $triggers['subs_activated']     = __('subscription activated', 'wc_followup_emails');
        $triggers['subs_cancelled']     = __('subscription cancelled', 'wc_followup_emails');
        $triggers['subs_expired']       = __('subscription expired', 'wc_followup_emails');
        $triggers['subs_suspended']     = __('subscription suspended', 'wc_followup_emails');
        $triggers['subs_reactivated']   = __('subscription reactivated', 'wc_followup_emails');
        
        return $triggers;
    }
    
    public static function subscription_activated( $user_id, $subs_key ) {
        global $wpdb;
        
        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $item_id        = $parts[1];
        
        $order          = new WC_Order($order_id);
        $items          = $order->get_items();
        $order_created  = false;
        $triggers[]     = 'subs_activated';
        
        FUE::create_order_from_triggers( $order_id, $triggers );
        
    }
    
    public static function subscription_cancelled( $user_id, $subs_key ) {
        global $wpdb;
        
        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $item_id        = $parts[1];
        
        $order          = new WC_Order($order_id);
        $items          = $order->get_items();
        $order_created  = false;
        $triggers[]     = 'subs_cancelled';
        
        FUE::create_order_from_triggers( $order_id, $triggers );
        
    }
    
    public static function subscription_expired( $user_id, $sub_key ) {
        global $wpdb;
        
        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $item_id        = $parts[1];
        
        $order          = new WC_Order($order_id);
        $items          = $order->get_items();
        $order_created  = false;
        $triggers[]     = 'subs_expired';
        
        FUE::create_order_from_triggers( $order_id, $triggers );
        
    }
    
    public static function reactivated_subscription( $user_id, $subs_key ) {
        global $wpdb;
        
        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $item_id        = $parts[1];
        
        $order          = new WC_Order($order_id);
        $items          = $order->get_items();
        $order_created  = false;
        $triggers[]     = 'subs_reactivated';
        
        FUE::create_order_from_triggers( $order_id, $triggers );
        
    }
    
    public static function suspended_subscription( $user_id, $subs_key ) {
        global $wpdb;
        
        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $item_id        = $parts[1];
        
        $order          = new WC_Order($order_id);
        $items          = $order->get_items();
        $order_created  = false;
        $triggers[]     = 'subs_suspended';
        
        FUE::create_order_from_triggers( $order_id, $triggers );
        
    }
    
}

$GLOBALS['fue_subscriptions'] = new FUE_Subscriptions();