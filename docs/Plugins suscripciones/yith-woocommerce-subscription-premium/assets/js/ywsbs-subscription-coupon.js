/**
 * ywsbs-subscription.coupon.js
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 * @version 2.3.0
 */
/* global ywsbs_subscription_admin */
jQuery(function ($) {

	let fields = $('.ywsbs_limited_for_payments_field, .ywsbs_limited_for_payments_type_field');

	fields.hide().insertAfter('p.discount_type_field');

	$(document).on('change', '#discount_type', function () {
		let value = $(this).val();
		if ( 'recurring_percent' === value || 'recurring_fixed' === value ) {
			fields.show();

			$('input[name="ywsbs_limited_for_payments_type"]').change();

		} else {
			fields.hide();
		}
	});

	$(document).on('change', 'input[name="ywsbs_limited_for_payments_type"]', function () {
		let field = $('.ywsbs_limited_for_payments_field');
		if ( 'limited' === $(this).val() && $(this).is(':checked') ) {
			field.show();
		} else {
			field.hide();
		}
	});

	// init.
	$('#discount_type').change();
});