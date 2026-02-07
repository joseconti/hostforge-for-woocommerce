
jQuery( function( $ ){
	jQuery(document).on( 'click', '#aswc_cancel_recurring_multisafepay', function( e ) {
          e.preventDefault();
		let subs_id = jQuery(this).data('id');
		jQuery.ajax({
			url: aswc_public_param.ajaxurl,
			type: 'POST',
			data: {
				action : 'aswc_cancel_recurring_payment',
				id : subs_id,
				nonce : aswc_public_param.nonce,
			},
			success: function(data) {
				location.reload();
			}
		});
	});
});