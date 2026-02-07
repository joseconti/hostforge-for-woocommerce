<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $aswc_obj;
// Add filed above susbcription list.
$aswc_template_settings = apply_filters( 'sfw_template_settings_array', array() );
?>
<!--  template file for admin settings. -->
<div class="aswc-section-wrap">
	<?php

	require_once ASWC_DIR_PATH . 'admin/partials/class-aswc-admin-subscription-list.php';
	?>
</div>
