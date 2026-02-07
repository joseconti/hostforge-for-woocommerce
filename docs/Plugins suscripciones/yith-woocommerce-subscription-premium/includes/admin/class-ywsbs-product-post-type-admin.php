<?php
/**
 * YWSBS_Product_Post_Type_Admin Class.
 *
 * Manage the subscription options inside the product editor.
 *
 * @class   YWSBS_Product_Post_Type_Admin
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Product_Post_Type_Admin' ) ) {

	/**
	 * Class YWSBS_Product_Post_Type_Admin
	 */
	class YWSBS_Product_Post_Type_Admin {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize actions and filters to be used
		 *
		 * @since 2.0.0
		 */
		private function __construct() {
			add_action( 'admin_init', array( $this, 'init' ), 10 );
		}

		/**
		 * Init function.
		 */
		public function init() {
			$product_id = isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0; //phpcs:ignore
			$post       = isset( $_GET['post'] ) ? $_GET['post'] : $product_id; //phpcs:ignore

			if ( apply_filters( 'ywsbs_enable_subscription_on_product', true, $post ) ) {
				// Product editor.
				add_filter( 'product_type_options', array( $this, 'add_type_options' ) );
				add_action( 'woocommerce_variation_options', array( $this, 'add_type_variation_options' ), 10, 3 );

				add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_subscription_settings_tab' ), 10, 1 );
				add_filter( 'woocommerce_product_data_panels', array( $this, 'add_subscription_settings_panel' ) );
				add_action( 'woocommerce_product_options_shipping', array( $this, 'add_custom_fields_for_shipping_products' ) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_fields_for_single_products' ), 10 );

				// Custom fields for variation.
				add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_custom_fields_for_variation_products' ), 14, 3 );
				add_action( 'woocommerce_save_product_variation', array( $this, 'save_custom_fields_for_variation_products' ), 10, 2 );
			}
		}

		/**
		 * Add a product type option in single product editor
		 *
		 * @param array $types List of types.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function add_type_options( $types ) {
			$types['ywsbs_subscription'] = array(
				'id'            => '_ywsbs_subscription',
				'class'         => 'checkbox_ywsbs_subscription',
				'wrapper_class' => 'show_if_simple',
				'label'         => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
				'description'   => esc_html__( 'Create a subscription for this product', 'yith-woocommerce-subscription' ),
				'default'       => 'no',
			);

			return $types;
		}

		/**
		 * Add a product type option in variable product editor
		 *
		 * @access public
		 *
		 * @param int     $loop Current loop index.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation Variation.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function add_type_variation_options( $loop, $variation_data, $variation ) {
			$variation = wc_get_product( $variation->ID );
			if ( ! $variation instanceof WC_Product ) {
				return;
			}

			$is_subscription = $variation->get_meta( '_ywsbs_subscription' );
			$checked         = checked( $is_subscription, 'yes', false );
			echo '<label><input type="checkbox" class="checkbox checkbox_ywsbs_subscription" name="variable_ywsbs_subscription[' . $loop . ']" ' . $checked . ' /> ' . esc_html__( 'Subscription', 'yith-woocommerce-subscription' ) . ' <a class="tips" data-tip="' . esc_html__( 'Sell this variable product as a subscription product.', 'yith-woocommerce-subscription' ) . '" href="#">[?]</a></label>'; // phpcs:ignore
		}

		/**
		 * Add subscription settings data tab to product edit
		 *
		 * @since 3.0.0
		 * @param array $tabs An array of product tabs.
		 * @return array
		 */
		public function add_subscription_settings_tab( $tabs ) {

			$subscription_tabs = apply_filters(
				'ywsbs_subscription_product_settings_tabs',
				array(
					'subscription-settings' => array(
						'label'    => _x( 'Subscription Options', 'Product options tab title', 'yith-woocommerce-subscription' ),
						'target'   => 'ywsbs_subscription_settings',
						'class'    => array( 'show_if_simple' ),
						'priority' => 11,
					),
				)
			);

			// If tabs are more than 1, activate sub-menu.
			if ( count( $subscription_tabs ) > 1 ) {

				// Base priority to use to make sure tabs are in the correct place.
				$priority = 11.1;

				// Duplicate main tab into sub tab.
				$subscription_tabs['subscription-settings-sub'] = array_merge(
					$subscription_tabs['subscription-settings'],
					array(
						'label'    => _x( 'Settings', 'Product options tab title', 'yith-woocommerce-subscription' ),
						'priority' => $priority,
					)
				);

				// Add classes.
				$subscription_tabs['subscription-settings']['class'][]     = 'ywsbs-has-sub-tab';
				$subscription_tabs['subscription-settings-sub']['class'][] = 'ywsbs-sub-tab';

				// Map other registered tabs.
				$subscription_tabs = array_map(
					function ( $tab ) use ( $priority ) {
						if ( 'ywsbs_subscription_settings' === $tab['target'] ) {
							return $tab;
						}

						$priority += 0.1;

						$tab['class']    = $tab['class'] ?? array();
						$tab['class'][]  = 'ywsbs-sub-tab';
						$tab['priority'] = $priority;

						return $tab;
					},
					$subscription_tabs
				);
			}

			$tabs = array_merge(
				$tabs,
				$subscription_tabs
			);

			return $tabs;
		}

		/**
		 * Add product data panel for subscription box option
		 *
		 * @since 3.0.0
		 */
		public function add_subscription_settings_panel() {
			global $product_object;

			$enable_pause = $product_object->get_meta( '_ywsbs_enable_pause' );
			$enable_trial = $product_object->get_meta( '_ywsbs_enable_trial' );
			$enable_fee   = $product_object->get_meta( '_ywsbs_enable_fee' );
			$enable_limit = $product_object->get_meta( '_ywsbs_enable_limit' );

			$max_pause          = $product_object->get_meta( '_ywsbs_max_pause' );
			$max_pause_duration = $product_object->get_meta( '_ywsbs_max_pause_duration' );
			$can_be_cancelled   = $product_object->get_meta( '_ywsbs_can_be_cancelled' );
			$max_length         = $product_object->get_meta( '_ywsbs_max_length' );
			$enable_max_length  = $product_object->get_meta( '_ywsbs_enable_max_length' );
			$_ywsbs_limit       = $product_object->get_meta( '_ywsbs_limit' );

			$_ywsbs_override_pause_settings        = $product_object->get_meta( '_ywsbs_override_pause_settings' );
			$_ywsbs_override_cancellation_settings = $product_object->get_meta( '_ywsbs_override_cancellation_settings' );

			// porting from minor version.

			$_ywsbs_limit = empty( $_ywsbs_limit ) ? 'no' : $_ywsbs_limit;

			if ( empty( $enable_limit ) ) {
				$enable_limit = 'no' === $_ywsbs_limit ? 'no' : 'yes';
				$_ywsbs_limit = 'no' === $_ywsbs_limit ? 'one-active' : $_ywsbs_limit;
			}

			if ( empty( $_ywsbs_override_pause_settings ) ) {
				$_ywsbs_override_pause_settings = empty( $enable_pause ) ? 'no' : 'yes';
			}

			if ( empty( $enable_pause ) ) {
				$enable_pause = empty( $max_pause ) ? 'no' : ( empty( $max_pause_duration ) ? 'yes' : 'limited' );
			}

			if ( empty( $enable_trial ) ) {
				$enable_trial = empty( $product_object->get_meta( '_ywsbs_trial_per' ) ) ? 'no' : 'yes';
			}

			if ( empty( $enable_fee ) ) {
				$enable_fee = empty( $product_object->get_meta( '_ywsbs_fee' ) ) ? 'no' : 'yes';
			}

			if ( empty( $enable_max_length ) ) {
				$enable_max_length = ! empty( $max_length ) ? 'yes' : 'no';
			}

			$args = array(
				'product'                               => $product_object,
				'_ywsbs_price_is_per'                   => $product_object->get_meta( '_ywsbs_price_is_per' ),
				'_ywsbs_price_time_option'              => $product_object->get_meta( '_ywsbs_price_time_option' ),
				'_ywsbs_enable_trial'                   => $enable_trial,
				'_ywsbs_trial_per'                      => $product_object->get_meta( '_ywsbs_trial_per' ),
				'_ywsbs_trial_time_option'              => $product_object->get_meta( '_ywsbs_trial_time_option' ),
				'_ywsbs_enable_fee'                     => $enable_fee,
				'_ywsbs_enable_limit'                   => $enable_limit,
				'_ywsbs_fee'                            => $product_object->get_meta( '_ywsbs_fee' ),
				'_ywsbs_enable_max_length'              => $enable_max_length,
				'_ywsbs_max_length'                     => $max_length,
				'_ywsbs_max_pause'                      => $max_pause,
				'_ywsbs_max_pause_duration'             => $max_pause_duration,
				'_ywsbs_can_be_cancelled'               => $can_be_cancelled,
				'_ywsbs_cancellation_limit'             => $product_object->get_meta( '_ywsbs_cancellation_limit' ),
				'_ywsbs_enable_pause'                   => $enable_pause,
				'_ywsbs_override_pause_settings'        => $_ywsbs_override_pause_settings,
				'_ywsbs_override_cancellation_settings' => $_ywsbs_override_cancellation_settings,
				'_ywsbs_limit'                          => $_ywsbs_limit,
				'max_lengths'                           => ywsbs_get_max_length_period(),
			);

			wc_get_template( 'product/single-product-options.php', $args, '', YITH_YWSBS_VIEWS_PATH . '/' );
		}

		/**
		 * Add custom fields for single product
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function add_custom_fields_for_single_products() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Product_Post_Type_Admin()->add_subscription_settings_panel' );
			$this->add_subscription_settings_panel();
		}

		/**
		 * Add the field One time shipping inside the shipping tab.
		 *
		 * @since 1.4
		 */
		public function add_custom_fields_for_shipping_products() {
			global $thepostid;
			$product                  = wc_get_product( $thepostid );
			$_ywsbs_one_time_shipping = $product->get_meta( '_ywsbs_one_time_shipping' );

			woocommerce_wp_checkbox(
				array(
					'id'            => '_ywsbs_one_time_shipping',
					'value'         => $_ywsbs_one_time_shipping,
					'wrapper_class' => 'show_if_simple show_if_variable',
					'label'         => esc_html__( 'One time shipping', 'yith-woocommerce-subscription' ),
					'description'   => esc_html__( 'Check it if you want recurring payments without shipping.', 'yith-woocommerce-subscription' ),
				)
			);
		}

		/**
		 * Save custom fields for single product
		 *
		 * @param int $post_id Product id.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function save_custom_fields_for_single_products( $post_id ) {

			$posted = $_POST; // phpcs:ignore

			if ( isset( $posted['product-type'] ) && 'variable' === $posted['product-type'] ) {
				$this->reset_custom_field_for_product( $post_id );
				return;
			}

			$product              = wc_get_product( $post_id );
			$manual_fields_saving = array( '_ywsbs_subscription', '_ywsbs_one_time_shipping', '_ywsbs_override_pause_settings', '_ywsbs_override_cancellation_settings', '_ywsbs_enable_trial', '_ywsbs_enable_fee', '_ywsbs_enable_limit' );
			$custom_fields        = array_diff( $this->get_custom_fields_list(), $manual_fields_saving );

			if ( isset( $posted['_ywsbs_price_time_option'] ) && isset( $posted['_ywsbs_max_length'] ) ) {
				$max_length                  = ywsbs_validate_max_length( $posted['_ywsbs_max_length'], $posted['_ywsbs_price_time_option'] );
				$posted['_ywsbs_max_length'] = $max_length;
			}

			if ( isset( $posted['_ywsbs_fee'] ) ) {
				$posted['_ywsbs_fee'] = wc_format_decimal( $posted['_ywsbs_fee'] );
			}

			foreach ( $manual_fields_saving as $manual_field ) {
				$value = isset( $posted[ $manual_field ] ) ? 'yes' : 'no';
				$product->update_meta_data( $manual_field, $value );
			}

			foreach ( $custom_fields as $meta ) {
				if ( isset( $posted[ $meta ] ) ) {
					$product->update_meta_data( $meta, $posted[ $meta ] );
				}
			}

			do_action( 'ywsbs_before_save_custom_product_meta', $product, $posted );

			$product->save();
		}

		/**
		 * Add custom fields for variation products
		 *
		 * @param int     $loop Current loop index.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation Variation.
		 *
		 * @since 1.0.0
		 */
		public function add_custom_fields_for_variation_products( $loop, $variation_data, $variation ) {

			$variation      = wc_get_product( $variation->ID );
			$parent         = wc_get_product( $variation->get_parent_id() );
			$num_variations = $parent->get_available_variations();

			$_ywsbs_switchable          = $variation->get_meta( '_ywsbs_switchable' );
			$_ywsbs_switchable_priority = $variation->get_meta( '_ywsbs_switchable_priority' );
			$_ywsbs_switchable_priority = ( empty( $_ywsbs_switchable_priority ) && 'yes' === $_ywsbs_switchable ) ? $loop : $_ywsbs_switchable_priority;

			$enable_pause       = $variation->get_meta( '_ywsbs_enable_pause' );
			$enable_trial       = $variation->get_meta( '_ywsbs_enable_trial' );
			$enable_fee         = $variation->get_meta( '_ywsbs_enable_fee' );
			$enable_limit       = $variation->get_meta( '_ywsbs_enable_limit' );
			$max_pause          = $variation->get_meta( '_ywsbs_max_pause' );
			$max_pause_duration = $variation->get_meta( '_ywsbs_max_pause_duration' );
			$can_be_cancelled   = $variation->get_meta( '_ywsbs_can_be_cancelled' );
			$max_length         = $variation->get_meta( '_ywsbs_max_length' );
			$enable_max_length  = $variation->get_meta( '_ywsbs_enable_max_length', 'no' );
			$_ywsbs_limit       = $variation->get_meta( '_ywsbs_limit' );

			$_ywsbs_override_pause_settings        = $variation->get_meta( '_ywsbs_override_pause_settings' );
			$_ywsbs_override_cancellation_settings = $variation->get_meta( '_ywsbs_override_cancellation_settings' );

			$_ywsbs_prorate_length            = $variation->get_meta( '_ywsbs_prorate_length' );
			$_ywsbs_prorate_recurring_payment = $variation->get_meta( '_ywsbs_prorate_recurring_payment' );
			$_ywsbs_limit                     = empty( $_ywsbs_limit ) ? 'no' : $_ywsbs_limit;

			if ( empty( $enable_limit ) ) {
				$enable_limit = 'no' === $_ywsbs_limit ? 'no' : 'yes';
				$_ywsbs_limit = 'no' === $_ywsbs_limit ? 'one-active' : $_ywsbs_limit;
			}

			// porting from minor version.
			if ( empty( $_ywsbs_override_pause_settings ) ) {
				$_ywsbs_override_pause_settings = empty( $enable_pause ) ? 'no' : 'yes';
			}

			if ( empty( $enable_pause ) ) {
				$enable_pause = empty( $max_pause ) ? 'no' : ( empty( $max_pause_duration ) ? 'yes' : 'limited' );
			}

			if ( empty( $enable_trial ) ) {
				$enable_trial = empty( $variation->get_meta( '_ywsbs_trial_per' ) ) ? 'no' : 'yes';
			}

			if ( empty( $enable_fee ) ) {
				$enable_fee = empty( $variation->get_meta( '_ywsbs_fee' ) ) ? 'no' : 'yes';
			}

			if ( empty( $enable_limit ) ) {
				$enable_limit = empty( $variation->get_meta( '_ywsbs_fee' ) ) ? 'no' : 'yes';
			}

			if ( empty( $enable_max_length ) ) {
				$enable_max_length = ! empty( $max_length ) ? 'yes' : 'no';
			}

			$_ywsbs_prorate_recurring_payment = empty( $_ywsbs_prorate_recurring_payment ) ? $_ywsbs_prorate_length : $_ywsbs_prorate_recurring_payment;
			$_ywsbs_prorate_recurring_payment = empty( $_ywsbs_prorate_recurring_payment ) ? 'no' : $_ywsbs_prorate_recurring_payment;

			$_ywsbs_fee = $variation->get_meta( '_ywsbs_fee' );
			$_ywsbs_fee = empty( $_ywsbs_fee ) ? 'no' : $variation->get_meta( '_ywsbs_fee' );

			$_ywsbs_switchable = empty( $_ywsbs_switchable ) ? 'no' : $_ywsbs_switchable;

			$args = array(
				'variation'                             => $variation,
				'_ywsbs_price_is_per'                   => $variation->get_meta( '_ywsbs_price_is_per' ),
				'_ywsbs_price_time_option'              => $variation->get_meta( '_ywsbs_price_time_option' ),
				'_ywsbs_trial_per'                      => $variation->get_meta( '_ywsbs_trial_per' ),
				'_ywsbs_trial_time_option'              => $variation->get_meta( '_ywsbs_trial_time_option' ),
				'_ywsbs_fee'                            => $variation->get_meta( '_ywsbs_fee' ),
				'_ywsbs_enable_max_length'              => $enable_max_length,
				'_ywsbs_max_length'                     => $max_length,
				'_ywsbs_enable_pause'                   => $enable_pause,
				'_ywsbs_enable_limit'                   => $enable_limit,
				'_ywsbs_enable_trial'                   => $enable_trial,
				'_ywsbs_enable_fee'                     => $enable_fee,
				'_ywsbs_max_pause'                      => $max_pause,
				'_ywsbs_max_pause_duration'             => $max_pause_duration,
				'_ywsbs_can_be_cancelled'               => $can_be_cancelled,
				'_ywsbs_cancellation_limit'             => $variation->get_meta( '_ywsbs_cancellation_limit' ),
				'_ywsbs_switchable'                     => $_ywsbs_switchable,
				'_ywsbs_prorate_recurring_payment'      => $_ywsbs_prorate_recurring_payment,
				'_ywsbs_prorate_fee'                    => $variation->get_meta( '_ywsbs_prorate_fee' ),
				'_ywsbs_switchable_priority'            => (int) $_ywsbs_switchable_priority,
				'_ywsbs_limit'                          => $_ywsbs_limit,
				'_ywsbs_override_pause_settings'        => $_ywsbs_override_pause_settings,
				'_ywsbs_override_cancellation_settings' => $_ywsbs_override_cancellation_settings,
				'max_lengths'                           => ywsbs_get_max_length_period(),
				'num_variations'                        => count( $num_variations ),
				'loop'                                  => $loop,
			);

			wc_get_template( 'product/variation-product-options.php', $args, '', YITH_YWSBS_VIEWS_PATH . '/' );
		}

		/**
		 * Save custom fields for variation products
		 *
		 * @param int $variation_id Variation id.
		 * @param int $index Variation array index.
		 * @return bool|void
		 * @since  1.0.0
		 */
		public function save_custom_fields_for_variation_products( $variation_id, $index ) {

			$posted = $_POST; // phpcs:ignore

			// reset custom field for the parent product.
			if ( isset( $posted['product_id'] ) ) {
				$this->reset_custom_field_for_product( $posted['product_id'] );
			}

			$variation = wc_get_product( $variation_id );

			$manual_fields_saving = array(
				'_ywsbs_subscription',
				'_ywsbs_override_pause_settings',
				'_ywsbs_override_cancellation_settings',
				'_ywsbs_enable_trial',
				'_ywsbs_enable_fee',
				'_ywsbs_enable_limit',
			);

			if ( isset( $posted['variable_ywsbs_fee'][ $index ] ) ) {
				$posted['variable_ywsbs_fee'][ $index ] = wc_format_decimal( $posted['variable_ywsbs_fee'][ $index ] );
			}

			$custom_fields = array_diff( $this->get_custom_fields_list(), $manual_fields_saving );

			if ( isset( $posted['variable_ywsbs_max_length'][ $index ] ) && isset( $posted['variable_ywsbs_price_time_option'][ $index ] ) ) {
				$max_length                = ywsbs_validate_max_length( $posted['variable_ywsbs_max_length'][ $index ], $posted['variable_ywsbs_price_time_option'][ $index ] );
				$args['_ywsbs_max_length'] = $max_length;
				$variation->update_meta_data( '_ywsbs_max_length', $max_length );
			}

			foreach ( $manual_fields_saving as $manual_field ) {
				$value = isset( $posted[ 'variable' . $manual_field ][ $index ] ) ? 'yes' : 'no';
				$variation->update_meta_data( $manual_field, $value );
			}

			foreach ( $custom_fields as $meta ) {
				if ( isset( $posted[ 'variable' . $meta ][ $index ] ) ) {
					$variation->update_meta_data( $meta, $posted[ 'variable' . $meta ][ $index ] );
				}
			}

			do_action( 'ywsbs_before_save_custom_product_variation_meta', $variation, $index, $posted );

			$variation->save();
		}

		/**
		 * Reset custom field
		 *
		 * @param int $product_id Product id.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		private function reset_custom_field_for_product( $product_id ) {

			$product       = wc_get_product( $product_id );
			$custom_fields = $this->get_custom_fields_list();

			foreach ( $custom_fields as $cf ) {
				$product->delete_meta_data( $cf );
			}

			isset( $_POST['_ywsbs_one_time_shipping'] ) && $product->update_meta_data( '_ywsbs_one_time_shipping', 'yes' ); //phpcs:ignore

			do_action( 'ywsbs_before_reset_custom_product_meta', $product );

			$product->save();
		}

		/**
		 * Return the list of custom fields relative to subscription.
		 *
		 * @return mixed|void
		 * @since  1.4
		 */
		private function get_custom_fields_list() {
			$custom_fields = array(
				'_ywsbs_subscription',
				'_ywsbs_price_is_per',
				'_ywsbs_price_time_option',
				'_ywsbs_max_length',
				'_ywsbs_fee',
				'_ywsbs_trial_per',
				'_ywsbs_trial_time_option',
				'_ywsbs_switchable',
				'_ywsbs_prorate_recurring_payment',
				'_ywsbs_prorate_fee',
				'_ywsbs_switchable_priority',
				'_ywsbs_max_pause',
				'_ywsbs_max_pause_duration',
				'_ywsbs_one_time_shipping',
				'_ywsbs_enable_max_length',
				'_ywsbs_can_be_cancelled',
				'_ywsbs_enable_pause',
				'_ywsbs_enable_trial',
				'_ywsbs_enable_fee',
				'_ywsbs_enable_limit',
				'_ywsbs_limit',
				'_ywsbs_override_pause_settings',
				'_ywsbs_override_cancellation_settings',
				'_ywsbs_cancellation_limit',
			);

			return apply_filters( 'ywsbs_custom_fields_list', $custom_fields );
		}
	}
}


if ( ! function_exists( 'YWSBS_Product_Post_Type_Admin' ) ) {
	/**
	 * Return the instance of class
	 *
	 * @return YWSBS_Product_Post_Type_Admin
	 */
	function YWSBS_Product_Post_Type_Admin() { //phpcs:ignore
		return YWSBS_Product_Post_Type_Admin::get_instance();
	}
}
