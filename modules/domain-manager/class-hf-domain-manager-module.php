<?php
/**
 * Domain Manager Module.
 *
 * Manages domain registration, transfers, renewals, DNS and WHOIS
 * through external registrar APIs.
 *
 * @package HostForge\Modules\DomainManager
 */

namespace HostForge\Modules\DomainManager;

use HostForge\Abstracts\HF_Module;
use HostForge\Interfaces\HF_Registrar;
use HostForge\Traits\HF_Has_Logs;
use HostForge\Traits\HF_Has_Settings;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Manager_Module
 */
class HF_Domain_Manager_Module extends HF_Module {

	use HF_Has_Logs;
	use HF_Has_Settings;

	/**
	 * Cached registrar instance.
	 *
	 * @var HF_Registrar|null
	 */
	private ?HF_Registrar $registrar_instance = null;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'domain-manager';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Domain Manager', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Domain registration, transfers, renewals, DNS and WHOIS management through registrar APIs.', 'hostforge' );
	}

	/**
	 * Get required dependencies.
	 *
	 * @return array<string>
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register CPT.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Domain engine (order hooks).
		$engine = new HF_Domain_Engine( $this );
		$engine->init();

		// Domain search (AJAX).
		$search = new HF_Domain_Search( $this );
		$search->init();

		// Checkout integration.
		$checkout = new HF_Domain_Checkout( $this );
		$checkout->init();

		// Admin hooks.
		if ( is_admin() ) {
			$admin = new Admin\HF_Domain_Admin( $this );
			$admin->init();
		}

		// Frontend hooks.
		if ( ! is_admin() || wp_doing_ajax() ) {
			$frontend = new HF_Domain_Frontend();
			$frontend->init();
		}

		// Scheduled actions.
		$this->register_scheduled_actions();

		// Action Scheduler callbacks.
		add_action( 'hostforge_register_domain', array( $this, 'process_register_domain' ) );
		add_action( 'hostforge_transfer_domain', array( $this, 'process_transfer_domain' ) );
		add_action( 'hostforge_renew_domain', array( $this, 'process_renew_domain' ) );
		add_action( 'hostforge_domain_expiry_check', array( $this, 'run_expiry_check' ) );
		add_action( 'hostforge_domain_auto_renew', array( $this, 'run_auto_renew' ) );

		// Email hooks.
		add_action( 'hostforge_domain_registered', array( $this, 'send_domain_registered_email' ) );
		add_action( 'hostforge_domain_expiring', array( $this, 'send_expiry_reminder_email' ), 10, 2 );
	}

	/**
	 * Called when the module is activated.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->create_tables();
		$this->register_post_type();
		flush_rewrite_rules();

		// Schedule recurring tasks.
		if ( function_exists( 'as_has_scheduled_action' ) && did_action( 'action_scheduler_init' ) ) {
			if ( ! as_has_scheduled_action( 'hostforge_domain_expiry_check' ) ) {
				as_schedule_recurring_action(
					time() + 300,
					DAY_IN_SECONDS,
					'hostforge_domain_expiry_check',
					array(),
					'hostforge-domain-manager'
				);
			}

			if ( ! as_has_scheduled_action( 'hostforge_domain_auto_renew' ) ) {
				as_schedule_recurring_action(
					time() + 600,
					DAY_IN_SECONDS,
					'hostforge_domain_auto_renew',
					array(),
					'hostforge-domain-manager'
				);
			}
		}

		$this->log_info( 'Domain Manager module activated.' );
	}

	/**
	 * Called when the module is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'hostforge_domain_expiry_check', array(), 'hostforge-domain-manager' );
			as_unschedule_all_actions( 'hostforge_domain_auto_renew', array(), 'hostforge-domain-manager' );
		}

		$this->log_info( 'Domain Manager module deactivated.' );
	}

	/**
	 * Register the hf_domain custom post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Domains', 'hostforge' ),
			'singular_name'      => __( 'Domain', 'hostforge' ),
			'add_new'            => __( 'Add New Domain', 'hostforge' ),
			'add_new_item'       => __( 'Add New Domain', 'hostforge' ),
			'edit_item'          => __( 'Edit Domain', 'hostforge' ),
			'new_item'           => __( 'New Domain', 'hostforge' ),
			'view_item'          => __( 'View Domain', 'hostforge' ),
			'search_items'       => __( 'Search Domains', 'hostforge' ),
			'not_found'          => __( 'No domains found.', 'hostforge' ),
			'not_found_in_trash' => __( 'No domains found in Trash.', 'hostforge' ),
			'all_items'          => __( 'Domains', 'hostforge' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'edit_post'          => 'manage_hostforge_domains',
				'read_post'          => 'manage_hostforge_domains',
				'delete_post'        => 'manage_hostforge_domains',
				'edit_posts'         => 'manage_hostforge_domains',
				'edit_others_posts'  => 'manage_hostforge_domains',
				'publish_posts'      => 'manage_hostforge_domains',
				'read_private_posts' => 'manage_hostforge_domains',
			),
			'map_meta_cap'        => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'exclude_from_search' => true,
		);

		register_post_type( 'hf_domain', $args );
	}

	/**
	 * Create module database tables.
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql_dns = "CREATE TABLE {$wpdb->prefix}hf_dns_records (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			domain_id BIGINT UNSIGNED NOT NULL,
			record_type VARCHAR(10) NOT NULL,
			host VARCHAR(255) NOT NULL,
			value TEXT NOT NULL,
			ttl INT UNSIGNED NOT NULL DEFAULT 3600,
			priority INT UNSIGNED DEFAULT NULL,
			weight INT UNSIGNED DEFAULT NULL,
			port INT UNSIGNED DEFAULT NULL,
			registrar_record_id VARCHAR(100) DEFAULT NULL,
			synced_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY domain_id (domain_id),
			KEY record_type (record_type),
			KEY domain_type (domain_id, record_type)
		) {$charset_collate};";

		$sql_tld = "CREATE TABLE {$wpdb->prefix}hf_tld_pricing (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tld VARCHAR(50) NOT NULL,
			registrar_id VARCHAR(50) NOT NULL,
			register_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			renew_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			transfer_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			currency VARCHAR(3) NOT NULL DEFAULT 'USD',
			min_years TINYINT UNSIGNED NOT NULL DEFAULT 1,
			max_years TINYINT UNSIGNED NOT NULL DEFAULT 10,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY tld_registrar (tld, registrar_id),
			KEY registrar_id (registrar_id),
			KEY is_active (is_active)
		) {$charset_collate};";

		$sql_queue = "CREATE TABLE {$wpdb->prefix}hf_domain_queue (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			domain_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(20) NOT NULL,
			params LONGTEXT,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts INT UNSIGNED NOT NULL DEFAULT 0,
			max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
			last_error TEXT,
			scheduled_at DATETIME NOT NULL,
			completed_at DATETIME,
			PRIMARY KEY (id),
			KEY domain_id (domain_id),
			KEY status (status),
			KEY status_scheduled (status, scheduled_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_dns );
		dbDelta( $sql_tld );
		dbDelta( $sql_queue );
	}

	/**
	 * Register scheduled actions.
	 *
	 * @return void
	 */
	public function register_scheduled_actions(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! did_action( 'action_scheduler_init' ) ) {
			add_action( 'action_scheduler_init', array( $this, 'register_scheduled_actions' ) );
			return;
		}

		if ( ! as_has_scheduled_action( 'hostforge_domain_expiry_check' ) ) {
			as_schedule_recurring_action(
				time() + 300,
				DAY_IN_SECONDS,
				'hostforge_domain_expiry_check',
				array(),
				'hostforge-domain-manager'
			);
		}

		if ( ! as_has_scheduled_action( 'hostforge_domain_auto_renew' ) ) {
			as_schedule_recurring_action(
				time() + 600,
				DAY_IN_SECONDS,
				'hostforge_domain_auto_renew',
				array(),
				'hostforge-domain-manager'
			);
		}
	}

	/**
	 * Get a registrar instance.
	 *
	 * @param string $registrar_id Optional registrar ID. Defaults to active registrar.
	 * @return HF_Registrar|null
	 */
	public function get_registrar( string $registrar_id = '' ): ?HF_Registrar {
		if ( empty( $registrar_id ) ) {
			$registrar_id = get_option( 'hf_active_registrar', 'namecheap' );
		}

		if ( $this->registrar_instance && get_option( 'hf_active_registrar', 'namecheap' ) === $registrar_id ) {
			return $this->registrar_instance;
		}

		$available_registrars = array(
			'namecheap' => __( 'Namecheap', 'hostforge' ),
		);

		/**
		 * Filters the list of available registrar providers.
		 *
		 * Use this to add custom registrar integrations. The returned array
		 * should map registrar ID => display name.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $available_registrars Registrar ID => label pairs.
		 */
		$available_registrars = apply_filters( 'hostforge_registrars', $available_registrars );

		$registrar = null;

		switch ( $registrar_id ) {
			case 'namecheap':
				$registrar = new Registrars\HF_Namecheap_Registrar();
				break;
		}

		/**
		 * Filter the registrar instance.
		 *
		 * @param HF_Registrar|null $registrar    Registrar instance.
		 * @param string            $registrar_id Registrar identifier.
		 */
		$registrar = apply_filters( 'hostforge_registrar_instance', $registrar, $registrar_id );

		if ( $registrar instanceof HF_Registrar ) {
			$this->registrar_instance = $registrar;
		}

		return $registrar;
	}

	/**
	 * Process a domain registration action.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function process_register_domain( int $domain_id ): void {
		$engine = new HF_Domain_Engine( $this );
		$engine->execute_register( $domain_id );
	}

	/**
	 * Process a domain transfer action.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function process_transfer_domain( int $domain_id ): void {
		$engine = new HF_Domain_Engine( $this );
		$engine->execute_transfer( $domain_id );
	}

	/**
	 * Process a domain renewal action.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function process_renew_domain( int $domain_id ): void {
		$engine = new HF_Domain_Engine( $this );
		$engine->execute_renew( $domain_id );
	}

	/**
	 * Run daily expiry check.
	 *
	 * Sends reminder emails for domains approaching expiry
	 * and marks expired domains.
	 *
	 * @return void
	 */
	public function run_expiry_check(): void {
		$reminder_days = array_map( 'absint', explode( ',', get_option( 'hf_domain_expiry_reminder_days', '30,14,7,1' ) ) );

		/**
		 * Filters the days-before-expiry intervals for domain reminder emails.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int> $reminder_days Array of day counts (e.g. [30, 14, 7, 1]).
		 */
		$reminder_days = apply_filters( 'hostforge_domain_expiry_reminder_days', $reminder_days );

		$domains = get_posts(
			array(
				'post_type'      => 'hf_domain',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_status',
						'value' => 'active',
					),
				),
			)
		);

		$now = time();

		foreach ( $domains as $domain ) {
			$expiry_date = get_post_meta( $domain->ID, '_hf_expiry_date', true );

			if ( empty( $expiry_date ) ) {
				continue;
			}

			$expiry_time    = strtotime( $expiry_date );
			$days_remaining = (int) ceil( ( $expiry_time - $now ) / DAY_IN_SECONDS );

			// Mark as expired if past expiry.
			if ( $days_remaining <= 0 ) {
				update_post_meta( $domain->ID, '_hf_status', 'expired' );

				$this->log_info(
					'Domain marked as expired.',
					array(
						'domain_id'   => $domain->ID,
						'domain_name' => get_post_meta( $domain->ID, '_hf_domain_name', true ),
					)
				);

				/**
				 * Fires when a domain has expired.
				 *
				 * @param int $domain_id Domain post ID.
				 */
				do_action( 'hostforge_domain_expired', $domain->ID );
				continue;
			}

			// Send reminders at configured intervals.
			foreach ( $reminder_days as $reminder_day ) {
				if ( $days_remaining <= $reminder_day ) {
					$transient_key = 'hf_domain_expiry_sent_' . $domain->ID . '_' . $reminder_day;

					if ( false === get_transient( $transient_key ) ) {
						/**
						 * Fires when a domain is approaching expiry.
						 *
						 * @param int $domain_id      Domain post ID.
						 * @param int $days_remaining  Days until expiry.
						 */
						do_action( 'hostforge_domain_expiring', $domain->ID, $days_remaining );

						// Prevent duplicate reminders for this interval.
						set_transient( $transient_key, 1, $reminder_day * DAY_IN_SECONDS );

						$this->log_info(
							'Domain expiry reminder sent.',
							array(
								'domain_id'      => $domain->ID,
								'days_remaining' => $days_remaining,
								'reminder_day'   => $reminder_day,
							)
						);
					}
					break; // Only send one reminder per check.
				}
			}
		}
	}

	/**
	 * Run daily auto-renewal check.
	 *
	 * Enqueues renewal actions for domains with auto-renew enabled
	 * that are approaching expiry.
	 *
	 * @return void
	 */
	public function run_auto_renew(): void {
		$auto_renew_days = absint( get_option( 'hf_domain_auto_renew_days', 14 ) );

		if ( 0 === $auto_renew_days ) {
			return;
		}

		$domains = get_posts(
			array(
				'post_type'      => 'hf_domain',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hf_status',
						'value' => 'active',
					),
					array(
						'key'   => '_hf_auto_renew',
						'value' => 'yes',
					),
				),
			)
		);

		$now = time();

		foreach ( $domains as $domain ) {
			$expiry_date = get_post_meta( $domain->ID, '_hf_expiry_date', true );

			if ( empty( $expiry_date ) ) {
				continue;
			}

			$expiry_time    = strtotime( $expiry_date );
			$days_remaining = (int) ceil( ( $expiry_time - $now ) / DAY_IN_SECONDS );

			if ( $days_remaining <= $auto_renew_days && $days_remaining > 0 ) {
				$renew_transient = 'hf_domain_renew_queued_' . $domain->ID;

				if ( false === get_transient( $renew_transient ) ) {
					$this->log_info(
						'Auto-renewing domain.',
						array(
							'domain_id'      => $domain->ID,
							'domain_name'    => get_post_meta( $domain->ID, '_hf_domain_name', true ),
							'days_remaining' => $days_remaining,
						)
					);

					as_enqueue_async_action(
						'hostforge_renew_domain',
						array( $domain->ID ),
						'hostforge-domain-manager'
					);

					// Prevent duplicate queuing for 7 days.
					set_transient( $renew_transient, 1, 7 * DAY_IN_SECONDS );
				}
			}
		}
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Domains', 'hostforge' ),
				'slug'       => 'hostforge-domains',
				'capability' => 'manage_hostforge_domains',
				'callback'   => array( new Admin\HF_Domain_Admin( $this ), 'render_domains_page' ),
			),
		);
	}

	/**
	 * Get My Account endpoints for this module.
	 *
	 * @return array
	 */
	public function get_myaccount_endpoints(): array {
		return array(
			array(
				'endpoint' => 'my-domains',
				'title'    => __( 'My Domains', 'hostforge' ),
			),
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Domain_Controller();
		$controller->register_routes();
	}

	/**
	 * Get dashboard widgets.
	 *
	 * @return array
	 */
	public function get_dashboard_widgets(): array {
		return array(
			array(
				'id'       => 'hf-domains-count',
				'title'    => __( 'Domains Overview', 'hostforge' ),
				'callback' => array( $this, 'render_dashboard_widget' ),
			),
		);
	}

	/**
	 * Render the domains dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS total
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = 'publish'
				GROUP BY pm.meta_value",
				'_hf_status',
				'hf_domain'
			)
		);

		$counts = array(
			'active'           => 0,
			'pending'          => 0,
			'expired'          => 0,
			'transferred_away' => 0,
			'cancelled'        => 0,
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				if ( isset( $counts[ $row->status ] ) ) {
					$counts[ $row->status ] = absint( $row->total );
				}
			}
		}

		$total = array_sum( $counts );

		// Count expiring soon (30 days).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expiring_soon = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = %s
					INNER JOIN {$wpdb->postmeta} pm_expiry ON p.ID = pm_expiry.post_id AND pm_expiry.meta_key = %s
					WHERE p.post_type = %s
					AND p.post_status = 'publish'
					AND pm_status.meta_value = 'active'
					AND pm_expiry.meta_value BETWEEN %s AND %s",
					'_hf_status',
					'_hf_expiry_date',
					'hf_domain',
					current_time( 'mysql' ),
					gmdate( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) )
				)
			)
		);

		?>
		<div class="hf-widget-stats">
			<div class="hf-stat">
				<span class="hf-stat__number"><?php echo esc_html( $total ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Total Domains', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--success"><?php echo esc_html( $counts['active'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Active', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--warning"><?php echo esc_html( $expiring_soon ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Expiring Soon', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--error"><?php echo esc_html( $counts['expired'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Expired', 'hostforge' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Send domain registered email to customer.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function send_domain_registered_email( int $domain_id ): void {
		$user_id = absint( get_post_meta( $domain_id, '_hf_user_id', true ) );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$domain_name     = get_post_meta( $domain_id, '_hf_domain_name', true );
		$registrar_id    = get_post_meta( $domain_id, '_hf_registrar_id', true );
		$reg_date        = get_post_meta( $domain_id, '_hf_registration_date', true );
		$expiry_date     = get_post_meta( $domain_id, '_hf_expiry_date', true );
		$ns_json         = get_post_meta( $domain_id, '_hf_nameservers', true );
		$nameservers_raw = json_decode( ! empty( $ns_json ) ? $ns_json : '[]', true );

		$args = array(
			'domain_name'       => $domain_name,
			'customer_name'     => $user->display_name,
			'registrar'         => ucfirst( $registrar_id ),
			'registration_date' => ! empty( $reg_date ) ? wp_date( get_option( 'date_format' ), strtotime( $reg_date ) ) : '',
			'expiry_date'       => ! empty( $expiry_date ) ? wp_date( get_option( 'date_format' ), strtotime( $expiry_date ) ) : '',
			'nameservers'       => implode( ', ', $nameservers_raw ),
			'manage_url'        => wc_get_endpoint_url( 'my-domains', $domain_id, wc_get_page_permalink( 'myaccount' ) ),
			'email'             => null,
			'email_heading'     => sprintf(
				/* translators: %s: domain name */
				__( 'Domain Registered: %s', 'hostforge' ),
				$domain_name
			),
		);

		$subject = sprintf(
			/* translators: %s: domain name */
			__( '[%1$s] Domain Registered: %2$s', 'hostforge' ),
			get_bloginfo( 'name' ),
			$domain_name
		);

		$html = hf_get_template_html( 'emails/domain-registered.php', $args );

		if ( ! empty( $html ) ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $user->user_email, $subject, $html, $headers );
		}
	}

	/**
	 * Send domain expiry reminder email to customer.
	 *
	 * @param int $domain_id      Domain post ID.
	 * @param int $days_remaining Days until expiry.
	 * @return void
	 */
	public function send_expiry_reminder_email( int $domain_id, int $days_remaining ): void {
		$user_id = absint( get_post_meta( $domain_id, '_hf_user_id', true ) );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$expiry_date = get_post_meta( $domain_id, '_hf_expiry_date', true );
		$auto_renew  = get_post_meta( $domain_id, '_hf_auto_renew', true );

		$args = array(
			'domain_name'    => $domain_name,
			'customer_name'  => $user->display_name,
			'expiry_date'    => ! empty( $expiry_date ) ? wp_date( get_option( 'date_format' ), strtotime( $expiry_date ) ) : '',
			'days_remaining' => $days_remaining,
			'auto_renew'     => $auto_renew,
			'manage_url'     => wc_get_endpoint_url( 'my-domains', $domain_id, wc_get_page_permalink( 'myaccount' ) ),
			'email'          => null,
			'email_heading'  => sprintf(
				/* translators: 1: domain name, 2: number of days */
				__( 'Domain Expiring: %1$s (%2$d days)', 'hostforge' ),
				$domain_name,
				$days_remaining
			),
		);

		$subject = sprintf(
			/* translators: 1: site name, 2: domain name, 3: days */
			__( '[%1$s] Domain %2$s expires in %3$d days', 'hostforge' ),
			get_bloginfo( 'name' ),
			$domain_name,
			$days_remaining
		);

		$html = hf_get_template_html( 'emails/domain-expiry-reminder.php', $args );

		if ( ! empty( $html ) ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $user->user_email, $subject, $html, $headers );
		}
	}

	/**
	 * Get all meta keys for the hf_domain CPT.
	 *
	 * @return array<string, string> Key => description.
	 */
	public static function get_domain_meta_keys(): array {
		return array(
			'_hf_domain_name'         => 'Full domain name (e.g. example.com)',
			'_hf_registrar_id'        => 'Registrar provider ID (e.g. namecheap)',
			'_hf_user_id'             => 'WordPress user ID (domain owner)',
			'_hf_order_id'            => 'WooCommerce order ID',
			'_hf_subscription_id'     => 'Subscription ID',
			'_hf_product_id'          => 'WooCommerce product ID',
			'_hf_status'              => 'pending, active, expired, transferred_away, cancelled',
			'_hf_registration_date'   => 'DateTime of original registration',
			'_hf_expiry_date'         => 'DateTime of expiry',
			'_hf_auto_renew'          => 'yes or no',
			'_hf_locked'              => 'yes or no (transfer lock)',
			'_hf_id_protection'       => 'yes or no (WHOIS privacy)',
			'_hf_nameservers'         => 'JSON array of nameservers',
			'_hf_registrar_domain_id' => 'Domain ID at registrar',
			'_hf_type'                => 'registration, transfer, existing',
			'_hf_epp_code'            => 'EPP/auth code for transfer (encrypted)',
			'_hf_whois_cache'         => 'JSON: cached WHOIS contact data',
			'_hf_whois_cache_time'    => 'Timestamp of WHOIS cache',
			'_hf_last_synced'         => 'DateTime of last sync with registrar',
			'_hf_linked_service_id'   => 'Related hf_service post ID (if any)',
		);
	}

	/**
	 * Get valid domain statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		$statuses = array(
			'pending'          => __( 'Pending', 'hostforge' ),
			'active'           => __( 'Active', 'hostforge' ),
			'expired'          => __( 'Expired', 'hostforge' ),
			'transferred_away' => __( 'Transferred Away', 'hostforge' ),
			'cancelled'        => __( 'Cancelled', 'hostforge' ),
		);

		/**
		 * Filters the available domain statuses.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $statuses Status slug => label pairs.
		 */
		return apply_filters( 'hostforge_domain_statuses', $statuses );
	}
}
