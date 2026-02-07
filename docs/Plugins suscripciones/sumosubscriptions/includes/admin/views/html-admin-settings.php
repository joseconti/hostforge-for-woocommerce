<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
			/**
			 * Get the tabs.
			 * 
			 * @param array $tabs
			 * @since 1.0
			 */
			$_tabs = apply_filters( 'sumosubscriptions_settings_tabs_array', array() );

			foreach ( $_tabs as $slug => $label ) {
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=sumosubs-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab == $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
			}

			/**
			 * Add settings tab.
			 * 
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_settings_tabs' );
			?>
		</h2>
		<?php
		switch ( $current_tab ) :
			default:
				/**
				 * Add section content based on tab requested.
				 * 
				 * @since 1.0
				 */
				do_action( 'sumosubscriptions_sections_' . $current_tab );

				/**
				 * Add settings content based on tab requested.
				 * 
				 * @since 1.0
				 */
				do_action( 'sumosubscriptions_settings_' . $current_tab );
				break;
		endswitch;
		?>
		<?php
		/**
		 * Need save button?
		 * 
		 * @since 1.0
		 */
		if ( apply_filters( 'sumosubscriptions_submit_' . $current_tab, true ) ) :
			?>
			<p class="submit">
				<?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
					<input name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'sumosubscriptions' ); ?>" />
				<?php endif; ?>
				<input type="hidden" name="subtab" id="last_tab" />
				<?php wp_nonce_field( 'sumosubscriptions-settings' ); ?>
			</p>
		<?php endif; ?>
	</form>
	<?php
	/**
	 * Need reset button?
	 * 
	 * @since 1.0
	 */
	if ( apply_filters( 'sumosubscriptions_reset_' . $current_tab, true ) ) :
		?>
		<form method="post" id="reset_mainform" action="" enctype="multipart/form-data" style="float: left; margin-top: -52px; margin-left: 159px;">
			<input name="reset" class="button-secondary" type="submit" value="<?php esc_attr_e( 'Reset', 'sumosubscriptions' ); ?>"/>
			<input name="reset_all" class="button-secondary" type="submit" value="<?php esc_attr_e( 'Reset All', 'sumosubscriptions' ); ?>"/>
			<?php wp_nonce_field( 'sumosubscriptions-reset_settings' ); ?>
		</form>    
	<?php endif; ?>
</div>
