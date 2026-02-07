<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle Admin subscriptions exporter.
 * 
 * @class SUMOSubs_Admin_Subscriptions_Exporter
 */
class SUMOSubs_Admin_Subscriptions_Exporter {

	/**
	 * Exporter page.
	 *
	 * @var string
	 */
	public static $exporter_page = 'sumosubs-exporter';

	/**
	 * Init SUMOSubs_Admin_Subscriptions_Exporter.
	 */
	public static function init() {
		add_action( 'admin_head', __CLASS__ . '::hide_from_menus' );
		add_action( 'admin_init', __CLASS__ . '::download_export_file' );
	}

	/**
	 * Get exporter page url
	 *
	 * @return string
	 */
	public static function get_exporter_page_url() {
		return admin_url( 'admin.php?page=' . self::$exporter_page );
	}

	/**
	 * Get exported data download url
	 *
	 * @param mixed $generated_data
	 * @return string
	 */
	public static function get_download_url( $generated_data ) {
		self::set_transient( $generated_data );

		return add_query_arg( array(
			'nonce'  => wp_create_nonce( 'sumo-subscription-exporter' ),
			'action' => 'download_subscription_csv',
				), self::get_exporter_page_url() );
	}

	/**
	 * Save expoted data as transient
	 */
	public static function set_transient( $generated_data ) {
		delete_transient( 'sumosubsc_exported_data' );
		set_transient( 'sumosubsc_exported_data', is_array( $generated_data ) ? $generated_data : array(), 60 );
	}

	/**
	 * Export page UI.
	 */
	public static function render_exporter_html_fields() {
		include 'views/html-admin-subscription-exporter.php';
	}

	/**
	 * Generate the CSV file.
	 */
	public static function download_export_file() {
		if (
				! isset( $_GET[ 'action' ], $_GET[ 'nonce' ] ) ||
				! wp_verify_nonce( wc_clean( wp_unslash( $_GET[ 'nonce' ] ) ), 'sumo-subscription-exporter' ) ||
				'download_subscription_csv' !== wc_clean( wp_unslash( $_GET[ 'action' ] ) )
		) {
			return;
		}

		$field_datas = get_transient( 'sumosubsc_exported_data' );

		ob_end_clean();
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=sumo-subscriptions-' . date_i18n( 'Y-m-d H:i:s' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$handle        = fopen( 'php://output', 'w' );
		/**
		 * Get the export csv delimiter.
		 * 
		 * @since 1.0
		 */
		$delimiter     = apply_filters( 'sumosubscriptions_export_csv_delimiter', ',' );
		/**
		 * Get the export csv enclosure.
		 * 
		 * @since 1.0
		 */
		$enclosure     = apply_filters( 'sumosubscriptions_export_csv_enclosure', '"' );
		/**
		 * Get the export csv headings.
		 * 
		 * @since 1.0
		 */
		$field_heading = apply_filters( 'sumosubscriptions_export_csv_headings', array(
			'Subscription Status',
			'Subscription Number',
			'Subscribed Product with Qty',
			'Order Id',
			'Buyer Email',
			'Billing Name',
			'Subscription Start Date',
			'Subscription End Date',
			'Subscription Expired Date',
			'Trial End Date',
			'Last Payment Date',
			'Next Payment Date',
			'Renewal Count',
				) );

		/**
		 * Get the export csv field data.
		 * 
		 * @since 1.0
		 */
		$field_datas = apply_filters( 'sumosubscriptions_export_csv_field_datas', $field_datas );

		fputcsv( $handle, $field_heading, $delimiter, $enclosure ); // here you can change delimiter/enclosure
		if ( is_array( $field_datas ) && $field_datas ) {
			foreach ( $field_datas as $field_data ) {
				fputcsv( $handle, $field_data, $delimiter, $enclosure ); // here you can change delimiter/enclosure
			}
		}
		fclose( $handle );
		exit();
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public static function hide_from_menus() {
		global $submenu;

		if ( isset( $submenu[ 'sumosubscriptions' ] ) ) {
			foreach ( $submenu[ 'sumosubscriptions' ] as $key => $menu ) {
				if ( self::$exporter_page === $menu[ 2 ] ) {
					unset( $submenu[ 'sumosubscriptions' ][ $key ] );
				}
			}
		}
	}

	/**
	 * Generate data to export.
	 *
	 * @param mixed $data
	 * @return array
	 */
	public static function generate_data( $data ) {
		if ( ! $data ) {
			return array();
		}

		$subscription_id = $data;
		return apply_filters( 'sumosubscriptions_export_csv_data', array(
			sumo_display_subscription_status( $subscription_id, false ),
			sumo_display_subscription_ID( $subscription_id, false ),
			sumo_display_subscription_name( $subscription_id, true, false, false ),
			'#' . get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ),
			get_post_meta( $subscription_id, 'sumo_buyer_email', true ),
			sumosubs_get_billing_name( $subscription_id ),
			sumo_display_start_date( $subscription_id ),
			sumo_display_end_date( $subscription_id ),
			sumo_display_expired_date( $subscription_id ),
			sumo_display_trial_end_date( $subscription_id ),
			sumo_display_last_payment_date( $subscription_id ),
			sumo_display_next_due_date( $subscription_id ),
			sumosubs_get_renewed_count( $subscription_id ),
				), $subscription_id );
	}
}

SUMOSubs_Admin_Subscriptions_Exporter::init();
