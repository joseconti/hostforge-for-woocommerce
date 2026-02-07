<?php
/**
 * Subscription Delivery Schedules list table
 *
 * @package YITH\Subscription
 * @since   2.2.0
 * @author YITH
 * @var YWSBS_Subscription_Delivery_Schedules_List_Table $table The table instance.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>
<div class="yith-plugin-ui--classic-wp-list-style yith-plugin-ui--wp-list-auto-h-scroll">
	<form method="get" style="padding: 0;">
		<input type="hidden" name="page" value="yith_woocommerce_subscription"/>
		<input type="hidden" name="tab" value="delivery"/>

		<?php if ( isset( $_GET['bulk-delivery-status-updated'] ) ) : ?>
			<div id="ywsbs-delivery-schedules-table-notice">
				<p><?php echo esc_html__( 'Delivery schedules status updated.', 'yith-woocommerce-subscription' ); ?></p>
			</div>
		<?php endif;

		$table->display();
		?>
	</form>
</div>
