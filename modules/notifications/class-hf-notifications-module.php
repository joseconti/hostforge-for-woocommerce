<?php
/**
 * Notifications Module.
 *
 * Registers WC_Email subclasses for all HostForge transactional emails
 * and provides a merge tags system for email templates.
 *
 * @package HostForge\Modules\Notifications
 */

namespace HostForge\Modules\Notifications;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Notifications_Module
 */
class HF_Notifications_Module extends HF_Module {

	use HF_Has_Logs;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'notifications';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Notifications', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Transactional email notifications for services, tickets, domains and provisioning events.', 'hostforge' );
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
		// Register WC_Email classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );

		// Initialize merge tags processor.
		$merge_tags = new HF_Merge_Tags();
		$merge_tags->init();

		// Hook into HostForge actions to trigger emails.
		add_action( 'hostforge_after_provision', array( $this, 'trigger_service_welcome' ), 10, 2 );
		add_action( 'hostforge_after_suspend', array( $this, 'trigger_service_suspended' ) );
		add_action( 'hostforge_after_unsuspend', array( $this, 'trigger_service_unsuspended' ) );
		add_action( 'hostforge_after_terminate', array( $this, 'trigger_service_terminated' ) );
		add_action( 'hostforge_provision_failed', array( $this, 'trigger_provision_failed' ), 10, 2 );
		add_action( 'hostforge_ticket_created', array( $this, 'trigger_ticket_new' ), 10, 2 );
		add_action( 'hostforge_ticket_replied', array( $this, 'trigger_ticket_reply' ), 10, 3 );
		add_action( 'hostforge_ticket_closed', array( $this, 'trigger_ticket_closed' ) );
		add_action( 'hostforge_domain_registered', array( $this, 'trigger_domain_registered' ), 10, 2 );
		add_action( 'hostforge_domain_expiring', array( $this, 'trigger_domain_expiry' ), 10, 2 );
	}

	/**
	 * Register WC_Email subclasses with WooCommerce.
	 *
	 * @param array $email_classes Existing email classes.
	 * @return array
	 */
	public function register_email_classes( array $email_classes ): array {
		$email_classes['HF_Email_Service_Welcome']     = new Emails\HF_Email_Service_Welcome();
		$email_classes['HF_Email_Service_Suspended']    = new Emails\HF_Email_Service_Suspended();
		$email_classes['HF_Email_Service_Unsuspended']  = new Emails\HF_Email_Service_Unsuspended();
		$email_classes['HF_Email_Service_Terminated']   = new Emails\HF_Email_Service_Terminated();
		$email_classes['HF_Email_Ticket_New_Staff']     = new Emails\HF_Email_Ticket_New_Staff();
		$email_classes['HF_Email_Ticket_Reply_Customer'] = new Emails\HF_Email_Ticket_Reply_Customer();
		$email_classes['HF_Email_Ticket_Reply_Staff']   = new Emails\HF_Email_Ticket_Reply_Staff();
		$email_classes['HF_Email_Ticket_Closed']        = new Emails\HF_Email_Ticket_Closed();
		$email_classes['HF_Email_Domain_Registered']    = new Emails\HF_Email_Domain_Registered();
		$email_classes['HF_Email_Domain_Expiry']        = new Emails\HF_Email_Domain_Expiry();
		$email_classes['HF_Email_Provision_Failed']     = new Emails\HF_Email_Provision_Failed();

		return $email_classes;
	}

	/**
	 * Trigger service welcome email.
	 *
	 * @param int   $service_id Service post ID.
	 * @param array $data       Account data.
	 * @return void
	 */
	public function trigger_service_welcome( int $service_id, array $data ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Service_Welcome'] ) ) {
			$emails['HF_Email_Service_Welcome']->trigger( $service_id, $data );
		}
	}

	/**
	 * Trigger service suspended email.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function trigger_service_suspended( int $service_id ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Service_Suspended'] ) ) {
			$emails['HF_Email_Service_Suspended']->trigger( $service_id );
		}
	}

	/**
	 * Trigger service unsuspended email.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function trigger_service_unsuspended( int $service_id ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Service_Unsuspended'] ) ) {
			$emails['HF_Email_Service_Unsuspended']->trigger( $service_id );
		}
	}

	/**
	 * Trigger service terminated email.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function trigger_service_terminated( int $service_id ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Service_Terminated'] ) ) {
			$emails['HF_Email_Service_Terminated']->trigger( $service_id );
		}
	}

	/**
	 * Trigger provision failed email.
	 *
	 * @param int    $service_id Service post ID.
	 * @param string $error      Error message.
	 * @return void
	 */
	public function trigger_provision_failed( int $service_id, string $error ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Provision_Failed'] ) ) {
			$emails['HF_Email_Provision_Failed']->trigger( $service_id, $error );
		}
	}

	/**
	 * Trigger new ticket email to staff.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @param int $user_id   Customer user ID.
	 * @return void
	 */
	public function trigger_ticket_new( int $ticket_id, int $user_id ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Ticket_New_Staff'] ) ) {
			$emails['HF_Email_Ticket_New_Staff']->trigger( $ticket_id, $user_id );
		}
	}

	/**
	 * Trigger ticket reply email.
	 *
	 * @param int  $ticket_id Ticket post ID.
	 * @param int  $reply_id  Reply comment ID.
	 * @param bool $is_staff  Whether the reply is from staff.
	 * @return void
	 */
	public function trigger_ticket_reply( int $ticket_id, int $reply_id, bool $is_staff ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( $is_staff && isset( $emails['HF_Email_Ticket_Reply_Customer'] ) ) {
			$emails['HF_Email_Ticket_Reply_Customer']->trigger( $ticket_id, $reply_id );
		}

		if ( ! $is_staff && isset( $emails['HF_Email_Ticket_Reply_Staff'] ) ) {
			$emails['HF_Email_Ticket_Reply_Staff']->trigger( $ticket_id, $reply_id );
		}
	}

	/**
	 * Trigger ticket closed email.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return void
	 */
	public function trigger_ticket_closed( int $ticket_id ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Ticket_Closed'] ) ) {
			$emails['HF_Email_Ticket_Closed']->trigger( $ticket_id );
		}
	}

	/**
	 * Trigger domain registered email.
	 *
	 * @param int    $domain_id   Domain post ID.
	 * @param string $domain_name Domain name.
	 * @return void
	 */
	public function trigger_domain_registered( int $domain_id, string $domain_name ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Domain_Registered'] ) ) {
			$emails['HF_Email_Domain_Registered']->trigger( $domain_id, $domain_name );
		}
	}

	/**
	 * Trigger domain expiry reminder email.
	 *
	 * @param int    $domain_id   Domain post ID.
	 * @param string $domain_name Domain name.
	 * @return void
	 */
	public function trigger_domain_expiry( int $domain_id, string $domain_name ): void {
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();

		if ( isset( $emails['HF_Email_Domain_Expiry'] ) ) {
			$emails['HF_Email_Domain_Expiry']->trigger( $domain_id, $domain_name );
		}
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array();
	}
}
