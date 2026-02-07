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
?>

<!-- SUBSCRIPTION MODAL -->
<?php if ( $pause ) : ?>
	<script type="text/template" id="tmpl-ywsbs-action-pause-subscription">
		<div class="ywsbs-modal-icon"><img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL . '/images/pause-subscription.svg' ); ?>" alt="" /></div>
		<div class="ywsbs-content-text"><?php echo wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $pause['modal_text'] ) ) ) ); //phpcs:ignore ?></div>

		<?php if ( ! empty( $pause['modal_button_label'] ) ) : ?>
			<div class="ywsbs-action-button-wrap">
				<button class="button btn ywsbs-action-button" data-action="pause" data-id="<?php echo esc_attr( $subscription->get_id() ); ?>" data-nonce="<?php echo esc_attr( $pause['nonce'] ); ?>"><?php echo esc_html( $pause['modal_button_label'] ); ?></button>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $pause['close_modal_button'] ) ) : ?>
			<a href="javascript:void(0)" class="close"><?php echo esc_html( $pause['close_modal_button'] ); ?></a>
		<?php endif; ?>
	</script>
<?php endif; ?>

<?php if ( $resume ) : ?>
	<script type="text/template" id="tmpl-ywsbs-action-resume-subscription">
		<div class="ywsbs-modal-icon"><img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL . '/images/resume-subscription.svg' ); ?>" alt="" /></div>
		<div class="ywsbs-content-text"><?php echo wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $resume['modal_text'] ) ) ) ); //phpcs:ignore ?></div>

		<?php if ( ! empty( $resume['modal_button_label'] ) ) : ?>
			<div class="ywsbs-action-button-wrap">
				<button class="button btn ywsbs-action-button" data-action="resume" data-id="<?php echo esc_attr( $subscription->get_id() ); ?>" data-nonce="<?php echo esc_attr( $resume['nonce'] ); ?>"><?php echo esc_html( $resume['modal_button_label'] ); ?></button>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $resume['close_modal_button'] ) ) : ?>
			<div class="close-modal-wrap"><a href="javascript:void(0)" class="close"><?php echo esc_html( $resume['close_modal_button'] ); ?></a></div>
		<?php endif; ?>
	</script>
<?php endif; ?>

<?php if ( $cancel ) : ?>
	<script type="text/template" id="tmpl-ywsbs-action-cancel-subscription">
		<div class="ywsbs-modal-icon"><img src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL . '/images/delete-subscription.svg' ); ?>" alt=""/></div>
		<div class="ywsbs-content-text"><?php echo wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $cancel['modal_text'] ) ) ) ); //phpcs:ignore ?></div>

		<?php if ( ! empty( $cancel['modal_button_label'] ) ) : ?>
			<div class="ywsbs-action-button-wrap">
				<button class="button btn ywsbs-action-button" data-action="cancel" data-id="<?php echo esc_attr( $subscription->get_id() ); ?>" data-nonce="<?php echo esc_attr( $cancel['nonce'] ); ?>"><?php echo esc_html( $cancel['modal_button_label'] ); ?></button>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $cancel['close_modal_button'] ) ) : ?>
			<div class="close-modal-wrap"><a href="javascript:void(0)" class="close"><?php echo esc_html( $cancel['close_modal_button'] ); ?></a></div>
		<?php endif; ?>
	</script>
<?php endif; ?>
