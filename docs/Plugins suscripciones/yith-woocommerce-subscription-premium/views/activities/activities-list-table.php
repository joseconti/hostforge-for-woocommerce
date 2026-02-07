<?php
/**
 * Subscription Activities list table
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>
<div class="ywsbs_subscription_activities yith-plugin-ui--classic-wp-list-style">
	<form id="posts-filter" method="GET">
		<input type="hidden" name="page" value="yith_woocommerce_subscription" />
		<input type="hidden" name="tab" value="subscription" />
		<input type="hidden" name="sub_tab" value="subscription-activities" />
		<?php $this->cpt_obj_activities->search_box( __( 'Search', 'yith-woocommerce-subscription' ), 'search_id' ); ?>
		<?php
		$this->cpt_obj_activities->prepare_items();
		$this->cpt_obj_activities->display();
		?>
	</form>
</div>
