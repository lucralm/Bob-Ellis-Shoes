<?php
/**
 * WC_Stock_Management_List_Table class.
 *
 * @extends WP_List_Table
 */
class WC_Stock_Management_List_Table extends WP_List_Table {

    var $index;
    var $last_product_id;

    /**
     * __construct function.
     *
     * @access public
     */
    function __construct(){
        global $status, $page;

        $this->index = 0;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'product',     	//singular name of the listed records
            'plural'    => 'products',    	//plural name of the listed records
            'ajax'      => false        	//does this table support ajax?
        ) );

    }

    /**
     * This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title()
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as
     * possible.

     * For more detailed insight into how columns are handled, take a look at
     * WP_List_Table::single_row_columns()
     *
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     */
    function column_default( $post, $column_name ) {

	    if ( function_exists( 'get_product' ) )
			$product = get_product( $post->ID );
		else
			$product = ( $post->post_type == 'product' ) ? new WC_Product( $post->ID ) : new WC_Product_Variation( $post->ID );

        switch( $column_name ) {
        	case 'thumb' :
        		return $product->get_image();
        	break;
            case 'id' :
            	if ( $post->post_type == 'product' )
            		$this->last_product_id = $post->ID;
                return $post->ID;
            break;
            case 'sku':
                if ($product->get_sku()) return $product->get_sku(); else return '<span class="na">&ndash;</span>';
            break;
           	case 'type' :
           		return ( $post->post_type == 'product' ) ? __( 'Product', 'wc_stock_management' ) : __( 'Variation', 'wc_stock_management' );
           	break;
           	case 'manage_stock' :
           		if ( $post->post_type == 'product_variation' && $product->managing_stock() && ! $product->variation_has_stock ) {
           			return '<mark class="yes">' . __( 'Parent', 'woocommerce' ) . '</mark>';
           		} else {
           			return ( $product->managing_stock() ) ? '<mark class="yes">' . __( 'Yes', 'woocommerce' ) . '</mark>' : '<mark class="no">' .__( 'No', 'woocommerce' ) . '</mark>';
           		}
           	break;
           	case 'stock' :
           		$this->index++;
           		?>

           		<input type="text" class="input-text" tabindex="<?php echo $this->index; ?>" name="stock_quantity[<?php echo $post->ID; ?>]" placeholder="<?php
           			if ( $product->managing_stock() ) {
           				if ( $post->post_type == 'product' || $product->variation_has_stock )
           					echo $product->stock;
           				else
           					_e('0', 'wc_stock_management');
           			} else {
           				_e('0', 'wc_stock_management');
           			}

           		?>" />

           		<input type="hidden" class="input-text" name="current_stock_quantity[<?php echo $post->ID; ?>]" value="<?php if ( $post->post_type == 'product' || $product->variation_has_stock ) echo $product->stock; ?>" />

           		<?php

           	break;
           	case 'stock_status' :
           		if ( $post->post_type == 'product_variation' )
           			return '<span class="na">&ndash;</span>';
           		else
           			return ( $product->is_in_stock() ) ? '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>' : '<mark class="outofstock">' .__( 'Out of stock', 'woocommerce' ) . '</mark>';
           	break;
           	case 'backorders' :
           		if ( $product->backorders_allowed() && $product->backorders_require_notification() )
           			echo '<mark class="yes">' . __( 'Notify', 'woocommerce' ) . '</mark>';
           		elseif ( $product->backorders_allowed() )
           			echo '<mark class="yes">' . __( 'Yes', 'woocommerce' ) . '</mark>';
           		else
           			echo '<mark class="no">' . __( 'No', 'woocommerce' ) . '</mark>';
           	break;
        }
	}

    function column_title( $post ){
        global $woocommerce;

        $post_to_edit_id = ( $post->post_type == 'product' ) ? $post->ID : $post->post_parent;
        $edit_link = admin_url( 'post.php?post=' . $post_to_edit_id . '&action=edit' );
        $view_link = esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_to_edit_id ) ) );

        //Build row actions
        $actions = array(
            'edit'      => '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
            'view'   	=> '<a href="' . $view_link . '">' . __( 'View' ) . '</a>',
        );

        if ( $post->post_type == 'product_variation' && $this->last_product_id == $post->post_parent )
        	$post_title = '';
        else
       		$post_title = $post->post_title;

        // Get variations
        if ( $post->post_type == 'product_variation' ) {
        	$post_title = trim(current(explode('-', $post_title)));

        	$post_title .= ' &mdash; ';

        	if ( function_exists( 'get_product' ) )
				$variable_product = get_product( $post->ID );
			else
				$variable_product = new WC_Product_Variation( $post->ID );

        	$list_attributes = array();
        	$attributes = $variable_product->get_variation_attributes();

        	foreach ( $attributes as $name => $attribute ) {
        		$list_attributes[] = $woocommerce->attribute_label( str_replace('attribute_', '', $name) ) . ': <strong>' . $attribute . '</strong>';
        	}

        	$post_title .= implode(', ', $list_attributes);
        }

        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $post_title,
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    /**
     * If displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     *
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     */
    function column_cb( $item ){
    	if ( $item->post_type == 'product' )
	        return sprintf(
	            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
	            /*$2%s*/ $item->ID                	//The value of the checkbox should be the record's id
	        );
    }

    /**
     * This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     *
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     */
    function get_columns(){
        $columns = array(
            'cb'        		=> '<input type="checkbox" />', //Render a checkbox instead of text
            'thumb'     		=> __('Image', 'wc_stock_management'),
            'title'     		=> __('Name', 'wc_stock_management'),
            'id'    			=> __('ID', 'wc_stock_management'),
            'sku'    			=> __('SKU', 'wc_stock_management'),
            'type'  			=> __('Type', 'wc_stock_management'),
            'manage_stock'  	=> __('Manage Stock', 'wc_stock_management'),
            'stock_status'  	=> __('Stock Status', 'wc_stock_management'),
            'backorders'  		=> __('Backorders', 'wc_stock_management'),
            'stock'  			=> __('Quantity', 'wc_stock_management'),
        );
        return $columns;
    }

    /**
     * If you want one or more columns to be sortable (ASC/DESC toggle),
     * you will need to register it here. This should return an array where the
     * key is the column that needs to be sortable, and the value is db column to
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     *
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     *
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array( 'title', true ),     //true means its already sorted
            'id'    	=> array( 'id', false ),
            'sku'  		=> array( 'sku', false ),
            'stock'  	=> array( 'stock', false )
        );
        return $sortable_columns;
    }

     /**
     * Get bulk actions
     */
    function get_bulk_actions() {
        $actions = array(
            'in_stock'    => __('Mark "In stock"', 'wc_stock_management'),
            'out_of_stock'    => __('Mark "Out of stock"', 'wc_stock_management'),
            'allow_backorders'    => __('Allow backorders', 'wc_stock_management'),
            'allow_backorders_notify'    => __('Allow backorders, but notify customer', 'wc_stock_management'),
            'do_not_allow_backorders'    => __('Do not allow backorders', 'wc_stock_management'),
        );
        return $actions;
    }

    /**
     * Process bulk actions
     */
    function process_bulk_action() {

        //Detect when a bulk action is being triggered…
        if( 'in_stock' === $this->current_action() ) {

            $products = array_map( 'intval', $_POST['product'] );

        	if ($products) foreach ( $products as $id ) {

        		$stock_qty = get_post_meta( $id, '_stock', true );
        		$backorders = get_post_meta( $id, '_backorders', true );

        		if ( $stock_qty <= 0 && $backorders =='no' ) {
        			update_post_meta( $id, '_stock_status', 'outofstock' );
        		} else {
        			update_post_meta( $id, '_stock_status', 'instock' );
        		}

        	}

            echo '<div class="updated"><p>' . __('Stock status updated', 'wc_stock_management') . '</p></div>';

        } elseif( 'out_of_stock' === $this->current_action() ) {

           	$products = array_map( 'intval', $_POST['product'] );

        	if ($products) foreach ( $products as $id ) {

        		update_post_meta( $id, '_stock_status', 'outofstock' );

        	}

            echo '<div class="updated"><p>' . __('Stock status updated', 'wc_stock_management') . '</p></div>';

		} elseif( 'allow_backorders' === $this->current_action() ) {

			$products = array_map( 'intval', $_POST['product'] );

        	if ($products) foreach ( $products as $id ) {

        		update_post_meta( $id, '_backorders', 'yes' );

        	}

            echo '<div class="updated"><p>' . __('Backorder status updated', 'wc_stock_management') . '</p></div>';

		} elseif( 'allow_backorders_notify' === $this->current_action() ) {

			$products = array_map( 'intval', $_POST['product'] );

        	if ($products) foreach ( $products as $id ) {

        		update_post_meta( $id, '_backorders', 'notify' );

        	}

            echo '<div class="updated"><p>' . __('Backorder status updated', 'wc_stock_management') . '</p></div>';

		} elseif( 'do_not_allow_backorders' === $this->current_action() ) {

			$products = array_map( 'intval', $_POST['product'] );

        	if ($products) foreach ( $products as $id ) {

        		update_post_meta( $id, '_backorders', 'no' );

        	}

            echo '<div class="updated"><p>' . __('Backorder status updated', 'wc_stock_management') . '</p></div>';

		}

    }

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function display_tablenav( $which ) {
		global $woocommerce;

		include_once( $woocommerce->plugin_path() . '/classes/walkers/class-product-cat-dropdown-walker.php' );

		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );

			?>

			<ul class="subsubsub">
				<li class="all"><a href="<?php echo admin_url('edit.php?post_type=product&page=wc_stock_management') ?>" class="<?php if ( empty( $_REQUEST['filter_product_type'] ) ) echo 'current'; ?>"><?php _e('All', 'wc_stock_management'); ?></a> |</li>
				<li class="product"><a href="<?php echo admin_url('edit.php?post_type=product&page=wc_stock_management&filter_product_type=product') ?>" class="<?php if ( ! empty( $_REQUEST['filter_product_type'] ) && $_REQUEST['filter_product_type']=='product' ) echo 'current'; ?>"><?php _e('Products', 'wc_stock_management'); ?></a> |</li>
				<li class="variation"><a href="<?php echo admin_url('edit.php?post_type=product&page=wc_stock_management&filter_product_type=product_variation') ?>" class="<?php if ( ! empty( $_REQUEST['filter_product_type'] ) && $_REQUEST['filter_product_type']=='product_variation' ) echo 'current'; ?>"><?php _e('Variations', 'wc_stock_management'); ?></a></li>
			</ul>

			<?php $this->search_box( __('Search'), 'search-products' );
		}
		?>

		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignright" style="padding: 3px 0 0 8px;">
				<input type="submit" name="save_stock" value="<?php _e('Save stock quantities', 'wc_stock_management'); ?>" class="button button-primary" />
			</div>

			<div class="alignleft actions">
				<?php $this->bulk_actions( $which ); ?>
			</div>

			<?php if ( 'top' == $which ) : ?>
			<div class="alignleft actions">
				<input type="hidden" name="filter_product_type" value="<?php if ( ! empty( $_REQUEST['filter_product_type'] ) ) echo $_REQUEST['filter_product_type']; ?>" />
				<select name="filter_manage_stock">
					<option value=""><?php _e('Stock Management on or off', 'wc_stock_management'); ?></option>
					<option value="yes" <?php if ( !empty( $_REQUEST['filter_manage_stock'] ) && $_REQUEST['filter_manage_stock'] == 'yes' ) selected( 1 ) ?>><?php _e('Managing stock', 'wc_stock_management'); ?></option>
					<option value="no" <?php if ( !empty( $_REQUEST['filter_manage_stock'] ) && $_REQUEST['filter_manage_stock'] == 'no' ) selected( 1 ) ?>><?php _e('Not managing stock', 'wc_stock_management'); ?></option>
				</select>
				<select name="filter_stock_status">
					<option value=""><?php _e('Any stock status', 'wc_stock_management'); ?></option>
					<option value="instock" <?php if ( !empty( $_REQUEST['filter_stock_status'] ) && $_REQUEST['filter_stock_status'] == 'instock' ) selected( 1 ) ?>><?php _e('In stock', 'wc_stock_management'); ?></option>
					<option value="outofstock" <?php if ( !empty( $_REQUEST['filter_stock_status'] ) && $_REQUEST['filter_stock_status'] == 'outofstock' ) selected( 1 ) ?>><?php _e('Out of stock', 'wc_stock_management'); ?></option>
				</select>
				<?php
					global $wp_query;

					$r = array();
					$r['pad_counts'] 	= 0;
					$r['hierarchal'] 	= 1;
					$r['hide_empty'] 	= 1;
					$r['show_count'] 	= 0;
					$r['selected'] 		= ( isset( $_REQUEST['filter_product_cat'] ) ) ? $_REQUEST['filter_product_cat'] : '';

					$terms = get_terms( 'product_cat', $r );

					if ( $terms ) {
						?>
						<select name='filter_product_cat' id='dropdown_product_cat'>
							<option value=""><?php _e('Any category', 'wc_stock_management'); ?></option>
							<?php
								echo woocommerce_walk_category_dropdown_tree( $terms, 0, $r );

								echo '<option value="0" ' . selected( isset( $_REQUEST['filter_product_cat'] ) ? $_REQUEST['filter_product_cat'] : '', '0', false ) . '>' . __('Uncategorized', 'woocommerce') . '</option>';
							?>
						</select>
						<?php
					}
				?>
				<select name="products_per_page">
					<option value=""><?php _e('50 per page', 'wc_stock_management'); ?></option>
					<option value="100" <?php if ( !empty( $_REQUEST['products_per_page'] ) && $_REQUEST['products_per_page'] == '100' ) selected( 1 ) ?>><?php _e('100 per page', 'wc_stock_management'); ?></option>
					<option value="200" <?php if ( !empty( $_REQUEST['products_per_page'] ) && $_REQUEST['products_per_page'] == '200' ) selected( 1 ) ?>><?php _e('200 per page', 'wc_stock_management'); ?></option>
					<option value="-1" <?php if ( !empty( $_REQUEST['products_per_page'] ) && $_REQUEST['products_per_page'] == '-1' ) selected( 1 ) ?>><?php _e('View All', 'wc_stock_management'); ?></option>
				</select>
				<input type="submit" name="save_stock" value="<?php _e('Filter', 'wc_stock_management'); ?>" class="button" />
			</div>
			<?php endif; ?>
			<?php
					$this->extra_tablenav( $which );
					$this->pagination( $which );
			?>

			<br class="clear" />
		</div><?php
	}

    /**
     * This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here…
     *
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {
        global $wpdb;

        $current_page 		= $this->get_pagenum();
        $per_page			= empty( $_REQUEST['products_per_page'] ) ? 50 : (int) $_REQUEST['products_per_page'];
        $post_type			= empty( $_REQUEST['filter_product_type'] ) ? '' : esc_attr( $_REQUEST['filter_product_type'] );
		$orderby 			= ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
		$order 				= ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
        $stock_status		= ! empty( $_REQUEST['filter_stock_status'] ) ? $_REQUEST['filter_stock_status'] : '';
		$stock_status		= $stock_status !== 'instock' && $stock_status !== 'outofstock' ? '' : $stock_status;
		$product_cat		= isset( $_REQUEST['filter_product_cat'] ) ? $_REQUEST['filter_product_cat'] : '';

        /**
         * Init column headers
         */
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        /**
         * Process bulk actions
         */
        $this->process_bulk_action();

        /**
         * Prepare ordering args
         */
        switch ( $orderby ) {
			case 'sku' :
				$meta_key 	= '_sku';
				$orderby 	= 'meta_value';
			break;
			case 'stock' :
				$meta_key = '_stock';
				$orderby 	= 'meta_value_num';
			break;
			default :
				$meta_key = '';
			break;
		}

		$tax_query = array();

		if ( $product_cat ) {

			$tax_query[] = array(
				'taxonomy'	=> 'product_cat',
				'field'		=> 'slug',
				'terms'	 	=> array( $product_cat )
			);

		} elseif ( $product_cat === '0' ) {

			$tax_query[] = array(
				'taxonomy'	=> 'product_cat',
				'field'		=> 'id',
				'terms' 	=> get_terms( 'product_cat', array( 'fields' => 'ids' ) ),
				'operator' 	=> 'NOT IN'
			);

		}

        /**
         * Get posts
         */
        if ( $post_type == '' || $post_type == 'product_variation' ) {

			$meta_query = array();

			if ( ! empty( $_REQUEST['filter_manage_stock'] ) ) {
				if ( $_REQUEST['filter_manage_stock'] == 'yes' ) {
					$meta_query[] = array(
						'key'		=> '_stock',
						'value'		=> array( '', null ),
						'compare'	=> 'NOT IN'
					);
				} else {
					$meta_query[] = array(
						'key'		=> '_stock',
						'value'		=> '',
						'compare'	=> '='
					);
				}
			}


			if ( $stock_status ) {
				$meta_query[] = array(
					'key'		=> '_stock',
					'value'		=> '0',
					'compare'	=> ( $stock_status == 'instock' ) ? '>' : '='
				);
			}

			/**
			 * Find ID's of variations managing stock
			 */
			$variation_ids = get_posts(array(
				'post_type' 		=> 'product_variation',
				'posts_per_page' 	=> -1,
				'post_status' 		=> 'publish',
				'fields'			=> 'id=>parent',
				'meta_query'		=> $meta_query,
				'meta_key'			=> $meta_key,
				's'					=> ( ! empty( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '',
				'orderby'			=> esc_attr( $orderby ),
				'order'				=> esc_attr( $order ),
			));

			foreach ( $variation_ids as $var_id => $parent ) {
				$found_ids[] = $var_id;
			}

		}

        if ( $post_type == '' || $post_type == 'product' ) {

			$meta_query = array();

			if ( ! empty( $_REQUEST['filter_manage_stock'] ) ) {
				$meta_query[] = array(
					'key'	=> '_manage_stock',
					'value'	=> ( $_REQUEST['filter_manage_stock'] == 'yes' ) ? 'yes' : 'no'
				);
			}

			if ( $stock_status ) {
				$meta_query[] = array(
					'key'	=> '_stock_status',
					'value'	=> $stock_status
				);
			}

			/**
			 * Find ID's of posts managing stock
			 */
			$product_ids = get_posts(array(
				'post_type' 		=> 'product',
				'posts_per_page' 	=> -1,
				'post_status' 		=> 'publish',
				'fields'			=> 'ids',
				'meta_query'		=> $meta_query,
				'tax_query'			=> $tax_query,
				'meta_key'			=> $meta_key,
				's'					=> ( ! empty( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '',
				'orderby'			=> esc_attr( $orderby ),
				'order'				=> esc_attr( $order ),
			));

			$found_ids = array();

			// Loop through and grab variations too
			foreach ( $product_ids as $post_id ) {

				$found_ids[] = $post_id;

				if ( ! empty( $variation_ids ) ) foreach ( $variation_ids as $var_id => $parent ) {
					if ( $parent == $post_id ) {
						$found_ids[] = $var_id;
						unset( $variation_ids[ $var_id ] );
					}
				}
			}

			if ( ! empty( $variation_ids ) && $product_cat == '' ) {

				$existing_product_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'product';" );

				foreach ( $variation_ids as $var_id => $parent ) {
					if ( in_array( $parent, $existing_product_ids ) )
						$found_ids[] = $var_id;
				}
			}

		}

		/**
		 * Handle pagination
		 */
		$this->set_pagination_args( array(
            'total_items' => sizeof( $found_ids ),
            'per_page'    => $per_page,
            'total_pages' => ceil( sizeof( $found_ids ) / $per_page )
        ) );

        $found_ids = array_unique( array_slice( $found_ids, ( ( $current_page - 1 ) * $per_page ), $per_page ) );

		/**
		 * Get post objects
		 */
		foreach ( $found_ids as $id )
			$this->items[] = get_post( $id );
    }

	/**
	 * Display the pagination.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );


		if ( ! empty( $_REQUEST['products_per_page'] ) )
			$current_url = add_query_arg( 'products_per_page', $_REQUEST['products_per_page'], $current_url );
		if ( ! empty( $_REQUEST['filter_stock_status'] ) )
			$current_url = add_query_arg( 'filter_stock_status', $_REQUEST['filter_stock_status'], $current_url );
		if ( ! empty( $_REQUEST['filter_product_cat'] ) )
			$current_url = add_query_arg( 'filter_product_cat', $_REQUEST['filter_product_cat'], $current_url );
		if ( ! empty( $_REQUEST['filter_manage_stock'] ) )
			$current_url = add_query_arg( 'filter_manage_stock', $_REQUEST['filter_manage_stock'], $current_url );
		if ( ! empty( $_REQUEST['filter_product_type'] ) )
			$current_url = add_query_arg( 'filter_product_type', $_REQUEST['filter_product_type'], $current_url );
		if ( ! empty( $_REQUEST['s'] ) )
			$current_url = add_query_arg( 's', $_REQUEST['s'], $current_url );


		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				esc_attr( 'paged' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	function print_column_headers( $with_id = true ) {
		$screen = get_current_screen();

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$current_url = remove_query_arg( 'paged', $current_url );



		if ( ! empty( $_REQUEST['products_per_page'] ) )
			$current_url = add_query_arg( 'products_per_page', $_REQUEST['products_per_page'], $current_url );
		if ( ! empty( $_REQUEST['filter_stock_status'] ) )
			$current_url = add_query_arg( 'filter_stock_status', $_REQUEST['filter_stock_status'], $current_url );
		if ( ! empty( $_REQUEST['filter_product_cat'] ) )
			$current_url = add_query_arg( 'filter_product_cat', $_REQUEST['filter_product_cat'], $current_url );
		if ( ! empty( $_REQUEST['filter_manage_stock'] ) )
			$current_url = add_query_arg( 'filter_manage_stock', $_REQUEST['filter_manage_stock'], $current_url );
		if ( ! empty( $_REQUEST['filter_product_type'] ) )
			$current_url = add_query_arg( 'filter_product_type', $_REQUEST['filter_product_type'], $current_url );
		if ( ! empty( $_REQUEST['s'] ) )
			$current_url = add_query_arg( 's', $_REQUEST['s'], $current_url );



		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

}
