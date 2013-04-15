<?php

/*
Plugin Name: WooCommerce Facebook Like Share Button
Plugin URI: http://terrytsang.com/shop/shop/woocommerce-facebook-share-like-button/
Description: Add a Facebook Like and Share button to your product pages
Version: 2.0.11
Author: Terry Tsang
Author URI: http://shop.terrytsang.com
*/

/*  Copyright 2012 Terry Tsang (email: terrytsang811@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
 * TSANG_WooCommerce_FbShareLike_Button
 */
if ( ! class_exists( 'TSANG_WooCommerce_FbShareLike_Button' ) ) {
	class TSANG_WooCommerce_FbShareLike_Button{
		var $id_name = 'tsang_fbsharelike_tab';
		var $id = 'tsang_fbsharelike';
		var $app_name = 'tsang_fbsharelike';
		var $plugin_url;
		var $options;
		var $key;
		var $language;
		var $like_verbs;
		var $color_schemes;
		var $fonts;
		var $get_pro_image;
		
		function __construct()
		{
			$this->plugin_url = trailingslashit(plugins_url(null,__FILE__));
			$this->key = 'tsang_fbsharelike';
			$this->language = array();
			
			$this->like_verbs = array('like' => 'Like', 'recommend' => 'Recommend');
			$this->color_schemes = array('default' => 'Default', 'dark' => 'Dark');
			$this->button_aligns = array('left' => 'Left', 'right' => 'Right');
			$this->fonts = array('' => 'Default', 'arial' => 'Arial', 'lucida grande' => 'Lucida Grande', 'segoe ui' => 'Segeo UI', 'tahoma' => 'Tahoma', 'trebuchet ms' => 'Trebuchet MS', 'verdana' => 'Verdana');
			
			$this->includes();
			
			//Add product write panel
			add_action( 'woocommerce_product_write_panels', array(&$this, 'tsang_fbsharelike_main') );
			add_action( 'woocommerce_product_write_panel_tabs', array(&$this,'tsang_fbsharelike_tab') );
			
			//Add product meta
			add_action( 'woocommerce_process_product_meta', array(&$this, 'tsang_fbsharelike_meta') );
			
			//Display on product page for the facebook share and like button
			$this->options = $this->get_options();
			$option_show_after_table = $this->options['custom_show_after_title'];
			
			if( $option_show_after_table == 'yes' )
				add_action( 'woocommerce_single_product_summary', array(&$this, 'tsang_fbsharelike_button' ), 8 );
			else
				add_action( 'woocommerce_single_product_summary', array(&$this, 'tsang_fbsharelike_button' ), 100 );
				
			$this->options = $this->get_options();
			
			//Display setting menu under woocommerce
			add_action( 'admin_menu', array( &$this, 'add_menu_items' ) );
			
			//load stylesheet
			add_action( 'wp_enqueue_scripts', array(&$this, 'custom_plugin_stylesheet') );
			
			//Add javascript after <body> tag
			//add_action( 'init', array( &$this, 'add_afterbody_scripts' ) );
			add_action( 'wp_footer', array( &$this, 'add_afterbody_scripts' ) );
			
			//Add image_src link for facebook thumbnail generation
			add_action( 'wp_head', array( &$this, 'add_head_imagesrc' ) );
		}
		
		function includes()
		{
			include_once( 'includes/languages.php');
		}
		
		/**
		 * Load stylesheet for the page
		 */
		function custom_plugin_stylesheet() {
			wp_register_style( 'fbshare-stylesheet', plugins_url('/css/fbshare.css', __FILE__) );
			wp_enqueue_style( 'fbshare-stylesheet' );
		}
		
		function add_head_imagesrc()
		{
		global $post;

		?>
			<?php if (is_single()) { ?>  
			<meta property="og:url" content="<?php the_permalink() ?>"/>  
			<meta property="og:title" content="<?php single_post_title(''); ?>" />  
			<meta property="og:description" content="<?php echo strip_tags(get_the_excerpt($post->ID)); ?>" />  
			<meta property="og:type" content="product" />  
			<?php 
				if (function_exists('wp_get_attachment_thumb_url')) 
				{
					if(wp_get_attachment_thumb_url(get_post_thumbnail_id($post->ID)) != "") { ?>
					<meta property="og:image" content="<?php echo wp_get_attachment_thumb_url(get_post_thumbnail_id($post->ID)); ?>" />  
					<link rel="image_src" href="<?php echo wp_get_attachment_thumb_url(get_post_thumbnail_id($post->ID)); ?>" />
					<?php }
				} 
			} 
			?> 
		<?php
		}
		
		function tsang_fbsharelike_main()
		{
			global $post;
			$enabled_option = get_post_meta($post->ID, $this->id, true);
			$label = 'Enable';
			$description = 'Enable Facebook Share Like Button on this product?';
			
			//if the option not set for yes or no, then default is yes
			if( 'yes' != $enabled_option && 'no' != $enabled_option ):
				$enabled_option = 'yes'; 
			endif;
			
			$check_id = $this->id;
			
			?>
			<div id="fbsharelike" class="panel woocommerce_options_panel" style="display: none; ">
				<fieldset>
					<p class="form-field">
						<?php
							woocommerce_wp_checkbox(array(
								'id'		=> $check_id,
								'label'		=> __($label, $this->id_name),
								'description'	=> __($description, $this->id_name),
								'value'		=> $enabled_option
							));
						?>
						<br /><br />
						<span class="alignright" style="font-size:75%; font-weight: bold;">Facebook ShareLike Extension by Terry Tsang - <a target="_blank" href="http://terrytsang.com/" title="Facebook ShareLike Extension by Terry Tsang">View More</a></span>
					</p>
				</fieldset>
			</div>
			<?php
		}
		
		function tsang_fbsharelike_tab()
		{
			?>
			<li class="tsang_fbsharelike_tab">	
				<a href="#fbsharelike"><?php _e('Facebook ShareLike', $this->app_name );?></a>
			</li>
			<?php
		}
		
		function tsang_fbsharelike_meta( $post_id )
		{
			$tsang_fbsharelike_option = isset($_POST[$this->id]) ? 'yes' : 'no';
	    	update_post_meta($post_id, $this->id, $tsang_fbsharelike_option);
		}
		
		function tsang_fbsharelike_button()
		{
			global $post;
			$enabled_option = get_post_meta($post->ID, $this->id, true);
			
			if( $enabled_option != 'yes' && $enabled_option != 'no' ):
				$enabled_option = 'yes'; //default new products or unset value to true
			endif;
			
			$this->options = $this->get_options();
			$option_show_only_like = $this->options['custom_show_only_like'];
			$option_facebook_width = $this->options['custom_facebook_width'];
			$option_like_verb 		= $this->options['custom_like_verb'];
			$option_color_scheme 	= $this->options['custom_color_scheme'];
			$option_button_align 	= $this->options['custom_button_align'];
			$option_font 			= $this->options['custom_font'];
			
			$data_send_option 		= true;
			$like_verb_default 		= '';
			$color_scheme_default	= '';
			$font_default			= '';
			$button_align_default	= 'left';
			
			if( $option_show_only_like == 'yes' )
				$data_send_option = false;
			
			if( $option_facebook_width == '' )
				$option_facebook_width = '450';
				
			if( $option_like_verb == 'recommend' )
				$like_verb_default =  ' data-action="recommend"';
				
			if( $option_color_scheme == 'dark' )
				$color_scheme_default = '  data-colorscheme="dark"';
			
			if( $option_button_align == 'right' )
				$button_align_default = 'right';
			
			if( $option_font != '' )
				$font_default = ' data-font="'.$option_font.'"';
				
			if( $enabled_option == 'yes' ):
			?>
				<div class="social-button-container" style="display:block;float:<?php echo $button_align_default; ?>;">
					<div class="social-button"><div class="fb-like" data-send="<?php echo $data_send_option; ?>" data-layout="button_count" data-width="<?php echo $option_facebook_width; ?>" data-show-faces="false"<?php echo $like_verb_default; ?><?php echo $color_scheme_default; ?><?php echo $font_default; ?>></div></div>
				</div>
			<?php
			endif;
		}
		
		function add_menu_items() {
			$wc_page = 'woocommerce';
			$comparable_settings_page = add_submenu_page( $wc_page , __( 'FbShareLike Setting', 'facebook-sharelike' ), __( 'FbShareLike Setting', 'facebook-sharelike' ), 'manage_options', 'fbsharelike-settings', array(
				&$this,
				'options_page'
			));

			//$image = $this->plugin_url . '/assets/images/icon.png';
			/*add_menu_page( __( 'FbShareLike', 'facebook-sharelike' ), __( 'FbShareLike', 'facebook-sharelike' ), 'manage_options', 'fbshare_settings', array(
				&$this,
				'options_page'
			), $image);*/
		}
		
		//start to include any script after <body> tag
		function add_afterbody_scripts()
		{
			$custom_facebook_app_id = $this->options['custom_facebook_app_id'];
			$option_language_code = $this->options['custom_language_code'];
			
			if ( ! $custom_facebook_app_id ) 
				$custom_facebook_app_id = '216944597824';
			
			if ( ! $option_language_code )
				$option_language_code = 'en_GB';
				
			echo '<div id="fb-root"></div> 
        	<script>(function(d, s, id) { 
	            var js, fjs = d.getElementsByTagName(s)[0]; 
	            if (d.getElementById(id)) return; 
	            js = d.createElement(s); js.id = id; 
	            js.src = "//connect.facebook.net/'.$option_language_code.'/all.js#xfbml=1&appId='.$custom_facebook_app_id.'"; 
	            fjs.parentNode.insertBefore(js, fjs); 
        	}(document, \'script\', \'facebook-jssdk\'));</script>';
		}
		
		function options_page() 
		{ 
			// If form was submitted
			if ( isset( $_POST['submitted'] ) ) 
			{			
				check_admin_referer( 'facebook-sharelike' );
				
				$this->options['custom_facebook_app_id'] = ! isset( $_POST['custom_facebook_app_id'] ) ? '216944597824' : $_POST['custom_facebook_app_id'];
				$this->options['custom_facebook_width'] = ! isset( $_POST['custom_facebook_width'] ) ? '450' : $_POST['custom_facebook_width'];
				$this->options['custom_show_after_title'] = ! isset( $_POST['custom_show_after_title'] ) ? '' : $_POST['custom_show_after_title'];
				$this->options['custom_show_only_like'] = ! isset( $_POST['custom_show_only_like'] ) ? '' : $_POST['custom_show_only_like'];
				$this->options['custom_like_verb'] = ! isset( $_POST['custom_like_verb'] ) ? 'like' : $_POST['custom_like_verb'];
				$this->options['custom_color_scheme'] = ! isset( $_POST['custom_color_scheme'] ) ? 'default' : $_POST['custom_color_scheme'];
				$this->options['custom_language_code'] = ! isset( $_POST['custom_language_code'] ) ? 'en_GB' : $_POST['custom_language_code'];
				$this->options['custom_button_align'] = ! isset( $_POST['custom_button_align'] ) ? 'left' : $_POST['custom_button_align'];
				$this->options['custom_font'] = ! isset( $_POST['custom_font'] ) ? '' : $_POST['custom_font'];
				
				update_option( $this->key, $this->options );
				
				// Show message
				echo '<div id="message" class="updated fade"><p>' . __( 'Facebook ShareLike options saved.', 'facebook-sharelike' ) . '</p></div>';
			} 
			
			$custom_facebook_app_id	 	= $this->options['custom_facebook_app_id'];
			$custom_facebook_width 		= $this->options['custom_facebook_width'];
			$custom_show_after_title 	= $this->options['custom_show_after_title'];
			$custom_show_only_like 		= $this->options['custom_show_only_like'];
			$custom_like_verb 			= $this->options['custom_like_verb'];
			$custom_color_scheme 		= $this->options['custom_color_scheme'];
			$custom_language_code 		= $this->options['custom_language_code'];
			$custom_button_align 		= $this->options['custom_button_align'];
			$custom_font				= $this->options['custom_font'];
			
			if ( ! $custom_facebook_app_id ) 
				$custom_facebook_app_id = '216944597824';
			
			$checked_value = '';
			if($custom_show_after_title == 'yes')
				$checked_value = 'checked="checked"';
				
			$checked_value2 = '';
			if($custom_show_only_like == 'yes')
				$checked_value2 = 'checked="checked"';
				
			if($custom_facebook_width == '')
				$custom_facebook_width = '450';
			
			if($custom_button_align == '')
				$custom_button_align = 'left';
			
			global $wp_version;
		
			$imgpath = $this->plugin_url.'/assets/images/';
			$actionurl = $_SERVER['REQUEST_URI'];
			$nonce = wp_create_nonce( 'facebook-sharelike' );
			
			$this->options = $this->get_options();
					
			// Configuration Page
					
			?>
			<div id="icon-options-general" class="icon32"></div>
			<h3><?php _e( 'FBShareLike Options', 'facebook-sharelike' ); ?></h3>
			
			
			<table width="90%" cellspacing="2">
			<tr>
				<td width="70%">
					<form action="<?php echo $actionurl; ?>" method="post">
					<table class="widefat fixed" cellspacing="0">
							<thead>
								<th width="30%">Option</th>
								<th>Setting</th>
							</thead>
							<tbody>
								<tr>
									<td>Your Facebook App ID<br /><span style="color:#ccc;">(leave it as default value if you do not have any facebook application id)</span></td>
									<td><input id="custom_facebook_app_id" name="custom_facebook_app_id" value="<?php echo $custom_facebook_app_id; ?>" size="20"/></td>
								</tr>
								<tr>
									<td>Width</td>
									<td><input id="custom_facebook_width" name="custom_facebook_width" value="<?php echo $custom_facebook_width; ?>" size="20"/></td>
								</tr>
								<tr>
									<td>Button Alignment</td>
									<td>
										<select name="custom_button_align">
										<?php foreach($this->button_aligns as $align_option => $button_align): ?>
											<?php if($align_option == $custom_button_align): ?>
												<option selected="selected" value="<?php echo $align_option; ?>"><?php echo $button_align; ?></option>
											<?php else: ?>
												<option value="<?php echo $align_option; ?>"><?php echo $button_align; ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>Show button below product title</td>
									<td><input class="checkbox" name="custom_show_after_title" id="custom_show_after_title" value="yes" <?php echo $checked_value; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td>Show Like button only</td>
									<td><input class="checkbox" name="custom_show_only_like" id="custom_show_only_like" value="yes" <?php echo $checked_value2; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td>Verb to display</td>
									<td>
										<select name="custom_like_verb">
										<?php foreach($this->like_verbs as $verb_option => $verb_name): ?>
											<?php if($verb_option == $custom_like_verb): ?>
												<option selected="selected" value="<?php echo $verb_option; ?>"><?php echo $verb_name; ?></option>
											<?php else: ?>
												<option value="<?php echo $verb_option; ?>"><?php echo $verb_name; ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>Color Scheme</td>
									<td>
										<select name="custom_color_scheme">
										<?php foreach($this->color_schemes as $scheme_option => $scheme_name): ?>
											<?php if($scheme_option == $custom_color_scheme): ?>
												<option selected="selected" value="<?php echo $scheme_option; ?>"><?php echo $scheme_name; ?></option>
											<?php else: ?>
												<option value="<?php echo $scheme_option; ?>"><?php echo $scheme_name; ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>Font</td>
									<td>
										<select name="custom_font">
										<?php foreach($this->fonts as $font_option => $font_name): ?>
											<?php if($font_option == $custom_font): ?>
												<option selected="selected" value="<?php echo $font_option; ?>"><?php echo $font_name; ?></option>
											<?php else: ?>
												<option value="<?php echo $font_option; ?>"><?php echo $font_name; ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>Language Setting</td>
									<td>
										<select name="custom_language_code">
										<?php foreach($this->language as $lang_code => $lang_name): ?>
											<?php if($lang_code == $custom_language_code): ?>
												<option selected="selected" value="<?php echo $lang_code; ?>"><?php echo $lang_name; ?></option>
											<?php else: ?>
												<option value="<?php echo $lang_code; ?>"><?php echo $lang_name; ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan=2">
										<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Options'); ?>" id="submitbutton" />
										<input type="hidden" name="submitted" value="1" /> 
										<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce; ?>" />
									</td>
								</tr>
							
							</tbody>
					</table>
					</form>
				
				</td>
				
				<td width="30%" style="background:#ececec;padding:10px 5px;" valign="top">
					<p><b>WooCommerce Facebook Share Like Button</b> is a free woocommerce plugin developed by <a href="http://www.terrytsang.com" target="_blank" title="Terry Tsang - a php and symfony developer">Terry Tsang</a>. I have spent a lot of time to improve and writing this.</p>
					
					<?php
						$get_pro_image = $this->plugin_url . '/assets/images/get-social-buttons-pro.png';
					?>
					<div><a href="http://terrytsang.com/shop/shop/woocommerce-social-buttons-pro/" target="_blank" title="WooCommerce Social Buttons PRO"><img src="<?php echo $get_pro_image; ?>" border="0" /></a></div>
					
					<h3>Get More Extensions</h3>
					
					<p>Vist <a href="http://shop.terrytsang.com" target="_blank" title="Premium &amp; Free Extensions/Plugins for E-Commerce by Terry Tsang">My Shop</a> to get more free and premium extensions/plugins for your ecommerce platform.</p>
					
					<h3>Spreading the Word</h3>

					<ul style="list-style:dash">If you find this plugin helpful, you can:	
						<li>- Write and review about it in your blog</li>
						<li>- Rate it on <a href="http://wordpress.org/extend/plugins/woocommerce-facebook-share-like-button/" target="_blank">wordpress plugin page</a></li>
						<li>- Share on your social media<br />
						<a href="http://www.facebook.com/sharer.php?u=http://terrytsang.com/shop/shop/woocommerce-facebook-share-like-button/&amp;t=WooCommerce Facebook Share Like Button" title="Share this WooCommerce Facebook Share Like Button plugin on Facebook" target="_blank"><img src="<?php echo $this->plugin_url; ?>/images/social_facebook.png" alt="Share this WooCommerce Facebook Share Like Button plugin on Facebook"></a> 
						<a href="https://twitter.com/share?url=https%3A%2F%2Fterrytsang.com%2Fshop%2Fshop%2Fwoocommerce-facebook-share-like-button%2F" target="_blank"><img src="<?php echo $this->plugin_url; ?>/images/social_twitter.png" alt="Tweet about WooCommerce Facebook Share Like Button plugin"></a>
						<a href="http://linkedin.com/shareArticle?mini=true&amp;url=http://terrytsang.com/shop/shop/woocommerce-facebook-share-like-button/&amp;title=WooCommerce Facebook Share Like Button plugin" title="Share this WooCommerce Facebook Share Like Button plugin on LinkedIn" target="_blank"><img src="<?php echo $this->plugin_url; ?>/images/social_linkedin.png" alt="Share this WooCommerce Facebook Share Like Button plugin on LinkedIn"></a>
						</li>
						<li>- Or make a donation</li>
					</ul>
					
					<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LJWSJDBBLNK7W" target="_blank"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" alt="" /></a>

					<h3>Thank you for your support!</h3>
				</td>
				
			</tr>
			</table>
			
			
			<br />
			
			<?php
		}
		
		// Handle our options
		function get_options() {
			$options = array(
				'custom_facebook_app_id' => '216944597824',
				'custom_show_after_title' => '',
			);
			$saved = get_option( $this->key );
			
			if ( ! empty( $saved ) ) {
				foreach ( $saved as $key => $option ) {
					$options[$key] = $option;
				}
			}
				  
			if ( $saved != $options ) {
				update_option( $this->key, $options );
			}
			
			return $options;
		}
	
	}
}


// finally instantiate the plugin class
$TSANG_WooCommerce_FbShareLike_Button = &new TSANG_WooCommerce_FbShareLike_Button();

?>