<div class="wrap woocommerce">
	<div id="icon-edit-comments" class="icon32"><br></div>
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
    	<a href="admin.php?page=wc-followup-emails&amp;tab=list" class="nav-tab <?php echo ($tab == 'list') ? 'nav-tab-active' : ''; ?>"><?php _e('Follow-Up Emails', 'wc_followup_emails'); ?></a>
        <a href="admin.php?page=wc-followup-emails&amp;tab=new" class="nav-tab <?php echo ($tab == 'new') ? 'nav-tab-active' : ''; ?>"><?php _e('New Email', 'wc_followup_emails'); ?></a>
        <?php do_action( 'fue_settings_tabs', $tab ); ?>
        <a href="admin.php?page=wc-followup-emails&amp;tab=settings" class="nav-tab <?php echo ($tab == 'settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'wc_followup_emails'); ?></a>
    </h2>
    
    <?php if (isset($_GET['created'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email created', 'wc_followup_emails'); ?></p></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email updated', 'wc_followup_emails'); ?></p></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['settings_updated'])): ?>
    <div id="message" class="updated"><p><?php _e('Settings updated', 'wc_followup_emails'); ?></p></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email deleted!', 'wc_followup_emails'); ?></p></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
    <div id="message" class="error"><p><?php echo $_GET['error']; ?></p></div>
    <?php endif; ?>
    
    <?php do_action('fue_settings_notification'); ?>