<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle subscription switcher.
 * 
 * @class SUMOSubs_Switcher
 */
class SUMOSubs_Switcher {

	protected static $can_switch                      = false;
	protected static $can_prorate_subscription_length = false;
	protected static $can_charge_signup_fee           = false;
	protected static $allow_user_to_switch_between    = array();
	protected static $allow_switcher_between          = array();
	protected static $payment_type                    = 'prorate';
	protected static $prorate_recurring_payment       = array();
	protected static $switch_based_on                 = 'price';
	protected static $prorate_subscription_length_for = '';
	protected static $charge_signup_fee_by            = '';
	protected static $switching_between               = '';
	protected static $switching_parent_product_id     = 0;
	protected static $valid_switcher_statuses         = array( 'Active' );
	protected static $old_subscription;
	protected static $switched_subscription_product;
	protected static $prorated_signup_fee;
	protected static $prorated_recurring_length;
	protected static $prorated_recurring_payment;
	protected static $prorated_next_payment_date;
	protected static $switched_method                 = '';
	protected static $redirect_to_cart                = false;

	const CANNOT_SWITCH_TO_SAME_SUBSCRIPTION       = 100;
	const CANNOT_SWITCH_AT_THIS_TIME               = 101;
	const CHOOSE_NEW_SUBSCRIPTION                  = 102;
	const CART_CONTAINS_INVALID_SWITCHES           = 103;
	const CANNOT_ALLOW_TO_SWITCH_THIS_SUBSCRIPTION = 104;

	/**
	 * Init SUMOSubs_Switcher.
	 */
	public static function init() {
		self::$allow_user_to_switch_between    = SUMOSubs_Admin_Options::get_option( 'allow_user_to_switch' );
		self::$can_prorate_subscription_length = 'no' !== SUMOSubs_Admin_Options::get_option( 'when_switch_prorate_subscription_length' );
		self::$can_charge_signup_fee           = 'no' !== SUMOSubs_Admin_Options::get_option( 'when_switch_charge_signup_fee' );
		self::$allow_switcher_between          = SUMOSubs_Admin_Options::get_option( 'allow_switch_between' );
		self::$prorate_recurring_payment       = SUMOSubs_Admin_Options::get_option( 'when_switch_prorate_recurring_payment_for' );
		self::$prorate_subscription_length_for = SUMOSubs_Admin_Options::get_option( 'when_switch_prorate_subscription_length' );
		self::$charge_signup_fee_by            = SUMOSubs_Admin_Options::get_option( 'when_switch_charge_signup_fee' );
		self::$switch_based_on                 = SUMOSubs_Admin_Options::get_option( 'switch_based_on' );
		self::$payment_type                    = SUMOSubs_Admin_Options::get_option( 'payment_mode_when_switch' );

		add_filter( 'woocommerce_is_purchasable', __CLASS__ . '::prevent_non_subscription_from_purchase', 999, 2 );
		add_action( 'wp_loaded', __CLASS__ . '::before_switching_process', 10 );
		add_action( 'woocommerce_before_add_to_cart_button', __CLASS__ . '::before_switching_process', 10 );
		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validate_before_switching_process', 999, 4 );
		add_filter( 'woocommerce_add_cart_item', __CLASS__ . '::start_switching_process', 10, 2 );
		add_action( 'wp_loaded', __CLASS__ . '::redirect_to_cart', 999 );
		add_filter( 'sumosubscriptions_get_line_total', __CLASS__ . '::set_line_total', 10, 5 );
		add_action( 'woocommerce_before_calculate_totals', __CLASS__ . '::refresh_cart', 999 );
		add_action( 'wp_loaded', __CLASS__ . '::cart_contains_switches', 99 );
		add_filter( 'sumosubscriptions_alter_subscription_plan_meta', __CLASS__ . '::process_switch', 20, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', __CLASS__ . '::get_switched_method_to_display', 10, 3 );
		add_filter( 'sumosubscriptions_create_subscription', __CLASS__ . '::switch_subscription', 10, 6 );
		add_filter( 'sumosubscriptions_get_next_payment_date', __CLASS__ . '::set_next_payment_date', 10, 5 );

		include_once 'class-sumosubs-variation-switcher.php';
	}

	public static function is_switcher_page( $method = 'get' ) {
		$method = 'get' === $method ? $_GET : $_REQUEST;

		if (
				isset( $method[ '_sumosubsnonce' ], $method[ 'action' ], $method[ 'subscription_id' ], $method[ 'item' ] ) &&
				'switch-subscription' === $method[ 'action' ] &&
				wp_verify_nonce( $method[ '_sumosubsnonce' ], $method[ 'subscription_id' ] )
		) {
			return true;
		}

		return false;
	}

	public static function can_switch( $subscription_id ) {
		if (
				'yes' === SUMOSubs_Admin_Options::get_option( 'allow_switcher' ) &&
				! empty( self::$allow_user_to_switch_between ) &&
				! empty( self::$allow_switcher_between ) &&
				in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), ( array ) self::$valid_switcher_statuses )
		) {
			self::$old_subscription = sumo_get_subscription( $subscription_id );

			if ( self::$old_subscription && ! self::$old_subscription->is_synced() ) {
				$subscribed_product                = sumo_get_subscription_product( self::$old_subscription->get_subscribed_product() );
				self::$switching_parent_product_id = self::get_parent_product_id( $subscribed_product );

				if ( self::$switching_parent_product_id ) {
					//may be switch in Variable products
					if ( 'variation' === $subscribed_product->get_type() && in_array( 'variations', self::$allow_switcher_between ) ) {
						self::$can_switch        = true;
						self::$switching_between = 'variable';
						//may be switch in Grouped products
					} else if ( in_array( 'grouped', self::$allow_switcher_between ) ) {
						self::$can_switch        = true;
						self::$switching_between = 'grouped';
					}
				}
			}
		}

