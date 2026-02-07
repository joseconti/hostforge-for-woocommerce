<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Delivery_Schedules Object.
 *
 * @class   YWSBS_Subscription_Delivery_Schedules
 * @since   2.2.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules_Admin' ) ) {

	/**
	 * Class YWSBS_Subscription_Delivery_Schedules_Admin
	 */
	class YWSBS_Subscription_Delivery_Schedules_Admin extends YWSBS_Subscription_Module_Admin {

		/**
		 * The module ID
		 *
		 * @var string
		 */
		protected $module_id = 'delivery-schedules';

		/**
		 * The list table class instance.
		 *
		 * @since 4.0.0
		 * @var object|null
		 */
		protected $list_table_class = null;

		/**
		 * Constructor
		 *
		 * @since  3.0.0
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'init', array( $this, 'register_scripts' ), 5 );

			add_action( 'current_screen', array( $this, 'preload_list_table_class' ) );

			add_action( 'ywsbs_after_single_product_options', array( $this, 'add_single_product_options' ) );
			add_action( 'ywsbs_after_variation_product_options', array( $this, 'add_single_product_options' ), 10, 2 );
			add_action( 'yith_ywsbs_delivery_schedules_tab', array( $this, 'delivery_status_tab' ) );

			add_action( 'add_meta_boxes', array( $this, 'delivery_schedules_metabox' ), 100, 2 );

			add_action( 'wp_ajax_ywsbs_update_delivery_status', array( $this, 'update_delivery_status' ) );
			add_action( 'admin_action_ywsbs_export_shipping_list', array( $this, 'export_shipping_list' ) );
			add_action( 'admin_init', array( $this, 'handle_delivery_table_actions' ) );
		}

		/**
		 * Preload list table class. This is useful for screen reader options.
		 *
		 * @since 4.0.0
		 */
		public function preload_list_table_class() {
			if ( empty( $this->list_table_class ) && ywsbs_is_admin_panel_page( 'delivery' ) && ( empty( $_GET['sub_tab'] ) || 'delivery-list-table' === sanitize_text_field( wp_unslash( $_GET['sub_tab'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$this->list_table_class = new YWSBS_Subscription_Delivery_Schedules_List_Table();
				// Overwrite current screen.
				set_current_screen( 'yith-plugins_page_ywsbs-delivery-schedules-list' );
				add_filter( 'yith_plugin_fw_wc_panel_screen_ids_for_assets', array( $this, 'register_list_table_screen_id_for_assets' ) );
			}
		}

		/**
		 * Register list table screen ID for assets
		 *
		 * @since  4.0.0
		 * @author YITH
		 * @param array $screen_ids An array of screen ids registered.
		 * @return array
		 */
		public function register_list_table_screen_id_for_assets( $screen_ids ) {
			$screen_ids[] = 'yith-plugins_page_ywsbs-delivery-schedules-list';
			return $screen_ids;
		}

		/**
		 * Register admin scripts
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function register_scripts() {

			// Register script.
			YITH_WC_Subscription_Assets::get_instance()->add_admin_script(
				'datatables',
				'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js',
				array(),
				'__return_false'
			);

			YITH_WC_Subscription_Assets::get_instance()->add_admin_script(
				'ywsbs-delivery-schedules-admin',
				YWSBS_DELIVERY_SCHEDULES_MODULE_URL . 'assets/js/delivery-schedules.js',
				array( 'jquery', 'datatables' ),
				array( $this, 'check_script_enqueue' )
			);

			YITH_WC_Subscription_Assets::get_instance()->localize_script(
				'ywsbs-delivery-schedules-admin',
				'ywsbsDeliverySchedulest',
				array(
					'ajaxurl'             => admin_url( 'admin-ajax.php' ),
					'deliveryAction'      => 'ywsbs_update_delivery_status',
					'deliveryNonce'       => wp_create_nonce( 'ywsbs_update_delivery_status' ),
					'confirmModalTitle'   => esc_html__( 'You are going to set this item as "Shipped".', 'yith-woocommerce-subscription' ),
					'confirmModalMessage' => esc_html__( 'This will automatically send a confirmation email to the customer. Do you want to continue?', 'yith-woocommerce-subscription' ),
					'datatableLengthMenu' => esc_html_x( 'Items per page:', '[Admin]metabox table length menu', 'yith-woocommerce-subscription' ),
				)
			);

			YITH_WC_Subscription_Assets::get_instance()->add_admin_style(
				'ywsbs-delivery-schedules-admin',
				YWSBS_DELIVERY_SCHEDULES_MODULE_URL . 'assets/css/delivery-schedules.css',
				array(),
				array( $this, 'check_script_enqueue' )
			);
		}

		/**
		 * Check if admin script must be enqueued
		 *
		 * @since  3.0.0
		 * @return boolean
		 */
		public function check_script_enqueue() {
			return ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE, true ) || ywsbs_is_admin_panel_page( 'delivery' );
		}

		/**
		 * Add single product options for module
		 *
		 * @since  3.0.0
		 * @param WC_Product $product The product object.
		 * @param integer    $loop    (Optional) The loop index for variation.
		 * @return void
		 */
		public function add_single_product_options( $product, $loop = 0 ) {

			if ( ! YWSBS_Subscription_Delivery_Schedules()->has_delivery_scheduled( $product ) ) {
				return;
			}

			$delivery_sync = $product->get_meta( '_ywsbs_delivery_synch' );
			if ( empty( $delivery_sync ) ) {
				$delivery_sync = YWSBS_Subscription_Delivery_Schedules()->get_general_delivery_options();
			}
			$override_delivery_schedule = $product->get_meta( '_ywsbs_override_delivery_schedule' );

			$template = $product->is_type( 'variation' ) ? 'delivery-schedules-variation-options' : 'delivery-schedules-product-options';
			include YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . "views/product/{$template}.php";
		}

		/**
		 * Delivery Schedules List Table
		 * Load the delivery schedules on admin page
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function delivery_status_tab() {
			if ( YWSBS_Subscription_Delivery_Schedules_DB::is_table_empty() || empty( $this->list_table_class ) ) {
				include_once YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . '/views/table/delivery-schedules-blank-state.php';
			} else {
				$table = $this->list_table_class;
				$table->prepare_items();

				include_once YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . '/views/table/delivery-schedules-list-table.php';
			}
		}

		/**
		 * Add the meta-box to show delivery scheduled
		 *
		 * @access public
		 * @since  3.0.0
		 * @param string $post_type Post type.
		 * @param object $post      WP_Post.
		 * @return void
		 */
		public function delivery_schedules_metabox( $post_type, $post ) {
			if ( YITH_YWSBS_POST_TYPE !== $post_type ) {
				return;
			}

			$subscription       = ywsbs_get_subscription( $post->ID );
			$delivery_schedules = $subscription->get( 'delivery_schedules' );

			if ( ! $subscription || ! $delivery_schedules ) {
				return;
			}

			$each = '';
			switch ( $delivery_schedules['delivery_period'] ) {
				case 'weeks':
					$day_weeks = ywsbs_get_period_options( 'day_weeks' );
					$each     .= $day_weeks[ $delivery_schedules['sych_weeks'] ];
					break;
				case 'months':
					$day_months = ywsbs_get_period_options( 'day_months' );
					$each      .= $day_months[ $delivery_schedules['months'] ] . ' ' . esc_html__( 'of each month', 'yith-woocommerce-subscription' );
					break;
				case 'years':
					$day_months = ywsbs_get_period_options( 'day_months' );
					$months     = ywsbs_get_period_options( 'months' );
					$each      .= $day_months[ $delivery_schedules['years_day'] ] . ' ' . $months[ $delivery_schedules['years_month'] ];
					break;
			}
			$each .= ')';
			// translators: 1.delivery gap 2. delivery period, 2. date.
			$label = sprintf( __( 'Every %1$d %2$s - on %3$s', 'yith-woocommerce-subscription' ), $delivery_schedules['delivery_gap'], ywsbs_get_time_options_sing_plur( $delivery_schedules['delivery_period'], $delivery_schedules['delivery_gap'] ), $each );

			add_meta_box( 'ywsbs-delivery-schedules-subscription', esc_html__( 'Delivery schedules', 'yith-woocommerce-subscription' ) . ' (' . $label, array( $this, 'output_metabox' ), YITH_YWSBS_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Delivery schedules metabox
		 *
		 * @param WP_Post $post Current post.
		 */
		public function output_metabox( $post ) {
			$delivery_schedules = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_ordered( $post->ID );
			include YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . '/views/metabox/subscription-delivery-schedules.php';
		}

		/**
		 * Get module tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_tabs() {
			return array(
				'delivery' => array(
					'title' => __( 'Delivery schedules', 'yith-woocommerce-subscription' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>',
				),
			);
		}

		/**
		 * Get product meta keys
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_product_meta() {
			return array(
				'_ywsbs_override_delivery_schedule',
				'_ywsbs_delivery_synch',
			);
		}

		/**
		 * Get posted meta value
		 *
		 * @since  3.0.0
		 * @param array   $posted The posted data.
		 * @param string  $key    The meta key to get value for.
		 * @param integer $index  (Optional) The index for array posted data. Default is false.
		 * @return mixed|null The meta posted value, null if not found.
		 */
		protected function get_product_meta_posted_value( $posted, $key, $index = false ) {

			if ( '_ywsbs_delivery_synch' === $key && isset( $posted[ $key ] ) ) {
				$delivery_sync = false === $index ? $posted[ $key ] : $posted[ $key ][ $index ];
				// Check for checkbox value.
				$delivery_sync['on'] = isset( $delivery_sync['on'] ) ? 'yes' : 'no';
				return wc_clean( $delivery_sync );
			}

			return parent::get_product_meta_posted_value( $posted, $key, $index );
		}

		/**
		 * Update the status of the delivery schedules via Ajax.
		 *
		 * @throws Exception Throws Exception.
		 */
		public function update_delivery_status() {

			check_ajax_referer( 'ywsbs_update_delivery_status', 'security' );

			try {

				if ( ! current_user_can( 'edit_shop_orders' ) || empty( $_POST['deliveryID'] ) || empty( $_POST['status'] ) ) { // phpcs:ignore
					throw new Exception( _x( 'You don\'t have permission to update the delivery status', 'Error message inside the subscription details (backend)', 'yith-woocommerce-subscription' ) );
				}

				$available_status = YWSBS_Subscription_Delivery_Schedules()->get_status();
				$status           = sanitize_text_field( wp_unslash( $_POST['status'] ) );
				if ( ! array_key_exists( $status, $available_status ) ) {
					throw new Exception( _x( 'This is not a valid delivery status', 'Error message inside the subscription details (backend)', 'yith-woocommerce-subscription' ) );
				}

				$delivery_id   = absint( $_POST['deliveryID'] );
				$update_result = YWSBS_Subscription_Delivery_Schedules()->update_status( $delivery_id, $status );

				if ( empty( $update_result['updated'] ) ) {
					throw new Exception( _x( 'Error updating delivery status!', 'Error message inside the subscription details (backend)', 'yith-woocommerce-subscription' ) );
				}

				wp_send_json_success(
					array(
						'status'      => $status,
						'statusLabel' => $available_status[ $status ],
						'sentOn'      => ywsbs_get_formatted_date( $update_result['sent_on'], '-' ),
					)
				);
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'error' => $e->getMessage() ) );
			}
		}

		/**
		 * Export the shipping delivery list
		 *
		 * @since  3.0.0
		 * @throws \Mpdf\MpdfException Throws Exception.
		 */
		public function export_shipping_list() {
			require_once YITH_YWSBS_DIR . 'lib/autoload.php';

			$rows      = YWSBS_Subscription_Delivery_Schedules_DB::get_processing_delivery_schedules();
			$mpdf_args = apply_filters(
				'ywsbs_mpdf_args',
				array(
					'autoScriptToLang' => true,
					'autoLangToFont'   => true,
				)
			);

			if ( is_array( $mpdf_args ) ) {
				$mpdf = new \Mpdf\Mpdf( $mpdf_args );
			} else {
				$mpdf = new \Mpdf\Mpdf();
			}

			$direction            = is_rtl() ? 'rtl' : 'ltr';
			$mpdf->directionality = apply_filters( 'yith_ywsbs_mpdf_directionality', $direction );

			$html    = '';
			$counter = 1;
			if ( $rows ) {
				foreach ( $rows as $row ) {
					$subscription = ywsbs_get_subscription( $row->subscription_id );
					$shipping     = $subscription->get_address_fields( 'shipping', true );
					if ( $shipping ) {

						$shipping = WC()->countries->get_formatted_address( $shipping, '<br>' );
						if ( ! empty( $shipping ) ) {
							$html .= '<div class="address" style="width:40%;padding:3%;margin-left:4%;float:left; margin-bottom: 20px;border:1px solid #cccccc">' . $shipping . '</div>';
							if ( $counter > 1 && 0 === ( $counter % 10 ) ) {
								$mpdf->WriteHTML( $html );
								$mpdf->AddPage();
								$html = '';
							}
							++$counter;
						}
					}
				}
			}

			$mpdf->WriteHTML( $html );

			$pdf      = $mpdf->Output();
			$filename = 'ywsbs-subscriptions-shipping-list-' . gmdate( 'Y-m-d-H-i' ) . '.pdf';
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: application/pdf; charset=' . get_option( 'blog_charset' ), true );

			$df = fopen( 'php://output', 'w' );

			fwrite( $df, $pdf ); //phpcs:ignore

			fclose( $df ); //phpcs:ignore
		}

		/**
		 * Handle delivery schedules table bulk actions
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function handle_delivery_table_actions() {
			if ( ! isset( $_GET['_wpnonce'], $_GET['action'], $_GET['delivery-schedules'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bulk-delivery-schedules' ) ) {
				return;
			}

			$schedules = array_map( 'absint', $_GET['delivery-schedules'] );
			$status    = str_replace( 'set_status_to_', '', sanitize_text_field( wp_unslash( $_GET['action'] ) ) );
			if ( empty( $status ) ) {
				return;
			}

			foreach ( $schedules as $schedule ) {
				YWSBS_Subscription_Delivery_Schedules()->update_status( $schedule, $status );
			}

			$redirect_url = remove_query_arg( array( 'action', 'action2', '_wpnonce', 'delivery-schedules' ) );
			wp_safe_redirect( add_query_arg( 'bulk-delivery-status-updated', '1', $redirect_url ) );
			exit;
		}
	}
}
