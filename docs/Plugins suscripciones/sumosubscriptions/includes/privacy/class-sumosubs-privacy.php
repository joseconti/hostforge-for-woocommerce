<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 * 
 * @class SUMOSubs_Privacy
 */
class SUMOSubs_Privacy {

	/**
	 * This is a list of exporters.
	 *
	 * @var array
	 */
	protected static $exporters = array();

	/**
	 * This is a list of erasers.
	 *
	 * @var array
	 */
	protected static $erasers = array();

	/**
	 * Background process to clean up subscriptions.
	 *
	 * @var array
	 */
	protected static $background_process = array();

	/**
	 * Limit background process to number of batches to avoid timeouts
	 *
	 * @var int 
	 */
	protected static $batch_limit = 10;

	/**
	 * Force erase personal data from user.
	 *
	 * @var bool 
	 */
	protected static $force_erase_personal_data = false;

	/**
	 * Init SUMOSubs_Privacy.
	 */
	public static function init() {
		self::$force_erase_personal_data = 'yes' === get_option( 'sumo_erasure_request_removes_subscription_data', 'no' );

		add_action( 'admin_init', __CLASS__ . '::add_privacy_message' );

		self::add_exporter( 'sumosubscriptions-customer-subscriptions', __( 'Customer Subscriptions', 'sumosubscriptions' ), __CLASS__ . '::subscription_data_exporter' );
		self::add_eraser( 'sumosubscriptions-customer-subscriptions', __( 'Customer Subscriptions', 'sumosubscriptions' ), __CLASS__ . '::subscription_data_eraser' );
		self::add_eraser( 'sumosubscriptions-customer-subscription-logs', __( 'Customer Subscription Logs', 'sumosubscriptions' ), __CLASS__ . '::subscription_log_data_eraser' );

		add_filter( 'wp_privacy_personal_data_exporters', __CLASS__ . '::register_exporters', 6 );
		add_filter( 'wp_privacy_personal_data_erasers', __CLASS__ . '::register_erasers' );

		//Prevent subscription order from WP data erasure
		add_filter( 'woocommerce_privacy_erase_order_personal_data', __CLASS__ . '::prevent_subscription_order_from_erasure', 99, 2 );

		//Add the following hooks when the corresponding hook named 'woocommerce_cleanup_personal_data' is fired
		add_filter( 'woocommerce_get_wp_query_args', __CLASS__ . '::set_meta_query_args', 99, 2 );
		add_filter( 'woocommerce_trash_pending_orders_query_args', __CLASS__ . '::prevent_subscription_orders_from_anonymization', 99, 2 );
		add_filter( 'woocommerce_trash_failed_orders_query_args', __CLASS__ . '::prevent_subscription_orders_from_anonymization', 99, 2 );
		add_filter( 'woocommerce_trash_cancelled_orders_query_args', __CLASS__ . '::prevent_subscription_orders_from_anonymization', 99, 2 );
		add_filter( 'woocommerce_anonymize_completed_orders_query_args', __CLASS__ . '::prevent_subscription_orders_from_anonymization', 99, 2 );

		// Cleanup orders daily - this is a callback on a daily cron event.
		add_action( 'woocommerce_cleanup_personal_data', __CLASS__ . '::queue_cleanup_personal_data' );
	}

	/**
	 * Get plugin name
	 * 
	 * @return string
	 */
	public static function get_plugin_name() {
		$plugin = get_plugin_data( SUMO_SUBSCRIPTIONS_PLUGIN_FILE );
		return $plugin[ 'Name' ];
	}

	/**
	 * Adds the privacy message on SUMO Subscriptions privacy page.
	 */
	public static function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = self::get_privacy_message();