		/**
		 * Can switchable?
		 * 
		 * @since 1.0
		 */
		return ( bool ) apply_filters( 'sumosubscriptions_can_upgrade_or_downgrade', self::$can_switch, $subscription_id );
	}

	public static function is_subscription_switched( $switched_subscription_product, $customer_id = 0 ) {
		$switched_data = self::get_switched_data( $switched_subscription_product, $customer_id );
		return ! empty( $switched_data[ 'old_subscription' ] ) && ! empty( $switched_data[ 'switched_subscription_product' ] );
	}

	public static function is_virtual_product_switched() {
		return self::$switched_subscription_product && ( self::$switched_subscription_product->is_virtual() || self::$switched_subscription_product->is_downloadable() );
	}

	public static function get_switch_button_text() {
		return SUMOSubs_Admin_Options::get_option( 'switch_button_text' );
	}

	public static function get_switched_data( $subscription_product = null, $customer_id = 0 ) {
		$switched_data = array();

		if ( is_numeric( $subscription_product ) ) {
			$requested_product_id = $subscription_product;
		} else if ( is_a( $subscription_product, 'SUMO_Subscription_Product' ) ) {
			$requested_product_id = $subscription_product->get_id();
		} else {
			$requested_product_id = 0;
		}

		if ( is_numeric( $customer_id ) && $customer_id ) {
			$switched_data = get_user_meta( $customer_id, 'sumosubs_subscription_switched_data', true );

			if ( ! empty( $switched_data[ $requested_product_id ] ) ) {
				$switched_data = $switched_data[ $requested_product_id ];
			}
		} else if ( ! is_null( WC()->cart ) ) {
			$cart_contents = empty( WC()->cart->cart_contents ) ? WC()->session->get( 'cart', array() ) : WC()->cart->cart_contents;

			foreach ( $cart_contents as $cart_item ) {
				if (
						! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] ) &&
						! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] )
				) {
					$item_id                       = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];
					$old_subscription              = maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] );
					$switched_subscription_product = maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] );

					if ( ! is_a( $old_subscription, 'SUMOSubs_Subscription' ) || ! is_a( $switched_subscription_product, 'SUMOSubs_Product' ) ) {
						continue;
					}

					if ( $requested_product_id == $switched_subscription_product->get_id() ) {
						$switched_data = $cart_item[ 'sumosubscriptions' ][ 'switched_data' ];
						break;
					} else {
						$switched_data[ $item_id ] = $cart_item[ 'sumosubscriptions' ][ 'switched_data' ];
					}
				}
			}
		}

		return is_array( $switched_data ) ? $switched_data : array();
	}

	public static function get_parent_product_id( $subscribed_product ) {
		if ( ! $subscribed_product ) {
			return 0;
		}

		$parent_id = 0;
		if ( sumosubs_is_subscription_product_type( $subscribed_product->get_type() ) ) {
			switch ( $subscribed_product->get_type() ) {
				case 'variation':
					$parent_id          = $subscribed_product->get_parent_id();
					break;
				default:
					global $wpdb;
					$_wpdb              = &$wpdb;
					$parent_product_ids = $_wpdb->get_col( $_wpdb->prepare( "SELECT post_id FROM {$_wpdb->prefix}postmeta WHERE meta_key = '_children' AND meta_value LIKE '%%i:%d;%%'", $subscribed_product->get_id() ) );
					$parent_id          = isset( $parent_product_ids[ 0 ] ) ? absint( $parent_product_ids[ 0 ] ) : 0;
					break;
			}
		}

		return $parent_id;
	}

	public static function get_switch_url() {
		return esc_url_raw( add_query_arg( array(
			'subscription_id' => self::$old_subscription->get_id(),
			'item'            => self::$old_subscription->get_subscribed_product(),
			'action'          => 'switch-subscription',
			'_sumosubsnonce'  => wp_create_nonce( self::$old_subscription->get_id() ),
						), get_permalink( self::$switching_parent_product_id ) ) );
	}

	public static function add_notice( $code ) {
		switch ( $code ) {
			case 100:
				return wc_add_notice( __( 'You cannot switch to same subscription product!!', 'sumosubscriptions' ), 'error' );
			case 101:
				return wc_add_notice( __( 'Sorry!! You cannot switch to any subscription product at this time!!', 'sumosubscriptions' ), 'error' );
			case 102:
				return wc_add_notice( __( 'Select the subscription product to switch', 'sumosubscriptions' ), 'success' );
			case 103:
				return wc_add_notice( __( 'An invalid subscription product has been removed from your cart.', 'sumosubscriptions' ), 'error' );
			case 104:
				/* translators: 1: switched method */
				return wc_add_notice( sprintf( __( 'Sorry!! You are not allowed to %s this subscription', 'sumosubscriptions' ), ucwords( str_replace( '-', '', substr( self::$switched_method, 0, -1 ) ) ) ), 'error' );
		}
	}

	public static function maybe_prorate_signup_fee() {
		if ( self::$can_charge_signup_fee ) {
			$new_signup_fee = self::$switched_subscription_product->get_signup( 'forced' ) ? floatval( self::$switched_subscription_product->get_signup( 'fee' ) ) : 0;
			$old_signup_fee = self::$old_subscription->get_signup( 'forced' ) ? floatval( self::$old_subscription->get_signup( 'fee' ) ) : 0;

			if ( 'gap-fee' === self::$charge_signup_fee_by ) {
				self::$prorated_signup_fee = max( $new_signup_fee, $old_signup_fee ) - min( $new_signup_fee, $old_signup_fee );
			} else if ( 'full-fee' === self::$charge_signup_fee_by ) {
				self::$prorated_signup_fee = $new_signup_fee;
			}

			if ( ! is_numeric( self::$prorated_signup_fee ) || self::$prorated_signup_fee <= 0 ) {
				self::$prorated_signup_fee = null;
			}
		}
	}

	public static function maybe_prorate_recurring_payment( $subscription, $qty = 1 ) {
		$last_payment_time = sumo_get_subscription_timestamp( get_post_meta( $subscription->get_id(), 'sumo_get_last_payment_date', true ) );
		$next_payment_time = sumo_get_subscription_timestamp( get_post_meta( $subscription->get_id(), 'sumo_get_next_payment_date', true ) );
		$end_time          = sumo_get_subscription_timestamp( get_post_meta( $subscription->get_id(), 'sumo_get_saved_due_date', true ) );
		$current_time      = sumo_get_subscription_timestamp();

		$old_recurring_total = floatval( self::$old_subscription->get_recurring_amount() );
		$new_recurring_total = floatval( self::$switched_subscription_product->get_recurring_amount() ) * $qty;

		$next_payment_time       = $next_payment_time > 0 ? $next_payment_time : $end_time;
		$days_since_last_payment = floor( ( $current_time - $last_payment_time ) / 86400 );
		$days_until_next_payment = ceil( ( $next_payment_time - $current_time ) / 86400 );

		$days_in_old_cycle = $days_until_next_payment + $days_since_last_payment;

		if (
				self::$old_subscription->get_duration_period_length() === self::$switched_subscription_product->get_duration_period_length() &&
				self::$old_subscription->get_duration_period() === self::$switched_subscription_product->get_duration_period()
		) {
			$days_in_new_cycle = $days_in_old_cycle;
		} else {
			$days_in_new_cycle = sumo_get_subscription_cycle( self::$switched_subscription_product->get_duration_period_length() . ' ' . self::$switched_subscription_product->get_duration_period(), true );
		}

		$old_plan_price_per_day = $days_in_old_cycle > 0 ? $old_recurring_total / $days_in_old_cycle : $old_recurring_total;
		$new_plan_price_per_day = $days_in_new_cycle > 0 ? $new_recurring_total / $days_in_new_cycle : $new_recurring_total;

		self::$switched_method = 'cross-graded';
		if ( 'duration' === self::$switch_based_on ) {
			if ( $days_in_new_cycle > $days_in_old_cycle ) {
				self::$switched_method = 'up-graded';
			} else if ( $days_in_new_cycle < $days_in_old_cycle ) {
				self::$switched_method = 'down-graded';
			}
		} elseif ( $old_plan_price_per_day < $new_plan_price_per_day ) {
				self::$switched_method = 'up-graded';
		} else if ( $old_plan_price_per_day > $new_plan_price_per_day && $new_plan_price_per_day >= 0 ) {
			self::$switched_method = 'down-graded';
		}

		self::$prorated_next_payment_date = sumo_get_subscription_date( $next_payment_time );
		self::$prorated_recurring_payment = 0;

		switch ( self::$switched_method ) {
			case 'up-graded':
				if ( 'full-payment' === self::$payment_type ) {
					self::$prorated_recurring_payment = $new_recurring_total; //To charge full fee do not prorate
					self::$prorated_next_payment_date = sumo_get_subscription_date( $next_payment_time + ( $days_in_new_cycle * 86400 ) );
					break;
				}

				if (
						! empty( self::$prorate_recurring_payment ) && (
						in_array( 'all-upgrades', self::$prorate_recurring_payment ) ||
						( self::is_virtual_product_switched() && in_array( 'virtual-upgrades', self::$prorate_recurring_payment ) ) )
				) {
					if ( $days_in_old_cycle > $days_in_new_cycle ) {
						//Shorter Billing Period
						$pre_paid_days  = 0;
						$new_total_paid = 0;

						while ( $new_total_paid < $old_recurring_total ) {
							$pre_paid_days++;
							$new_total_paid = $pre_paid_days * $new_plan_price_per_day;
						}

						if ( $days_since_last_payment < $pre_paid_days ) {
							self::$prorated_next_payment_date = sumo_get_subscription_date( $last_payment_time + ( $pre_paid_days * 86400 ) );
						} else {
							self::$prorated_next_payment_date = null; // To set next due date from switched subscription do not prorate
							self::$prorated_recurring_payment = null; //To charge full fee do not prorate
						}
					} else {
						//Same or Longer Billing Period
						self::$prorated_recurring_payment = $days_until_next_payment * ( max( $new_plan_price_per_day, $old_plan_price_per_day ) - min( $new_plan_price_per_day, $old_plan_price_per_day ) );

						if ( self::$prorated_recurring_payment > 0 ) {
							self::$prorated_recurring_payment /= $qty;
						}

						if ( self::$prorated_recurring_payment < 0 ) {
							self::$prorated_recurring_payment = 0;
						}
					}
				}
				break;
			case 'down-graded':
				if ( 'full-payment' === self::$payment_type ) {
					self::$prorated_recurring_payment = $new_recurring_total; //To charge full fee do not prorate
					self::$prorated_next_payment_date = sumo_get_subscription_date( $next_payment_time + ( $days_in_new_cycle * 86400 ) );
					break;
				}

				if (
						! empty( self::$prorate_recurring_payment ) && (
						in_array( 'all-downgrades', self::$prorate_recurring_payment ) ||
						( self::is_virtual_product_switched() && in_array( 'virtual-downgrades', self::$prorate_recurring_payment ) ) )
				) {
					$pre_paid_amt           = $old_plan_price_per_day * $days_until_next_payment;
					$new_amt_to_pay_per_day = $new_plan_price_per_day;

					for ( $days_to_add = 0; $new_amt_to_pay_per_day <= $pre_paid_amt; $days_to_add++ ) {
						$new_amt_to_pay_per_day = $days_to_add * $new_plan_price_per_day;
					}

					$days_to_add -= $days_until_next_payment;

					self::$prorated_next_payment_date = sumo_get_subscription_date( $next_payment_time + ( $days_to_add * 86400 ) );
				}
				break;
			case 'cross-graded':
				if ( 'full-payment' === self::$payment_type ) {
					self::$prorated_recurring_payment = $new_recurring_total; //To charge full fee do not prorate
					self::$prorated_next_payment_date = sumo_get_subscription_date( $next_payment_time + ( $days_in_new_cycle * 86400 ) ); // To set next due date from switched subscription do not prorate
				}
				break;
		}
	}

	public static function maybe_prorate_recurring_length( $subscription ) {
		$completed_payments_count        = sumosubs_get_renewed_count( $subscription->get_id() );
		$new_recurring_length            = self::$switched_subscription_product->get_installments();
		self::$prorated_recurring_length = self::$switched_subscription_product->get_installments();

		if ( self::$can_prorate_subscription_length ) {
			if (
					'all-subscriptions' === self::$prorate_subscription_length_for ||
					( self::is_virtual_product_switched() && 'virtual-subscriptions' === self::$prorate_subscription_length_for )
			) {
				if ( $new_recurring_length > 0 && $completed_payments_count !== $new_recurring_length ) {
					self::$prorated_recurring_length = absint( $completed_payments_count - $new_recurring_length );
				}
			}
		}
	}

	public static function prevent_non_subscription_from_purchase( $is_purchasable, $product ) {
		if ( self::is_switcher_page() ) {
			$subscribed_product = sumo_get_subscription_product( $product );

			if ( ! $subscribed_product ) {
				return false;
			}
		}

		return $is_purchasable;
	}

	public static function before_switching_process() {
		if ( ! self::is_switcher_page() ) {
			return;
		}

		if ( doing_action( 'wp_loaded' ) ) {
			self::add_notice( self::CHOOSE_NEW_SUBSCRIPTION );
		} else {
			$get = $_GET;
			echo '<input type="hidden" name="subscription_id" value="' . absint( wp_unslash( $get[ 'subscription_id' ] ) ) . '" />'
			. '<input type="hidden" name="action" value="' . esc_attr( wc_clean( wp_unslash( $get[ 'action' ] ) ) ) . '" />'
			. '<input type="hidden" name="item" value="' . esc_attr( wc_clean( wp_unslash( $get[ 'item' ] ) ) ) . '" />'
			. '<input type="hidden" name="_sumosubsnonce" value="' . esc_attr( wc_clean( wp_unslash( $get[ '_sumosubsnonce' ] ) ) ) . '" />';
		}
	}

	public static function validate_before_switching_process( $bool, $product_id, $quantity, $variation_id = 0 ) {
		delete_transient( 'sumo_subscription_switching_into_cart' );

		if ( ! self::is_switcher_page( 'post' ) ) {
			return $bool;
		}

		$requested = $_REQUEST;
		if ( ! self::can_switch( $requested[ 'subscription_id' ] ) ) {
			self::add_notice( self::CANNOT_SWITCH_AT_THIS_TIME );
			wp_safe_redirect( get_permalink( self::$switching_parent_product_id ) );
			return false;
		}

		$newly_switched_item = 0;
		$old_subscribed_item = absint( wp_unslash( $requested[ 'item' ] ) );

		switch ( self::$switching_between ) {
			case 'variable':
				$newly_switched_item = absint( $variation_id );
				break;
			case 'grouped':
				$newly_switched_item = absint( $product_id );
				break;
		}

		self::$switched_subscription_product = sumo_get_subscription_product( $newly_switched_item );
		if ( self::$switched_subscription_product ) {
			if ( $old_subscribed_item === $newly_switched_item ) {
				self::add_notice( self::CANNOT_SWITCH_TO_SAME_SUBSCRIPTION );
				wp_safe_redirect( self::get_switch_url() );
				exit;
			} elseif ( is_array( WC()->cart->cart_contents ) ) {
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
					if ( ! isset( $cart_item[ 'product_id' ] ) ) {
						continue;
					}

					if ( self::get_parent_product_id( $cart_item[ 'data' ] ) === self::$switching_parent_product_id ) {
						WC()->cart->remove_cart_item( $cart_item_key );
					}
				}
			}

			//calculate prorate
			self::maybe_prorate_signup_fee();
			self::maybe_prorate_recurring_length( self::$old_subscription );
			self::maybe_prorate_recurring_payment( self::$old_subscription, absint( $quantity ) );
			set_transient( 'sumo_subscription_switching_into_cart', true, 20 ); //May be useful when the current product is switching

			if ( ! in_array( substr( self::$switched_method, 0, -1 ), self::$allow_user_to_switch_between ) ) {
				self::add_notice( self::CANNOT_ALLOW_TO_SWITCH_THIS_SUBSCRIPTION );
				wp_safe_redirect( self::get_switch_url() );
				exit;
			}
		}

		return $bool;
	}

	public static function start_switching_process( $cart_item, $cart_item_key ) {
		if ( self::$can_switch && self::$switched_subscription_product ) {
			$cart_item[ 'sumosubscriptions' ][ 'switched_data' ] = array(
				'switched_between'              => self::$switching_between,
				'switched_method'               => self::$switched_method,
				'old_subscription'              => serialize( self::$old_subscription ),
				'switched_subscription_product' => serialize( self::$switched_subscription_product ),
				'qty'                           => absint( $cart_item[ 'quantity' ] ),
				'prorated_signup_fee'           => self::$prorated_signup_fee,
				'prorated_recurring_payment'    => self::$prorated_recurring_payment,
				'prorated_next_payment_date'    => self::$prorated_next_payment_date,
				'prorated_recurring_length'     => self::$prorated_recurring_length,
				'signup_amount'                 => null,
			);

			if ( is_numeric( self::$prorated_signup_fee ) && is_numeric( self::$prorated_recurring_payment ) ) {
				$cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_signup_fee + self::$prorated_recurring_payment;
			} elseif ( is_numeric( self::$prorated_signup_fee ) ) {
				$cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_signup_fee;
			} elseif ( is_numeric( self::$prorated_recurring_payment ) ) {
				$cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_recurring_payment;
			}

			self::$redirect_to_cart = true;
		}

		return $cart_item;
	}

	public static function redirect_to_cart() {
		if ( self::$redirect_to_cart ) {
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
	}

	public static function set_line_total( $line_total, $subscription, $default_line_total, $is_trial_enabled, $subscription_obj_type ) {
		if ( 'product' === $subscription_obj_type && self::is_subscription_switched( $subscription->get_id() ) ) {
			$switched_data = self::get_switched_data( $subscription->get_id() );

			if ( isset( $switched_data[ 'signup_amount' ] ) && is_numeric( $switched_data[ 'signup_amount' ] ) ) {
				return wc_format_decimal( $switched_data[ 'signup_amount' ], wc_get_price_decimals() );
			}
		}

		return $line_total;
	}

	public static function refresh_cart( $cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
					! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] ) &&
					! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] )
			) {

				self::$old_subscription              = sumo_get_subscription( maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] ) );
				self::$switched_subscription_product = sumo_get_subscription_product( maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] ) );

				if ( self::$old_subscription && self::$switched_subscription_product ) {
					self::maybe_prorate_signup_fee();
					self::maybe_prorate_recurring_length( self::$old_subscription );
					self::maybe_prorate_recurring_payment( self::$old_subscription, $cart_item[ 'quantity' ] );

					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_method' ]               = self::$switched_method;
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ]              = serialize( self::$old_subscription );
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] = serialize( self::$switched_subscription_product );
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'qty' ]                           = absint( $cart_item[ 'quantity' ] );
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'prorated_signup_fee' ]           = self::$prorated_signup_fee;
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'prorated_recurring_payment' ]    = self::$prorated_recurring_payment;
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'prorated_next_payment_date' ]    = self::$prorated_next_payment_date;
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'prorated_recurring_length' ]     = self::$prorated_recurring_length;
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ]                 = null;

					if ( is_numeric( self::$prorated_signup_fee ) && is_numeric( self::$prorated_recurring_payment ) ) {
						WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_signup_fee + self::$prorated_recurring_payment;
					} elseif ( is_numeric( self::$prorated_signup_fee ) ) {
						WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_signup_fee;
					} elseif ( is_numeric( self::$prorated_recurring_payment ) ) {
						WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ][ 'signup_amount' ] = self::$prorated_recurring_payment;
					}
				} else {
					WC()->cart->cart_contents[ $cart_item_key ][ 'sumosubscriptions' ][ 'switched_data' ] = array();
				}
			}
		}
	}

	public static function cart_contains_switches( $throw_err = true ) {
		$contains_switches = false;
		$invalid_switches  = false;

		if ( ! is_null( WC()->cart ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ] ) ) {
					if (
							! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] ) &&
							! empty( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] )
					) {
						$old_subscription              = maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'old_subscription' ] );
						$switched_subscription_product = maybe_unserialize( $cart_item[ 'sumosubscriptions' ][ 'switched_data' ][ 'switched_subscription_product' ] );

						if ( is_a( $old_subscription, 'SUMOSubs_Subscription' ) && is_a( $switched_subscription_product, 'SUMOSubs_Product' ) ) {
							$contains_switches = true;
						} elseif ( $throw_err ) {
								$invalid_switches = WC()->cart->remove_cart_item( $cart_item_key );
						}
					} elseif ( $throw_err ) {
							$invalid_switches = WC()->cart->remove_cart_item( $cart_item_key );
					}
				}
			}
		}

		if ( $throw_err && $invalid_switches ) {
			self::add_notice( self::CART_CONTAINS_INVALID_SWITCHES );
		}

		return $contains_switches;
	}

	public static function process_switch( $subscribed_plan_meta, $subscription_id, $product_id ) {
		if ( $subscription_id ) {
			$switched_data = get_post_meta( $subscription_id, 'sumo_switched_data', true );

			if ( ! empty( $switched_data[ 'old_subscription' ] ) && ! empty( $switched_data[ 'switched_subscription_product' ] ) ) {
				$subscribed_plan_meta[ 'signusumoee_selection' ]  = '2';
				$subscribed_plan_meta[ 'trial_selection' ]        = '2';
				$subscribed_plan_meta[ 'synchronization_status' ] = '2';
				$subscribed_plan_meta[ 'product_qty' ]            = $switched_data[ 'qty' ] ? absint( $switched_data[ 'qty' ] ) : 1; //BKWD CMPT
			}
		} else {
			$process_switch = false;
			if ( self::is_switcher_page() ) {
				$switched       = false;
				$process_switch = true;
			} else if ( ! is_product() && ! is_shop() ) {
				$switched       = self::is_subscription_switched( $product_id );
				$process_switch = ! empty( $switched );
			}

			if ( $process_switch ) {
				$subscribed_plan_meta[ 'trial_selection' ]        = '2';
				$subscribed_plan_meta[ 'synchronization_status' ] = '2';

				if ( ! empty( $switched ) ) {
					$switched_data = self::get_switched_data( $product_id );

					if ( ! empty( $switched_data[ 'qty' ] ) && absint( $switched_data[ 'qty' ] ) ) {
						$subscribed_plan_meta[ 'product_qty' ] = $switched_data[ 'qty' ] ? absint( $switched_data[ 'qty' ] ) : 1;
					}
				}
			}
		}

		return $subscribed_plan_meta;
	}

	public static function get_switched_method_to_display( $product_subtotal, $cart_item, $cart_item_key ) {
		if ( is_object( $cart_item ) || ! isset( $cart_item[ 'product_id' ] ) ) {
			$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		}

		$product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];

		if ( self::is_subscription_switched( $product_id ) ) {
			$switched_data    = self::get_switched_data( $product_id );
			/* translators: 1: switched method */
			$product_subtotal .= sprintf( __( '<p><small style="color:#777;">(<strong>%s</strong>)</small></p>', 'sumosubscriptions' ), ucwords( str_replace( '-', '', $switched_data[ 'switched_method' ] ) ) );
		}

		return $product_subtotal;
	}

	public static function update_user_meta( $customer_id ) {
		delete_user_meta( $customer_id, 'sumosubs_subscription_switched_data' );

		$switched_data = self::get_switched_data();
		if ( $switched_data ) {
			add_user_meta( $customer_id, 'sumosubs_subscription_switched_data', $switched_data );
		}
	}

	public static function switch_subscription( $new_subscription_id, $order, $product_id, $item, $change_status_to, $subscription_type ) {
		if ( ! self::is_subscription_switched( $product_id, $order->get_customer_id() ) ) {
			return $new_subscription_id;
		}

		$switched_data                 = self::get_switched_data( $product_id, $order->get_customer_id() );
		$old_subscription              = maybe_unserialize( $switched_data[ 'old_subscription' ] );
		$switched_subscription_product = maybe_unserialize( $switched_data[ 'switched_subscription_product' ] );

		$old_parent_order_id      = get_post_meta( $old_subscription->get_id(), 'sumo_get_parent_order_id', true );
		$old_subscription_product = wc_get_product( $old_subscription->get_subscribed_product() );
		$old_parent_order         = wc_get_order( $old_parent_order_id );

		if ( $old_parent_order ) {
			foreach ( sumosubs_get_meta_keys_by( 'subscription_parent_order' ) as $meta_key ) {
				$old_parent_order->delete_meta_data( $meta_key );
			}
			$old_parent_order->save_meta_data();
		}

		if ( sumosubs_unpaid_renewal_order_exists( $old_subscription->get_id() ) ) {
			wc_get_order( get_post_meta( $old_subscription->get_id(), 'sumo_get_renewal_id', true ) )->update_status( 'cancelled' );
		}

		foreach ( sumosubs_get_meta_keys_by( 'subscription' ) as $meta_key ) {
			if ( apply_filters( 'sumosubscriptions_delete_meta_before_swiching', true, $meta_key ) ) {
				delete_post_meta( $old_subscription->get_id(), "{$meta_key}" );
			}
		}

		$cron_event = new SUMOSubs_Cron_Event( $old_subscription->get_id() );
		$cron_event->unset_events();

		$subscription_meta = array_merge( $switched_subscription_product->item_meta, array(
			'product_qty' => $item[ 'qty' ],
				) );

		add_post_meta( $old_subscription->get_id(), 'sumo_get_status', 'Pending' );
		add_post_meta( $old_subscription->get_id(), 'sumo_subscription_product_details', $subscription_meta );
		add_post_meta( $old_subscription->get_id(), 'sumo_product_name', $switched_subscription_product->product->get_name() );
		add_post_meta( $old_subscription->get_id(), 'sumo_get_parent_order_id', $order->get_id() );
		add_post_meta( $old_subscription->get_id(), 'sumo_buyer_email', $order->get_billing_email() );
		add_post_meta( $old_subscription->get_id(), 'sumo_get_user_id', $order->get_customer_id() );
		add_post_meta( $old_subscription->get_id(), 'sumo_subscr_plan', $switched_subscription_product->get_duration_period_length() . ' ' . $switched_subscription_product->get_duration_period() );
		add_post_meta( $old_subscription->get_id(), 'sumo_get_renewals_count', 0 );
		add_post_meta( $old_subscription->get_id(), 'sumo_subscription_version', SUMO_SUBSCRIPTIONS_VERSION );
		add_post_meta( $old_subscription->get_id(), 'sumo_get_subscription_type', $subscription_type ); //set Subscription type
		add_post_meta( $old_subscription->get_id(), 'sumo_is_switched', 'yes' );
		add_post_meta( $old_subscription->get_id(), 'sumo_switched_data', $switched_data );
		add_post_meta( $old_subscription->get_id(), 'sumo_get_subscriber_data', get_user_by( 'id', $order->get_customer_id() ) ); //set Customer data
		add_post_meta( $old_subscription->get_id(), 'sumo_previous_parent_order', $old_parent_order_id );

		$order->update_meta_data( 'sumosubs_is_switched', 'yes' );
		$order->update_meta_data( 'sumo_is_subscription_order', 'yes' );
		$order->save_meta_data();

		SUMOSubs_Order::save_order_item_data( $old_subscription->get_id(), $item );
		SUMOSubs_Order::save_global_admin_settings( $old_subscription->get_id(), $product_id, $order->get_customer_id() );
		SUMOSubs_Order::save_subscription_in_parent_order( $order, $old_subscription->get_id(), array( $product_id => $old_subscription->get_id() ) );

		/* translators: 1: switched method 2: from product title 3: to product title */
		sumo_add_subscription_note( sprintf( __( 'Subscriber %1$s from %2$s to %3$s.', 'sumosubscriptions' ), ucwords( str_replace( '-', '', $switched_data[ 'switched_method' ] ) ), $old_subscription_product->get_formatted_name(), $switched_subscription_product->product->get_formatted_name() ), $old_subscription->get_id(), sumo_note_status( 'Pending' ), __( 'Subscriber switched subscription', 'sumosubscriptions' ) );

		/**
		 * After subscription is switched.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_subscription_is_switched', $order, $old_subscription, $switched_subscription_product );
		return $old_subscription->get_id();
	}

	public static function set_next_payment_date( $next_payment_time, $subscription_id, $product_id, $is_trial_on_process, $args ) {
		if ( $args[ 'initial_payment' ] ) {
			if ( $subscription_id > 0 ) {
				$switched_data = get_post_meta( $subscription_id, 'sumo_switched_data', true );
			} elseif ( self::is_subscription_switched( $product_id ) ) {
					$switched_data = self::get_switched_data( $product_id );
			}

			if ( ! empty( $switched_data[ 'prorated_next_payment_date' ] ) ) {
				if ( $args[ 'get_as_timestamp' ] ) {
					$next_payment_time = sumo_get_subscription_timestamp( $switched_data[ 'prorated_next_payment_date' ] );
				} else {
					$next_payment_time = $switched_data[ 'prorated_next_payment_date' ];
				}
			}
		}

		return $next_payment_time;
	}
}

SUMOSubs_Switcher::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Upgrade_Or_Downgrade extends SUMOSubs_Switcher {
	
}
