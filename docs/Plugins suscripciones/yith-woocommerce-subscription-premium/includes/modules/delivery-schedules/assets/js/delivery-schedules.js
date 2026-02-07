/**
 * Delivery schedules admin JS
 *
 * @since 3.0.0
 */

(function ($) {
	"use strict";

	if ( typeof ywsbsDeliverySchedulest === 'undefined' ) {
		return false;
	}

	var dataTable,
		subscriptionID = $('input#post_ID').val(); // If subscriptionID is set, we are on single subscription page.

	// Customize url to remove notice query args
	var locationUrl = window.location.href;
	if ( -1 !== locationUrl.indexOf( 'bulk-delivery-status-updated' ) ) {
		window.history.pushState(
			{},
			document.title,
			locationUrl.replace( '&bulk-delivery-status-updated=1', '' )
		);
	}

	$(document).on('change', '.delivery-status .status-change', function () {
		var value = $(this).val(),
			wrapper = $(this).parent(),
			id = wrapper.data('id');

		var data = {
			action: ywsbsDeliverySchedulest.deliveryAction,
			security: ywsbsDeliverySchedulest.deliveryNonce,
			deliveryID: id,
			status: value,
			subscriptionID: subscriptionID
		};

		if ( 'shipped' === value ) {
			yith.ui.confirm(
				{
					title: ywsbsDeliverySchedulest.confirmModalTitle,
					message: ywsbsDeliverySchedulest.confirmModalMessage,
					closeAfterConfirm: true,
					onCancel: function () {

					},
					onConfirm: function () {
						updateStatus(data, wrapper);
					},
				}
			);
		} else {
			updateStatus(data, wrapper);
		}

	});

	function updateStatus(data, wrapper) {
		$.ajax({
			url: ywsbsDeliverySchedulest.ajaxurl,
			data: data,
			type: 'POST',
			dataType: 'json',
			beforeSend: function () {
				wrapper.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					}
				});
			},
			success: function (response) {

				wrapper.unblock();

				if ( response.success ) {

					wrapper.find('.status-label').text(response.data.statusLabel);
					wrapper.find('.status-label').attr( 'data-value', response.data.status);
					wrapper.find('.status-change').val(response.data.status);
					wrapper.closest('tr').find('.sent_on').text(response.data.sentOn);

					var orderFilter = $('#ywsbs-delivery-schedules-status'),
						order = orderFilter.length ? orderFilter.val() : false;

					if ( order ) {
						orderFilter.val(order).change();
					}
				}
			}
		});
	}
	function initTable() {
		if ( typeof jQuery.fn.DataTable === 'undefined' ) {
			return false;
		}

		// Destroy old instance if any.
		if ( typeof dataTable == 'object' ) {
			dataTable.destroy();
		}

		dataTable = $(document).find('.ywsbs-delivery-schedules-table').DataTable({
			"searching": true,
			"ordering": false,
			language: {
				"lengthMenu": ywsbsDeliverySchedulest.datatable_lengthMenu + " _MENU_",
			},
		});

		$(document).on('change', '#ywsbs-delivery-schedules-status', function () {
			dataTable.columns(1).search(this.value).draw();
		});
	}
	initTable();

	$(document).on('change', '#ywsbs_delivery_default_schedule_delivery_period', function () {
		var t = $(this),
			current_period = t.val(),
			wrapper = $( '#ywsbs_delivery_default_schedule2' );

		wrapper.find( '[class*="show-if-"]' ).hide();

		if ( 'days' === current_period ) {
			wrapper.closest( '.yith-plugin-fw__panel__section' ).hide();
		} else {
			wrapper.closest( '.yith-plugin-fw__panel__section' ).show();
			wrapper.find('.show-if-' + current_period).show();
		}
	});

	$('#ywsbs_delivery_default_schedule_delivery_period').change();

})(jQuery);