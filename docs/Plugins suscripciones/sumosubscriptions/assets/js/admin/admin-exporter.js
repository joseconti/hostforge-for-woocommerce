/* global sumosubs_admin_exporter_params */

jQuery( function ( $ ) {

	// sumosubs_admin_exporter_params is required to continue, ensure the object exists
	if ( typeof sumosubs_admin_exporter_params === 'undefined' ) {
		return false;
	}

	var is_blocked = function ( $node ) {
		return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
	};

	/**
	 * Block a node visually for processing.
	 *
	 * @param {JQuery Object} $node
	 */
	var block = function ( $node ) {
		if ( !is_blocked( $node ) ) {
			$node.addClass( 'processing' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		}
	};

	/**
	 * Unblock a node after processing is complete.
	 *
	 * @param {JQuery Object} $node
	 */
	var unblock = function ( $node ) {
		$node.removeClass( 'processing' ).unblock();
	};

	var $exporter_div = $( '.sumo-subscription-exporter-wrapper' ).closest( 'div' );

	var subscription_exporter = {
		init: function () {
			this.importDatepicker();

			$( document ).on( 'click', 'form.subscription-exporter > div.export-actions > input', this.export );
		},
		export: function ( evt ) {
			$( evt.currentTarget ).closest( 'form' ).find( '#exported_data' ).val( '' );

			$.blockUI.defaults.overlayCSS.cursor = 'wait';
			block( $exporter_div );

			$.ajax( {
				type: 'POST',
				url: sumosubs_admin_exporter_params.wp_ajax_url,
				data: {
					action: 'sumosubscription_init_data_export',
					security: sumosubs_admin_exporter_params.exporter_nonce,
					exportDataBy: $( evt.currentTarget ).closest( 'form' ).serialize(),
				},
				success: function ( response ) {
					if ( 'done' === response.export ) {
						window.location = response.redirect_url;
					} else if ( 'processing' === response.export ) {
						var i, j = 1, chunkedData, chunk = 10, step = 0;

						for ( i = 0, j = response.original_data.length; i < j; i += chunk ) {
							chunkedData = response.original_data.slice( i, i + chunk );
							step += chunkedData.length;
							subscription_exporter.processExport( response.original_data.length, chunkedData, step );
						}
					} else {
						window.location = response.redirect_url;
					}
				},
				complete: function () {
					unblock( $exporter_div );
				}
			} );
		},
		processExport: function ( originalDataLength, chunkedData, step ) {

			$.ajax( {
				type: 'POST',
				url: sumosubs_admin_exporter_params.wp_ajax_url,
				async: false,
				dataType: 'json',
				data: {
					action: 'sumosubscription_handle_exported_data',
					security: sumosubs_admin_exporter_params.exporter_nonce,
					exportDataBy: $( 'form.subscription-exporter' ).serialize(),
					originalDataLength: originalDataLength,
					chunkedData: chunkedData,
					step: step,
					generated_data: $( 'form.subscription-exporter' ).find( '#exported_data' ).val(),
				},
				success: function ( response ) {
					if ( 'done' === response.export ) {
						window.location = response.redirect_url;
					} else if ( 'processing' === response.export ) {
						$( 'form.subscription-exporter' ).find( '#exported_data' ).val( JSON.stringify( response.generated_data ) );
					} else {
						window.location = response.redirect_url;
					}
				}
			} );
		},
		importDatepicker: function () {
			$( '#sumo_subscription_from_date' ).datepicker( {
				changeMonth: true,
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
				defaultDate: '',
				showOn: 'focus',
				buttonImageOnly: true,
				onClose: function ( selectedDate ) {
					var maxDate = new Date( Date.parse( selectedDate ) );
					maxDate.setDate( maxDate.getDate() + 1 );
					$( '#sumo_subscription_to_date' ).datepicker( 'option', 'minDate', maxDate );
				}
			} );

			$( '#sumo_subscription_to_date' ).datepicker( {
				changeMonth: true,
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
				defaultDate: '',
				showOn: 'focus',
				buttonImageOnly: true,
			} );
		}
	};

	subscription_exporter.init();
} );
