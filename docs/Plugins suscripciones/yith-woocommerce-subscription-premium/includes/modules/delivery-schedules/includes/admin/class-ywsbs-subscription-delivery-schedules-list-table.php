<?php
/**
 * Delivery Schedules List Table
 *
 * @class   YWSBS_Subscription_Delivery_Schedules_List_Table
 * @since   2.2.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class YWSBS_Subscription_Delivery_Schedules_List_Table
 */
class YWSBS_Subscription_Delivery_Schedules_List_Table extends WP_List_Table {

	/**
	 * YWSBS_Subscription_Delivery_Schedules_List_Table constructor.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'plural'   => 'delivery-schedules',
				'singular' => 'delivery-schedules',
				'ajax'     => false,
				'screen'   => 'yith-plugins_page_ywsbs-delivery-schedules-list',
			)
		);
		parent::__construct( $args );

		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
	}

	/**
	 * Get the columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                            => '<input type="checkbox" />',
			'product'                       => esc_html_x( 'Product', 'Delivery scheduled table - subscription product name', 'yith-woocommerce-subscription' ),
			'subscription_id'               => esc_html_x( 'Subscription', 'Delivery scheduled table - Subscription id', 'yith-woocommerce-subscription' ),
			'subscription_status'           => esc_html_x( 'Subscription status', 'Delivery scheduled table - Related subscription status', 'yith-woocommerce-subscription' ),
			'subscription_payment_due_date' => esc_html_x( 'Payment due', 'Delivery scheduled table - Related subscription payment date', 'yith-woocommerce-subscription' ),
			'customer'                      => esc_html_x( 'Customer', 'Delivery scheduled table - Customer ', 'yith-woocommerce-subscription' ),
			'status'                        => esc_html_x( 'Delivery status', 'Delivery scheduled table - Status of delivery ', 'yith-woocommerce-subscription' ),
			'scheduled_date'                => esc_html_x( 'Shipping on', 'Delivery scheduled table - Shipping date of the delivery', 'yith-woocommerce-subscription' ),
			'sent_on'                       => esc_html_x( 'Shipped on', 'Delivery scheduled table - Date of delivery', 'yith-woocommerce-subscription' ),
			'delivery_info'                 => esc_html_x( 'Delivery info', 'Delivery scheduled table - Delivery address', 'yith-woocommerce-subscription' ),
		);
	}

	/**
	 * Adjust which columns are displayed by default.
	 *
	 * @since  4.0.0
	 * @author YITH
	 * @param array  $hidden Current hidden columns.
	 * @param object $screen Current screen.
	 * @return array
	 */
	public function default_hidden_columns( $hidden, $screen ) {
		$hidden = array_merge(
			$hidden,
			array(
				'subscription_status',
				'subscription_payment_due_date',
			)
		);

		return $hidden;
	}

	/**
	 * Gets a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @since 3.1.0
	 * @return string[] Array of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = parent::get_table_classes();

		return array_merge( $classes, array( 'yith-plugin-fw__classic-table' ) );
	}

	/**
	 * Prepare items to show
	 *
	 * @since  3.0.0
	 */
	public function prepare_items() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		global $wpdb;

		$join         = '';
		$where        = '';
		$order_string = '';

