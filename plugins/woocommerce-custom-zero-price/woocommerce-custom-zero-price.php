<?php
/*
Plugin Name: WooCommerce Custom Zero Price
Plugin URI: http://www.oxfordshireweb.com
Description: Allows you to add a custom message when a product has no price
Version: 1.0
Author: Joe Clifton
Author URI: http://www.oxfordshireweb.com
License: GPL
*/

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    // Enqueue the color picker script in the admin area
    
    function colorPicker() {
        wp_register_script( 'colorPicker', plugins_url() . '/woocommerce-custom-zero-price/javascript/jscolor/jscolor.js' );
        wp_enqueue_script('colorPicker');
    }

    add_action('admin_enqueue_scripts', 'colorPicker');
    
    // Call the zero price if the WooCommerce price field is empty
    
    add_filter('woocommerce_empty_price_html', 'woocommerce_custom_zero_price');
 
    function woocommerce_custom_zero_price() {
        global $post;
        $message = get_post_meta($post->ID, 'zeroprice_message', true);
        $color = get_post_meta($post->ID, 'zeroprice_color', true);
        $id = get_the_ID();
        if (!empty($message)) {
            if(!empty($color)) {
                echo '<span class="woocommerce-zeroprice" id="woocommerce-zeroprice-',$id,'" style="color: #';
                echo $color;
                echo ' !important;">';
                echo $message;
                echo '</span>';
            } else {
                echo '<span class="woocommerce-zeroprice" id="woocommerce-zeroprice-',$id,'">';
                echo $message;
                echo '</span>';
            }
        }
    }
    
// Add the Meta Box
    
    function add_zeroprice_meta_box() {  
        add_meta_box(  
            'zeroprice_meta_box', // $id  
            'Custom Zero Price', // $title  
            'show_zeroprice_meta_box', // $callback  
            'product', // $page  
            'side', // $context  
            'default'); // $priority  
    }  
    add_action('add_meta_boxes', 'add_zeroprice_meta_box');
    
    // Field Array
    
    $prefix = 'zeroprice_';  
    $zeroprice_meta_fields = array(  
        array(  
            'label'=> 'Zero Price Message',  
            'desc'  => 'Enter the message to display if there is no price',  
            'id'    => $prefix.'message',  
            'type'  => 'textarea'  
        ),
        array(  
            'label'=> 'Color',  
            'desc'  => 'The colour of the text - this will override any color set in the stylesheet',  
            'id'    => $prefix.'color',  
            'type'  => 'text'  
        )
    );  
    
    // The Callback
    
    function show_zeroprice_meta_box() {  
    global $zeroprice_meta_fields, $post;
    $id = get_the_ID();
    // Use nonce for verification  
    echo '<input type="hidden" name="zeroprice_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';  
        // Begin the field table and loop  
        echo '<table class="form-table">';  
        foreach ($zeroprice_meta_fields as $field) {  
            // get value of this field if it exists for this post  
            $meta = get_post_meta($post->ID, $field['id'], true);  
            // begin a table row with  
            echo '<tr> 
                    <th><label for="'.$field['id'].'">'.$field['label'].'</label></th> 
                    <td>';  
                    switch($field['type']) {  
                        case 'text':  
                            echo '<input type="text" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="6" maxlength="6" class="color {required:false}" /> 
                                <br /><span class="description">'.$field['desc'].'</span>';  
                        break;
                        case 'textarea':  
                            echo '<textarea name="'.$field['id'].'" id="'.$field['id'].'" cols="25" rows="4">'.$meta.'</textarea> 
                                <br /><span class="description">'.$field['desc'].'</span>';  
                        break;  
                    } //end switch  
            echo '</td></tr>';  
        } // end foreach  
        echo '</table>'; // end table
        if (isset($id)) {
            echo '<p>Your product/post id is: <strong>',$id,'</strong></p>';
        }
    }  
      
    // Save the Data
    
    function save_zeroprice_meta($post_id) {  
        global $zeroprice_meta_fields;  
        // verify nonce  
        if (!wp_verify_nonce($_POST['zeroprice_meta_box_nonce'], basename(__FILE__)))  
            return $post_id;  
        // check autosave  
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)  
            return $post_id;  
        // check permissions  
        if ('page' == $_POST['post_type']) {  
            if (!current_user_can('edit_page', $post_id))  
                return $post_id;  
            } elseif (!current_user_can('edit_post', $post_id)) {  
                return $post_id;  
        }  
        // loop through fields and save the data  
        foreach ($zeroprice_meta_fields as $field) {  
            $old = get_post_meta($post_id, $field['id'], true);  
            $new = $_POST[$field['id']];  
            if ($new && $new != $old) {  
                update_post_meta($post_id, $field['id'], $new);  
            } elseif ('' == $new && $old) {  
                delete_post_meta($post_id, $field['id'], $old);  
            }  
        } // end foreach  
    }  
    add_action('save_post', 'save_zeroprice_meta');
    
}
?>