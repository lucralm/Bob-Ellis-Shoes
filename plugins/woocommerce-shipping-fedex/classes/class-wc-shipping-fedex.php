<?php
/**
 * WC_Shipping_Fedex class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_Fedex extends WC_Shipping_Method {

	private $default_boxes  = array(
		array(
			'id'                 => 'FEDEX_10KG_BOX',
			'max_weight'         => 22,
			'box_weight'         => 0.019375,
			'length'             => 15.81,
			'width'              => 12.94,
			'height'             => 10.19
		),
		array(
			'id'                 => 'FEDEX_25KG_BOX',
			'max_weight'         => 55,
			'box_weight'         => 0.035625,
			'length'             => 21.56,
			'width'              => 16.56,
			'height'             => 13.19
		),
		array(
			'id'                 => 'FEDEX_BOX_SMALL',
			'max_weight'         => 20,
			'box_weight'         => 0.28125,
			'length'             => 12.25,
			'width'              => 10.9,
			'height'             => 1.5
		),
		array(
			'id'                 => 'FEDEX_BOX_MEDIUM',
			'max_weight'         => 20,
			'box_weight'         => 0.40625,
			'length'             => 13.25,
			'width'              => 11.5,
			'height'             => 2.38
		),
		array(
			'id'                 => 'FEDEX_BOX_LARGE',
			'max_weight'         => 20,
			'box_weight'         => 0.90625,
			'length'             => 17.88,
			'width'              => 12.38,
			'height'             => 3
		),
		array(
			'id'                 => 'FEDEX_ENVELOPE',
			'max_weight'         => 1.1,
			'box_weight'         => 0.1125,
			'length'             => 13.189,
			'width'              => 9.252,
			'height'             => 1
		),
		array(
			'id'                 => 'FEDEX_PAK',
			'max_weight'         => 5.5,
			'box_weight'         => 0.0625,
			'length'             => 15.5,
			'width'              => 12,
			'height'             => 1
		),
		array(
			'id'                 => 'FEDEX_PAK_XL',
			'max_weight'          => 5.5,
			'box_weight'         => 0.09375,
			'length'             => 20.75,
			'width'              => 17.5,
			'height'             => 1
		)
	);

	private $found_rates;
	private $services = array(
		'FIRST_OVERNIGHT'                    => 'FedEx First Overnight',
		'PRIORITY_OVERNIGHT'                 => 'FedEx Priority Overnight',
		'STANDARD_OVERNIGHT'                 => 'FedEx Standard Overnight',
		'FEDEX_2_DAY_AM'                     => 'FedEx 2Day A.M',
		'FEDEX_2_DAY'                        => 'FedEx 2Day',
		'FEDEX_EXPRESS_SAVER'                => 'FedEx Express Saver',
		'GROUND_HOME_DELIVERY'               => 'FedEx Ground Home Delivery',
		'FEDEX_GROUND'                       => 'FedEx Ground',
		'INTERNATIONAL_ECONOMY'              => 'FedEx International Economy',
		'INTERNATIONAL_FIRST'                => 'FedEx International First',
		'INTERNATIONAL_PRIORITY'             => 'FedEx International Priority',
		'EUROPE_FIRST_INTERNTIONAL_PRIORITY' => 'FedEx Europe First International Priority',
		'FEDEX_1_DAY_FREIGHT'                => 'FedEx 1 Day Freight',
		'FEDEX_2_DAY_FREIGHT'                => 'FedEx 2 Day Freight',
		'FEDEX_3_DAY_FREIGHT'                => 'FedEx 3 Day Freight',
		'INTERNATIONAL_ECONOMY_FREIGHT'      => 'FedEx Economy Freight',
		'INTERNATIONAL_PRIORITY_FREIGHT'     => 'FedEx Priority Freight',
		'FEDEX_FREIGHT'                      => 'Fedex Freight',
		'FEDEX_NATIONAL_FREIGHT'             => 'FedEx National Freight',
		'INTERNATIONAL_GROUND'               => 'FedEx International Ground',
		'SMART_POST'                         => 'FedEx Smart Post'
	);

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'fedex';
		$this->method_title       = __( 'FedEx', 'wc_fedex' );
		$this->method_description = __( 'The <strong>FedEx</strong> extension obtains rates dynamically from the FedEx API during cart/checkout.', 'wc_fedex' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title           = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->availability    = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
		$this->countries       = isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
		$this->origin          = isset( $this->settings['origin'] ) ? $this->settings['origin'] : '';

		$this->account_number  = isset( $this->settings['account_number'] ) ? $this->settings['account_number'] : '';
		$this->meter_number    = isset( $this->settings['meter_number'] ) ? $this->settings['meter_number'] : '';
		$this->smartpost_hub   = isset( $this->settings['smartpost_hub'] ) ? $this->settings['smartpost_hub'] : '';
		$this->api_key         = isset( $this->settings['api_key'] ) ? $this->settings['api_key'] : '';
		$this->api_pass        = isset( $this->settings['api_pass'] ) ? $this->settings['api_pass'] : '';
		$this->production      = isset( $this->settings['production'] ) && $this->settings['production'] == 'yes' ? true : false;
		$this->debug           = isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;

		$this->insure_contents = isset( $this->settings['insure_contents'] ) && $this->settings['insure_contents'] == 'yes' ? true : false;
		$this->request_type    = isset( $this->settings['request_type'] ) ? $this->settings['request_type'] : 'LIST';
		$this->packing_method  = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->boxes           = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		$this->custom_services = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->offer_rates     = isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';

		$this->rateservice_version = 13;
		$this->addressvalidationservice_version = 2;

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		if ( ! in_array( get_woocommerce_currency(), array( 'USD', 'CAD' ) ) ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'FedEx requires that the <a href="%s">currency</a> is set to US Dollars.', 'wc_fedex' ), admin_url( 'admin.php?page=woocommerce_settings&tab=catalog' ) ) . '</p>
			</div>';
		}

		elseif ( ! in_array( $woocommerce->countries->get_base_country(), array( 'US', 'CA' ) ) ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'FedEx requires that the <a href="%s">base country/region</a> is set to United States.', 'wc_fedex' ), admin_url( 'admin.php?page=woocommerce_settings&tab=general' ) ) . '</p>
			</div>';
		}

		elseif ( ! $this->origin && $this->enabled == 'yes' ) {
			echo '<div class="error">
				<p>' . __( 'FedEx is enabled, but the origin postcode has not been set.', 'wc_fedex' ) . '</p>
			</div>';
		}
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}

	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Services', 'wc_fedex' ); ?></th>
			<td class="forminp">
				<table class="fedex_services widefat">
					<thead>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'Service Code', 'wc_fedex' ); ?></th>
						<th><?php _e( 'Name', 'wc_fedex' ); ?></th>
						<th><?php _e( 'Enabled', 'wc_fedex' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'wc_fedex' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'wc_fedex' ); ?></th>
					</thead>
					<tbody>
						<?php
							$sort = 0;
							$this->ordered_services = array();

							foreach ( $this->services as $code => $name ) {

								if ( isset( $this->custom_services[ $code ]['order'] ) ) {
									$sort = $this->custom_services[ $code ]['order'];
								}

								while ( isset( $this->ordered_services[ $sort ] ) )
									$sort++;

								$this->ordered_services[ $sort ] = array( $code, $name );

								$sort++;
							}

							ksort( $this->ordered_services );

							foreach ( $this->ordered_services as $value ) {
								$code = $value[0];
								$name = $value[1];
								?>
								<tr>
									<td class="sort"><input type="hidden" class="order" name="fedex_service[<?php echo $code; ?>][order]" value="<?php echo isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : ''; ?>" /></td>
									<td><strong><?php echo $code; ?></strong></td>
									<td><input type="text" name="fedex_service[<?php echo $code; ?>][name]" placeholder="<?php echo $name; ?>" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="50" /></td>
									<td><input type="checkbox" name="fedex_service[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ), true ); ?> /></td>
									<td><input type="text" name="fedex_service[<?php echo $code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment'] ) ? $this->custom_services[ $code ]['adjustment'] : ''; ?>" size="4" /></td>
									<td><input type="text" name="fedex_service[<?php echo $code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment_percent'] ) ? $this->custom_services[ $code ]['adjustment_percent'] : ''; ?>" size="4" /></td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<th scope="row" class="titledesc"><?php _e( 'Box Sizes', 'wc_fedex' ); ?></th>
			<td class="forminp">
				<style type="text/css">
					.fedex_boxes td, .fedex_services td {
						vertical-align: middle;
						padding: 4px 7px;
					}
					.fedex_boxes td input {
						margin-right: 4px;
					}
					.fedex_boxes .check-column {
						vertical-align: middle;
						text-align: left;
						padding: 0 7px;
					}
					.fedex_services th.sort {
						width: 16px;
					}
					.fedex_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;
					}
				</style>
				<table class="fedex_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Name', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Length', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Width', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Height', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Box Weight', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Max Weight', 'wc_fedex' ); ?></th>
							<th><?php _e( 'Enabled', 'wc_fedex' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'wc_fedex' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'wc_fedex' ); ?></a>
							</th>
							<th colspan="6">
								<small class="description"><?php _e( 'Items will be packed into these boxes depending based on item dimensions and volume. Outer dimensions will be passed to FedEx, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'wc_fedex' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
							if ( $this->default_boxes ) {
								foreach ( $this->default_boxes as $key => $box ) {
									?>
									<tr>
										<td class="check-column"></td>
										<td><?php echo $box['id']; ?></td>
										<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['length'] ); ?>" />in</td>
										<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['width'] ); ?>" />in</td>
										<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['height'] ); ?>" />in</td>
										<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['box_weight'] ); ?>" />lbs</td>
										<td><input type="text" size="5" readonly value="<?php echo esc_attr( $box['max_weight'] ); ?>" />lbs</td>
										<td><input type="checkbox" name="boxes_enabled[<?php echo $box['id']; ?>]" <?php checked( ! isset( $this->boxes[ $box['id'] ]['enabled'] ) || $this->boxes[ $box['id'] ]['enabled'] == 1, true ); ?> /></td>
									</tr>
									<?php
								}
							}
							if ( $this->boxes ) {
								foreach ( $this->boxes as $key => $box ) {
									if ( ! is_numeric( $key ) )
										continue;
									?>
									<tr>
										<td class="check-column"><input type="checkbox" /></td>
										<td>&nbsp;</td>
										<td><input type="text" size="5" name="boxes_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['length'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['width'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['height'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" />lbs</td>
										<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" />lbs</td>
										<td><input type="checkbox" name="boxes_enabled[<?php echo $key; ?>]" <?php checked( $box['enabled'], true ); ?> /></td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">

					jQuery(window).load(function(){

						jQuery('#woocommerce_fedex_packing_method').change(function(){

							if ( jQuery(this).val() == 'box_packing' )
								jQuery('#packing_options').show();
							else
								jQuery('#packing_options').hide();

						}).change();

						jQuery('.fedex_boxes .insert').click( function() {
							var $tbody = jQuery('.fedex_boxes').find('tbody');
							var size = $tbody.find('tr').size();
							var code = '<tr class="new">\
									<td class="check-column"><input type="checkbox" /></td>\
									<td>&nbsp;</td>\
									<td><input type="text" size="5" name="boxes_length[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_width[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_height[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" />lbs</td>\
									<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" />lbs</td>\
									<td><input type="checkbox" name="boxes_enabled[' + size + ']" /></td>\
								</tr>';

							$tbody.append( code );

							return false;
						} );

						jQuery('.fedex_boxes .remove').click(function() {
							var $tbody = jQuery('.fedex_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').hide().find('input').val('');
							});

							return false;
						});

						// Ordering
						jQuery('.fedex_services tbody').sortable({
							items:'tr',
							cursor:'move',
							axis:'y',
							handle: '.sort',
							scrollSensitivity:40,
							forcePlaceholderSize: true,
							helper: 'clone',
							opacity: 0.65,
							placeholder: 'wc-metabox-sortable-placeholder',
							start:function(event,ui){
								ui.item.css('baclbsround-color','#f6f6f6');
							},
							stop:function(event,ui){
								ui.item.removeAttr('style');
								fedex_services_row_indexes();
							}
						});

						function fedex_services_row_indexes() {
							jQuery('.fedex_services tbody tr').each(function(index, el){
								jQuery('input.order', el).val( parseInt( jQuery(el).index('.fedex_services tr') ) );
							});
						};

					});

				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {
		$boxes_length = $_POST['boxes_length'];
		$boxes_width  = $_POST['boxes_width'];
		$boxes_height = $_POST['boxes_height'];
		$boxes_box_weight   = $_POST['boxes_box_weight'];
		$boxes_max_weight   = $_POST['boxes_max_weight'];
		$boxes_enabled = $_POST['boxes_enabled'];

		$boxes = array();

		for ( $i = 0; $i < max( $boxes_length ); $i ++ ) {

			if ( $boxes_length[ $i ] && $boxes_width[ $i ] && $boxes_height[ $i ] ) {

				$boxes[] = array(
					'length'     => floatval( $boxes_length[ $i ] ),
					'width'      => floatval( $boxes_width[ $i ] ),
					'height'     => floatval( $boxes_height[ $i ] ),
					'box_weight' => floatval( $boxes_box_weight[ $i ] ),
					'max_weight' => floatval( $boxes_max_weight[ $i ] ),
					'enabled'    => isset( $boxes_enabled[ $i ] ) ? true : false
				);

			}

		}
		foreach ( $this->default_boxes as $box ) {
			$boxes[ $box['id'] ] = array(
				'enabled' => isset( $boxes_enabled[ $box['id'] ] ) ? true : false
			);
		}
		return $boxes;
	}

	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services         = array();
		$posted_services  = $_POST['fedex_service'];

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => woocommerce_clean( $settings['name'] ),
				'order'              => woocommerce_clean( $settings['order'] ),
				'enabled'            => isset( $settings['enabled'] ) ? true : false,
				'adjustment'         => woocommerce_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', woocommerce_clean( $settings['adjustment_percent'] ) )
			);
		}

		return $services;
	}

    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
	    global $woocommerce;

    	$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'wc_fedex' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'wc_fedex' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'Method Title', 'wc_fedex' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'wc_fedex' ),
				'default'         => __( 'FedEx', 'wc_fedex' )
			),
			'origin'           => array(
				'title'           => __( 'Origin Postcode', 'wc_fedex' ),
				'type'            => 'text',
				'description'     => __( 'Enter the postcode for the <strong>sender</strong>.', 'wc_fedex' ),
				'default'         => ''
		    ),
		    'availability'  => array(
				'title'           => __( 'Method Availability', 'wc_fedex' ),
				'type'            => 'select',
				'default'         => 'all',
				'class'           => 'availability',
				'options'         => array(
					'all'            => __( 'All Countries', 'wc_fedex' ),
					'specific'       => __( 'Specific Countries', 'wc_fedex' ),
				),
			),
			'countries'        => array(
				'title'           => __( 'Specific Countries', 'wc_fedex' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $woocommerce->countries->get_allowed_countries(),
			),
		    'api'              => array(
				'title'           => __( 'API Settings', 'wc_fedex' ),
				'type'            => 'title',
				'description'     => __( 'Your API access details are obtained from the FedEx website. After signup, get a <a href="https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/develop.html">developer key here</a>. After testing you can get a <a href="https://www.fedex.com/wpor/web/jsp/drclinks.jsp?links=wss/production.html">production key here</a>.', 'wc_fedex' ),
		    ),
		    'account_number'           => array(
				'title'           => __( 'FedEx Account Number', 'wc_fedex' ),
				'type'            => 'text',
				'description'     => '',
				'default'         => ''
		    ),
		    'meter_number'           => array(
				'title'           => __( 'Fedex Meter Number', 'wc_fedex' ),
				'type'            => 'text',
				'description'     => '',
				'default'         => ''
		    ),
		    'api_key'           => array(
				'title'           => __( 'Web Services Key', 'wc_fedex' ),
				'type'            => 'text',
				'description'     => '',
				'default'         => '',
				'custom_attributes' => array(
					'autocomplete' => 'off'
				)
		    ),
		    'api_pass'           => array(
				'title'           => __( 'Web Services Password', 'wc_fedex' ),
				'type'            => 'password',
				'description'     => '',
				'default'         => '',
				'custom_attributes' => array(
					'autocomplete' => 'off'
				)
		    ),
		    'production'      => array(
				'title'           => __( 'Production Key', 'wc_fedex' ),
				'label'           => __( 'This is a production key', 'wc_fedex' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'If this is a production API key and not a developer key, check this box.', 'wc_fedex' )
			),
			'debug'      => array(
				'title'           => __( 'Debug Mode', 'wc_fedex' ),
				'label'           => __( 'Enable debug mode', 'wc_fedex' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'description'     => __( 'Enable debug mode to show debugging information on the cart/checkout.', 'wc_fedex' )
			),
		    'rates'           => array(
				'title'           => __( 'Rates and Services', 'wc_fedex' ),
				'type'            => 'title',
				'description'     => __( 'The following settings determine the rates you offer your customers.', 'wc_fedex' ),
		    ),
		    'insure_contents'      => array(
				'title'           => __( 'Insurance', 'wc_fedex' ),
				'label'           => __( 'Enable Insurance', 'wc_fedex' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'description'     => __( 'Sends the package value to FedEx for insurance.', 'wc_fedex' )
			),
			'request_type'     => array(
				'title'           => __( 'Request Type', 'wc_fedex' ),
				'type'            => 'select',
				'default'         => 'LIST',
				'class'           => '',
				'options'         => array(
					'LIST'        => __( 'List rates', 'wc_fedex' ),
					'ACCOUNT'     => __( 'Account rates', 'wc_fedex' ),
				),
				'description'     => __( 'Choose whether to return List or Account (discounted) rates from the API.', 'wc_fedex' )
			),
			'smartpost_hub'           => array(
				'title'           => __( 'Fedex SmartPost Hub', 'wc_fedex' ),
				'type'            => 'select',
				'description'     => __( 'Only required if using SmartPost.', 'wc_fedex' ),
				'default'         => '',
				'options'         => array(
					''            => __( 'N/A', 'wc_fedex' ),
					5185          => 'ALPA Allentown',
					5303          => 'ATGA Atlanta',
					5281          => 'CHNC Charlotte',
					5602          => 'CIIL Chicago',
					5929          => 'COCA Chino',
					5751          => 'DLTX Dallas',
					5802          => 'DNCO Denver',
					5481          => 'DTMI Detroit',
					5087          => 'EDNJ Edison',
					5431          => 'GCOH Grove City',
					5771          => 'HOTX Houston',
					5465          => 'ININ Indianapolis',
					5648          => 'KCKS Kansas City',
					5902          => 'LACA Los Angeles',
					5254          => 'MAWV Martinsburg',
					5379          => 'METN Memphis',
					5552          => 'MPMN Minneapolis',
					5531          => 'NBWI New Berlin (Also used for development keys)',
					5110          => 'NENY Newburgh',
					5015          => 'NOMA Northborough',
					5327          => 'ORFL Orlando',
					5194          => 'PHPA Philadelphia',
					5854          => 'PHAZ Phoenix',
					5150          => 'PTPA Pittsburgh',
					5958          => 'SACA Sacramento',
					5843          => 'SCUT Salt Lake City',
					5983          => 'SEWA Seattle',
					5631          => 'STMO St. Louis'
				)
		    ),
			'packing_method'   => array(
				'title'           => __( 'Parcel Packing Method', 'wc_fedex' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'packing_method',
				'options'         => array(
					'per_item'       => __( 'Default: Pack items individually', 'wc_fedex' ),
					'box_packing'    => __( 'Recommended: Pack into boxes with weights and dimensions', 'wc_fedex' ),
				),
			),
			'boxes'  => array(
				'type'            => 'box_packing'
			),
			'offer_rates'   => array(
				'title'           => __( 'Offer Rates', 'wc_fedex' ),
				'type'            => 'select',
				'description'     => '',
				'default'         => 'all',
				'options'         => array(
				    'all'         => __( 'Offer the customer all returned rates', 'wc_fedex' ),
				    'cheapest'    => __( 'Offer the customer the cheapest rate only, anonymously', 'wc_fedex' ),
				),
		    ),
			'services'  => array(
				'type'            => 'services'
			),
		);
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) {
    	global $woocommerce;

    	$this->found_rates            = array();
    	$package_requests = $this->get_package_requests( $package );

    	if ( $package_requests ) {

			try {

				$client = new SoapClient( plugin_dir_path( dirname( __FILE__ ) ) . 'api/' . ( $this->production ? 'production' : 'test' ) . '/RateService_v' . $this->rateservice_version. '.wsdl', array( 'trace' => 1 ) );

		    	foreach ( $package_requests as $key => $request ) {

		    		if ( $this->debug )
						$woocommerce->add_message( 'FedEx REQUEST: <pre style="height:200px">' . print_r( $request, true ) . '</pre>' );

			    	$result = $client->getRates( $request );

					if ( $this->debug )
						$woocommerce->add_message( 'FedEx RESPONSE: <pre style="height:200px">' . print_r( $result, true ) . '</pre>' );
				}

			} catch (Exception $e) {
				$woocommerce->add_error( print_r( $e, true ) );
				return false;
			}

			if ( $result ) {

				if ( ! empty ( $result->RateReplyDetails ) ) {

					$rate_reply_details = &$result->RateReplyDetails;

					// Workaround for when an object is returned instead of array
					if ( is_object( $rate_reply_details ) && isset( $rate_reply_details->ServiceType ) )
						$rate_reply_details = array( $rate_reply_details );

					if ( ! is_array( $rate_reply_details ) )
						return false;

					foreach ( $rate_reply_details as $quote ) {

						if ( is_array( $quote->RatedShipmentDetails ) ) {
							foreach ( $quote->RatedShipmentDetails as $i => $d ) {
								if ( $d->ShipmentRateDetail->RateType == $quote->ActualRateType ) {
									$details = &$quote->RatedShipmentDetails[ $i ];
									break;
								}
							}
						} else {
							$details = &$quote->RatedShipmentDetails;
						}

						if ( empty( $details ) )
							continue;

						$rate_code = strval( $quote->ServiceType );
						$rate_id   = $this->id . ':' . $rate_code;
						$rate_name = (string) $this->services[ $quote->ServiceType ];
						$rate_cost = (float) $details->ShipmentRateDetail->TotalNetCharge->Amount;

						$this->prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost );

					}

				}

			}
		}

		// Ensure rates were found for all packages
		if ( $this->found_rates ) {
			foreach ( $this->found_rates as $key => $value ) {
				if ( $value['packages'] < sizeof( $package_requests ) )
					unset( $this->found_rates[ $key ] );
			}
		}

		// Add rates
		if ( $this->found_rates ) {

			if ( $this->offer_rates == 'all' ) {

				uasort( $this->found_rates, array( $this, 'sort_rates' ) );

				foreach ( $this->found_rates as $key => $rate ) {
					$this->add_rate( $rate );
				}

			} else {

				$cheapest_rate = '';

				foreach ( $this->found_rates as $key => $rate ) {
					if ( ! $cheapest_rate || $cheapest_rate['cost'] > $rate['cost'] )
						$cheapest_rate = $rate;
				}

				$cheapest_rate['label'] = $this->title;

				$this->add_rate( $cheapest_rate );

			}
		}

    }

    /**
     * prepare_rate function.
     *
     * @access private
     * @param mixed $rate_code
     * @param mixed $rate_id
     * @param mixed $rate_name
     * @param mixed $rate_cost
     * @return void
     */
    private function prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost ) {

	    // Name adjustment
		if ( ! empty( $this->custom_services[ $rate_code ]['name'] ) )
			$rate_name = $this->custom_services[ $rate_code ]['name'];

		// Cost adjustment %
		if ( ! empty( $this->custom_services[ $rate_code ]['adjustment_percent'] ) )
			$rate_cost = $rate_cost + ( $rate_cost * ( floatval( $this->custom_services[ $rate_code ]['adjustment_percent'] ) / 100 ) );
		// Cost adjustment
		if ( ! empty( $this->custom_services[ $rate_code ]['adjustment'] ) )
			$rate_cost = $rate_cost + floatval( $this->custom_services[ $rate_code ]['adjustment'] );

		// Enabled check
		if ( isset( $this->custom_services[ $rate_code ] ) && empty( $this->custom_services[ $rate_code ]['enabled'] ) )
			return;

		// Merging
		if ( isset( $this->found_rates[ $rate_id ] ) ) {
			$rate_cost = $rate_cost + $this->found_rates[ $rate_id ]['cost'];
			$packages  = 1 + $this->found_rates[ $rate_id ]['packages'];
		} else {
			$packages = 1;
		}

		// Sort
		if ( isset( $this->custom_services[ $rate_code ]['order'] ) ) {
			$sort = $this->custom_services[ $rate_code ]['order'];
		} else {
			$sort = 999;
		}

		$this->found_rates[ $rate_id ] = array(
			'id'       => $rate_id,
			'label'    => $rate_name,
			'cost'     => $rate_cost,
			'sort'     => $sort,
			'packages' => $packages
		);
    }

    /**
     * sort_rates function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function sort_rates( $a, $b ) {
		if ( $a['sort'] == $b['sort'] ) return 0;
		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
    }

    /**
     * get_request function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function get_request( $package ) {
		global $woocommerce;

		$request = array();

		// Prepare Shipping Request for FedEx
		$request['WebAuthenticationDetail'] = array(
			'UserCredential' => array(
				'Key'      => $this->api_key,
				'Password' => $this->api_pass
			)
		);
		$request['ClientDetail'] = array(
			'AccountNumber' => $this->account_number,
			'MeterNumber'   => $this->meter_number
		);
		$request['TransactionDetail'] = array(
			'CustomerTransactionId'     => ' *** WooCommerce Rate Request ***'
		);
        $request['Version'] = array(
			'ServiceId'              => 'crs',
		    'Major'                  => $this->rateservice_version,
		    'Intermediate'           => '0',
		    'Minor'                  => '0'
		);
		$request['ReturnTransitAndCommit'] = true;
		$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP';
		$request['RequestedShipment']['ShipTimestamp'] = date('c');
		$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING';
		$request['RequestedShipment']['Shipper'] = array(
		    'Address'               => array(
				'PostalCode'              => str_replace( ' ', '', strtoupper( $this->origin ) ),
				'CountryCode'             => $woocommerce->countries->get_base_country()
		    )
		);

		$request['RequestedShipment']['ShippingChargesPayment'] = array(
			'PaymentType' => 'SENDER',
            'Payor' => array(
				'ResponsibleParty' => array(
					'AccountNumber'           => $this->account_number,
					'CountryCode'             => $woocommerce->countries->get_base_country()
				)
			)
		);
		$request['RequestedShipment']['RateRequestTypes'] = $this->request_type;
		$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';

		// SMART_POST
		if ( ! empty( $this->smartpost_hub ) && $package['destination']['country'] == 'US' ) {
			$request['RequestedShipment']['SmartPostDetail'] = array(
			    'Indicia' => 'PARCEL_SELECT',
			    'HubId'   => $this->smartpost_hub
			);
		}

		return $request;
    }

    /**
     * get_package_requests function.
     *
     * @access private
     * @return void
     */
    private function get_package_requests( $package ) {

	    $requests = array();

	    $residential = true;

	    // Address Validation API only available for production
	    if ( $this->production ) {

		    // Check if address is residential or commerical
	    	try {

				$client = new SoapClient( plugin_dir_path( dirname( __FILE__ ) ) . 'api/production/AddressValidationService_v' . $this->addressvalidationservice_version. '.wsdl', array( 'trace' => 1 ) );

				$request = array();

				$request['WebAuthenticationDetail'] = array(
					'UserCredential' => array(
						'Key'      => $this->api_key,
						'Password' => $this->api_pass
					)
				);
				$request['ClientDetail'] = array(
					'AccountNumber' => $this->account_number,
					'MeterNumber'   => $this->meter_number
				);
				$request['TransactionDetail'] = array( 'CustomerTransactionId' => ' *** Address Validation Request v2 from WooCommerce ***' );
				$request['Version'] = array( 'ServiceId' => 'aval', 'Major' => $this->addressvalidationservice_version, 'Intermediate' => '0', 'Minor' => '0' );
				$request['RequestTimestamp'] = date( 'c' );
				$request['Options'] = array(
					'CheckResidentialStatus' => 1,
					'MaximumNumberOfMatches' => 1,
					'StreetAccuracy' => 'LOOSE',
					'DirectionalAccuracy' => 'LOOSE',
					'CompanyNameAccuracy' => 'LOOSE',
					'ConvertToUpperCase' => 1,
					'RecognizeAlternateCityNames' => 1,
					'ReturnParsedElements' => 1
				);
				$request['AddressesToValidate'] = array(
					0 => array(
						'AddressId' => 'WTC',
						'Address' => array(
							'StreetLines' => array( $package['destination']['address'], $package['destination']['address_2'] ),
							'PostalCode'  => $package['destination']['postcode'],
						)
					)
				);

				$response = $client->addressValidation( $request );

				if ( $response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
					foreach( $response->AddressResults as $addressResult ) {
	        			if ( $addressResult->ProposedAddressDetails->ResidentialStatus == 'BUSINESS' ) {
		        			$residential = false;
	        			}
	        		}
	        	}

			} catch (Exception $e) {}

		}

		$residential = apply_filters( 'woocommerce_fedex_address_type', $residential, $package );

		if ( $this->debug && $residential == false )
    		$woocommerce->add_message( __( 'Business Address', 'wc_fedex' ) );

	    // All reguests for this package get this data
	    $package_request = $this->get_request( $package );
	    $package_request['RequestedShipment']['Recipient'] = array(
			'Address' => array(
				'Residential' => $residential,
				'PostalCode'  => str_replace( ' ', '', strtoupper( $package['destination']['postcode'] ) ),
				'CountryCode' => $package['destination']['country']
			)
	    );

		// Add state to US/Canadian requests
		if ( in_array( $package['destination']['country'], array( 'US', 'CA' ) ) )
			$package_request['RequestedShipment']['Recipient']['Address']['StateOrProvinceCode'] = $package['destination']['state'];

	    // Choose selected packing
    	switch ( $this->packing_method ) {
	    	case 'box_packing' :
	    		$parcels = $this->box_shipping( $package );
	    	break;
	    	case 'per_item' :
	    	default :
	    		$parcels = $this->per_item_shipping( $package );
	    	break;
    	}

    	if ( $parcels ) {
	    	// Max 99
	    	$parcel_chunks = array_chunk( $parcels, 99 );

	    	foreach ( $parcel_chunks as $parcels ) {

	    		// Make request
	    		$request = $package_request;

	    		// Store value
	    		$total_value = 0;
	    		$total_packages = 0;

	    		// Store parcels as lin items
	    		$request['RequestedShipment']['RequestedPackageLineItems'] = array();

	    		foreach ( $parcels as $key => $parcel ) {

		    		$total_value += $parcel['InsuredValue']['Amount'];
		    		$total_packages += $parcel['GroupPackageCount'];

		    		if ( ! $this->insure_contents )
		    			unset( $parcel['InsuredValue'] );

		    		$parcel = array_merge( array( 'SequenceNumber' => $key + 1 ), $parcel );
		    		$request['RequestedShipment']['RequestedPackageLineItems'][] = $parcel;
	    		}

	    		// Add insurance
	    		if ( $this->insure_contents )
					$request['RequestedShipment']['TotalInsuredValue'] = array( 'Amount' => round( $total_value ), 'Currency' => get_woocommerce_currency() );

				// Size
	    		$request['RequestedShipment']['PackageCount'] = $total_packages;

	    		$requests[] = $request;
	    	}
    	}

    	return $requests;
    }

    /**
     * per_item_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function per_item_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();

	    $group = 1;

    	// Get weight of order
    	foreach ( $package['contents'] as $item_id => $values ) {

    		if ( ! $values['data']->needs_shipping() ) {
    			if ( $this->debug )
    				$woocommerce->add_message( sprintf( __( 'Product # is virtual. Skipping.', 'wc_fedex' ), $item_id ) );
    			continue;
    		}

    		if ( ! $values['data']->get_weight() ) {
	    		if ( $this->debug )
	    			$woocommerce->add_error( sprintf( __( 'Product # is missing weight. Aborting.', 'wc_fedex' ), $item_id ) );
	    		return;
    		}

    		$request = array();

    		$request['GroupNumber'] = $group;
    		$request['GroupPackageCount'] = $values['quantity'];

			$request['Weight'] = array(
				'Value'         => max( '0.5', round( woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ), 2 ) ),
				'Units'         => 'LB'
		    );

			if ( $values['data']->length && $values['data']->height && $values['data']->width ) {

				$dimensions = array( $values['data']->length, $values['data']->width, $values['data']->height );

				sort( $dimensions );

				$request['Dimensions'] = array(
			    	'Length'     => max( 1, round( woocommerce_get_dimension( $dimensions[2], 'in' ), 2 ) ),
			    	'Width'      => max( 1, round( woocommerce_get_dimension( $dimensions[1], 'in' ), 2 ) ),
			    	'Height'     => max( 1, round( woocommerce_get_dimension( $dimensions[0], 'in' ), 2 ) ),
			    	'Units'      => 'IN'
				);
			}

			$request['InsuredValue'] = array( 'Amount' => round( $values['data']->get_price() * $values['quantity'] ), 'Currency' => get_woocommerce_currency() );

			$requests[] = $request;

			$group++;
    	}

		return $requests;
    }

    /**
     * box_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function box_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();

	  	if ( ! class_exists( 'WC_Boxpack' ) )
	  		include_once 'box-packer/class-wc-boxpack.php';

	    $boxpack = new WC_Boxpack();

	    // Define boxes
		foreach ( $this->boxes + $this->default_boxes as $key => $box ) {
			if ( ! is_numeric( $key ) )
				continue;

			$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'], $box['box_weight'] );

			if ( isset( $box['id'] ) )
				$newbox->set_id( $box['id'] );

			if ( $box['max_weight'] )
				$newbox->set_max_weight( $box['max_weight'] );
		}

		// Add items
		foreach ( $package['contents'] as $item_id => $values ) {

			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

				for ( $i = 0; $i < $values['quantity']; $i ++ ) {
					$boxpack->add_item(
						woocommerce_get_dimension( $dimensions[2], 'in' ),
						woocommerce_get_dimension( $dimensions[1], 'in' ),
						woocommerce_get_dimension( $dimensions[0], 'in' ),
						woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ),
						$values['data']->get_price()
					);
				}

			} else {
				$woocommerce->add_error( sprintf( __( 'Product # is missing dimensions. Aborting.', 'wc_fedex' ), $item_id ) );
				return;
			}
		}

		// Pack it
		$boxpack->pack();

		// Get packages
		$packages = $boxpack->get_packages();

		$group = 1;

		foreach ( $packages as $package ) {

			$dimensions = array( $package->length, $package->width, $package->height );

			sort( $dimensions );

    		$request = array();

    		$request['GroupNumber'] = $group;
    		$request['GroupPackageCount'] = 1;

			$request['Weight'] = array(
				'Value'         => max( '0.5', round( $package->weight, 2 ) ),
				'Units'         => 'LB'
		    );


			if ( $values['data']->length && $values['data']->height && $values['data']->width ) {
				$request['Dimensions'] = array(
			    	'Length'     => max( 1, round( $dimensions[2], 2 ) ),
			    	'Width'      => max( 1, round( $dimensions[1], 2 ) ),
			    	'Height'     => max( 1, round( $dimensions[0], 2 ) ),
			    	'Units'      => 'IN'
				);
			}

			$request['InsuredValue'] = array( 'Amount' => round( $package->value ), 'Currency' => get_woocommerce_currency() );

    		$requests[] = $request;

    		$group++;
		}

		return $requests;
    }

}
