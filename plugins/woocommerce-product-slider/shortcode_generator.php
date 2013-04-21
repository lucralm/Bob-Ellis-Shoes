<?php
/*
* Shortcode_generator.php generates the shorcode based on selection of the options
* This html is pops up when clicked on the toolbar button of the visual editor.
*
* example : 
* [woo_product_slider 
*	animation_duration="600" 
*	width="100" height="100" 
*	direction_nav="true" 
*	cat_ids="87,88" 
*	template="merchant-theme.css" 
*	animation="fade" 
*	num_of_prods="6" 
*	show_price="true" 
*	show_title="true"]
*/

//loads WordPress JQuery
require_once( "../../../wp-load.php" );
add_action('wp_enqueue_scripts', 'wc_slider_scripts_method');

function wc_slider_scripts_method() {
	wp_enqueue_script('jquery');
}
?>
<html>
	<head>			
		<style>
		table {font-size:12px;}
		.smalltext{font-style:italic;}
		.stf {display:none;}
		</style>
		<script>
			//generates the shortcode based on selection in the form
			function gen_shortcode() {
			
				var shortcode = '[woo_product_slider ';
				
				//animation speed
				if(jQuery('#animation_speed').val() != "") {
					shortcode +=' animation_duration="'+jQuery('#animation_speed').val()+'"';
				}			
			
				//image width
				if(jQuery('#image_width').val() != "") {
					shortcode +=' width="'+jQuery('#image_width').val()+'"';
				}
				
				//image height
				if(jQuery('#image_height').val() != "") {
					shortcode +=' height="'+jQuery('#image_height').val()+'"';
				}
				
				//navigation
				if(jQuery('#navigation').attr('checked')) {
					shortcode +=' direction_nav="true"';
				}
				
				//if animation type is slide
				if(jQuery('#animation').val() == "slide") {
					
					if(jQuery('#speed').val() != "") {
						shortcode +=' speed="'+jQuery('#speed').val()+'"';
					}
					
					if(jQuery('#autostart').attr('checked')) {
						shortcode +=' slide_show="'+jQuery('#autostart').val()+'"';
					} else {
						shortcode +=' slide_show = false';
					}
					
					if(jQuery('#slide_direction').val() != "") {
						shortcode +=' slide_direction="'+jQuery('#slide_direction').val()+'"';
					}					
				
					
				}

				//slides limit
				if(jQuery('#slides_limit').val() != "") {
						shortcode +=' num_of_prods="'+jQuery('#slides_limit').val()+'"';
				}
				
				//product IDs
				if(jQuery('#prod_ids').val()!='') {
					shortcode +=' prod_ids="'+jQuery('#prod_ids').val()+'"';
				} else if(jQuery('#prod_tags').val()!='') {
					shortcode +=' prod_tags="'+jQuery('#prod_tags').val()+'"';
				} else if(jQuery('#cat_ids').val()!='') {
					shortcode +=' cat_ids="'+jQuery('#cat_ids').val()+'"';
				}		

				
				//Price	
				if(jQuery('#show_price').attr('checked')) {
					shortcode +=' show_price="true"';
				} else {
					shortcode +=' show_price="false"';
				}
			
				//Title
				if(jQuery('#show_title').attr('checked')) {
					shortcode +=' show_title="true"';
				} else {
					shortcode +=' show_title="false"';
				}

				//remaining
				shortcode += ' template="'+jQuery('#template').val()+'"';
				shortcode += ' animation="'+jQuery('#animation').val()+'"';
				shortcode += ' image_source ="'+jQuery('#image_source').val()+'"';
				shortcode +=']';

				// inserts the shortcode into the active editor
				tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
				
				// closes Thickbox
				tb_remove();

			}//end of gen_shortcode function
			
			
			//hide or show set of options in the form based on animation type "fade" or "slide"
			jQuery('#animation').change(function() {
				if(jQuery(this).val()=="fade") {
					jQuery('.stf').css('display','none');
				} else {
					jQuery('.stf').css('display','block');
				}
			});
			
		</script>
	</head>
	<body>
		<table cellspacing="5" cellpadding="5">
				<tr>
					<td align="left">Choose Template</td>
					<td>
						<div>
							<div>
								<select name="template" id="template" class="select">
									<?php
										$files_list = scandir(realpath('./templates'));
										unset($files_list[0]);
										unset($files_list[1]);
										foreach($files_list as $filename) {
											echo "<option value='$filename'>$filename";
										}
									?>
								</select>								
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td align="left">Slides Limits</td>
					<td>				
						<select name="slides_limit" id="slides_limit" class="select">
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="6">6</option>
						</select>
						<span class="smalltext">Pick number of slides visible at once.</SPAN>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">Select either categories or enter product Tags or product IDs</td>
				</tr>
				<tr>
					<td align="left">Category IDs</td>
					<td>
						<input type="text" name="cat_ids" id="cat_ids" value="" size="30"> <span class="smalltext">Please enter Category IDs with comma seperated</span>
					</td>
				</tr>
				<tr>
					<td align="left">Product Tags</td>
					<td  align="left">
						<input type="text" name="prod_tags" id="prod_tags" value="" size="30"> <span class="smalltext">Please enter Product Tags with comma seperated</span>
					</td>
				</tr>
				<tr>
					<td align="left" style="border-bottom: 1px solid #CCC;">Product IDs</td>
					<td  align="left" style="border-bottom: 1px solid #CCC;">
						<input type="text" name="prod_ids" id="prod_ids" value="" size="30"> <span class="smalltext">Please enter Product IDs with comma seperated</span>
					</td>
				</tr>
				<tr>
					<td align="left">Show Title</td>
					<td>
						<span class="on_off">
							<input type="checkbox" value="true" name="show_title" id="show_title" checked="checked">
						</span>
					</td>
				</tr>	
				<tr>
					<td style="border-bottom: 1px solid #CCC;">Show Price</td>
					<td  style="border-bottom: 1px solid #CCC;">
						<span class="on_off">
							<input type="checkbox" value="true" name="show_price" id="show_price" checked="checked">
						</span>
					</td>
				</tr>	
				<tr>
					<td align="left">Image Source</td>
					<td>
						<select name="image_source" id="image_source" class="select">
							<option value="thumbnail">Thumbnail</option>
							<option value="medium">Medium</option>
							<option value="large">Large</option>
							<option value="full">Full</option>
						</select>
					</td>
				</tr>	
				<tr>
					<td align="left">Image Height</td>
					<td>
						<input type="text" name="image_height" id="image_height" value="100" size="5">% <span class="smalltext">Enter Image Height</span>
					</td>
				</tr>
				<tr>
					<td align="left"  style="border-bottom: 1px solid #CCC;">Image Width</td>
					<td   style="border-bottom: 1px solid #CCC;">
						<input type="text" name="image_width" id="image_width" value="100" size="5">% <span class="smalltext">Enter Image Width</span>
					</td>
				</tr>	
				<tr>
					<td align="left">Navigation</td>
					<td>
						<input type="checkbox" value="1" name="navigation" id="navigation" checked="checked">
						<span class="smalltext">Enable navigation arrows</span>
					</td>
				</tr>	
				<tr>
					<td align="left">Animation</td>
					<td>
						<div>
							<div>
								<select name="animation" id="animation" class="select">
									<option value="fade">Fade</option>
									<option value="slide">Slide</option>
								</select>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td align="left">Animation Speed</td>
					<td>
						<input type="text" name="animation_speed" id="animation_speed" value="600" size="5"> <span class="smalltext">Set the speed of animations, in milliseconds</span>
					</td>
				</tr>		
				<tr>
					<td colspan="2">
						<div  class="stf" style="padding:0px;" border="1">
							<table cellspacing="5" cellpadding="5" border="0" width="100%">
							<tr>
								<td align="left">Slide Speed</td>
								<td>
									<input type="text" name="speed" id="speed" value="4000" size="5"> <span class="smalltext">Set the speed of the slideshow cycling, in milliseconds</span>
								</td>
							</tr>		
							<tr>
								<td align="left">Auto-Start</td>
								<td>
									<span class="on_off">
										<input type="checkbox" value="true" name="autostart" id="autostart" checked="checked">
									</span>
								</td>
							</tr>				
							<tr>
								<td align="left">Slide Direction</td>
								<td>
									<select name="slide_direction" id="slide_direction" class="select">
										<option value="horizontal">Horizontal</option>
										<option value="vertical">Vertical</option>
									</select>
								</td>
							</tr>	
						</table>
						</div>
					</td>
				</tr>						
				
			
				<tr>
				 <td colspan="2"><input type="button" name="gen_shortco" value="Generate Shortcode" onClick="gen_shortcode();"></td>
				</tr>
		</table>
	</body>
</html>