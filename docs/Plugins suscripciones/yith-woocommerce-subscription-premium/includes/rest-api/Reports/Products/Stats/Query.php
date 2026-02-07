<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Query class
 *
 * @class   \YITH\Subscription\RestApi\Reports\Products\Stats\Query
 * @package YITH\Subscription
 * @since   2.3.0
 * @author YITH
 */


namespace YITH\Subscription\RestApi\Reports\Products\Stats;

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class Query
 */
class Query extends \WC_Object_Query {

	const REPORT_NAME = 'yith-ywsbs-report-products-stats';

	/**
	 * Valid fields for Product Stats report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get categories data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args    = apply_filters( 'yith_ywsbs_reports_products_stats_query_args', $this->get_query_vars() );
		$results = \WC_Data_Store::load( self::REPORT_NAME )->get_data( $args );
		return apply_filters( 'yith_ywsbs_reports_products_stats_select_query', $results, $args );
	}
}
