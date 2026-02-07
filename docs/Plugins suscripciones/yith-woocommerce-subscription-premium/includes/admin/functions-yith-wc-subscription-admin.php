<?php
/**
 * Implements helper functions for YITH WooCommerce Subscription
 *
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'ywsbs_is_admin_panel_page' ) ) {
	/**
	 * Check if the current page is one from the plugin admin panel.
	 *
	 * @param string|array $tab (Optional) A single tab type or an array of tabs to check. If array the check condition to exit is always OR. Default is empty string.
	 * @return boolean
	 */
	function ywsbs_is_admin_panel_page( $tab = '' ) {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore
		if ( YITH_WC_Subscription_Admin::get_instance()->get_panel_page_slug() !== $page ) {
			return false;
		}

		if ( ! empty( $tab ) ) {
			$panel_tabs  = array_keys( YITH_WC_Subscription_Admin::get_instance()->get_panel_tabs() );
			$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : array_shift( $panel_tabs ); //phpcs:ignore
			return is_array( $tab ) ? in_array( $current_tab, $tab, true ) : $tab === $current_tab;
		}

		return true;
	}
}

if ( ! function_exists( 'ywsbs_get_admin_panel_page_url' ) ) {
	/**
	 * Get the page url for the plugin admin panel.
	 *
	 * @param string $tab (Optional) The tab to get.
	 * @param string $subtab (Optional) The subtab to get.
	 * @return string
	 */
	function ywsbs_get_admin_panel_page_url( $tab = '', $subtab = '' ) {
		$args = array(
			'page' => YITH_WC_Subscription_Admin::get_instance()->get_panel_page_slug(),
		);

		if ( ! empty( $tab ) ) {
			$args['tab'] = $tab;
		}

		if ( ! empty( $subtab ) ) {
			$args['sub-tab'] = $subtab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}
}

if ( ! function_exists( 'ywsbs_check_valid_admin_page' ) ) {
	/**
	 * Return if the current screen is valid for a post_type, useful if you want ro add a meta-box, scripts inside the editor of a particular post type.
	 *
	 * @param string|array $post_type A single post type or an array of post types to check. If array the check condition to exit is always OR.
	 * @param boolean      $single (Optional) Check only for a single post type page, like post.php or post-new.php. Default true.
	 * @return boolean
	 */
	function ywsbs_check_valid_admin_page( $post_type, $single = false ) {
		global $current_screen;

		// If current screen is not set always return false.
		if ( ! $current_screen instanceof WP_Screen ) {
			return false;
		}

		if ( is_array( $post_type ) ) {
			foreach ( $post_type as $type ) {
				if ( ywsbs_check_valid_admin_page( $type, $single ) ) {
					return true;
				}
			}
		}

		$is_valid = $post_type === $current_screen->post_type && ( ! $single || 'post' === $current_screen->base );
		return apply_filters( 'ywsbs_check_valid_admin_page', $is_valid, $post_type, $single );
	}
}

if ( ! function_exists( 'yith_ywsbs_is_wc_admin_enabled' ) ) {
	/**
	 * Is WC Admin plugin enabled?
	 *
	 * @return bool
	 */
	function yith_ywsbs_is_wc_admin_enabled() {
		return class_exists( 'Automattic\WooCommerce\Admin\Loader' );
	}
}

if ( ! function_exists( 'ywsbs_get_order_fields_to_edit' ) ) {
	/**
	 * Return the list of fields that can be edited on a subscription.
	 *
	 * @param string $type Type of fields.
	 *
	 * @return array|void
	 */
	function ywsbs_get_order_fields_to_edit( $type ) {
		$fields = array();

		if ( 'billing' === $type ) {
			// APPLY_FILTER: ywsbs_admin_billing_fields : filtering the admin billing fields.
			$fields = apply_filters(
				'ywsbs_admin_billing_fields',
				array(
					'first_name' => array(
						'label' => esc_html__( 'First name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'last_name'  => array(
						'label' => esc_html__( 'Last name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'company'    => array(
						'label' => esc_html__( 'Company', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_1'  => array(
						'label' => esc_html__( 'Address line 1', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_2'  => array(
						'label' => esc_html__( 'Address line 2', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'city'       => array(
						'label' => esc_html__( 'City', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'postcode'   => array(
						'label' => esc_html__( 'Postcode / ZIP', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'country'    => array(
						'label'   => esc_html__( 'Country', 'yith-woocommerce-subscription' ),
						'show'    => false,
						'class'   => 'js_field-country select short',
						'type'    => 'select',
						'options' => array( '' => esc_html__( 'Select a country&hellip;', 'yith-woocommerce-subscription' ) ) + WC()->countries->get_allowed_countries(),
					),
					'state'      => array(
						'label' => esc_html__( 'State / County', 'yith-woocommerce-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					),
					'email'      => array(
						'label' => esc_html__( 'Email address', 'yith-woocommerce-subscription' ),
					),
					'phone'      => array(
						'label' => esc_html__( 'Phone', 'yith-woocommerce-subscription' ),
					),
				)
			);
		} elseif ( 'shipping' === $type ) {
			// APPLY_FILTER: ywsbs_admin_shipping_fields : filtering the admin shipping fields.
			$fields = apply_filters(
				'ywsbs_admin_shipping_fields',
				array(
					'first_name' => array(
						'label' => esc_html__( 'First name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'last_name'  => array(
						'label' => esc_html__( 'Last name', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'company'    => array(
						'label' => esc_html__( 'Company', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_1'  => array(
						'label' => esc_html__( 'Address line 1', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'address_2'  => array(
						'label' => esc_html__( 'Address line 2', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'city'       => array(
						'label' => esc_html__( 'City', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'postcode'   => array(
						'label' => esc_html__( 'Postcode / ZIP', 'yith-woocommerce-subscription' ),
						'show'  => false,
					),
					'country'    => array(
						'label'   => esc_html__( 'Country', 'yith-woocommerce-subscription' ),
						'show'    => false,
						'type'    => 'select',
						'class'   => 'js_field-country select short',
						'options' => array( '' => esc_html__( 'Select a country&hellip;', 'yith-woocommerce-subscription' ) ) + WC()->countries->get_shipping_countries(),
					),
					'state'      => array(
						'label' => esc_html__( 'State / County', 'yith-woocommerce-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					),
				)
			);
		}

		return $fields;
	}
}
