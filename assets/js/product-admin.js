/**
 * HostForge Product Admin JS.
 *
 * Handles show/hide of HostForge product data tabs in the WooCommerce product editor.
 *
 * @package HostForge
 */

(function ($) {
	'use strict';

	/**
	 * HostForge product types.
	 */
	var hfTypes = [
		'hf_shared_hosting',
		'hf_reseller_hosting',
		'hf_vps_server',
		'hf_dedicated_server',
		'hf_domain',
		'hf_ssl_certificate',
		'hf_software_license'
	];

	/**
	 * Toggle visibility of elements based on product type.
	 */
	function toggleProductType() {
		var productType = $('#product-type').val();

		// Hide all HF panels initially.
		hfTypes.forEach(function (type) {
			$('.show_if_' + type).hide();
		});

		// Show relevant ones.
		if (productType && productType.indexOf('hf_') === 0) {
			$('.show_if_' + productType).show();

			// Hide general tab options not relevant to hosting products.
			$('.show_if_simple').hide();
			$('.show_if_external').hide();
			$('.show_if_grouped').hide();

			// Show general tab (for pricing).
			$('.options_group.pricing').show();
			$('#general_product_data').show();

			// Hosting products are always virtual.
			$('#_virtual').prop('checked', true).closest('.form-field').hide();
			$('#_downloadable').prop('checked', false).closest('.form-field').hide();
		}
	}

	$(document).ready(function () {
		// Initial toggle.
		toggleProductType();

		// On product type change.
		$('#product-type').on('change', toggleProductType);
	});

})(jQuery);
