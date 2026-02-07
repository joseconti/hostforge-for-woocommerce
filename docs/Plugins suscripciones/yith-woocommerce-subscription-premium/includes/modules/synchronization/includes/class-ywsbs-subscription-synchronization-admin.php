<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Synchronization_Admin Class.
 * Handle the admin section for module "synchronization"
 *
 * @class   YWSBS_Subscription_Synchronization_Admin
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Synchronization_Admin' ) ) {
	/**
	 * Class YWSBS_Subscription_Synchronization_Admin
	 */
	class YWSBS_Subscription_Synchronization_Admin extends YWSBS_Subscription_Module_Admin {

		/**
		 * The module ID
		 *
		 * @var string
		 */
		protected $module_id = 'synchronization';

		/**
		 * Constructor
		 *
		 * Initialize the YWSBS_Subscription_Synchronization Object
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			parent::__construct();

			// Handle product meta.
			add_action( 'ywsbs_single_product_options_after_recurring_price', array( $this, 'add_single_product_options' ), 10, 1 );
			add_action( 'ywsbs_product_variation_options_after_recurring_price', array( $this, 'add_single_product_options' ), 10, 2 );
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
			if ( ! YWSBS_Subscription_Synchronization()->is_synchronizable( $product ) ) {
				return;
			}

			// Define template variables.
			$sync_info = $product->get_meta( '_ywsbs_synchronize_info' );
            $sync_ref_date = $product->get_meta( '_ywsbs_synchronize_ref_date' ) ?: 'purchase_date';

			$template = $product->is_type( 'variation' ) ? 'sync-variation-options' : 'sync-product-options';
			include YWSBS_SYNCHRONIZATION_MODULE_PATH . "views/product/{$template}.php";
		}

		/**
		 * Get product meta keys
		 *
		 * @since 3.0.0
		 * @return string|string[]
		 */
		protected function get_product_meta() {
			return array( '_ywsbs_synchronize_info', '_ywsbs_synchronize_ref_date' );
		}

		/**
		 * Get module tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_tabs() {
			return array(
				'synchronization' => array(
					'title'       => __( 'Renewal synchronization', 'yith-woocommerce-subscription' ),
					'description' => __( 'Synchronize all subscription renewals to easily track your customers’ payments.', 'yith-woocommerce-subscription' ),
					'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>',
				),
			);
		}
	}
}
