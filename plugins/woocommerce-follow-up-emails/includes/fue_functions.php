<?php

function fue_add_custom_fields( $matches ) {
    if ( empty($matches) ) return '';
    
    $id = $matches[1];
    $cf = $matches[2];
    
    $meta = get_post_meta( $id, $cf, true );
    
    if ($meta) return $meta;
    return '';
}

function fue_settings_header( $tab ) {
    include FUE_TEMPLATES_DIR .'/settings_header.php';
}

function fue_settings_footer() {
    include FUE_TEMPLATES_DIR .'/settings_footer.php';
}