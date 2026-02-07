<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for API tab.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $aswc_obj;
$aswc_subscription_box_settings = apply_filters( 'aswc_subscription_box_settings_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="aswc-gen-section-form">
	<div class="aswc-secion-wrap">
		<?php
		$aswc_api_html = $aswc_obj->aswc_plug_generate_html( $aswc_subscription_box_settings );
		echo esc_html( $aswc_api_html );
		wp_nonce_field( 'aswc-subscription-box-nonce', 'aswc-subscription-box-nonce-field' );
		?>
	</div>
</form>
