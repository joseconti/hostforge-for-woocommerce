<?php
/**
 * CSV Exporter.
 *
 * Exports report data as CSV downloads.
 *
 * @package HostForge\Modules\Reports
 */

namespace HostForge\Modules\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_CSV_Exporter
 */
class HF_CSV_Exporter {

	/**
	 * Data provider instance.
	 *
	 * @var HF_Report_Data
	 */
	private HF_Report_Data $data;

	/**
	 * Constructor.
	 *
	 * @param HF_Report_Data $data Data provider.
	 */
	public function __construct( HF_Report_Data $data ) {
		$this->data = $data;
	}

	/**
	 * Export data as CSV.
	 *
	 * @param string $type Export type (revenue, services, tickets, domains, servers).
	 * @return void
	 */
	public function export( string $type ): void {
		$filename = 'hostforge-' . $type . '-' . gmdate( 'Y-m-d' ) . '.csv';

		// Set headers for CSV download.
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			return;
		}

		// Add BOM for Excel compatibility.
		fwrite( $output, "\xEF\xBB\xBF" );

		switch ( $type ) {
			case 'revenue':
				$this->export_revenue( $output );
				break;
			case 'services':
				$this->export_services( $output );
				break;
			case 'tickets':
				$this->export_tickets( $output );
				break;
			case 'domains':
				$this->export_domains( $output );
				break;
			case 'servers':
				$this->export_servers( $output );
				break;
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export revenue data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_revenue( $output ): void {
		fputcsv( $output, array( 'Month', 'Revenue', 'Orders' ) );

		$data = $this->data->get_revenue_data(
			gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			gmdate( 'Y-m-d' )
		);

		foreach ( $data as $row ) {
			fputcsv( $output, array( $row->month, $row->revenue, $row->order_count ) );
		}
	}

	/**
	 * Export services data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_services( $output ): void {
		fputcsv( $output, array( 'ID', 'Domain', 'Username', 'Status', 'Server', 'Created' ) );

		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $services as $service ) {
			$server_id = (int) get_post_meta( $service->ID, '_hf_server_id', true );

			fputcsv(
				$output,
				array(
					$service->ID,
					get_post_meta( $service->ID, '_hf_domain', true ),
					get_post_meta( $service->ID, '_hf_panel_username', true ),
					get_post_meta( $service->ID, '_hf_status', true ),
					$server_id ? get_the_title( $server_id ) : '',
					get_the_date( 'Y-m-d', $service ),
				)
			);
		}
	}

	/**
	 * Export tickets data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_tickets( $output ): void {
		fputcsv( $output, array( 'ID', 'Subject', 'Status', 'Priority', 'Department', 'Customer', 'Created' ) );

		$tickets = get_posts(
			array(
				'post_type'      => 'hf_ticket',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $tickets as $ticket ) {
			$user_id = (int) get_post_meta( $ticket->ID, '_hf_client_user_id', true );
			$user    = get_userdata( $user_id );

			$departments = wp_get_object_terms( $ticket->ID, 'hf_department', array( 'fields' => 'names' ) );
			$department  = ! is_wp_error( $departments ) && ! empty( $departments ) ? $departments[0] : '';

			fputcsv(
				$output,
				array(
					$ticket->ID,
					$ticket->post_title,
					get_post_meta( $ticket->ID, '_hf_status', true ),
					get_post_meta( $ticket->ID, '_hf_priority', true ),
					$department,
					$user ? $user->display_name : '',
					get_the_date( 'Y-m-d', $ticket ),
				)
			);
		}
	}

	/**
	 * Export domains data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_domains( $output ): void {
		fputcsv( $output, array( 'ID', 'Domain', 'Registrar', 'Status', 'Expiry', 'Auto-Renew', 'Customer' ) );

		$domains = get_posts(
			array(
				'post_type'      => 'hf_domain',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $domains as $domain ) {
			$user_id = (int) get_post_meta( $domain->ID, '_hf_user_id', true );
			$user    = get_userdata( $user_id );

			fputcsv(
				$output,
				array(
					$domain->ID,
					get_post_meta( $domain->ID, '_hf_domain_name', true ),
					get_post_meta( $domain->ID, '_hf_registrar', true ),
					get_post_meta( $domain->ID, '_hf_status', true ),
					get_post_meta( $domain->ID, '_hf_expiry_date', true ),
					get_post_meta( $domain->ID, '_hf_auto_renew', true ),
					$user ? $user->display_name : '',
				)
			);
		}
	}

	/**
	 * Export servers data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_servers( $output ): void {
		fputcsv( $output, array( 'ID', 'Name', 'Hostname', 'Panel', 'Status', 'Accounts', 'Max Accounts', 'Usage %' ) );

		$servers = get_posts(
			array(
				'post_type'      => 'hf_server',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $servers as $server ) {
			$max     = (int) get_post_meta( $server->ID, '_hf_max_accounts', true );
			$current = (int) get_post_meta( $server->ID, '_hf_current_accounts', true );
			$usage   = $max > 0 ? round( ( $current / $max ) * 100, 1 ) : 0;

			fputcsv(
				$output,
				array(
					$server->ID,
					$server->post_title,
					get_post_meta( $server->ID, '_hf_hostname', true ),
					get_post_meta( $server->ID, '_hf_panel_type', true ),
					get_post_meta( $server->ID, '_hf_status', true ),
					$current,
					$max,
					$usage . '%',
				)
			);
		}
	}
}
