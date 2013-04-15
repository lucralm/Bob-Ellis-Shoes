=== Plugin Name ===
Contributors: terrytsang
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=terry@terrytsang.com&item_name=Donation+for+TerryTsang+Wordpress+WebDev
Plugin Name: WooCommerce Facebook Share Like Button
Plugin URI:  http://terrytsang.com/shop/shop/woocommerce-facebook-share-like-button/
Tags: woocommerce, facebook, e-commerce, like
Requires at least: 2.7
Tested up to: 3.5.1
Stable tag: 2.0.11
Version: 2.0.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce plugin that implements facebook share and like button on product page with flexible options.

== Description ==

This is a WooCommerce plugin that implements facebook share and like button on the product page with flexible options. After you activated the plugin, the default option is 'Enabled' for all the existing products.

Under WooCommerce sidebar panel, there will be a new child menu link called 'FBShareLike Settings' section.

The list of options for the section above:

*   Replace default app id value with yours one, or else just leave it
*   Set width
*   Choose 'Button Alignment' - default(left) or right
*   Enable "Show button below product title" option
*   Enable 'Show Like button only' option
*   Choose 'Verb to display' for LIKE button - default(like) or recommend
*   Choose 'Color Scheme' - default(light) or dark button
*   Choose 'Font' - default or custom font (Arial, Lucida Grande, Segeo UI, Tahoma, Trebuchet MS, Verdana)
*   Select 'Language Setting' for the language for button. (77 languages supported)

NOTE: This plugin requires the WooCommerce Extension.

[GET PRO VERSION] (http://terrytsang.com/shop/shop/woocommerce-social-buttons-pro/)
[VIEW MORE PLUGINS] (http://shop.terrytsang.com)


== Installation ==

1. Upload the entire *woocommerce-terrytsang-fbsharelike* folder to the */wp-content/plugins/* directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new menu link 'FbShareLike Setting' appeared under WooCommerce sidebar panel, you can click that to update options.
4. That's it. You're ready to go and cheers!

== Screenshots ==

1. Screenshot Admin Product Enabled Option 
2. Screenshot Frontend Product Detail Page
3. Screenshot Admin FbShareLike Settings Option Page
4. Screenshot Frontend - Chinese + Show After Product Title
5. Screenshot Frontend - French + Show Like Button Only + Button Align Right

== Frequently Asked Questions ==

= After activated the plugin, do i need to insert facebook javascript after <body> tag? =

No, you can straight away use the plugin as the plugin included the script.


= If i want to use my own facebook application id, what should i do? =

To update your facebook app id for the plugin, go to Admin left sidebar, menu 'FbShareLike Settings' under 'WoocCommerce' and update option there. (NOTE: This is not your facebook page URL)


= If i want to align button to right? =

You can select the option 'Right' at 'Button Alignment' section.


= If i want to change the default button position[bottom of product summary] to position after product title, what should i do? =

You can check the option 'Show button after product title' at 'FbShareLike Settings' section.


= If i only want to show facebook like button on product page, not the share button. =

You can check the option 'Show only like button' at 'FbShareLike Settings' section.


= If i only want to show dark color button on product page, not the light color button. =

You can select 'Dark' at 'Color Scheme' option under 'FbShareLike Settings' section.


= If i only want to change font for the button text. =

You can select custom font at 'Font' option under 'FbShareLike Settings' section.


= If i only want to show different language for the button text? =

You can select at 'Language Settings' option under 'FbShareLike Settings' section. (77 Languages Supported Now)


== Upgrade Notice ==

coming soon...

== Changelog ==

= 2.0.11 =

* Fixed bugs

= 2.0.10 =

* Updated CSS for facebook like and send button box that was cropped

= 2.0.9 =

* Fixed Double add to cart issues

= 2.0.8 =

* Fixed bugs

= 2.0.7 =

* Updated : updated "overflow: visible;" css for social-button-container 
* NEW : add custom font option for the setting page

= 2.0.6 =

* Fixed : remove additional <br> below the button
* Updated : add "overflow: hidden;" css for social-button-container 
* NEW : add new link for "Get Social Buttons Pro" with more social media

= 2.0.5 =

* Fixed : remove additional bloginfo description output on top page
* NEW : add new option 'Button Alignment' where you can choose to align left or right

= 2.0.4 =

* Fixed : subtitle problem
* Updated : og:type set to 'product'


= 2.0.3 =

* Fixed undefined $post
* Updated facebook application id help description


= 2.0.2 =

* Fixed bugs


= 2.0.1 =

* Fixed missing variables
* Update Product Description (200 chars limit) for OG <Meta> description


= 2.0.0 =

* Change menu 'FbShareLike' sidebar to 'FBShareLike Settings' link under 'WooCommerce' sidebar panel
* Add more options for the setting page (width, languages, verb to display and color scheme)
* Add OG <Meta> tags for facebook graph apps
