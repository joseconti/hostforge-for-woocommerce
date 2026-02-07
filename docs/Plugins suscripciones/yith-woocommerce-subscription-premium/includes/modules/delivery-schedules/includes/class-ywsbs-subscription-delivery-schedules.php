<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Delivery_Schedules Object.
 *
 * @class   YWSBS_Subscription_Delivery_Schedules
 * @since   2.2.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ) {

	/**
	 * Class YWSBS_Subscription_Delivery_Schedules
	 */
	class YWSBS_Subscription_Delivery_Schedules extends YWSBS_Subscription_Delivery_Schedules_Legacy {
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
		 * @var YWSBS_Subscription_Delivery_Schedules_Admin
		 */
		public $admin;

		/**
		 * Constructor
		 * Initialize the YWSBS_Subscription_Delivery_Schedules Object
		 *
		 * @since 3.0.0
		 */
		private function __construct() {
			$this->init();

			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			add_action( 'ywsbs_customer_subscription_delivery_schedules_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );

			add_action( 'ywsbs_subscription_started', array( $this, 'set_delivery_schedules' ), 10, 1 );
			add_action( 'ywsbs_subscription_updated', array( $this, 'set_delivery_schedules' ), 10, 1 );
			add_action( 'ywsbs_subscription_status_cancelled', array( $this, 'update_delivery_for_cancelled_subscription' ), 10, 1 );
			add_action( 'ywsbs_subscription_status_resume', array( $this, 'update_delivery_for_resumed_subscription' ), 10, 1 );

			add_action( 'ywsbs_scheduled_data_updated', array( $this, 'update_delivery_for_change_payment_due_date' ), 10, 4 );
			add_action( 'ywsbs_delivery_schedules_status_change', array( $this, 'set_status_to_delivery_schedules' ) );

			add_action( 'deleted_post', array( $this, 'maybe_delete_delivery_status' ) );

			add_filter( 'ywsbs_product_price_additional_messages', array( $this, 'product_price_additional_messages' ), 10, 2 );
			add_filter( 'ywsbs_add_subscription_args', array( $this, 'filter_new_subscription_args' ), 10, 2 );
			add_filter( 'ywsbs_subscription_data', array( $this, 'filter_get_subscription_data' ), 10, 2 );
			add_action( 'ywsbs_after_view_subscription', array( $this, 'view_subscription_delivery_schedules' ), 10, 1 );
		}

		/**
		 * Set class variables
		 *
		 * @since  3.0.0
		 * @return void
		 */
		protected function init() {
			$this->time_of_day = apply_filters( 'ywsbs_delivery_schedules_time_of_day', 4 );

			YWSBS_Subscription_Delivery_Schedules_DB::init();

			if ( YITH_WC_Subscription::is_request( 'admin' ) ) {
				$this->admin = new YWSBS_Subscription_Delivery_Schedules_Admin();
			}
		}

		/**
		 * Filters woocommerce available mails
		 *
		 * @since  1.0.0
		 * @param array $emails WooCommerce email list.
		 *
		 * @return array
		 */
		public function add_woocommerce_emails( $emails ) {
			$emails['YITH_WC_Customer_Subscription_Delivery_Schedules'] = include YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/emails/class-yith-wc-customer-subscription-delivery-schedules.php';
			return $emails;
		}

		/**
		 * Return the delivery settings to store inside the subscription meta.
		 *
		 * @param WC_Product $product Subscription product.
		 *
		 * @return array
		 */
		public function get_delivery_settings( $product ) {
			$settings = $product->get_meta( '_ywsbs_delivery_synch' );
			$override = $product->get_meta( '_ywsbs_override_delivery_schedule' );

			return ( 'yes' === $override && $settings ) ? $settings : $this->get_general_delivery_options();
		}

		/**
		 * Check if the product has a delivery scheduled.
		 *
		 * @param WC_Product $product Subscription product.
		 * @return bool
		 */
		public function has_delivery_scheduled( $product ) {
			$enabled_delivery = get_option( 'ywsbs_enable_delivery', 'all_products' );
			$result           = false;
			switch ( $enabled_delivery ) {
				case 'all_products':
					$exclude_products_category = get_option( 'ywsbs_delivery_exclude_category_and_product', 'no' );
					if ( 'yes' === $exclude_products_category ) {
						$excluded_products = (array) get_option( 'ywsbs_delivery_exclude_products_all_products', array() );
						$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore

						if ( $result ) {
							$excluded_categories = (array) get_option( 'ywsbs_delivery_exclude_categories_all_products', array() );
							$result              = ! ywsbs_check_categories( $product, $excluded_categories );
						}
					} else {
						$result = true;
					}
					break;
				case 'physical':
					$result = ! $product->is_virtual();

					if ( $result && 'yes' === get_option( 'ywsbs_delivery_exclude_category_and_product_non_virtual', 'yes' ) ) {
						$excluded_products = (array) get_option( 'ywsbs_delivery_exclude_products_physical', array() );
						$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore

						if ( $result ) {
							$excluded_categories = (array) get_option( 'ywsbs_delivery_exclude_categories_physical', array() );
							$result              = ! ywsbs_check_categories( $product, $excluded_categories );
						}
					}

					break;
				case 'products':
					$included = (array) get_option( 'ywsbs_delivery_include_product', array() );
					$result   = in_array( $product->get_id(), $included ); //phpcs:ignore
					break;
				case 'categories':
					$categories       = (array) get_option( 'ywsbs_delivery_include_categories', array() );
					$result           = ywsbs_check_categories( $product, $categories );
					$exclude_products = get_option( 'ywsbs_delivery_include_categories_enable_exclude_products', 'no' );
					if ( $result && 'yes' === $exclude_products ) {
						$excluded_products = (array) get_option( 'ywsbs_delivery_exclude_products_from_categories', array() );
						$result            = ! in_array( $product->get_id(), $excluded_products ); //phpcs:ignore
					}
			}

			return apply_filters( 'ywsbs_has_delivery_scheduled', $result, $product );
		}


		/**
		 * Return general delivery options.
		 *
		 * @return array
		 */
		public function get_general_delivery_options() {
			$delivery_default_schedule  = get_option(
				'ywsbs_delivery_default_schedule',
				array(
					'delivery_gap'    => 1,
					'delivery_period' => 'months',
				)
			);
			$delivery_default_schedule2 = get_option(
				'ywsbs_delivery_default_schedule2',
				array(
					'sych_weeks' => 1,
					'months'     => 'months',
				)
			);

			$general_delivery_option = array(
				'delivery_gap'    => $delivery_default_schedule['delivery_gap'],
				'delivery_period' => $delivery_default_schedule['delivery_period'],
				'on'              => get_option( 'ywsbs_delivery_sync_delivery_schedules', 'no' ),
				'sych_weeks'      => $delivery_default_schedule2['sych_weeks'],
				'months'          => $delivery_default_schedule2['months'],
				'years_month'     => isset( $delivery_default_schedule2['years_month'] ) ? $delivery_default_schedule2['years_month'] : '',
				'years_day'       => isset( $delivery_default_schedule2['years_day'] ) ? $delivery_default_schedule2['years_day'] : '',
			);

			return $general_delivery_option;
		}

		/**
		 * Set the schedule inside the table.
		 *
		 * @param int $subscription_id Subscription id.
		 */
		public function set_delivery_schedules( $subscription_id ) {
			$subscription      = ywsbs_get_subscription( $subscription_id );
			$delivery_settings = $subscription->get( 'delivery_schedules' );

			if ( empty( $delivery_settings ) ) {
				return;
			}

			$previous_payment_date = $subscription->get( 'previous_payment_due_date' );
			$start_date            = new DateTime();
			if ( ! empty( $previous_payment_date ) && $previous_payment_date > time() ) {
				$start_date->setTimestamp( $previous_payment_date );
			}

			$schedules = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_subscription( $subscription->get_id() );
			if ( $schedules ) {
				$last_date  = end( $schedules );
				$start_date = new DateTime( $last_date->scheduled_date );
				if ( isset( $delivery_settings['on'] ) && 'yes' === $delivery_settings['on'] ) {
					$start_date->modify( '+1 day' );
				} else {
					$start_date = $this->calculate_first_delivery_date( $delivery_settings, $start_date, 'date' );
				}
			}

			if ( isset( $delivery_settings['on'] ) && 'yes' === $delivery_settings['on'] ) {
				$first_delivery_date = $this->calculate_first_delivery_date( $delivery_settings, $start_date );
			} elseif ( $start_date instanceof DateTime ) {
				$first_delivery_date = $start_date->getTimestamp();
			} else {
				$first_delivery_date = $start_date;
			}

			$last_delivery_date = ywsbs_get_timestamp_from_option( $first_delivery_date, $subscription->get( 'price_is_per' ), $subscription->get( 'price_time_option' ) );

			$this->add_multiple_delivery_schedules( $delivery_settings, $first_delivery_date, $last_delivery_date, $subscription_id );
		}

		/**
		 * Calculate the first delivery date.
		 *
		 * @param array    $delivery_settings Delivery settings.
		 * @param DateTime $start_date        Start date.
		 * @param string   $type              Timestamp or date.
		 * @return int|bool
		 */
		public function calculate_first_delivery_date( $delivery_settings, $start_date, $type = 'timestamp' ) {

			if ( ! in_array( $delivery_settings['delivery_period'], array( 'weeks', 'months', 'years' ), true ) ) {
				$start_date = new DateTime();
				return $start_date->getTimestamp();
			}

			$caller = 'get_delivery_date_for_' . $delivery_settings['delivery_period'];

			$first_delivery_date = $this->$caller( $delivery_settings, $start_date, $type );

			return $first_delivery_date;
		}

		/**
		 * Return the delivery date calculated for weekly periods.
		 *
		 * @param array    $delivery_settings Delivery settings.
		 * @param DateTime $start_date        Start date.
		 * @param string   $type              Format of date.
		 * @return int|DateTime
		 */
		public function get_delivery_date_for_weeks( $delivery_settings, $start_date, $type = 'timestamp' ) {

			$new_date = $start_date;
			$new_date->modify( 'next ' . ywsbs_get_week_day_string( $delivery_settings['sych_weeks'] ) );
			$new_date->setTime( $this->time_of_day, 0, 0 );

			return 'timestamp' === $type ? $new_date->getTimestamp() : $new_date;
		}

		/**
		 * Return the delivery date calculated for monthly periods.
		 *
		 * @param array    $delivery_settings Delivery settings.
		 * @param DateTime $start_date        Start date.
		 * @param string   $type              Format of date.
		 * @return int|DateTime
		 */
		public function get_delivery_date_for_months( $delivery_settings, $start_date, $type = 'timestamp' ) {

			$new_date = $start_date;

			if ( 'end' === $delivery_settings['months'] ) {
				$new_date->modify( 'last day of this month' );
			} else {
				$month = (int) $delivery_settings['months'];
				if ( $new_date->format( 'd' ) <= (int) $month ) {
					$diff = $month - $new_date->format( 'd' );
					$new_date->modify( '+ ' . $diff . ' days' );
				} else {
					$new_date->modify( 'first day of next month' );
					$new_date->add( new DateInterval( 'P' . ( $month - 1 ) . 'D' ) );
				}
			}

			$new_date->setTime( $this->time_of_day, 0, 0 );
			return 'timestamp' === $type ? $new_date->getTimestamp() : $new_date;
		}

		/**
		 * Return the delivery date calculated for yearly periods.
		 *
		 * @param array    $delivery_settings Delivery settings.
		 * @param DateTime $start_date        Start date.
		 * @param string   $type              Format of date.
		 * @return int|DateTime
		 */
		public function get_delivery_date_for_years( $delivery_settings, $start_date, $type = 'timestamp' ) {

			$new_date = $start_date;

			$day   = ( 'end' === $delivery_settings['years_day'] ) ? 1 : $delivery_settings['years_day'];
			$month = $delivery_settings['years_month'];

			if ( $new_date->format( 'n' ) < $month || ( $new_date->format( 'n' ) == $month ) && ( $new_date->format( 'd' ) < $day ) ) { //phpcs:ignore
				$new_date = $new_date->modify( $new_date->format( 'y' ) . '-' . $month . '-' . $day );
			} else {
				$new_date = $new_date->modify( ( (int) $new_date->format( 'y' ) + 1 ) . '-' . $month . '-' . $day );
			}

			// Move the date at the end of the month.
			if ( 'end' === $delivery_settings['years_day'] ) {
				$new_date->modify( 'last day of this month' );
			}

			$new_date->setTime( $this->time_of_day, 0, 0 );

			return 'timestamp' === $type ? $new_date->getTimestamp() : $new_date;
		}

		/**
		 * Get status.
		 *
		 * @return array
		 */
		public function get_status() {
			$status = array(
				'processing' => esc_html_x( 'In process', 'Delivery schedules status', 'yith-woocommerce-subscription' ),
				'waiting'    => esc_html_x( 'Waiting', 'Delivery schedules status', 'yith-woocommerce-subscription' ),
				'shipped'    => esc_html_x( 'Shipped', 'Delivery schedules status', 'yith-woocommerce-subscription' ),
				'cancelled'  => esc_html_x( 'Cancelled', 'Delivery schedules status', 'yith-woocommerce-subscription' ),
			);

			return apply_filters( 'ywsbs_delivery_schedules_status', $status );
		}

		/**
		 * Get status label
		 *
		 * @param string $status_index Status.
		 * @return string
		 */
		public function get_status_label( $status_index ) {
			$status = $this->get_status();
			return isset( $status[ $status_index ] ) ? $status[ $status_index ] : $status_index;
		}

		/**
		 * Update the status of a delivery schedules
		 *
		 * @param int    $delivery_id Delivery id.
		 * @param string $new_status  New status.
		 * @return array
		 */
		public function update_status( $delivery_id, $new_status ) {
			global $wpdb;

			$update_result = array(
				'updated' => 0,
				'sent_on' => '',
			);

			$available_status = $this->get_status();
			if ( ! array_key_exists( $new_status, $available_status ) ) {
				return $update_result;
			}

			$delivery_info = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_id( $delivery_id );
			if ( $delivery_info ) {
				$now                      = 'shipped' === $new_status ? gmdate( 'Y-m-d H:i:s', time() ) : '';
				$update_result['sent_on'] = $now;
				$update_result['updated'] = $wpdb->query( $wpdb->prepare( "Update {$wpdb->ywsbs_delivery_schedules} SET status = %s, sent_on = %s  WHERE id = %d", $new_status, $now, $delivery_id ) );  // phpcs:ignore

				if ( $update_result['updated'] ) {
					do_action( 'ywsbs_delivery_status_change', $new_status, $delivery_id );
					if ( 'shipped' === $new_status ) {
						WC()->mailer();
						do_action( 'ywsbs_customer_subscription_delivery_schedules_mail_notification', YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_id( $delivery_id ) );
					}
				}
			}

			return $update_result;
		}

		/**
		 * Update delivery schedules status when the subscription is cancelled.
		 *
		 * @param int $subscription_id Subscription cancelled.
		 */
		public function update_delivery_for_cancelled_subscription( $subscription_id ) {
			$subscription      = ywsbs_get_subscription( $subscription_id );
			$delivery_settings = $subscription->get( 'delivery_schedules' );

			if ( empty( $delivery_settings ) ) {
				return;
			}

			$end_date = $subscription->get_end_date();
			// Set to cancelled the status of delivery schedules.
			if ( $end_date <= current_time( 'timestamp' ) ) {  // phpcs:ignore
				global $wpdb;
				$wpdb->get_results( $wpdb->prepare( "UPDATE {$wpdb->ywsbs_delivery_schedules} SET status = 'cancelled' WHERE subscription_id = %d AND status NOT LIKE %s and scheduled_date >= CURRENT_DATE()", $subscription_id, 'shipped' ) );  // phpcs:ignore

				do_action( 'ywsbs_updated_delivery_for_cancelled_subscription', $subscription_id );
			}
		}

		/**
		 * Update delivery schedules status when the subscription is resumed from a pause.
		 *
		 * @param int $subscription_id Subscription cancelled.
		 */
		public function update_delivery_for_resumed_subscription( $subscription_id ) {
			$subscription      = ywsbs_get_subscription( $subscription_id );
			$delivery_settings = $subscription->get( 'delivery_schedules' );

			if ( empty( $delivery_settings ) ) {
				return;
			}

			$start_date = new DateTime();
			$date_pause = $subscription->get( 'date_of_pauses' );
			$last       = ( $date_pause[ count( $date_pause ) - 1 ] );

			global $wpdb;
			$ds_to_update          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_delivery_schedules} WHERE subscription_id = %d AND scheduled_date >= FROM_UNIXTIME(%s) AND status NOT LIKE %s ", $subscription_id, $last, 'shipped' ) ); // phpcs:ignore
			$processing_date       = ( time() + DAY_IN_SECONDS );
			$current_delivery_date = ( isset( $delivery_settings['on'] ) && 'yes' === $delivery_settings['on'] ) ? $this->calculate_first_delivery_date( $delivery_settings, $start_date ) : $start_date->getTimestamp();
			if ( $ds_to_update ) {
				foreach ( $ds_to_update as $current_ds ) {
					$status = ( $processing_date > $current_delivery_date ) ? 'processing' : 'waiting';
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->ywsbs_delivery_schedules} SET scheduled_date = FROM_UNIXTIME(%s), status = %s WHERE id = %d", $current_delivery_date, $status, $current_ds->id ) ); // phpcs:ignore

					do_action( 'ywsbs_updated_delivery_for_resumed_subscription', $subscription_id, $current_ds->id, $current_delivery_date );
					// calculate next delivery schedule date.
					$current_delivery_date = ywsbs_get_timestamp_from_option( $current_delivery_date, $delivery_settings['delivery_gap'], $delivery_settings['delivery_period'] );
				}
			}
		}

		/**
		 * Update delivery scheduled
		 *
		 * @param string             $key          Meta data changed.
		 * @param mixed              $new_value    New date changed.
		 * @param mixed              $old_value    Old date.
		 * @param YWSBS_Subscription $subscription Subscription.
		 */
		public function update_delivery_for_change_payment_due_date( $key, $new_value, $old_value, $subscription ) {
			// check if the date changed is the payment due date or if it changed.
			if ( 'payment_due_date' !== $key || $old_value > $new_value ) {
				return;
			}

			$delivery_settings = $subscription->get( 'delivery_schedules' );
			// check if delivery setting is set.
			if ( empty( $delivery_settings ) ) {
				return;
			}

			$ds                    = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_subscription( $subscription->get_id() );
			$last                  = end( $ds );
			$current_delivery_date = strtotime( $last->scheduled_date );

			$this->add_multiple_delivery_schedules( $delivery_settings, $current_delivery_date, $new_value, $subscription->get_id() );
		}

		/**
		 * Add delivery schedule from a start date to a last date.
		 *
		 * @param array $delivery_settings     Delivery settings.
		 * @param int   $current_delivery_date Start date timestamp.
		 * @param int   $last_delivery_date    Last date timestamp.
		 * @param int   $subscription_id       Subscription id.
		 */
		public function add_multiple_delivery_schedules( $delivery_settings, $current_delivery_date, $last_delivery_date, $subscription_id ) {
			$processing_date = ( time() + DAY_IN_SECONDS );

			while ( $current_delivery_date < $last_delivery_date ) {
				$status      = ( $processing_date > $current_delivery_date ) ? 'processing' : 'waiting';
				$delivery_id = YWSBS_Subscription_Delivery_Schedules_DB::add_delivery_schedules( $subscription_id, $current_delivery_date, $status );

				if ( $delivery_id ) {
					do_action( 'ywsbs_added_delivery_for_subscription', $subscription_id, $delivery_id, $current_delivery_date );
				}
				// calculate next delivery schedule date.
				$current_delivery_date = ywsbs_get_timestamp_from_option( $current_delivery_date, $delivery_settings['delivery_gap'], $delivery_settings['delivery_period'] );
			}
		}

		/**
		 * Check if the post deleted is a subscription and in that case remove the delivery schedules.
		 *
		 * @param int $post_id Post deleted.
		 */
		public function maybe_delete_delivery_status( $post_id ) {
			$post = get_post( $post_id );
			if ( empty( $post ) || YITH_YWSBS_POST_TYPE !== $post->post_type ) {
				return;
			}

			YWSBS_Subscription_Delivery_Schedules_DB::delete_delivery_by_subscription( $post_id );
			do_action( 'ywsbs_delivery_schedules_deleted', $post_id );
		}

		/**
		 * Return a message for a product that has a delivery schedule.
		 *
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public function get_product_delivery_message( $product ) {

			if ( 'yes' !== get_option( 'ywsbs_delivery_show_product_info', 'yes' ) || ! $this->has_delivery_scheduled( $product ) ) {
				return '';
			}

			$message           = sprintf( '<strong>%s </strong>', esc_html_x( 'Delivery schedules:', 'delivery info in single product page', 'yith-woocommerce-subscription' ) );
			$delivery_settings = $this->get_delivery_settings( $product );

			if ( $delivery_settings['delivery_gap'] ) {
				$gap = ( 1 == $delivery_settings['delivery_gap'] ) ? '' : $delivery_settings['delivery_gap']; //phpcs:ignore
				// translators: placeholder i.e. Every 5 days.
				$message .= sprintf( __( 'Every %1$s %2$s', 'yith-woocommerce-subscription' ), $gap, ywsbs_get_time_options_sing_plur( $delivery_settings['delivery_period'], (int) $delivery_settings['delivery_gap'] ) );
				if ( 'days' !== $delivery_settings['delivery_period'] && isset( $delivery_settings['on'] ) && 'yes' === $delivery_settings['on'] ) {
					$years_day = $delivery_settings['years_day'];
					$months    = $delivery_settings['months'];

					if ( class_exists( 'NumberFormatter' ) ) {
						$nf        = new NumberFormatter( get_locale(), NumberFormatter::ORDINAL );
						$months    = $nf->format( (int) $delivery_settings['months'] );
						$years_day = $nf->format( (int) $delivery_settings['years_day'] );
					}

					switch ( $delivery_settings['delivery_period'] ) {
						case 'weeks':
							$day_weeks = ywsbs_get_period_options( 'day_weeks' );
							// translators: placeholder day of week i.e. on Friday.
							$message .= ' ' . sprintf( __( 'on %s', 'yith-woocommerce-subscription' ), $day_weeks[ $delivery_settings['sych_weeks'] ] );
							break;
						case 'months':
							$day = 'end' !== $delivery_settings['months'] ? $months : __( 'at the end of the month', 'yith-woocommerce-subscription' );
							// translators: placeholder day of month i.e. on day 15.
							$message .= ' ' . sprintf( __( 'on day %s ', 'yith-woocommerce-subscription' ), $day );
							break;
						case 'years':
							$day_months = ywsbs_get_period_options( 'months' );
							$day        = 'end' !== $delivery_settings['years_day'] ? $years_day : __( 'at the end of ', 'yith-woocommerce-subscription' );
							// translators: placeholder day of year i.e. on 15 August.
							$message .= ' ' . sprintf( __( 'on %1$s %2$s', 'yith-woocommerce-subscription' ), $day, $day_months[ $delivery_settings['years_month'] ] );
							break;
					}

					if ( apply_filters( 'ywsbs_delivery_schedules_next_delivery_date', true, $product, $delivery_settings ) ) {
						$start_date          = new DateTime();
						$first_delivery_date = $this->calculate_first_delivery_date( $delivery_settings, $start_date );
						$message            .= sprintf( '<br><strong>%s </strong>', esc_html_x( 'Next delivery:', 'delivery info in single product page', 'yith-woocommerce-subscription' ) );
						$message            .= date_i18n( wc_date_format(), $first_delivery_date );
					}
				}
			}

			// Wrap message.
			$message = '<span class="ywsbs-delivery-info">' . $message . '</span>';

			return apply_filters( 'ywsbs_delivery_schedules_next_delivery_date', $message, $product, $delivery_settings );
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
			$message = $this->get_product_delivery_message( $product );
			if ( is_admin() || ! $message ) {
				return $additional_messages;
			}

			$show_message = false;
			$show_message = apply_filters_deprecated(
				'ywsbs_hide_in_delivery_sync_info',
				array(
					$show_message,
					$product,
				),
				'3.0.0',
				'ywsbs_show_product_delivery_schedules_info'
			);

			if ( is_single( $product->get_id() ) || apply_filters( 'ywsbs_show_product_delivery_schedules_info', $show_message, $product ) ) {
				$additional_messages['delivery'] = $message;
			}

			return $additional_messages;
		}

		/**
		 * Filter new subscription args to match delivery options
		 *
		 * @since  3.0.0
		 * @param array              $args         An array of subscription arguments.
		 * @param YWSBS_Subscription $subscription The subscription instance.
		 * @return array
		 */
		public function filter_new_subscription_args( $args, $subscription ) {
			$product_id = ! empty( $args['variation_id'] ) ? $args['variation_id'] : $args['product_id'];
			$product    = wc_get_product( $product_id );

			if ( $product && $this->has_delivery_scheduled( $product ) ) {
				$args['delivery_schedules'] = $this->get_delivery_settings( $product );
			}

			return $args;
		}

		/**
		 * Filter get subscription args to add delivery options
		 *
		 * @since  3.0.0
		 * @param array              $data         An array of subscription data.
		 * @param YWSBS_Subscription $subscription The subscription instance.
		 * @return array
		 */
		public function filter_get_subscription_data( $data, $subscription ) {
			$data['delivery_schedules'] = array();
			$delivery_objects           = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_ordered( $subscription->id );
			if ( ! empty( $delivery_objects ) ) {
				foreach ( $delivery_objects as $delivery ) {
					if ( isset( $delivery->subscription_id ) ) {
						unset( $delivery->subscription_id );
					}
					$data['delivery_schedules'][] = $delivery;
				}
			}

			return $data;
		}

		/**
		 * Add delivery schedules to view subscription detail
		 *
		 * @since 3.0.0
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return void
		 */
		public function view_subscription_delivery_schedules( $subscription ) {
			$delivery_settings = $subscription->get( 'delivery_schedules' );
			if ( empty( $delivery_settings ) ) {
				return;
			}

			$limit = apply_filters( 'ywsbs_delivery_schedules_my_account_show_max', 5 );
			$ds    = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_ordered( $subscription->get_id(), $limit );

			wc_get_template( 'view-subscription-delivery-schedules.php', array( 'ds' => $ds ), '', YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'templates/' );
		}

		/**
		 * Get closest next delivery date for subscription
		 *
		 * @since 3.0.0
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return string|boolean The next delivery date, false if no delivery is set
		 */
		public function get_next_delivery_date( $subscription ) {
			$delivery = YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_subscription( $subscription->get_id(), array( 'processing', 'waiting' ), 1 );
			if ( empty( $delivery ) ) {
				return false;
			}

			$delivery = array_shift( $delivery ); // it's only one!
			return $delivery->scheduled_date;
		}
	}
}
