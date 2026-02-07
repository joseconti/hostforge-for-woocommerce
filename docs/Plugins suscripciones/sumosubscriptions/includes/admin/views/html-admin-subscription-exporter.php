<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div class="wrap woocommerce">
	<h1><?php esc_html_e( 'Export Subscriptions', 'sumosubscriptions' ) ; ?></h1>
	<div class="sumo-subscription-exporter-wrapper">
		<form class="subscription-exporter">
			<header>
				<h2><?php esc_html_e( 'Export Subscriptions to a CSV file', 'sumosubscriptions' ) ; ?></h2>
				<p><?php esc_html_e( 'This tool allows you to generate and download a CSV file containing a list of all subscriptions.', 'sumosubscriptions' ) ; ?></p>
			</header>
			<section>
				<table class="form-table exporter-options">
					<tbody>
						<tr>
							<th scope="row">
								<label for="subscription-statuses-exporter"><?php esc_html_e( 'Which subscription statuses should be exported?', 'sumosubscriptions' ) ; ?></label>
							</th>
							<td>
								<?php
								sumosubs_wc_enhanced_select_field( array(
									'name'    => 'subscription_statuses',
									'options' => sumo_get_subscription_statuses(),
								) ) ;
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="subscription-products-exporter"><?php esc_html_e( 'Which subscription products should be exported?', 'sumosubscriptions' ) ; ?></label>
							</th>
							<td>
								<?php
								sumosubs_wc_search_field( array(
									'class'       => 'wc-product-search',
									'name'        => 'subscription_products',
									'action'      => 'sumosubscription_json_search_subscription_products_and_variations',
									'placeholder' => __( 'Search for a subscription product&hellip;', 'sumosubscriptions' ),
								) ) ;
								?>
							</td>
						</tr>                       
						<tr>
							<th scope="row">
								<label for="subscription-buyers-exporter"><?php esc_html_e( 'Which subscription buyers should be exported?', 'sumosubscriptions' ) ; ?></label>
							</th>
							<td>
								<?php
								sumosubs_wc_search_field( array(
									'class'       => 'wc-product-search',
									'name'        => 'subscription_buyers',
									'action'      => 'sumosubscription_json_search_customers_by_email',
									'placeholder' => __( 'Search for a buyer email&hellip;', 'sumosubscriptions' ),
								) ) ;
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="subscription-from-to-date-exporter"><?php esc_html_e( 'Date Range', 'sumosubscriptions' ) ; ?></label>
							</th>
							<td>
								<input type="text" id="sumo_subscription_from_date" name="subscription_from_date" placeholder="<?php esc_attr_e( 'Select From Date', 'sumosubscriptions' ) ; ?>"  value="">
								<input type="text" id="sumo_subscription_to_date" name="subscription_to_date" placeholder="<?php esc_attr_e( 'Select To Date', 'sumosubscriptions' ) ; ?>"  value="">
							</td>
						</tr>
					</tbody>
				</table>
			</section>
			<div class="export-actions">
				<input type="hidden" id="exported_data" value=""/>
				<input type="button" class="exporter-button button button-primary" value="<?php esc_attr_e( 'Generate CSV', 'sumosubscriptions' ) ; ?>">
			</div>
		</form>
	</div>
</div>
