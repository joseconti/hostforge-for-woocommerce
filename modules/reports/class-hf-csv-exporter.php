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

		/**
		 * Filter the CSV export filename.
		 *
		 * @since 1.0.0
		 *
		 * @param string $filename Export filename.
		 * @param string $type     Export type (revenue, services, tickets, domains, servers).
		 */
		$filename = apply_filters( 'hostforge_csv_export_filename', $filename, $type );

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
		$headers = array( 'Month', 'Revenue', 'Orders' );

		/**
		 * Filter CSV column headers before output.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $headers Array of column header strings.
		 * @param string $type    Export type.
		 */
		$headers = apply_filters( 'hostforge_csv_export_headers', $headers, 'revenue' );

		fputcsv( $output, $headers );

		$data = $this->data->get_revenue_data(
			gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			gmdate( 'Y-m-d' )
		);

		$rows = array();
		foreach ( $data as $row ) {
			$rows[] = array( $row->month, $row->revenue, $row->order_count );
		}

		/**
		 * Filter CSV row data before output.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $rows Array of row arrays.
		 * @param string $type Export type.
		 */
		$rows = apply_filters( 'hostforge_csv_export_rows', $rows, 'revenue' );

		foreach ( $rows as $csv_row ) {
			fputcsv( $output, $csv_row );
		}
	}

	/**
	 * Export services data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_services( $output ): void {
		$headers = array( 'ID', 'Domain', 'Username', 'Status', 'Server', 'Created' );

		/** This filter is documented in this file, export_revenue method. */
		$headers = apply_filters( 'hostforge_csv_export_headers', $headers, 'services' );

		fputcsv( $output, $headers );

		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$rows = array();
		foreach ( $services as $service ) {
			$server_id = (int) get_post_meta( $service->ID, '_hf_server_id', true );

			$rows[] = array(
				$service->ID,
				get_post_meta( $service->ID, '_hf_domain', true ),
				get_post_meta( $service->ID, '_hf_panel_username', true ),
				get_post_meta( $service->ID, '_hf_status', true ),
				$server_id ? get_the_title( $server_id ) : '',
				get_the_date( 'Y-m-d', $service ),
			);
		}

		/** This filter is documented in this file, export_revenue method. */
		$rows = apply_filters( 'hostforge_csv_export_rows', $rows, 'services' );

		foreach ( $rows as $csv_row ) {
			fputcsv( $output, $csv_row );
		}
	}

	/**
	 * Export tickets data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_tickets( $output ): void {
		$headers = array( 'ID', 'Subject', 'Status', 'Priority', 'Department', 'Customer', 'Created' );

		/** This filter is documented in this file, export_revenue method. */
		$headers = apply_filters( 'hostforge_csv_export_headers', $headers, 'tickets' );

		fputcsv( $output, $headers );

		$tickets = get_posts(
			array(
				'post_type'      => 'hf_ticket',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$rows = array();
		foreach ( $tickets as $ticket ) {
			$user_id = (int) get_post_meta( $ticket->ID, '_hf_client_user_id', true );
			$user    = get_userdata( $user_id );

			$departments = wp_get_object_terms( $ticket->ID, 'hf_department', array( 'fields' => 'names' ) );
			$department  = ! is_wp_error( $departments ) && ! empty( $departments ) ? $departments[0] : '';

			$rows[] = array(
				$ticket->ID,
				$ticket->post_title,
				get_post_meta( $ticket->ID, '_hf_status', true ),
				get_post_meta( $ticket->ID, '_hf_priority', true ),
				$department,
				$user ? $user->display_name : '',
				get_the_date( 'Y-m-d', $ticket ),
			);
		}

		/** This filter is documented in this file, export_revenue method. */
		$rows = apply_filters( 'hostforge_csv_export_rows', $rows, 'tickets' );

		foreach ( $rows as $csv_row ) {
			fputcsv( $output, $csv_row );
		}
	}

	/**
	 * Export domains data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_domains( $output ): void {
		$headers = array( 'ID', 'Domain', 'Registrar', 'Status', 'Expiry', 'Auto-Renew', 'Customer' );

		/** This filter is documented in this file, export_revenue method. */
		$headers = apply_filters( 'hostforge_csv_export_headers', $headers, 'domains' );

		fputcsv( $output, $headers );

		$domains = get_posts(
			array(
				'post_type'      => 'hf_domain',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$rows = array();
		foreach ( $domains as $domain ) {
			$user_id = (int) get_post_meta( $domain->ID, '_hf_user_id', true );
			$user    = get_userdata( $user_id );

			$rows[] = array(
				$domain->ID,
				get_post_meta( $domain->ID, '_hf_domain_name', true ),
				get_post_meta( $domain->ID, '_hf_registrar', true ),
				get_post_meta( $domain->ID, '_hf_status', true ),
				get_post_meta( $domain->ID, '_hf_expiry_date', true ),
				get_post_meta( $domain->ID, '_hf_auto_renew', true ),
				$user ? $user->display_name : '',
			);
		}

		/** This filter is documented in this file, export_revenue method. */
		$rows = apply_filters( 'hostforge_csv_export_rows', $rows, 'domains' );

		foreach ( $rows as $csv_row ) {
			fputcsv( $output, $csv_row );
		}
	}

	/**
	 * Export servers data.
	 *
	 * @param resource $output File handle.
	 * @return void
	 */
	private function export_servers( $output ): void {
		$headers = array( 'ID', 'Name', 'Hostname', 'Panel', 'Status', 'Accounts', 'Max Accounts', 'Usage %' );

		/** This filter is documented in this file, export_revenue method. */
		$headers = apply_filters( 'hostforge_csv_export_headers', $headers, 'servers' );

		fputcsv( $output, $headers );

		$servers = get_posts(
			array(
				'post_type'      => 'hf_server',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		$rows = array();
		foreach ( $servers as $server ) {
			$max     = (int) get_post_meta( $server->ID, '_hf_max_accounts', true );
			$current = (int) get_post_meta( $server->ID, '_hf_current_accounts', true );
			$usage   = $max > 0 ? round( ( $current / $max ) * 100, 1 ) : 0;

			$rows[] = array(
				$server->ID,
				$server->post_title,
				get_post_meta( $server->ID, '_hf_hostname', true ),
				get_post_meta( $server->ID, '_hf_panel_type', true ),
				get_post_meta( $server->ID, '_hf_status', true ),
				$current,
				$max,
				$usage . '%',
			);
		}

		/** This filter is documented in this file, export_revenue method. */
		$rows = apply_filters( 'hostforge_csv_export_rows', $rows, 'servers' );

		foreach ( $rows as $csv_row ) {
			fputcsv( $output, $csv_row );
		}
	}
}
