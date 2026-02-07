<?php
/**
 * Implements YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription' ) ) {

	/**
	 * Class YITH_WC_Subscription
	 */
	class YITH_WC_Subscription extends YITH_WC_Subscription_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Subscription Admin.
		 *
		 * @var YITH_WC_Subscription_Admin
		 */
		public $admin;

		/**
		 * Subscription Frontend.
		 *
		 * @var YITH_WC_Subscription_Frontend
		 */
		public $frontend;

		/**
		 * Subscription Assets.
		 *
		 * @var YITH_WC_Subscription_Assets
		 */
		public $assets;

		/**
		 * Shortcodes.
		 *
		 * @var YWSBS_Subscription_Shortcodes
		 */
		public $shortcodes;

		/**
		 * Subscriptions endpoint
		 *
		 * @var string
		 */
		public static $endpoint = '';

		/**
		 * Subscriptions view endpoint
		 *
		 * @var string
		 */
		public static $view_endpoint = '';

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			global $sitepress;

			$this->include_required();
			YITH_WC_Subscription_Install::install();

			// Loads classes.
			add_action( 'plugins_loaded', array( 'YWSBS_Subscription_Gateways', 'load_gateways' ), 9 );
			add_action( 'plugins_loaded', array( $this, 'load' ), 15 );

			// Register endpoints.
			add_action( 'init', array( $this, 'add_endpoint' ), 15 );

			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_emails' ) );
			add_action( 'woocommerce_init', array( $this, 'load_wc_mailer' ) );

			// Security check.
			add_action( 'wp', array( $this, 'security_check' ), 1 );

			if ( apply_filters( 'ywsbs_needs_flushing', true ) && ! $sitepress && ! class_exists( 'BuddyPress' ) ) {
				add_filter( 'option_rewrite_rules', array( $this, 'rewrite_rules' ), 1 );
				function_exists( 'get_home_path' ) && flush_rewrite_rules();
			}

			add_filter( 'yith_wcstripe_plan_trial_period', array( $this, 'yith_wcstripe_plan_trial_period' ), 10, 4 );

			// Handle custom meta query for wc_get_orders.
			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'filter_get_order_query' ), 10, 2 );
			add_filter( 'woocommerce_order_query_args', array( $this, 'filter_order_query_args' ), 1, 1 );
		}

		/**
		 * Include required common functions.
		 * Must be available immediately.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		protected function include_required() {
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription-updates.php';
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription.php';
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription-product.php';
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription-deprecated.php';
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 2.0.0
		 */
		public function load() {

			// load deprecated hooks class.
			new YWSBS_Subscription_Deprecated_Filters();

			if ( self::is_request( 'admin' ) ) {
				$this->admin = YITH_WC_Subscription_Admin::get_instance();
				// Privacy.
				YWSBS_Subscription_Privacy::get_instance();
			}

			if ( self::is_request( 'frontend' ) ) {
				$this->frontend = YITH_WC_Subscription_Frontend::get_instance();
				YITH_WC_Subscription_Limit::get_instance();
			}

			if ( 'yes' === get_option( 'woocommerce_enable_coupons' ) ) {
				YWSBS_Subscription_Coupons::get_instance();
			}

			$this->assets     = YITH_WC_Subscription_Assets::get_instance();
			$this->shortcodes = new YWSBS_Subscription_Shortcodes();

			YWSBS_Subscription_Modules::init();
			YWSBS_Subscription_Helper::get_instance();
			YWSBS_Subscription_Order::get_instance();
			YITH_WC_Subscription_Ajax::get_instance();
			YITH_WC_Activity::get_instance();
			YWSBS_Subscription_Resubscribe::get_instance();
			YWSBS_Subscription_Switch::get_instance();
			YWSBS_Webhooks::get_instance();
			if ( ywsbs_scheduled_actions_enabled() ) {
				YWSBS_Subscription_Scheduler::get_instance();
				YWSBS_Subscription_Scheduler_Actions::get_instance();
			}

			YWSBS_Subscription_Cron::get_instance();

			// REST API.
			include_once YITH_YWSBS_INC . 'rest-api/Loader.php';
			\YITH\Subscription\RestApi\Loader::get_instance();

			// Gutenberg.
			include_once YITH_YWSBS_INC . 'builders/gutenberg/class-ywsbs-gutenberg.php';

			// Plugin integration.
			$this->load_plugin_integration();
		}


		/**
		 * Load the classes that support different plugins integration
		 */
		private function load_plugin_integration() {
			// YITH WooCommerce Multivendor compatibility.
			if ( defined( 'YITH_WPV_PREMIUM' ) ) {
				include_once YITH_YWSBS_INC . 'compatibility/yith-woocommerce-product-vendors.php';
				YWSBS_Multivendor();
			}

			// YITH WooCommerce Membership compatibility.
			if ( defined( 'YITH_WCMBS_PREMIUM' ) ) {
				include_once YITH_YWSBS_INC . 'compatibility/yith-woocommerce-membership.php';
				YWSBS_Membership();
			}

			// YITH Multi Currency Switcher for WooCommerce compatibility.
			if ( defined( 'YITH_WCMCS_VERSION' ) && ! function_exists( 'ywsbs_yith_wcmcs' ) ) {
				include_once YITH_YWSBS_INC . 'compatibility/class-ywsbs-multi-currency-switcher.php';
				ywsbs_yith_wcmcs();
			}
		}

		/**
		 * What type of request is this?
		 *
		 * @param string $type admin, ajax, cron or frontend.
		 *
		 * @return boolean
		 */
		public static function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin() && ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( ! isset( $_REQUEST['context'] ) || ( isset( $_REQUEST['context'] ) && 'frontend' !== $_REQUEST['context'] ) ) ); //phpcs:ignore

				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'ajax':
					return defined( 'DOING_AJAX' );

				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return false;
		}


		/**
		 * Add the endpoint for the pages in my account to manage the subscription list and view.
		 *
		 * @since 1.0.0
		 */
		public function add_endpoint() {
			self::$endpoint      = apply_filters( 'ywsbs_endpoint', 'my-subscription' );
			self::$view_endpoint = apply_filters( 'ywsbs_view_endpoint', 'view-subscription' );

			$endpoints = array(
				'subscriptions'     => self::$endpoint,
				'view-subscription' => self::$view_endpoint,
			);

			foreach ( $endpoints as $key => $endpoint ) {
				WC()->query->query_vars[ $key ] = $endpoint;
				add_rewrite_endpoint( $endpoint, WC()->query->get_endpoints_mask() );
			}
		}


		/**
		 * Check if the permalink should be flushed.
		 *
		 * @param array $rules Rewrite Rules.
		 *
		 * @return array|boolean
		 */
		public function rewrite_rules( $rules ) {
			$ep = self::$endpoint;
			$vp = self::$view_endpoint;

			return isset( $rules[ "(.?.+?)/{$ep}(/(.*))?/?$" ] ) && isset( $rules[ "(.?.+?)/{$vp}(/(.*))?/?$" ] ) ? $rules : false;
		}

		/**
		 * Check if is main site URL so we can disable some actions on Sandbox websites
		 *
		 * @access public
		 * @return boolean
		 */
		public function is_main_site() {
			return ! ( defined( 'WP_ENV' ) && WP_ENV );
		}

		/**
		 * Renew the subscription
		 *
		 * @since  1.0.0
		 * @param YWSBS_Subscription $subscription subscription to renew.
		 *
		 * @return void
		 * @throws Exception Return error.
		 */
		public function renew_the_subscription( $subscription ) {
			WC()->cart->add_to_cart( $subscription->get( 'product_id' ), $subscription->get( 'quantity' ), $subscription->get( 'variation_id' ) );
		}


		/**
		 * Return the ids of user subscriptions
		 *
		 * @param integer $user_id User ID.
		 * @param string  $status  Status of Subscription.
		 *
		 * @return array|integer
		 */
		public function get_user_subscriptions( $user_id, $status = '' ) {
			$args = array(
				'post_type'      => 'ywsbs_subscription',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'user_id',
						'value'   => $user_id,
						'compare' => '=',
					),
				),
			);

			if ( ! empty( $status ) ) {
				$args['meta_query'][] = array(
					'key'     => 'status',
					'value'   => $status,
					'compare' => '=',
				);
			}

			$posts = get_posts( $args );

			return $posts ? wp_list_pluck( $posts, 'ID' ) : 0;
		}

		/**
		 * Change the status of subscription manually
		 *
		 * @since  1.0.0
		 * @param string             $new_status New Status.
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @param string             $from Who wants to change the status.
		 * @return boolean
		 */
		public function manual_change_status( $new_status, $subscription, $from = '' ) {
			switch ( $new_status ) {
				case 'active':
					if ( ! $subscription->can_be_active() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be activated', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						$subscription->update_status( 'active', $from );
						$this->add_notice( esc_html__( 'This subscription is now active', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'overdue':
					if ( ! $subscription->can_be_overdue() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be in "Overdue" status', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						$subscription->update_status( 'overdue', $from );
						$this->add_notice( esc_html__( 'This subscription is now in overdue status', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'suspended':
					if ( ! $subscription->can_be_suspended() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be in "Suspended" status', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						$subscription->update_status( 'suspended', $from );
						$this->add_notice( esc_html__( 'This subscription is now suspended', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'cancelled':
					if ( ! $subscription->can_be_cancelled() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						// filter added to gateway payments.
						if ( ! apply_filters( 'ywsbs_cancel_recurring_payment', true, $subscription ) ) {
							$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );

							return false;
						}

						$subscription->update_status( 'cancelled', $from );
						$this->add_notice( esc_html__( 'This subscription is now cancelled', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'cancel-now':
					if ( ! $subscription->can_be_cancelled() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						// filter added to gateway payments.
						if ( ! apply_filters( 'ywsbs_cancel_recurring_payment', true, $subscription ) ) {
							$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );

							return false;
						}

						$subscription->update_status( 'cancel-now', $from );
						$this->add_notice( esc_html__( 'This subscription is now cancelled', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'paused':
					if ( ! $subscription->can_be_paused() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be paused', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						// filter added to gateway payments.
						if ( ! apply_filters( 'ywsbs_suspend_recurring_payment', true, $subscription ) ) {
							$this->add_notice( esc_html__( 'This subscription cannot be paused', 'yith-woocommerce-subscription' ), 'error' );

							return false;
						}

						$subscription->update_status( 'paused', $from );
						$subscription->status = 'paused';
						// todo: check if it necessary.
						$this->add_notice( esc_html__( 'This subscription is now paused', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				case 'resumed':
					if ( ! $subscription->can_be_resumed() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be resumed', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						// filter added to gateway payments.
						if ( ! apply_filters( 'ywsbs_resume_recurring_payment', true, $subscription ) ) {
							$this->add_notice( esc_html__( 'This subscription cannot be resumed', 'yith-woocommerce-subscription' ), 'error' );

							return false;
						}

						$subscription->update_status( 'resume', $from );
						$subscription->status = 'active';
						// todo: check if it necessary.
						$this->add_notice( esc_html__( 'This subscription is now active', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				default:
			}

			return false;
		}

		/**
		 * Print a WC message
		 *
		 * @since 1.0.0
		 * @param string $message Message to show.
		 * @param string $type    Type od message.
		 */
		public function add_notice( $message, $type ) {
			if ( ! is_admin() ) {
				wc_add_notice( $message, $type );
			}
		}

		/**
		 * Check if in the order there are subscription
		 *
		 * @since  1.0.0
		 * @param WC_Order|integer $order Order instance or order ID.
		 * @return boolean
		 */
		public function order_has_subscription( $order ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			$order_items = $order->get_items();
			if ( empty( $order_items ) ) {
				return false;
			}

			foreach ( $order_items as $key => $order_item ) {
				$id = ( $order_item['variation_id'] ) ? $order_item['variation_id'] : $order_item['product_id'];

				if ( ywsbs_is_subscription_product( $id ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Filters woocommerce available mails
		 *
		 * @since  1.0.0
		 * @param array $emails WooCommerce email list.
		 * @return array
		 */
		public function add_woocommerce_emails( $emails ) {
			// Load common.
			require_once YITH_YWSBS_INC . 'emails/class-yith-wc-subscription-email.php';
			require_once YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription.php';

			$emails['YITH_WC_Subscription_Status']                   = include YITH_YWSBS_INC . 'emails/class-yith-wc-subscription-status.php';
			$emails['YITH_WC_Customer_Subscription_Cancelled']       = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-cancelled.php';
			$emails['YITH_WC_Customer_Subscription_Suspended']       = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-suspended.php';
			$emails['YITH_WC_Customer_Subscription_Expired']         = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-expired.php';
			$emails['YITH_WC_Customer_Subscription_Before_Expired']  = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-before-expired.php';
			$emails['YITH_WC_Customer_Subscription_Paused']          = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-paused.php';
			$emails['YITH_WC_Customer_Subscription_Resumed']         = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-resumed.php';
			$emails['YITH_WC_Customer_Subscription_Request_Payment'] = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-request-payment.php';
			$emails['YITH_WC_Customer_Subscription_Renew_Reminder']  = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-renew-reminder.php';
			$emails['YITH_WC_Customer_Subscription_Payment_Done']    = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-payment-done.php';
			$emails['YITH_WC_Customer_Subscription_Payment_Failed']  = include YITH_YWSBS_INC . 'emails/class-yith-wc-customer-subscription-payment-failed.php';

			return $emails;
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function load_wc_mailer() {

			if ( 'yes' === get_option( 'ywsbs_site_staging', 'no' ) ) {
				return;
			}

			$actions = array(
				'ywsbs_subscription_admin_mail',
				'ywsbs_customer_subscription_expired_mail',
				'ywsbs_customer_subscription_cancelled_mail',
				'ywsbs_customer_subscription_before_expired_mail',
				'ywsbs_customer_subscription_suspended_mail',
				'ywsbs_customer_subscription_resumed_mail',
				'ywsbs_customer_subscription_paused_mail',
				'ywsbs_customer_subscription_request_payment_mail',
				'ywsbs_customer_subscription_renew_reminder_mail',
				'ywsbs_customer_subscription_payment_done_mail',
				'ywsbs_customer_subscription_payment_failed_mail',
			);

			foreach ( $actions as $action ) {
				add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10 );
			}
		}

		/**
		 * Start the downgrade process
		 *
		 * @since  1.0.0
		 * @param integer            $from_id Current Variation id.
		 * @param integer            $to_id Variation to switch.
		 * @param YWSBS_Subscription $subscription Current subscription.
		 * @return void
		 * @throws Exception Return error.
		 */
		public function downgrade_process( $from_id, $to_id, $subscription ) {
			// retrieve the days left to the next payment or to the expiration data.
			$left_time       = YWSBS_Subscription_Helper()->get_left_time_to_next_payment( $subscription );
			$days            = ywsbs_get_days( $left_time );
			$subscription_id = $subscription->get_id();

			if ( $left_time <= 0 && $days > 1 ) {
				add_user_meta(
					$subscription->get_user_id(),
					'ywsbs_upgrade_' . $to_id,
					array(
						'subscription_id' => $subscription_id,
						'pay_gap'         => 0,
					)
				);
			} elseif ( $left_time > 0 ) {
				$user_id = $subscription->get_user_id();
				add_user_meta( $user_id, 'ywsbs_downgrade_' . $to_id, $subscription_id );
				add_user_meta(
					$user_id,
					'ywsbs_trial_' . $to_id,
					array(
						'subscription_id' => $subscription_id,
						'trial_days'      => $days,
					)
				);
			}

			$variation = wc_get_product( $to_id );

			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $subscription->get( 'product_id' ), $subscription->get( 'quantity' ), $to_id, $variation->get_variation_attributes() ) ) {
				wc_add_notice( esc_html__( 'This subscription cannot be switched. Contact us for more information.', 'yith-woocommerce-subscription' ), 'error' );

				return;
			}

			WC()->cart->add_to_cart( $subscription->get( 'product_id' ), $subscription->get( 'quantity' ), $to_id, $variation->get_variation_attributes() );

			$checkout_url = wc_get_checkout_url();
			/**
			 * DO_ACTION: ywsbs_subscription_downgrade_process
			 *
			 * Action triggered during the downgrade option
			 *
			 * @param int                $from_id      Current subscription product to switch.
			 * @param int                $to_id        Final variation id.
			 * @param YWSBS_Subscription $subscription Subscription object
			 */
			do_action( 'ywsbs_subscription_downgrade_process', $subscription->get_variation_id(), $to_id, $subscription );

			wp_safe_redirect( $checkout_url );
			exit;
		}

		/**
		 * Start the upgrade process
		 *
		 * @since  1.0.0
		 * @param integer            $from_id current Variation id.
		 * @param integer            $to_id Variation to switch.
		 * @param YWSBS_Subscription $subscription Current subscription.
		 * @param float              $pay_gap Gap Amount.
		 * @return void
		 * @throws Exception Return error.
		 */
		public function upgrade_process( $from_id, $to_id, $subscription, $pay_gap ) {
			add_user_meta(
				$subscription->get_user_id(),
				'ywsbs_upgrade_' . $to_id,
				array(
					'subscription_id' => $subscription->get_id(),
					'pay_gap'         => $pay_gap,
				),
				true
			);

			$variation = wc_get_product( $to_id );

			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $subscription->get( 'product_id' ), $subscription->get( 'quantity' ), $to_id, $variation->get_variation_attributes() ) ) {
				wc_add_notice( esc_html__( 'This subscription cannot be switched. Contact us for more information.', 'yith-woocommerce-subscription' ), 'error' );

				return;
			}

			WC()->cart->add_to_cart( $subscription->get( 'product_id' ), $subscription->get( 'quantity' ), $to_id, $variation->get_variation_attributes() );

			$checkout_url = wc_get_checkout_url();
			do_action( 'ywsbs_subscription_upgrade_process', $subscription->get_variation_id(), $to_id, $subscription, $pay_gap );

			wp_safe_redirect( $checkout_url );
			exit;
		}


		/**
		 * Cancel the subscription
		 *
		 * @since  1.0.0
		 * @param integer $subscription_id Subscription to cancel.
		 * @return void
		 */
		public function cancel_subscription_after_upgrade( $subscription_id ) {
			$subscription = ywsbs_get_subscription( $subscription_id );

			if ( ! apply_filters( 'ywsbs_cancel_recurring_payment', true, $subscription ) ) {
				$this->add_notice( esc_html__( 'This subscription cannot be cancelled. It\'s not possible to switch to related subscriptions.', 'yith-woocommerce-subscription' ), 'error' );

				return;
			}

			$subscription->update_status( 'cancelled', 'customer' );
			$subscription->status = 'cancelled';
			// todo:check if it necessary.
			do_action( 'ywsbs_subscription_cancelled_mail', $subscription );

			YITH_WC_Activity()->add_activity( $subscription_id, 'switched', 'success', 0, esc_html__( 'Subscription cancelled due to switch', 'yith-woocommerce-subscription' ) );
		}

		/**
		 * Checks whether plugin is currently active on the site it was originally installed
		 * If site url has changed from original one, it could happen that db was cloned on another installation
		 *
		 * @since  2.2.0
		 * @return void
		 */
		public function security_check() {

			if ( 'yes' === get_option( 'ywsbs_skip_security_check', 'no' ) ) {
				return;
			}

			// skip this check if WPML Language URL format is set like a different domain per language.
			global $sitepress_settings;
			if ( isset( $sitepress_settings, $sitepress_settings['language_negotiation_type'] ) && 2 === (int) $sitepress_settings['language_negotiation_type'] ) {
				return;
			}

			$registered_url = get_option( 'ywsbs_registered_url', '' );
			if ( ! $registered_url ) {
				update_option( 'ywsbs_registered_url', get_site_url() );

				return;
			}

			$registered_url = str_replace( array( 'https://', 'http://', 'www.' ), '', $registered_url );
			$current_url    = str_replace( array( 'https://', 'http://', 'www.' ), '', get_site_url() );
			$allowed_urls   = apply_filters( 'ywsbs_site_urls_allowed', array( $registered_url ) );

			if ( apply_filters( 'ywsbs_validate_site_url', ! in_array( $current_url, $allowed_urls, true ), $registered_url, $current_url, $allowed_urls ) ) {
				yith_subscription_log( 'YITH Subscription set to staging mode: Registered url ' . $registered_url . ' - Current url ' . $current_url );
				update_option( 'ywsbs_site_staging', 'yes' );
				update_option( 'ywsbs_site_changed', 'yes' );
			}
		}

		/**
		 * Return the number of trial days.
		 *
		 * @param int                $trial        Num of trial days.
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @return int
		 */
		public function yith_wcstripe_plan_trial_period( int $trial, YWSBS_Subscription $subscription ): int {
			if ( $trial && 'backend' === $subscription->get( 'created_via' ) ) {
				$trial = ( (int) $subscription->get( 'payment_due_date' ) - time() ) / DAY_IN_SECONDS;
			}

			return (int) $trial;
		}

		/**
		 * Filter get order query args
		 *
		 * @since 3.10.0
		 * @param array $query      Current query args.
		 * @param array $query_vars Request query args.
		 * @return array
		 */
		public function filter_get_order_query( $query, $query_vars ) {

			if ( ! empty( $query_vars['ywsbs_meta_query'] ) ) {
				$query['meta_query'] = array_merge( $query['meta_query'] ?? array(), $query_vars['ywsbs_meta_query'] ); // phpcs:ignore
			}

			return $query;
		}

		/**
		 * Filter order query args
		 *
		 * @since 3.10.0
		 * @param array $query_vars Request query args.
		 * @return array
		 */
		public function filter_order_query_args( $query_vars ) {

			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && ! empty( $query_vars['ywsbs_meta_query'] ) ) {
				if ( ! empty( $query_vars['custom_meta'] ) ) {
					$query_vars['meta_query'] = array_merge( $query['meta_query'] ?? array(), $query_vars['ywsbs_meta_query'] ); // phpcs:ignore
					unset( $query_vars['ywsbs_meta_query'] );
				}
			}

			return $query_vars;
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Subscription class
 *
 * @return YITH_WC_Subscription
 */
function YITH_WC_Subscription() { //phpcs:ignore
	return YITH_WC_Subscription::get_instance();
}
