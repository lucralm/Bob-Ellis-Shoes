/* This file handles the functionality of the shortcode generator button in the toolbar of the visual Editor*/

(function() {
    tinymce.create('tinymce.plugins.woo_product_slider', {
        init : function(ed, url) {
            ed.addButton('woo_product_slider', {
                title : 'Add a Slider',
                image : url+'/../images/shortcode-icon.png',
                onclick : function() {
                   // triggers the thickbox
						var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
						W = W - 20;
						H = H - 23;
						tb_show( 'Woo Product Slider',woo_product_slider_url+'shortcode_generator.php?width=' + W + '&height=' + H + '&inlineId=mygallery-form' );
               }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('woo_product_slider', tinymce.plugins.woo_product_slider);
})();