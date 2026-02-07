<?php
/**
 * Subscription actions
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 *
 * @var YWSBS_Subscription $subscription Current Subscription.
 * @var string             $style How to show the actions
 * @var array              $pause Pause info.
 * @var array              $cancel Cancel info
 * @var array              $resume Resume info
 * @var string             $close_modal_button Label of button inside the modal.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( 'dropdown' === $style ) : ?>
	<div class="ywsbs-dropdown-wrapper">
		<a href="javascript:void(0)"><?php esc_html_e( 'Change status >', 'yith-woocommerce-subscription' ); ?></a>
		<div class="ywsbs-dropdown">
			<?php if ( $pause ) : ?>
				<div class="ywsbs-dropdown-item ywsbs-open-modal" data-target="action-pause-subscription">
					<?php echo wp_kses_post( wpautop( $pause['dropdown_text'] ) ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $resume ) : ?>
				<div class="ywsbs-dropdown-item ywsbs-open-modal" data-target="action-resume-subscription">
					<?php echo wp_kses_post( wpautop( $resume['dropdown_text'] ) ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $cancel ) : ?>
				<div class="ywsbs-dropdown-item ywsbs-open-modal" data-target="action-cancel-subscription">
					<?php echo wp_kses_post( wpautop( $cancel['dropdown_text'] ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php else : ?>
	<div class="ywsbs-change-status-buttons-wrapper">
		<?php if ( $pause ) : ?>
			<button class="ywsbs-open-modal" data-target="action-pause-subscription"><?php echo esc_html( $pause['button_label'] ); ?></button>
		<?php endif; ?>
		<?php if ( $resume ) : ?>
			<button class="ywsbs-open-modal" data-target="action-resume-subscription"><?php echo esc_html( $resume['button_label'] ); ?></button>
		<?php endif; ?>
		<?php if ( $cancel ) : ?>
			<button class="ywsbs-open-modal" data-target="action-cancel-subscription"><?php echo esc_html( $cancel['button_label'] ); ?></button>
		<?php endif; ?>
	</div>
<?php endif; ?>
