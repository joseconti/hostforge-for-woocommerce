<?php
/**
 * View box content template
 *
 * @package YITH\Subscription
 * @version 4.0.0
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<script type="text/template" id="tmpl-ywsbs-subscription-box-content">
	<div class="ywsbs-subscription-box-cart-content">
		<h3><?php echo esc_html__( 'Your box', 'yith-woocommerce-subscription' ); ?></h3>
		<# for( step of data ) { #>
		<div class="ywsbs-subscription-box-cart-step">
			<h4>{{step.label}}</h4>
			<ul class="ywsbs-box-products">
				<# for( item of step.items ) { #>
				<li class="ywsbs-box-product">
					<div class="ywsbs-box-product-image">
						<img src="{{item.image}}" width="60" height="60" alt="" />
					</div>
					<div class="ywsbs-box-product-data">
						<div class="ywsbs-box-product-name">{{item.name}}</div>
						<# if ( item.price ) { #>
							<div class="ywsbs-box-product-price">{{item.price}}</div>
						<# } #>
					</div>
				</li>
				<# } #>
			</ul>
		</div>
		<# } #>
	</div>
</script>