			if ( $content ) {
				wp_add_privacy_policy_content( self::get_plugin_name(), $content );
			}
		}
	}

	/**
	 * Integrate this exporter implementation within the WordPress core exporters.
	 *
	 * @param array $exporters List of exporter callbacks.
	 * @return array
	 */
	public static function register_exporters( $exporters = array() ) {
		foreach ( self::$exporters as $id => $exporter ) {
			$exporters[ $id ] = $exporter;
		}

		return $exporters;
	}

	/**
	 * Integrate this eraser implementation within the WordPress core erasers.
	 *
	 * @param array $erasers List of eraser callbacks.
	 * @return array
	 */
	public static function register_erasers( $erasers = array() ) {
		foreach ( self::$erasers as $id => $eraser ) {
			$erasers[ $id ] = $eraser;
		}

		return $erasers;
	}

	/**
	 * Add exporter to list of exporters.
	 *
	 * @param string $id       ID of the Exporter.
	 * @param string $name     Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public static function add_exporter( $id, $name, $callback ) {
		self::$exporters[ $id ] = array(
			'exporter_friendly_name' => $name,
			'callback'               => $callback,
		);
		return self::$exporters;
	}

	/**
	 * Add eraser to list of erasers.
	 *
	 * @param string $id       ID of the Eraser.
	 * @param string $name     Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public static function add_eraser( $id, $name, $callback ) {
		self::$erasers[ $id ] = array(
			'eraser_friendly_name' => $name,
			'callback'             => $callback,
		);
		return self::$erasers;
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 */
	public static function get_privacy_message() {
		ob_start();
		?>
		<p>
			<?php esc_html_e( 'This includes the basics of what personal data your store may be collecting, storing and sharing. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary.', 'sumosubscriptions' ); ?>
		</p>
		<h2><?php esc_html_e( 'What the Plugin does', 'sumosubscriptions' ); ?></h2>
		<p>
			<?php esc_html_e( 'Using this plugin, you can create and sell subscription products on your WooCommerce shop.', 'sumosubscriptions' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'This plugin comes up with inbuilt payment gateways SUMO Subscriptions -- PayPal Adaptive Split Payment, SUMO Subscriptions -- PayPal Reference Transactions, SUMO Subscriptions -- Stripe which is used for getting subscription payments.', 'sumosubscriptions' ); ?>
		</p>
		<h2><?php esc_html_e( 'What we collect and share', 'sumosubscriptions' ); ?></h2>
		<h2><?php esc_html_e( 'Email ID', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used for tracking the user', 'sumosubscriptions' ); ?>
			</li>
			<li>
				<?php esc_html_e( '- Used for sending subscription emails to the user', 'sumosubscriptions' ); ?>
			</li>
		</ul>
		<h2><?php esc_html_e( 'User ID', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used for tracking the previous purchase of the user', 'sumosubscriptions' ); ?>
			</li>            
		</ul>
		<h2><?php esc_html_e( 'User Object', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used for getting coupon information used by the user for the subscription orders', 'sumosubscriptions' ); ?>
			</li>            
		</ul>
		<h2><?php esc_html_e( 'User Name', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- Used in subscription logs', 'sumosubscriptions' ); ?>
			</li>            
		</ul>
		<h2><?php esc_html_e( 'What we collect and share for SUMO Subscriptions -- PayPal Adaptive Split Payment', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li>
				<?php esc_html_e( '- We does not store any Personal Information from the user for this payment gateway.', 'sumosubscriptions' ); ?>
			</li>  
			<li>
				<?php esc_html_e( '- But, we share the following information from the user with PayPal Payment provider.', 'sumosubscriptions' ); ?>
				<ul>
					<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order ID', 'sumosubscriptions' ); ?></li>
					<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order Amount', 'sumosubscriptions' ); ?></li>
					<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Admin PayPal Email ID', 'sumosubscriptions' ); ?></li>
				</ul>
			</li>  
		</ul>
		<p>
			<?php echo wp_kses_post( __( 'To know more about how the PayPal Payment Provider uses and stores the data shared with them, check the Privacy Policy of PayPal Payment Provider here <a href="https://www.paypal.com/in/webapps/mpp/ua/privacy-full">https://www.paypal.com/in/webapps/mpp/ua/privacy-full</a>', 'sumosubscriptions' ) ); ?>
		</p>
		<h2><?php esc_html_e( 'What we collect and share for SUMO Subscriptions -- PayPal Reference Transactions', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li><?php esc_html_e( '- We does not store any Personal Information from the user for this payment gateway.', 'sumosubscriptions' ); ?></li>
			<li><?php esc_html_e( '- But, we share the following information from the user with PayPal Payment provider.', 'sumosubscriptions' ); ?></li>
			<ul>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order ID', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order Amount', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Product Details', 'sumosubscriptions' ); ?></li>
			</ul>
		</ul>
		<p>
			<?php echo wp_kses_post( __( 'To know more about how the PayPal Payment Provider uses and stores the data shared with them, check the Privacy Policy of PayPal Payment Provider here <a href="https://www.paypal.com/in/webapps/mpp/ua/privacy-full">https://www.paypal.com/in/webapps/mpp/ua/privacy-full</a>', 'sumosubscriptions' ) ); ?>
		</p>
		<h2><?php esc_html_e( 'What we collect and share for SUMO Subscriptions -- Stripe', 'sumosubscriptions' ); ?></h2>
		<ul>
			<li><?php esc_html_e( '- We does not store any Personal Information from the user for this payment gateway.', 'sumosubscriptions' ); ?></li>
			<li><?php esc_html_e( '- But, we share the following information from the user with Stripe Payment provider.', 'sumosubscriptions' ); ?></li>
			<ul>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order ID', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Order Amount', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Credit/Debit Card Number', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Credit/Debit Card Expiry Date', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Credit/Debit Card CSV number', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'User Email ID', 'sumosubscriptions' ); ?></li>
				<li><?php echo esc_html( str_repeat( '&nbsp;', 5 ) ); ?><?php esc_html_e( 'Shipping Details', 'sumosubscriptions' ); ?></li>
			</ul>
		</ul>
		<p>
			<?php echo wp_kses_post( __( 'To know more about how the Stripe Payment Provider uses and stores the data shared with them, check the Privacy Policy of Stripe Payment Provider here <a href="https://stripe.com/us/privacy/">https://stripe.com/us/privacy/</a>', 'sumosubscriptions' ) ); ?>
		</p>
		<?php
		/**
		 * Get the privacy policy content.
		 * 
		 * @since 1.0
		 */
		return apply_filters( 'sumosubscriptions_privacy_policy_content', ob_get_clean() );
	}

	/**
	 * Prevent subscription order from force erasure of order by WordPress data erasure.
	 * 
	 * @param bool $erasure_enabled
	 * @param WC_Order object $order
	 * @return bool false to prevent subscription order
	 */
	public static function prevent_subscription_order_from_erasure( $erasure_enabled, $order ) {
		if ( ! self::$force_erase_personal_data && sumo_order_contains_subscription( $order ) ) {
			return false;
		}

		return $erasure_enabled;
	}

	/**
	 * Set our meta query in wc_get_orders(). 
	 * Since by default, wc_get_orders() is neglecting our meta query which is passed via self::prevent_subscription_orders_from_anonymization()
	 * 
	 * @param array $wp_query_args filtered query args from WC_Data_Store_WP::get_wp_query_args( $query_vars )
	 * @param array $query_vars original_query args passed via wc_get_orders( $query_vars )
	 * @return array
	 */
	public static function set_meta_query_args( $wp_query_args, $query_vars ) {
		if ( ! is_array( $wp_query_args ) || ! $wp_query_args || ! isset( $query_vars[ 'meta_query' ] ) ) {
			return $wp_query_args;
		}

		$query_vars_named_meta_queries = array_keys( $query_vars[ 'meta_query' ] );
		if (
				in_array( 'sumosubscriptions_key_1', $query_vars_named_meta_queries ) &&
				in_array( 'sumosubscriptions_key_2', $query_vars_named_meta_queries )
		) {
			$wp_query_args[ 'meta_query' ] = $query_vars[ 'meta_query' ];
		}

		return $wp_query_args;
	}

	/**
	 * For a given query, prevent subscription orders from anonymization of orders.
	 *
	 * @param array $query_args Query.
	 * @return array
	 */
	public static function prevent_subscription_orders_from_anonymization( $query_args ) {
		if ( ! is_array( $query_args ) || ! $query_args ) {
			return $query_args;
		}

		$query_args[ 'meta_query' ] = array(
			'sumosubscriptions_key_1' => array(
				'key'     => 'sumo_subsc_get_available_postids_from_parent_order',
				'compare' => 'NOT EXISTS',
			),
			'sumosubscriptions_key_2' => array(
				'key'     => 'sumo_renewal_order_date',
				'compare' => 'NOT EXISTS',
			),
		);

		return $query_args;
	}

	/**
	 * Spawn events for subscription cleanup.
	 */
	public static function queue_cleanup_personal_data() {
		self::$background_process[] = 'trash_ended_subscriptions';

		foreach ( self::$background_process as $process ) {
			self::{$process}();
		}
	}

	/**
	 * Find and trash ended subscriptions.
	 *
	 * @return int Number of subscriptions processed.
	 */
	public static function trash_ended_subscriptions() {
		$option = wc_parse_relative_date_option( get_option( 'sumo_anonymize_ended_subscriptions' ) );

		if ( empty( $option[ 'number' ] ) ) {
			return 0;
		}

		$remove_data_after = sumo_get_subscription_date( "-{$option[ 'number' ]} {$option[ 'unit' ]}" );
		$subscriptions     = sumosubscriptions()->query->get( array(
			'type'       => 'sumosubscriptions',
			'limit'      => self::$batch_limit,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_anonymized',
					'compare' => 'NOT EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'sumo_get_sub_end_date',
						'value'   => $remove_data_after,
						'compare' => '<',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'sumo_get_sub_exp_date',
						'value'   => $remove_data_after,
						'compare' => '<',
						'type'    => 'DATETIME',
					),
				),
			),
				) );

		if ( $subscriptions ) {
			foreach ( $subscriptions as $subscription_id ) {
				self::remove_subscription_personal_data( $subscription_id );
			}
		}
	}

	/**
	 * Finds and exports data which could be used to identify a person from SUMO Subscriptions data associated with an email address.
	 *
	 * Subscriptions are exported in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function subscription_data_exporter( $email_address, $page ) {
		$done           = false;
		$data_to_export = array();
		$user           = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.

		if ( $user instanceof WP_User ) {
			$subscriptions = sumosubscriptions()->query->get( array(
				'type'       => 'sumosubscriptions',
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => 'sumo_buyer_email',
				'meta_value' => $email_address,
					) );

			if ( 0 < count( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription_id ) {
					$data_to_export[] = array(
						'group_id'    => 'sumo_subscriptions',
						'group_label' => __( 'SUMO Subscriptions', 'sumosubscriptions' ),
						'item_id'     => "subscription-{$subscription_id}",
						'data'        => self::get_subscription_personal_data( $subscription_id ),
					);
				}
				$done = 10 > count( $subscriptions );
			} else {
				$done = true;
			}
		}

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Finds and erases data which could be used to identify a person from SUMO Subscriptions data assocated with an email address.
	 *
	 * Subscriptions are erased in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function subscription_data_eraser( $email_address, $page ) {
		$user     = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( $user instanceof WP_User ) {
			$subscriptions = sumosubscriptions()->query->get( array(
				'type'       => 'sumosubscriptions',
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => 'sumo_buyer_email',
				'meta_value' => $email_address,
					) );

			if ( 0 < count( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription_id ) {
					/**
					 * Need to erase subscription personal data?
					 * 
					 * @since 1.0
					 */
					if ( apply_filters( 'sumosubscriptions_privacy_erase_subscription_personal_data', self::$force_erase_personal_data, $subscription_id ) ) {
						self::remove_subscription_personal_data( $subscription_id );

						/* Translators: %s Subscription number. */
						$response[ 'messages' ][]    = sprintf( __( 'Removed personal data from subscription %s.', 'sumosubscriptions' ), $subscription_id );
						$response[ 'items_removed' ] = true;
					} else {
						/* Translators: %s Subscription number. */
						$response[ 'messages' ][]     = sprintf( __( 'Personal data within subscription %s has been retained.', 'sumosubscriptions' ), $subscription_id );
						$response[ 'items_retained' ] = true;
					}
				}
				$response[ 'done' ] = 10 > count( $subscriptions );
			} else {
				$response[ 'done' ] = true;
			}
		}

		return $response;
	}

	/**
	 * Finds and erases data which could be used to identify a person from SUMO Subscriptions log data assocated with an email address.
	 *
	 * Subscription Logs are erased in blocks of 10 to avoid timeouts.
	 *
	 * @param string $email_address The user email address.
	 * @param int    $page  Page.
	 * @return array An array of personal data in name value pairs
	 */
	public static function subscription_log_data_eraser( $email_address, $page ) {
		$user     = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( $user instanceof WP_User ) {
			$subscription_logs = sumosubscriptions()->query->get( array(
				'type'       => 'sumomasterlog',
				'limit'      => self::$batch_limit,
				'page'       => absint( $page ),
				'meta_key'   => 'user_name',
				'meta_value' => $user->display_name,
					) );

			if ( 0 < count( $subscription_logs ) ) {
				foreach ( $subscription_logs as $log_id ) {
					/**
					 * Need to erase subscription log personal data?
					 * 
					 * @since 1.0
					 */
					if ( apply_filters( 'sumosubscriptions_privacy_erase_subscription_log_personal_data', self::$force_erase_personal_data, $log_id ) ) {
						self::remove_subscription_log_personal_data( $log_id );

						/* Translators: %s Subscription log id. */
						$response[ 'messages' ][]    = sprintf( __( 'Removed personal data from subscription log %s.', 'sumosubscriptions' ), $log_id );
						$response[ 'items_removed' ] = true;
					} else {
						/* Translators: %s Subscription log id. */
						$response[ 'messages' ][]     = sprintf( __( 'Personal data within subscription log %s has been retained.', 'sumosubscriptions' ), $log_id );
						$response[ 'items_retained' ] = true;
					}
				}
				$response[ 'done' ] = 10 > count( $subscription_logs );
			} else {
				$response[ 'done' ] = true;
			}
		}

		return $response;
	}

	/**
	 * Get personal data (key/value pairs) for an Subscription.
	 *
	 * @param int $subscription_id Subscriptions post ID.
	 * @return array
	 */
	public static function get_subscription_personal_data( $subscription_id ) {
		$personal_data   = array();
		/**
		 * Get subscription personal data props.
		 * 
		 * @since 1.0
		 */
		$props_to_export = apply_filters( 'sumosubscriptions_privacy_export_subscription_personal_data_props', array(
			'subscription_number'            => __( 'Subscription Number', 'sumosubscriptions' ),
			'subscribed_product(s)'          => __( 'Subscribed Product(s)', 'sumosubscriptions' ),
			'subscription_amount'            => __( 'Subscription Amount', 'sumosubscriptions' ),
			'trial_end_date'                 => __( 'Trial End Date', 'sumosubscriptions' ),
			'subscription_start_date'        => __( 'Subscription Start Date', 'sumosubscriptions' ),
			'subscription_next_payment_date' => __( 'Subscription Next Payment Date', 'sumosubscriptions' ),
			'subscription_end_date'          => __( 'Subscription End Date', 'sumosubscriptions' ),
			'subscription_expired_date'      => __( 'Subscription Expired Date', 'sumosubscriptions' ),
			'customer_email'                 => __( 'Customer Email Address', 'sumosubscriptions' ),
				), $subscription_id );

		foreach ( $props_to_export as $prop => $name ) {
			$value = '';

			switch ( $prop ) {
				case 'subscription_number':
					$value = sumo_get_subscription_number( $subscription_id );
					break;
				case 'subscribed_product(s)':
					$value = sumo_display_subscription_name( $subscription_id, true, false, false );
					break;
				case 'subscription_amount':
					$value = wc_format_decimal( sumo_get_recurring_fee( $subscription_id ) );
					break;
				case 'trial_end_date':
					$value = sumo_display_trial_end_date( $subscription_id );
					break;
				case 'subscription_start_date':
					$value = sumo_display_start_date( $subscription_id );
					break;
				case 'subscription_next_payment_date':
					$value = sumo_display_next_due_date( $subscription_id );
					break;
				case 'subscription_end_date':
					$value = sumo_display_end_date( $subscription_id );
					break;
				case 'subscription_expired_date':
					$value = sumo_display_expired_date( $subscription_id );
					break;
				case 'customer_email':
					$value = get_post_meta( $subscription_id, 'sumo_buyer_email', true );
					break;
			}

			/**
			 * Get subscription personal data prop.
			 * 
			 * @since 1.0
			 */
			$value = apply_filters( 'sumosubscriptions_privacy_export_subscription_personal_data_prop', $value, $prop, $subscription_id );
			if ( $value ) {
				$personal_data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		/**
		 * Allow extensions to register their own personal data for this subscription for the export.
		 *
		 * @param array $personal_data Array of name value pairs to expose in the export.
		 * @param int $subscription_id
		 * @since 1.0
		 */
		$personal_data = apply_filters( 'sumosubscriptions_privacy_export_subscription_personal_data', $personal_data, $subscription_id );
		return $personal_data;
	}

	/**
	 * Remove personal data specific to Subscription.
	 * 
	 * @param int $subscription_id Subscriptions post ID.
	 */
	public static function remove_subscription_personal_data( $subscription_id ) {
		$anonymized_data = array();

		/**
		 * Allow extensions to remove their own personal data for this subscription first, so subscription data is still available.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_privacy_before_remove_subscription_personal_data', $subscription_id );

		/**
		 * Expose props and data types we'll be anonymizing.
		 * 
		 * @since 1.0
		 */
		$props_to_remove = apply_filters( 'sumosubscriptions_privacy_remove_subscription_personal_data_props', array(
			'sumo_buyer_email'         => 'email',
			'sumo_get_subscriber_data' => 'object',
				), $subscription_id );

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value.
				$value = get_post_meta( $subscription_id, $prop, true );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '';

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 * 
				 * @since 1.0
				 */
				$anonymized_data[ $prop ] = apply_filters( 'sumosubscriptions_privacy_remove_subscription_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $subscription_id );
			}
		}

		//Cancel anonymized subscriptions
		sumosubs_cancel_subscription( $subscription_id, array(
			'note' => __( 'Personal data removed.', 'sumosubscriptions' ),
		) );

		// Set all new props and persist the new data to the database.
		foreach ( $anonymized_data as $prop => $anon_value ) {
			if ( $anon_value ) {
				update_post_meta( $subscription_id, $prop, $anon_value );
			} else {
				delete_post_meta( $subscription_id, $prop );
			}
		}

		update_post_meta( $subscription_id, '_anonymized', 'yes' );

		/**
		 * Allow extensions to remove their own personal data for this subscription.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_privacy_remove_subscription_personal_data', $subscription_id );
	}

	/**
	 * Remove personal data specific to Subscription log.
	 * 
	 * @param int $log_id Subscriptions log post ID.
	 */
	public static function remove_subscription_log_personal_data( $log_id ) {
		$anonymized_data = array();

		/**
		 * Allow extensions to remove their own personal data for this subscription log first, so subscription log data is still available.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_privacy_before_remove_subscription_log_personal_data', $log_id );

		/**
		 * Expose props and data types we'll be anonymizing.
		 * 
		 * @since 1.0
		 */
		$props_to_remove = apply_filters( 'sumosubscriptions_privacy_remove_subscription_log_personal_data_props', array(
			'user_name' => 'text',
				), $log_id );

		if ( ! empty( $props_to_remove ) && is_array( $props_to_remove ) ) {
			foreach ( $props_to_remove as $prop => $data_type ) {
				// Get the current value.
				$value = get_post_meta( $log_id, $prop, true );

				// If the value is empty, it does not need to be anonymized.
				if ( empty( $value ) || empty( $data_type ) ) {
					continue;
				}

				$anon_value = function_exists( 'wp_privacy_anonymize_data' ) ? wp_privacy_anonymize_data( $data_type, $value ) : '';

				/**
				 * Expose a way to control the anonymized value of a prop via 3rd party code.
				 * 
				 * @since 1.0
				 */
				$anonymized_data[ $prop ] = apply_filters( 'sumosubscriptions_privacy_remove_subscription_log_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $log_id );
			}
		}

		// Set all new props and persist the new data to the database.
		foreach ( $anonymized_data as $prop => $anon_value ) {
			if ( $anon_value ) {
				update_post_meta( $log_id, $prop, $anon_value );
			} else {
				delete_post_meta( $log_id, $prop );
			}
		}

		update_post_meta( $log_id, '_anonymized', 'yes' );

		/**
		 * Allow extensions to remove their own personal data for this subscription log.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_privacy_remove_subscription_log_personal_data', $log_id );
	}
}

SUMOSubs_Privacy::init();
