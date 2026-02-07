<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box Object.
 *
 * @class   YWSBS_Subscription_Box
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Box {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * The product type
		 *
		 * @since 4.0.0
		 * @const string
		 */
		const PRODUCT_TYPE = 'ywsbs-subscription-box';

		/**
		 * YWSBS_Subscription_Box_Admin instance.
		 *
		 * @var YWSBS_Subscription_Box_Admin
		 */
		protected $admin;

		/**
		 * YWSBS_Subscription_Box_Admin instance.
		 *
		 * @var YWSBS_Subscription_Box_Frontend
		 */
		protected $frontend;

		/**
		 * Constructor
		 *
		 * @since 4.0.0
		 * @return void
		 */
		private function __construct() {
			$this->init_variables();
			$this->init_hooks();
		}

		/**
		 * Set class variables
		 *
		 * @since  4.0.0
		 * @return void
		 */
		protected function init_variables() {
			// include functions file.
			include_once 'functions-ywsbs-subscription-box.php';

			YWSBS_Subscription_Box_Email_Handler::init();
			YWSBS_Subscription_Box_Order::init();

			if ( YITH_WC_Subscription::is_request( 'admin' ) ) {
				$this->admin = new YWSBS_Subscription_Box_Admin();
			}

			if ( YITH_WC_Subscription::is_request( 'frontend' ) ) {
				YWSBS_Subscription_Box_Rest::install();
				YWSBS_Subscription_Box_Cart::init();

				$this->frontend = new YWSBS_Subscription_Box_Frontend();
			}
		}

		/**
		 * Init class hooks
		 *
		 * @since 4.0.0
		 * @return void
		 */
		protected function init_hooks() {
			// Register new product type.
			add_action( 'product_type_selector', array( $this, 'add_new_product_type' ) );
			add_action( 'woocommerce_product_class', array( $this, 'filter_product_class' ), 10, 2 );
			// Schedules event.
			add_action( 'ywsbs_subscription_box_schedule_emails', array( $this, 'schedules_emails' ) );
		}

		/**
		 * Return main admin instance
		 *
		 * @since 4.0.0
		 * @return YWSBS_Subscription_Box_Admin|null
		 */
		public function get_admin() {
			return $this->admin;
		}

		/**
		 * Return main frontend instance
		 *
		 * @since 4.0.0
		 * @return YWSBS_Subscription_Box_Frontend|null
		 */
		public function get_frontend() {
			return $this->frontend;
		}

		/**
		 * Add new product type to main product type select
		 *
		 * @since 4.0.0
		 * @param array $types The product types.
		 * @return array
		 */
		public function add_new_product_type( $types ) {
			$types[ self::PRODUCT_TYPE ] = __( 'Subscription box', 'yith-woocommerce-subscription' );
			return $types;
		}

		/**
		 * Set correct class for subscription-box product type
		 *
		 * @since 4.0.0
		 * @param string $classname The product classname.
		 * @param string $product_type The product type.
		 * @return string
		 */
		public function filter_product_class( $classname, $product_type ) {
			return self::PRODUCT_TYPE === $product_type ? 'YWSBS_Subscription_Box_Product' : $classname;
		}
	}
}
