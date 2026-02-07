<?php
/**
 * Empty activities list table template.
 *
 * @since 3.0.0
 * @package YITH\Subscription
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div class="wrap ywsbs_subscription_activities">
	<div class="ywsbs-admin-no-posts">
		<div class="ywsbs-admin-no-posts-container">
			<div class="ywsbs-admin-no-posts-logo">
				<img width="80" src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL . '/images/activities.svg' ); ?>">
			</div>
			<div class="ywsbs-admin-no-posts-text">
				<span>
					<strong><?php echo esc_html_x( 'You don\'t have any Subscription Activity yet.', 'Text showed when the list of email is empty.', 'yith-woocommerce-subscription' ); ?></strong>
				</span>
				<p><?php echo esc_html_x( 'But don\'t worry, your subscription activities will appear here soon!', 'Text showed when the list of email is empty.', 'yith-woocommerce-subscription' ); ?></p>
			</div>
		</div>
	</div>
</div>