<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Synchronization Object.
 *
 * @class   YWSBS_Subscription_Synchronization
 * @since   2.1.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Synchronization' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Synchronization extends YWSBS_Subscription_Synchronization_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Time of the day when the synchronization should be scheduled.
		 * Usually when the site has lower traffic.
		 *
		 * @var int
		 */
		protected $time_of_day = 0;

		/**
		 * YWSBS_Subscription_Synchronization_Admin instance.
		 *
		 * @var YWSBS_Subscription_Synchronization_Admin
		 */
		protected $admin;

		/**
		 * Constructor
		 *
		 * Initialize the YWSBS_Subscription_Synchronization Object
		 *
		 * @since 2.1.0
		 */
		private function __construct() {
			$this->init();

			add_filter( 'ywsbs_subscription_resume_payment_due_date', array( $this, 'get_next_payment_due_date_sync' ), 10, 2 );
			add_filter( 'ywsbs_product_price_additional_messages', array( $this, 'product_price_additional_messages' ), 10, 2 );
			add_filter( 'ywsbs_switch_info', array( $this, 'filter_subscription_switch_info' ), 10, 3 );
			add_filter( 'ywsbs_subscription_info_order_item_meta', array( $this, 'filter_subscription_info_order_item_meta' ), 10, 2 );
			add_filter( 'ywsbs_add_subscription_args', array( $this, 'filter_new_subscription_args' ), 10, 2 );
		}

		/**
		 * Set class variables
		 *
		 * @since  3.0.0
		 * @return void
		 */
		protected function init() {
			$this->time_of_day = apply_filters( 'ywsbs_synchronization_time_of_day', 2 );

			$this->cart = new YWSBS_Subscription_Synchronization_Cart();

			if ( YITH_WC_Subscription::is_request( 'admin' ) ) {
				include_once 'class-ywsbs-subscription-synchronization-admin.php';
				$this->admin = new YWSBS_Subscription_Synchronization_Admin();
			}
		}

		/**
		 * Check if the product can be synchronized.
		 *
		 * @param WC_Product $product Product.
		 * @param bool       $deep    Check if it can be synchronized today.
		 * @return bool
		 */
		public function is_synchronizable( $product, $deep = false ) {

			$sync_products = wp_cache_get( 'sync-products', 'yith-ywsbs' );
			if ( false === $sync_products || ! isset( $sync_products[ $product->get_id() ] ) ) {

				$sync_products = is_array( $sync_products ) ? $sync_products : array();
				$enabled_sync  = get_option( 'ywsbs_enable_sync', 'all_products' );

				switch ( $enabled_sync ) {
					case 'all_products':
						$result = true;

						if ( 'yes' === get_option( 'ywsbs_sync_exclude_category_and_product', 'yes' ) ) {

							$excluded_products = (array) get_option( 'ywsbs_sync_exclude_products_all_products', array() );
							$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore

							if ( $result ) {
								$excluded_categories = (array) get_option( 'ywsbs_sync_exclude_categories_all_products', array() );
								$result              = ! ywsbs_check_categories( $product, $excluded_categories );
							}
						}
						break;

					case 'virtual':
						$result = $product->is_virtual();

						if ( $result && 'yes' === get_option( 'ywsbs_sync_exclude_category_and_product_virtual', 'yes' ) ) {
							$excluded_products = (array) get_option( 'ywsbs_sync_exclude_products_virtual', array() );
							$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore

							if ( $result ) {
								$excluded_categories = (array) get_option( 'ywsbs_sync_exclude_categories_virtual', array() );
								$result              = ! ywsbs_check_categories( $product, $excluded_categories );
							}
						}
						break;

					case 'products':
						$included = (array) get_option( 'ywsbs_sync_include_product', array() );
						$result   = in_array( $product->get_id(), $included ); //phpcs:ignore
						break;

					case 'categories':
						$categories = (array) get_option( 'ywsbs_sync_include_categories', array() );
						$result     = ywsbs_check_categories( $product, $categories );

						if ( $result && 'yes' === get_option( 'ywsbs_sync_include_categories_enable_exclude_products', 'no' ) ) {
							$excluded_products = (array) get_option( 'ywsbs_sync_exclude_products_from_categories', array() );
							$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore
						}
						break;

					default:
						$result = false;
						break;
				}

				if ( $deep && $result ) {
					$next_payment_due_date = YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );
					$next_payment_due_date = $this->get_next_payment_due_date_sync( $next_payment_due_date, $product );
					$today                 = new DateTime();
					if ( $today->format( 'Y-m-d' ) === date( 'Y-m-d', $next_payment_due_date ) ) { //phpcs:ignore
						$result = false;
					}
				}

				$sync_products                       = is_array( $sync_products ) ? $sync_products : array();
				$sync_products[ $product->get_id() ] = $result;
				wp_cache_set( 'sync-products', $sync_products, 'yith-ywsbs' );
			}

			return apply_filters( 'ywsbs_is_synchronizable', $sync_products[ $product->get_id() ], $product, $deep );
		}

		/**
		 * Change the product price at first payment due to synchronization.
		 *
		 * @param float      $price                 Recurring price.
		 * @param WC_Product $product               Product.
		 * @param int        $next_payment_due_date Next payment due date.
		 */
		public function get_new_price_sync( $price, $product, $next_payment_due_date ) {

			$prorate_option = get_option( 'ywsbs_sync_first_payment', 'no' );
			$is_trial       = (int) ywsbs_get_product_trial( $product );

			if ( 'no' === $prorate_option || $is_trial > 0 ) {
				return 0;
			}

			if ( 'full' === $prorate_option ) {
				return $price;
			}

			$prorate_disabled = apply_filters( 'ywsbs_sync_prorate_disabled_days', get_option( 'ywsbs_sync_prorate_disabled', array( 'number_of_days' => 30 ) ), $product, $price );

			$daily_price  = ywsbs_get_daily_amount_of_a_product( $product );
			$diff_in_days = ceil( ( (int) $next_payment_due_date - time() ) / DAY_IN_SECONDS );

			$price = ( $diff_in_days < $prorate_disabled['number_of_days'] ) ? 0 : ( $diff_in_days * $daily_price );

			return $price;
		}

		/**
		 * Get next payment due date synchronized.
		 *
		 * @param int        $next_payment_due_date Next payment due date timestamp.
		 * @param WC_Product $product               Product.
		 * @return int
		 */
		public function get_next_payment_due_date_sync( $next_payment_due_date, $product ) {

			$period = $product->get_meta( '_ywsbs_price_time_option' );
			if ( ! in_array( $period, array( 'weeks', 'months', 'years' ), true ) ) {
				return $next_payment_due_date;
			}

			$sync_info_meta = $product->get_meta( '_ywsbs_synchronize_info' );
			$sync_info      = isset( $sync_info_meta[ $period ] ) ? $sync_info_meta[ $period ] : $this->get_default_sync_info( $period );

			if ( false !== $sync_info ) {
				$caller                = 'get_next_payment_date_for_' . $period;
				$calculation_date      = $this->get_start_calculation_date( $product, $next_payment_due_date );
				$next_payment_due_date = $this->$caller( $sync_info, $calculation_date );
			}

			return $next_payment_due_date;
		}

		/**
		 * Return the next payment due date calculated to synchronize weekly periods.
		 *
		 * @param string   $week_day Synchronization week day value.
		 * @param DateTime $calculation_date  Start calculation date.
		 * @return int
		 */
		protected function get_next_payment_date_for_weeks( $week_day, $calculation_date ) {
			$week_day = (int) $week_day;
			$today    = new DateTime();

			if ( ywsbs_get_week_day_string( $week_day ) === strtolower( $today->format( 'l' ) ) ) {
				return $calculation_date->getTimestamp();
			}

			$calculation_date->modify( 'next ' . ywsbs_get_week_day_string( $week_day ) );
			$calculation_date->setTime( $this->time_of_day, 0, 0 );

			return $calculation_date->getTimestamp();
		}

		/**
		 * Return the next payment due date calculated to synchronize monthly periods.
		 *
		 * @param string   $month_day Synchronization week day value. Can be a number from 1 to 28 or 'end'.
		 * @param DateTime $calculation_date Start calculation date.
		 * @return int
		 */
		public function get_next_payment_date_for_months( $month_day, $calculation_date ) {

			if ( 'end' === $month_day ) {
				$calculation_date->modify( 'last day of this month' );
			} else {
				$month_day = (int) $month_day;
				if ( $calculation_date->format( 'd' ) <= (int) $month_day ) {
					$diff = $month_day - $calculation_date->format( 'd' );
					$calculation_date->modify( '+ ' . $diff . ' days' );
				} else {
					$calculation_date->modify( 'first day of next month' );
					$calculation_date->add( new DateInterval( 'P' . ( $month_day - 1 ) . 'D' ) );
				}
			}
			$calculation_date->setTime( $this->time_of_day, 0, 0 );

			return $calculation_date->getTimestamp();
		}

		/**
		 * Return the next payment due date calculated to synchronize yearly periods.
		 *
		 * @param array    $years_month_day Synchronization years month and day value.
		 * @param DateTime $calculation_date Start calculation date.
		 *
		 * @return int
		 */
		public function get_next_payment_date_for_years( $years_month_day, $calculation_date ) {
			$day   = ( 'end' === $years_month_day['day'] ) ? 1 : $years_month_day['day'];
			$today = new DateTime();

			// Move the date at the end of the month.
			if ( 'end' === $years_month_day['day'] ) {
				$calculation_date->modify( 'last day of this month' );
				$day_of_month = $calculation_date->format( 'd' );
			} else {
				$day_of_month = $years_month_day['day'];
			}

			if ( $today->format( 'n' ) === $years_month_day['month'] && $today->format( 'd' ) === $day_of_month ) {
				$calculation_date = $today;
			} elseif ( $calculation_date->format( 'n' ) < $years_month_day['month'] || ( $calculation_date->format( 'n' ) === $years_month_day['month'] ) && ( $calculation_date->format( 'd' ) < $day_of_month ) ) {
				$calculation_date = $calculation_date->modify( $calculation_date->format( 'y' ) . '-' . $years_month_day['month'] . '-' . $day );
			} else {
				$calculation_date = $calculation_date->modify( ( (int) $calculation_date->format( 'y' ) + 1 ) . '-' . $years_month_day['month'] . '-' . $day );
			}

			$calculation_date->setTime( $this->time_of_day, 0, 0 );

			return $calculation_date->getTimestamp();
		}

		/**
		 * Set the default sync info by period.
		 *
		 * @param string $period Weeks, months or years.
		 * @return mixed
		 */
		public function get_default_sync_info( $period ) {
			$default_sync_info = array(
				'weeks'  => get_option( 'start_of_week' ),
				'months' => 1,
				'years'  => array(
					'month' => 1,
					'day'   => 1,
				),
			);

			return isset( $default_sync_info[ $period ] ) ? $default_sync_info[ $period ] : false;
		}

		/**
		 * Return a message for a product that can be synchronized.
		 *
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public function get_product_sync_message( $product ) {

			$message       = '';
			$first_payment = get_option( 'ywsbs_sync_first_payment', 'no' );
			$show_message  = get_option( 'ywsbs_sync_show_product_info', 'yes' );

			if ( 'yes' !== $show_message || ! in_array( $first_payment, array( 'no', 'prorate' ), true ) || ! $this->is_synchronizable( $product, true ) ) {
				return $message;
			}

			// Check the next payment due date.
			$next_payment_due_date = YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );
			$next_payment_due_date = $this->get_next_payment_due_date_sync( $next_payment_due_date, $product );
			$price                 = (float) $this->get_new_price_sync( $product->get_price(), $product, $next_payment_due_date );
			$fee                   = (float) ywsbs_get_product_fee( $product );

			if ( ! empty( $fee ) && $fee > 0 ) {
				$price += $fee;
			}

			if ( $product->get_price() !== $price ) {
				$new_price  = wc_get_price_to_display( $product, array( 'price' => $price ) );
				$next_price = wc_get_price_to_display( $product, array( 'price' => $product->get_price() ) );

				if ( empty( $price ) ) {
					$message = sprintf(
					/* translators: Prorate message on single product page. 1. Amount to pay now, 2. Recurring amount, 3. Next renewal date */
						_x( 'Nothing to pay now! Your next payment will be scheduled on %3$s', 'Prorate message on single product page. 1. Amount to pay now, 2. Recurring amount, 3. Next renewal date ', 'yith-woocommerce-subscription' ),
						wc_price( $new_price ),
						wc_price( $next_price ),
						date_i18n( wc_date_format(), $next_payment_due_date )
					);
				} else {

					$message = sprintf(
					/* translators: Prorate message on single product page. 1. Amount to pay now, 2. Recurring amount, 3. Next renewal date */
						_x( 'Pay %1$s now and %2$s on %3$s', 'Prorate message on single product page. 1. Amount to pay now, 2. Recurring amount, 3. Next renewal date ', 'yith-woocommerce-subscription' ),
						wc_price( $new_price ),
						wc_price( $next_price ),
						date_i18n( wc_date_format(), $next_payment_due_date )
					);
				}
			}

			return $message;
		}

		/**
		 * Return the now Date Time translated of the trial period.
		 *
		 * @param WC_Product $product Product.
		 * @param int        $next_payment_due_date Current next payment due date.
		 * @return DateTime
		 */
		protected function get_start_calculation_date( $product, $next_payment_due_date ) {
			$now = new DateTime();

			// if we are charging full amount set start calculation to next payment due date.
			if ( 'full' === get_option( 'ywsbs_sync_first_payment', 'no' ) && 'renew_date' === $product->get_meta('_ywsbs_synchronize_ref_date' ) ) {
				$next_payment_due_date = $next_payment_due_date ? $next_payment_due_date : YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );
				$now                   = $now->setTimestamp( (int) $next_payment_due_date );
			} else {
				// add trial period to translate the calculation.
				$period = ywsbs_get_trial_period( $product );
				if ( $period ) {
					$now->add( new DateInterval( $period ) );
				}
			}

			return apply_filters( 'ywsbs_sync_start_calculation_date', $now, $product );
		}

		/**
		 * Add sync message for product price html
		 *
		 * @since  3.0.0
		 * @param array      $additional_messages An array of product prices additional messages.
		 * @param WC_Product $product             The product.
		 * @return array
		 */
		public function product_price_additional_messages( $additional_messages, $product ) {
			$message = $this->get_product_sync_message( $product );
			if ( is_admin() || ! $message ) {
				return $additional_messages;
			}

			$show_message = false;
			$show_message = apply_filters_deprecated(
				'ywsbs_hide_in_loop_sync_info',
				array(
					$show_message,
					$product,
				),
				'3.0.0',
				'ywsbs_show_product_synchronization_info'
			);

			if ( is_single( $product->get_id() ) || apply_filters( 'ywsbs_show_product_synchronization_info', $show_message, $product ) ) {
				$additional_messages['sync-info'] = '<span class="ywsbs-synch-info">' . $message . '</span>';
			}

			return $additional_messages;
		}

		/**
		 * Filter subscription switch info if product has synchronization options
		 *
		 * @since  3.0.0
		 * @param array              $switch_info  The subscription switch info array.
		 * @param YWSBS_Subscription $subscription The subscription.
		 * @param WC_Product         $product      The subscription product.
		 * @return array
		 */
		public function filter_subscription_switch_info( $switch_info, $subscription, $product ) {

			if ( ! isset( $switch_info['gap_amount'] ) || $this->is_synchronizable( $product ) ) {
				return $switch_info;
			}

			$next_payment_due_date           = $this->get_next_payment_due_date_sync( $switch_info['next_payment_due_date'], $product );
			$today                           = new DateTime();
			$next_payment_due_date_date_time = new DateTime( '@' . $next_payment_due_date );

			if ( $today->format( 'Y-m-d' ) === $next_payment_due_date_date_time->format( 'Y-m-d' ) ) {
				$switch_info['gap_amount']            = $product->get_price();
				$switch_info['next_payment_due_date'] = YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );
			} else {
				$switch_info['gap_amount'] = $this->get_new_price_sync( $switch_info['gap_amount'], $product, $next_payment_due_date );
			}

			$switch_info['fee'] = ( $switch_info['gap_amount'] >= 0 ) ? $switch_info['gap_amount'] : 0;

			return $switch_info;
		}

		/**
		 * Filter order item meta for synchronized products. Add sync flag.
		 *
		 * @since  3.0.0
		 * @param array         $info       Current order item meta value.
		 * @param WC_Order_Item $order_item Current order item.
		 * @return array
		 */
		public function filter_subscription_info_order_item_meta( $info, $order_item ) {
			$product = $order_item->get_product();
			if ( $this->is_synchronizable( $product ) ) {
				$info['sync'] = $this->is_synchronizable( $product );
			}

			return $info;
		}

		/**
		 * Filter new subscription args to match sync options
		 *
		 * @since  3.0.0
		 * @param array              $args         An array of subscription arguments.
		 * @param YWSBS_Subscription $subscription The subscription instance.
		 * @return array
		 */
		public function filter_new_subscription_args( $args, $subscription ) {
			$product_id = ! empty( $args['variation_id'] ) ? $args['variation_id'] : $args['product_id'];
			$product    = wc_get_product( $product_id );

			if ( $product && $this->is_synchronizable( $product ) && 'checkout' !== $subscription->get_created_via() && isset( $args['payment_due_date'] ) ) {
				$args['payment_due_date'] = $this->get_next_payment_due_date_sync( $args['payment_due_date'], $product );
			}

			return $args;
		}
	}
}
