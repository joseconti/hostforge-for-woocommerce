<?php
/**
 * Deprecated filter hooks
 *
 * @class   YWSBS_Subscription_Deprecated_Filters
 * @since   4.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit;

if ( ! class_exists( 'YWSBS_Subscription_Deprecated_Filters' ) ) {
	/**
	 * This class maps old filters to new ones.
	 *
	 * @class YWSBS_Subscription_Deprecated_Filters
	 * @since 4.0.0
	 */
	class YWSBS_Subscription_Deprecated_Filters extends WC_Deprecated_Hooks {

		/**
		 * Array of deprecated hooks we need to handle.
		 * Format of 'new' => 'old'.
		 *
		 * @var array
		 */
		protected $deprecated_hooks = array(
			'ywsbs_gateway_paypal_available'               => 'ywsbs_load_paypal_standard_handler',
			'ywsbs_gateway_woocommerce_amazon_pay_available' => 'ywsbs_enable_amazon_pay',
			'ywsbs_gateway_woocommerce_payments_available' => 'ywsbs_enable_woocommerce_payments',
			'ywsbs_gateway_woocommerce_paypal_payments_available' => 'ywsbs_enable_woocommerce_paypal_payments_gateway',
			'ywsbs_gateway_woocommerce_eway_available'     => 'ywsbs_enable_woocommerce_eway_gateway',
			'ywsbs_gateway_redsys_available'               => 'ywsbs_enable_woocommerce_redsys_gateway',

			'ywsbs_show_complete_price_on_substotal_cart'  => 'ywsbs_force_detailed_price_on_cart_item',
			'ywsbs_change_price_in_cart_html'              => 'ywsbs_cart_item_price',
			'ywsbs_change_subtotal_price_in_cart_html'     => 'ywsbs_cart_item_subtotal',
		);

		/**
		 * Array of versions on each hook has been deprecated.
		 *
		 * @var array
		 */
		protected $deprecated_version = array();

		/**
		 * Hook into the new hook so we can handle deprecated hooks once fired.
		 *
		 * @param string $hook_name Hook name.
		 */
		public function hook_in( $hook_name ) {
			add_filter( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
		}

		/**
		 * If the old hook is in-use, trigger it.
		 *
		 * @param string $new_hook          New hook name.
		 * @param string $old_hook          Old hook name.
		 * @param array  $new_callback_args New callback args.
		 * @param mixed  $return_value      Returned value.
		 * @return mixed
		 */
		public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
			if ( has_filter( $old_hook ) ) {
				$this->display_notice( $old_hook, $new_hook );
				$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
			}

			return $return_value;
		}

		/**
		 * Fire off a legacy hook with it's args.
		 *
		 * @param string $old_hook          Old hook name.
		 * @param array  $new_callback_args New callback args.
		 * @return mixed
		 */
		protected function trigger_hook( $old_hook, $new_callback_args ) {
			return apply_filters_ref_array( $old_hook, $new_callback_args );
		}

		/**
		 * Get deprecated version.
		 *
		 * @param string $old_hook Old hook name.
		 * @return string
		 */
		protected function get_deprecated_version( $old_hook ) {
			return ! empty( $this->deprecated_version[ $old_hook ] ) ? $this->deprecated_version[ $old_hook ] : YITH_YWSBS_VERSION;
		}
	}
}
