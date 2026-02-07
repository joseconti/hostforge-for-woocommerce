<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
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
$aswc_genaral_settings = apply_filters( 'aswc_others_settings_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="aswc-gen-section-form">
	<div class="aswc-section-wrap">
		<?php
				$aswc_general_html = $aswc_obj->aswc_plug_generate_html( $aswc_genaral_settings );
		echo esc_html( $aswc_general_html );
				wp_nonce_field( 'aswc-others-nonce', 'aswc-others-nonce-field' );
		?>
	</div>
</form>
