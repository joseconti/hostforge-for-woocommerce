<?php
/**
 * Admin modules list
 *
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 * @var array $modules A list of available modules.
 */

defined( 'YITH_YWSBS_VERSION' ) || exit;

?>

<div id="modules-container">
	<?php foreach ( $modules as $module_id => $module ) : ?>
	<div class="module" data-module="<?php echo esc_attr( $module_id ); ?>">
		<header>
			<?php if ( isset( $module['name'] ) ) : ?>
				<h3><?php echo esc_html( $module['name'] ); ?></h3>
			<?php endif; ?>
		</header>
		<?php if ( isset( $module['description'] ) ) : ?>
			<div class="module-description"><?php echo wp_kses_post( $module['description'] ); ?></div>
		<?php endif; ?>
		<div class="module-activation">
			<label for="<?php echo esc_attr( $module_id ); ?>_active">
				<?php echo esc_html__( 'Enable module', 'yith-woocommerce-subscription' ); ?>
			</label>
			<?php
			yith_plugin_fw_get_field(
				array(
					'id'                => $module_id . '_active',
					'name'              => $module_id . '_active',
					'type'              => 'onoff',
					'default'           => 'no',
					'class'             => 'on-off-module',
					'value'             => YWSBS_Subscription_Modules::is_module_active( $module_id ) ? 'yes' : 'no',
					'custom_attributes' => array( 'data-module' => $module_id ),
				),
				true,
				false
			);
			?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