		// Build the request.
		if ( ! empty( $_REQUEST['delivery_status_filter'] ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', sanitize_text_field( wp_unslash( $_REQUEST['delivery_status_filter'] ) ) );
		}

		if ( ! empty( $_REQUEST['start_date'] ) ) {
			$start_date = sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) );
			$where     .= $wpdb->prepare( ' AND scheduled_date >= %s', wp_date( 'Y-m-d 00:00:00', strtotime( $start_date ) ) );
		}

		if ( ! empty( $_REQUEST['end_date'] ) ) {
			$end_date = sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) );
			$where   .= $wpdb->prepare( ' AND scheduled_date <= %s', wp_date( 'Y-m-d 00:00:00', strtotime( $end_date ) ) );
		}

		if ( ! empty( $_REQUEST['customer_user'] ) ) {
			$join   = ' LEFT JOIN ' . $wpdb->postmeta . ' as pm ON pm.post_id = act.subscription_id ';
			$where .= $wpdb->prepare( ' AND ( pm.meta_key = "user_id" AND pm.meta_value = %d )', absint( $_REQUEST['customer_user'] ) );
		}

		if ( ! empty( $_REQUEST['product_search'] ) ) {
			$join   = ' LEFT JOIN ' . $wpdb->postmeta . ' as pm2 ON pm2.post_id = act.subscription_id ';
			$where .= $wpdb->prepare( ' AND ( pm2.meta_key IN ("product_id","variation_id") AND pm2.meta_value = %d )', absint( $_REQUEST['product_search'] ) );
		}

		if ( ! empty( $_REQUEST['subscription_status_filter'] ) ) {
			$join   = ' LEFT JOIN ' . $wpdb->postmeta . ' as pm3 ON pm3.post_id = act.subscription_id ';
			$where .= $wpdb->prepare( ' AND ( pm3.meta_key = "status" AND pm3.meta_value = %s )', sanitize_text_field( wp_unslash( $_REQUEST['subscription_status_filter'] ) ) );
		}

		$join  = apply_filters( 'ywsbs_delivery_schedules_list_table_join', $join, $wpdb->ywsbs_delivery_schedules );
		$where = apply_filters( 'ywsbs_delivery_schedules_list_table_where', $where, $wpdb->ywsbs_delivery_schedules );

		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'act.id'; //phpcs:ignore
		$order   = ! empty( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC'; //phpcs:ignore
		if ( ! empty( $orderby ) && ! empty( $order ) ) {
			$order_string = ' ORDER BY ' . $orderby . ' ' . $order;
		}

		// Which page is this?
		$paged    = ! empty( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 25;
		$offset   = ( empty( $paged ) || $paged <= 0 ) ? 0 : ( $paged - 1 ) * $per_page;

		// Sets columns headers.
		$columns               = $this->get_columns();
		$hidden                = get_hidden_columns( $this->screen->id );
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items                       = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->ywsbs_delivery_schedules} AS act {$join} WHERE 1=1 {$where} {$order_string} LIMIT {$offset}, {$per_page}" );  //phpcs:ignore
		$total_items                       = $wpdb->get_var( 'SELECT FOUND_ROWS();' ); // phpcs:ignore

		/* -- Register the pagination -- */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => ceil( $total_items / $per_page ),
				'per_page'    => $per_page,
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Fill the columns.
	 *
	 * @param object $item        Current Object.
	 * @param string $column_name Current Column.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$subscription = ywsbs_get_subscription( $item->subscription_id );
		switch ( $column_name ) {
			case 'product':
				$product = $subscription->get_product();
				if ( $product ) {
					return '<a href="' . admin_url( 'post.php?post=' . $subscription->get( 'product_id' ) . '&action=edit' ) . '">' . $product->get_formatted_name() . '</a>';
				} else {
					return $subscription->get_product_name();
				}

			case 'subscription_id':
				return '<a href="' . admin_url( 'post.php?post=' . $item->subscription_id . '&action=edit' ) . '">' . $subscription->get_number() . '</a>';

			case 'subscription_status':
				$subscription_status_list = ywsbs_get_status();
				$status                   = $subscription->get_status();
				$subscription_status      = $subscription_status_list[ $status ] ?? 'cancelled';
				return sprintf( '<span class="status %1$s">%2$s</span>', esc_attr( $subscription->get_status() ), esc_html( $subscription_status ) );

			case 'subscription_payment_due_date':
				return ywsbs_get_formatted_date( $subscription->get_payment_due_date() );

			case 'status':
				$this->get_status_element( $item->id, $item->status );
				break;

			case 'scheduled_date':
				return ywsbs_get_formatted_date( $item->scheduled_date, '' );

			case 'sent_on':
				return ywsbs_get_formatted_date( $item->sent_on, '-' );

			case 'delivery_info':
				$shipping = $subscription->get_address_fields( 'shipping', true );
				return $shipping ? WC()->countries->get_formatted_address( $shipping, ', ' ) : '-';

			case 'customer':
				$customer = YWSBS_Subscription_User::get_user_info_for_subscription_list( $subscription );
				echo wp_kses_post( $customer );
				break;

			default:
				return $item->$column_name; // Show the whole array for troubleshooting purposes.
		}
	}

	/**
	 * Prints column cb
	 *
	 * @since 1.0.0
	 * @param object $item Item to use to print CB record.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['plural'], // Let's simply repurpose the table's plural label.
			$item->id // The value of the checkbox should be the record's id.
		);
	}

	/**
	 * Return status element to update manually the status of the delivery schedules.
	 *
	 * @since  3.0.0
	 * @param int    $delivery_scheduled_id Delivery schedules id.
	 * @param string $status                Status of delivery schedules.
	 */
	public function get_status_element( $delivery_scheduled_id, $status ) {
		$status_list = YWSBS_Subscription_Delivery_Schedules()->get_status();
		?>
		<div class="delivery-status" data-id="<?php echo esc_attr( $delivery_scheduled_id ); ?>">
			<div class="status-label" data-value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_list[ $status ] ); ?></div>
			<select class="status-change">
				<?php foreach ( $status_list as $key => $single_status ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $single_status ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $single_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Get sortable columns.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'subscription_id' => array( 'subscription_id', false ),
			'status'          => array( 'status', false ),
			'scheduled_date'  => array( 'scheduled_date', false ),
			'sent_on'         => array( 'sent_on', false ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination, which
	 * includes our Filters
	 *
	 * @since 3.0.0
	 * @param string $which The placement, one of 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$this->render_status_filter();
			$this->render_subscription_status_filter();
			$this->render_product_filter();
			$this->render_customer_filter();
			$this->render_start_end_date_filter();
			$this->render_filter_button();
		}
	}

	/**
	 * Render filter button
	 *
	 * @since  3.0.0
	 */
	protected function render_filter_button() {
		echo '<button id="post-query-submit" class="button">' . esc_html__( 'Filter', 'yith-woocommerce-subscription' ) . '</button>';
	}


	/**
	 * Sets bulk actions for table
	 *
	 * @since 3.0.0
	 * @return array Array of available actions.
	 */
	public function get_bulk_actions() {
		$actions  = array();
		$statuses = YWSBS_Subscription_Delivery_Schedules()->get_status();

		foreach ( $statuses as $key => $label ) {
			// translators: %s is the status label.
			$actions[ "set_status_to_{$key}" ] = sprintf( _x( 'Set status to "%s"', '[Admin]Delivery schedules bulk action label', 'yith-woocommerce-subscription' ), $label );
		}

		return apply_filters( 'ywsbs_delivery_schedules_list_table_bulk_actions', $actions );
	}


	/**
	 * Render customer filter.
	 *
	 * @since 3.0.0
	 */
	protected function render_customer_filter() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		echo '<div class="alignleft actions yith-search-customer-wrapper">';

		// Customers select 2.
		$user_string = '';
		$customer_id = '';

		if ( ! empty( $_REQUEST['customer_user'] ) ) {
			$customer_id = absint( $_REQUEST['customer_user'] );
			$user        = get_user_by( 'id', $customer_id );
			$user_string = $user ? esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) : '';
		}

		yit_add_select2_fields(
			array(
				'type'             => 'hidden',
				'class'            => 'wc-customer-search',
				'id'               => 'customer_user',
				'name'             => 'customer_user',
				'data-placeholder' => esc_html__( 'Show all customers', 'yith-woocommerce-subscription' ),
				'data-allow_clear' => true,
				'data-selected'    => array( $customer_id => esc_attr( $user_string ) ),
				'data-multiple'    => false,
				'value'            => $customer_id,
				'style'            => 'width:200px',
			)
		);

		echo '</div>';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render product filter.
	 *
	 * @since 3.0.0
	 */
	protected function render_product_filter() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		echo '<div class="alignleft actions yith-search-product-wrapper">';

		// Customers select 2.
		$product_name = '';
		$product_id   = '';

		if ( ! empty( $_REQUEST['product_search'] ) ) { // phpcs:ignore
			$product_id   = absint( $_REQUEST['product_search'] ); // phpcs:ignore
			$product      = wc_get_product( $product_id );
			$product_name = '#' . $product_id . ' ' . $product->get_name();
		}

		yit_add_select2_fields(
			array(
				'type'             => 'hidden',
				'class'            => 'wc-product-search',
				'id'               => 'product_search',
				'name'             => 'product_search',
				'data-placeholder' => esc_html__( 'Show all product', 'yith-woocommerce-subscription' ),
				'data-allow_clear' => true,
				'data-selected'    => array( $product_id => esc_attr( $product_name ) ),
				'data-multiple'    => false,
				'value'            => $product_id,
				'style'            => 'width:300px',
				'data-action'      => 'ywsbs_json_search_ywsbs_products',
			)
		);

		echo '</div>';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render start date and end date filter.
	 *
	 * @since 3.0.0
	 */
	protected function render_start_end_date_filter() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$start_date = ( isset( $_REQUEST['start_date'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) ) : '';
		$end_date   = ( isset( $_REQUEST['end_date'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) ) : '';
		?>
		<div class="alignleft actions yith-start-and-end-date-wrapper">
			<?php echo esc_html__( 'From: ', 'yith-woocommerce-subscription' ); ?>
			<input type="text" size="11" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" placeholder="yyyy-mm-dd" data-date-format="yy-mm-dd" class="range_datepicker from yith-plugin-fw-datepicker" autocomplete="off" id="start_date">
			<?php echo esc_html__( ' To: ', 'yith-woocommerce-subscription' ); ?>
			<input type="text" size="11"  value="<?php echo esc_html( $end_date ); ?>" name="end_date" placeholder="yyyy-mm-dd" data-date-format="yy-mm-dd" class="range_datepicker to yith-plugin-fw-datepicker" autocomplete="off" id="end_date">
		</div>
		<?php
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render delivery schedules filter.
	 *
	 * @since 3.0.0
	 */
	protected function render_status_filter() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = ( isset( $_REQUEST['delivery_status_filter'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['delivery_status_filter'] ) ) : '';
		?>
		<div class="alignleft actions yith-delivery-status-filter-wrapper">
			<select name="delivery_status_filter" class="delivery_status_filter wc-enhanced-select">
				<option value=""><?php echo esc_html_x( 'All delivery statuses', 'Option to select all delivered schedules status', 'yith-woocommerce-subscription' ); ?></option>
				<?php foreach ( YWSBS_Subscription_Delivery_Schedules()->get_status() as $key => $value ) : ?>
					<option value="<?php echo esc_html( $key ); ?>" <?php selected( $key, $status ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render delivery subscription status filter.
	 *
	 * @since 3.0.0
	 */
	protected function render_subscription_status_filter() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = ( isset( $_REQUEST['subscription_status_filter'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['subscription_status_filter'] ) ) : '';
		?>
		<div class="alignleft actions yith-subscription-status-filter-wrapper">
			<select name="subscription_status_filter" class="subscription_status_filter wc-enhanced-select">
				<option value=""><?php echo esc_html__( 'All subscription statuses', 'yith-woocommerce-subscription' ); ?></option>
				<?php foreach ( ywsbs_get_status() as $key => $value ) : ?>
					<option value="<?php echo esc_html( $key ); ?>" <?php selected( $key, $status ); ?>><?php echo esc_html( ucfirst( $value ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
