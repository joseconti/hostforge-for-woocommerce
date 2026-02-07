<?php 
/**
 * YWSBS_Shop_Order_Post_Type_Admin Class.
 *
 * Add custom information inside WC_Order Admin.
 *
 * @class   YWSBS_Shop_Order_Post_Type_Admin
 * @since   2.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Shop_Order_Post_Type_Admin' ) ) {

	/**
	 * Class YWSBS_Shop_Order_Post_Type_Admin
	 */
	class YWSBS_Shop_Order_Post_Type_Admin {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Order screen id.
		 *
		 * @var string
		 */
		protected $screen_id = '';

		/**
		 * Constructor
		 *
		 * Initialize actions and filters to be used
		 *
		 * @since 2.0.0
		 */
		private function __construct() {
			// Init hooks to let WC initialize correctly.
			add_action( 'admin_init', array( $this, 'setup' ) );
		}

		/**
		 * Setup class hooks and filters.
		 *
		 * @return void
		 */
		public function setup() {
			$this->screen_id = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

			// Customize edit order view.
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_order_hidden_input' ), 10, 1 );
			// Add the column subscription on order list.
			// Shop order as post_type.
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'manage_shop_order_columns' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'show_subscription_ref' ) );
			// Shop order with HPOS system.
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'manage_shop_order_columns' ), 20 );
			add_action( 'manage_' . $this->screen_id . '_custom_column', array( $this, 'render_shop_table_column' ), 10, 2 );
			add_filter( 'ywsbs_check_valid_admin_page', array( $this, 'set_valid_pages_for_orders' ), 10, 2 );

			$this->register_subscription_metabox();
		}

		/**
		 * Add order input hidden to distinguish renew from original subscription order.
		 *
		 * @since 3.0.0
		 * @param WC_Order $order The order object.
		 * @return void
		 */
		public function add_order_hidden_input( $order ) {
			$subscriptions = $order->get_meta( 'subscriptions' );
			if ( empty( $subscriptions ) ) {
				return;
			}

			// TODO find a better solution for be compliant to old WC version and new HPOS system.
			echo '<input type="hidden" name="ywsbs_is_renew" value="' . ( $order->get_meta( 'is_a_renew' ) ? 'yes' : 'no' ) . '"/>';
		}

		/**
		 * Add the metabox to show the info of subscription
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function show_related_subscription() {
			_deprecated_function( __METHOD__, '3.0.0' );
			$this->register_subscription_metabox();
		}

		/**
		 * Meta-box to show the related subscriptions inside the order editor
		 *
		 * @since 1.0.0
		 * @param WP_Post $post WP_Post object.
		 * @return void
		 */
		public function show_related_subscription_metabox( $post ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			$this->output_subscription_metabox( $post );
		}

		/**
		 * Check if we are in HPOS edit order page and enqueue subscription style.
		 *
		 * @since 3.0.0
		 * @param boolean $is_valid  True if the current screen is valid for the post_type, false otherwise.
		 * @param string  $post_type The post type to check.
		 * @return boolean
		 */
		public function set_valid_pages_for_orders( $is_valid, $post_type ) {
			global $current_screen;
			return $is_valid || ( $current_screen && $this->screen_id === $current_screen->id && 'shop_order' === $post_type );
		}

		/**
		 * Add the metabox to show the info of subscription
		 *
		 * @since  1.0.0
		 * @return void
		 */
		protected function register_subscription_metabox() {
			add_meta_box( 'ywsbs-related-subscription', esc_html__( 'Related subscriptions', 'yith-woocommerce-subscription' ), array( $this, 'output_subscription_metabox' ), $this->screen_id, 'normal', 'core' );
		}

		/**
		 * Meta-box to show the related subscriptions inside the order editor
		 *
		 * @since 1.0.0
		 * @param WP_Post|WC_Order $order WP_Post|WC_Order object.
		 * @return void
		 */
		public function output_subscription_metabox( $order ) {
			$order             = $order instanceof WC_Order ? $order : wc_get_order( $order->ID );
			$subscription_list = $order ? $order->get_meta( 'subscriptions' ) : array();

			if ( empty( $subscription_list ) ) {
				printf( '<p>%s</p>', esc_html__( 'There are no subscriptions related to this order.', 'yith-woocommerce-subscription' ) );
				return;
			}

			$subscriptions = array();

			foreach ( $subscription_list as $subscription_id ) {
				$subscription = ywsbs_get_subscription( $subscription_id );
				if ( is_null( $subscription->post ) ) {
					continue;
				}

				array_push( $subscriptions, $subscription );
			}

			include YITH_YWSBS_VIEWS_PATH . '/metabox/related-subscriptions.php';
		}

		/**
		 * Add subscription column
		 *
		 * @since  1.4.5
		 * @param array $columns Column list.
		 * @return array
		 */
		public function manage_shop_order_columns( $columns ) {

			$order_items = array( 'subscription_ref' => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ) );
			$ref_pos     = array_search( 'order_date', array_keys( $columns ) ); //phpcs:ignore
			$columns     = array_slice( $columns, 0, $ref_pos + 1, true ) + $order_items + array_slice( $columns, $ref_pos + 1, count( $columns ) - 1, true );

			$order_items = array( 'subscription_payment_type' => esc_html__( 'Payment type', 'yith-woocommerce-subscription' ) );
			$ref_pos     = array_search( 'order_status', array_keys( $columns ) ); //phpcs:ignore
			$columns     = array_slice( $columns, 0, $ref_pos + 1, true ) + $order_items + array_slice( $columns, $ref_pos + 1, count( $columns ) - 1, true );

			return $columns;
		}

		/**
		 * Show the subscription number inside the order list.
		 *
		 * @param string $column Column.
		 * @return void
		 */
		public function show_subscription_ref( $column ) {
			global $post, $the_order;

			if ( empty( $the_order ) || ( ( $the_order instanceof WC_Order ) && $the_order->get_id() !== $post->ID ) ) {
				$the_order = wc_get_order( $post->ID );
			}

			if ( method_exists( $this, "render_{$column}_column" ) ) {
				call_user_func( array( $this, "render_{$column}_column" ), $the_order );
			}
		}

		/**
		 * Render shop table column
		 *
		 * @since 3.0.0
		 * @param string   $column_id The column id to render.
		 * @param WC_Order $order     Order instance.
		 * @return void
		 */
		public function render_shop_table_column( $column_id, $order ) {
			if ( method_exists( $this, "render_{$column_id}_column" ) ) {
				call_user_func( array( $this, "render_{$column_id}_column" ), $order );
			}
		}

		/**
		 * Render subscription_ref column
		 *
		 * @since 3.0.0
		 * @param WC_Order $order The order object.
		 * @return void
		 */
		protected function render_subscription_ref_column( $order ) {
			$subscriptions = $order->get_meta( 'subscriptions' );
			if ( empty( $subscriptions ) ) {
				echo '-';
				return;
			}

			$links = array();
			foreach ( $subscriptions as $subscription_id ) {
				$subscription = ywsbs_get_subscription( $subscription_id );
				$links[]      = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $subscription_id ), apply_filters( 'yswbw_subscription_number', $subscription->get_number() ) );
			}

			echo wp_kses_post( implode( ', ', $links ) );
		}

		/**
		 * Render subscription_payment_type column
		 *
		 * @since 3.0.0
		 * @param WC_Order $order The order object.
		 * @return void
		 */
		protected function render_subscription_payment_type_column( $order ) {
			$is_first_payment = $order->get_meta( '_ywsbs_order_version' );
			$is_a_renew       = $order->get_meta( 'is_a_renew' );
			$show             = ( '' !== $is_first_payment ) ? esc_html__( 'First payment', 'yith-woocommerce-subscription' ) : '';
			$show             = ( 'yes' === $is_a_renew ) ? esc_html__( 'Renewal', 'yith-woocommerce-subscription' ) : $show;

			echo esc_html( $show );
		}
	}
}
