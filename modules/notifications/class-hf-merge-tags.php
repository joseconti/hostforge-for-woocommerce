<?php
/**
 * Merge Tags System.
 *
 * Processes merge tags in email subjects and content.
 * Tags like {customer_name}, {service_domain}, {ticket_id} etc.
 * are replaced with actual values from the context.
 *
 * @package HostForge\Modules\Notifications
 */

namespace HostForge\Modules\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Merge_Tags
 */
class HF_Merge_Tags {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'hostforge_email_merge_tags', array( $this, 'register_default_tags' ), 10, 2 );
	}

	/**
	 * Register default merge tags.
	 *
	 * @param array  $tags    Current merge tags.
	 * @param string $context Context identifier (e.g. 'service', 'ticket', 'domain').
	 * @return array
	 */
	public function register_default_tags( array $tags, string $context ): array {
		// Global tags.
		$tags['{site_name}']   = get_bloginfo( 'name' );
		$tags['{site_url}']    = home_url();
		$tags['{admin_email}'] = get_option( 'admin_email' );

		$company_name           = get_option( 'hf_company_name', '' );
		$tags['{company_name}'] = ! empty( $company_name ) ? $company_name : get_bloginfo( 'name' );

		return $tags;
	}

	/**
	 * Process merge tags in a string.
	 *
	 * @param string $content Content with merge tags.
	 * @param array  $tags    Tag => value pairs.
	 * @return string Processed content.
	 */
	public static function process( string $content, array $tags ): string {
		if ( empty( $tags ) ) {
			return $content;
		}

		foreach ( $tags as $tag => $value ) {
			/**
			 * Filter an individual merge tag value before replacement.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value   The replacement value for the tag.
			 * @param string $tag     The merge tag key (e.g. '{customer_name}').
			 * @param string $content The full content string being processed.
			 */
			$tags[ $tag ] = apply_filters( 'hostforge_merge_tag_value', $value, $tag, $content );
		}

		return str_replace(
			array_keys( $tags ),
			array_values( $tags ),
			$content
		);
	}

	/**
	 * Get merge tags for a service context.
	 *
	 * @param int   $service_id Service post ID.
	 * @param array $extra      Extra data.
	 * @return array Tag => value pairs.
	 */
	public static function get_service_tags( int $service_id, array $extra = array() ): array {
		$tags = array();

		$user_id = (int) get_post_meta( $service_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		$tags['{customer_name}']    = $user ? $user->display_name : '';
		$tags['{customer_email}']   = $user ? $user->user_email : '';
		$tags['{service_id}']       = (string) $service_id;
		$tags['{service_domain}']   = get_post_meta( $service_id, '_hf_domain', true );
		$tags['{service_username}'] = get_post_meta( $service_id, '_hf_panel_username', true );
		$tags['{service_status}']   = get_post_meta( $service_id, '_hf_status', true );
		$tags['{panel_type}']       = get_post_meta( $service_id, '_hf_panel_type', true );

		// Server info.
		$server_id = (int) get_post_meta( $service_id, '_hf_server_id', true );

		if ( $server_id ) {
			$hostname = get_post_meta( $server_id, '_hf_hostname', true );
			$port     = get_post_meta( $server_id, '_hf_port', true );
			$protocol = get_post_meta( $server_id, '_hf_protocol', true );

			$panel_url = '';
			if ( ! empty( $hostname ) ) {
				$panel_url = ( ! empty( $protocol ) ? $protocol : 'https' ) . '://' . $hostname;
				if ( ! empty( $port ) ) {
					$panel_url .= ':' . $port;
				}
			}

			$tags['{panel_url}']       = $panel_url;
			$tags['{server_name}']     = get_the_title( $server_id );
			$tags['{server_hostname}'] = ! empty( $hostname ) ? $hostname : '';
		}

		// Extra data (password, etc.).
		if ( ! empty( $extra['password'] ) ) {
			$tags['{service_password}'] = $extra['password'];
		}

		if ( ! empty( $extra['package'] ) ) {
			$tags['{service_package}'] = $extra['package'];
		}

		/**
		 * Filter merge tags for service emails.
		 *
		 * @param array $tags       Tag => value pairs.
		 * @param int   $service_id Service post ID.
		 */
		$tags = apply_filters( 'hostforge_email_merge_tags', $tags, 'service' );

		return $tags;
	}

	/**
	 * Get merge tags for a ticket context.
	 *
	 * @param int $ticket_id Ticket post ID.
	 * @return array Tag => value pairs.
	 */
	public static function get_ticket_tags( int $ticket_id ): array {
		$tags = array();

		$ticket  = get_post( $ticket_id );
		$user_id = (int) get_post_meta( $ticket_id, '_hf_client_user_id', true );
		$user    = get_userdata( $user_id );

		$tags['{customer_name}']   = $user ? $user->display_name : '';
		$tags['{customer_email}']  = $user ? $user->user_email : '';
		$tags['{ticket_id}']       = (string) $ticket_id;
		$tags['{ticket_subject}']  = $ticket ? $ticket->post_title : '';
		$tags['{ticket_status}']   = get_post_meta( $ticket_id, '_hf_status', true );
		$tags['{ticket_priority}'] = get_post_meta( $ticket_id, '_hf_priority', true );

		// Department.
		$departments                 = wp_get_object_terms( $ticket_id, 'hf_department', array( 'fields' => 'names' ) );
		$tags['{ticket_department}'] = ! is_wp_error( $departments ) && ! empty( $departments ) ? $departments[0] : '';

		// Ticket URL for customer.
		$tags['{ticket_url}'] = wc_get_account_endpoint_url( 'support-tickets' ) . $ticket_id . '/';

		/**
		 * Filter merge tags for ticket emails.
		 *
		 * @param array $tags      Tag => value pairs.
		 * @param int   $ticket_id Ticket post ID.
		 */
		$tags = apply_filters( 'hostforge_email_merge_tags', $tags, 'ticket' );

		return $tags;
	}

	/**
	 * Get merge tags for a domain context.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return array Tag => value pairs.
	 */
	public static function get_domain_tags( int $domain_id ): array {
		$tags = array();

		$user_id = (int) get_post_meta( $domain_id, '_hf_user_id', true );
		$user    = get_userdata( $user_id );

		$tags['{customer_name}']     = $user ? $user->display_name : '';
		$tags['{customer_email}']    = $user ? $user->user_email : '';
		$tags['{domain_id}']         = (string) $domain_id;
		$tags['{domain_name}']       = get_post_meta( $domain_id, '_hf_domain_name', true );
		$tags['{domain_status}']     = get_post_meta( $domain_id, '_hf_status', true );
		$tags['{domain_registrar}']  = get_post_meta( $domain_id, '_hf_registrar', true );
		$tags['{domain_expiry}']     = get_post_meta( $domain_id, '_hf_expiry_date', true );
		$tags['{domain_auto_renew}'] = 'yes' === get_post_meta( $domain_id, '_hf_auto_renew', true )
			? __( 'Enabled', 'hostforge' )
			: __( 'Disabled', 'hostforge' );

		// Domain URL for customer.
		$tags['{domain_url}'] = wc_get_account_endpoint_url( 'my-domains' ) . $domain_id . '/';

		/**
		 * Filter merge tags for domain emails.
		 *
		 * @param array $tags      Tag => value pairs.
		 * @param int   $domain_id Domain post ID.
		 */
		$tags = apply_filters( 'hostforge_email_merge_tags', $tags, 'domain' );

		return $tags;
	}

	/**
	 * Get a formatted list of all available merge tags for documentation.
	 *
	 * @return array
	 */
	public static function get_available_tags(): array {
		return array(
			__( 'Global', 'hostforge' )   => array(
				'{site_name}'    => __( 'Site name', 'hostforge' ),
				'{site_url}'     => __( 'Site URL', 'hostforge' ),
				'{company_name}' => __( 'Company name', 'hostforge' ),
				'{admin_email}'  => __( 'Admin email', 'hostforge' ),
			),
			__( 'Customer', 'hostforge' ) => array(
				'{customer_name}'  => __( 'Customer display name', 'hostforge' ),
				'{customer_email}' => __( 'Customer email', 'hostforge' ),
			),
			__( 'Service', 'hostforge' )  => array(
				'{service_id}'       => __( 'Service ID', 'hostforge' ),
				'{service_domain}'   => __( 'Service domain', 'hostforge' ),
				'{service_username}' => __( 'Panel username', 'hostforge' ),
				'{service_password}' => __( 'Panel password', 'hostforge' ),
				'{service_package}'  => __( 'Hosting package', 'hostforge' ),
				'{service_status}'   => __( 'Service status', 'hostforge' ),
				'{panel_type}'       => __( 'Panel type', 'hostforge' ),
				'{panel_url}'        => __( 'Panel URL', 'hostforge' ),
				'{server_name}'      => __( 'Server name', 'hostforge' ),
			),
			__( 'Ticket', 'hostforge' )   => array(
				'{ticket_id}'         => __( 'Ticket ID', 'hostforge' ),
				'{ticket_subject}'    => __( 'Ticket subject', 'hostforge' ),
				'{ticket_status}'     => __( 'Ticket status', 'hostforge' ),
				'{ticket_priority}'   => __( 'Ticket priority', 'hostforge' ),
				'{ticket_department}' => __( 'Ticket department', 'hostforge' ),
				'{ticket_url}'        => __( 'Ticket URL', 'hostforge' ),
			),
			__( 'Domain', 'hostforge' )   => array(
				'{domain_name}'       => __( 'Domain name', 'hostforge' ),
				'{domain_status}'     => __( 'Domain status', 'hostforge' ),
				'{domain_registrar}'  => __( 'Registrar', 'hostforge' ),
				'{domain_expiry}'     => __( 'Expiry date', 'hostforge' ),
				'{domain_auto_renew}' => __( 'Auto-renew status', 'hostforge' ),
				'{domain_url}'        => __( 'Domain management URL', 'hostforge' ),
			),
		);
	}
}
