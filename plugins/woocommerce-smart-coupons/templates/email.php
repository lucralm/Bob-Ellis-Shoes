<?php if (!defined('ABSPATH')) exit; ?>

<?php woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading )); ?>

<?php echo $message_from_sender; ?>

<p><?php echo sprintf(__("To redeem your discount use the following coupon code during checkout:", 'wc_smart_coupons'), $blogname); ?></p>

<strong style="margin: 10px 0; font-size: 2em; line-height: 1.2em; font-weight: bold; display: block; text-align: center;"><?php echo $coupon_code; ?></strong>

<center><a href="<?php echo $url; ?>"><?php echo sprintf(__("Visit store",'wc_smart_coupons') ); ?></a></center>

<div style="clear:both;"></div>

<?php woocommerce_get_template('emails/email-footer.php'); ?>