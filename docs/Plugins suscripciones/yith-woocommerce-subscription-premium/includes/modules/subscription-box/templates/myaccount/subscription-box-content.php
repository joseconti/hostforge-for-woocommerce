<?php
/**
 * Subscription box content section in my account
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/subscription-box-content.php.
 *
 * @since   4.0.0
 * @package YITH\Subscription
 * @var YWSBS_Subscription $subscription        Current subscription.
 * @var array              $box_content         The box content.
 * @var string             $delivery_date       The next delivery date.
 * @var string             $title               The section title.
 * @var string             $payment_due_date    The subscription payment due date.
 * @var boolean            $edit_enabled        True if edit is enabled, false otherwise.
 * @var boolean            $is_edit             True if is edit section, false otherwise.
 * @var boolean            $box_editable        True if box is editable, false otherwise.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div class="ywsbs-box ywsbs-subscription-box <?php echo $box_editable ? 'box-editable' : ''; ?>">
	<div class="ywsbs-subscription-box-content">
		<h3 class="ywsbs-subscription-box-title">
			<?php echo esc_html( $title ); ?>
			<?php if ( $delivery_date ) : ?>
				<span> - 
				<?php
					// Translators: %s is the next delivery date.
					echo esc_html( sprintf( __( 'Shipping on %s', 'yith-woocommerce-subscription' ), $delivery_date ) );
				?>
				</span>
			<?php endif; ?>
		</h3>
		<?php foreach ( $box_content as $step ) : ?>
			<div class="ywsbs-subscription-box-items">
				<h4><?php echo esc_html( $step['label'] ); ?></h4>
				<?php foreach ( $step['items'] as $item ) : ?>
					<div class="ywsbs-subscription-box-item">
						<span class="ywsbs-subscription-box-item__image">
							<img src="<?php echo esc_url( $item['image'] ); ?>" width="60" height="60" alt=""/>
						</span>
						<span class="ywsbs-subscription-box-item__name">
							<?php echo $item['name']; // phpcs:ignore ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $box_editable ) : ?>
		<div class="ywsbs-subscription-box-edit <?php echo ! $edit_enabled ? 'edit-locked' : ''; ?>">
			<span class="ywsbs-subscription-box-edit__icon">
				<img src="<?php echo esc_url( YWSBS_SUBSCRIPTION_BOX_MODULE_URL ); ?>assets/images/checklist.svg" width="60" alt=""/>
			</span>
			<h4 class="ywsbs-subscription-box-edit__title">
				<?php echo $edit_enabled ? esc_html__( 'Edit box content', 'yith-woocommerce-subscription' ) : esc_html__( 'Box editing not available', 'yith-woocommerce-subscription' ); ?>
			</h4>
			<p class="ywsbs-subscription-box-edit__desc">
				<?php
				if ( $edit_enabled ) :
					echo esc_html__( 'Feel free to customize the box content by adding, removing, or replacing items.', 'yith-woocommerce-subscription' );
				else :
					echo esc_html__( 'Unfortunately, it is not possible to edit the content of this box.', 'yith-woocommerce-subscription' );
				endif;
				?>
			</p>
			<?php if ( $edit_enabled ) : ?>
				<span class="ywsbs-subscription-box-edit__trigger">
					<button id="ywsbs-box-setup-trigger"><?php echo esc_html__( 'Edit box', 'yith-woocommerce-subscription' ); ?></button>
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
