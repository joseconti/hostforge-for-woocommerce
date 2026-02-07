/* global sumosubs_admin_settings_wc_params */

jQuery( function ( $ ) {

    // sumosubs_admin_settings_wc_params is required to continue, ensure the object exists
    if ( typeof sumosubs_admin_settings_wc_params === 'undefined' ) {
        return false;
    }

    $( '#woocommerce_sumo_paypal_reference_txns_dev_debug_enabled' ).change( function () {
        $( '#woocommerce_sumo_paypal_reference_txns_user_roles_for_dev' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#woocommerce_sumo_paypal_reference_txns_user_roles_for_dev' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#woocommerce_paypal_sumosubs_enabled_for_subscriptions' ).change( function () {
        $( '#woocommerce_paypal_sumosubs_show_when_multiple_subscriptions_in_cart' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#woocommerce_paypal_sumosubs_show_when_multiple_subscriptions_in_cart' ).closest( 'tr' ).show();
        }
    } ).change();
    
    $( '#woocommerce-coupon-data #discount_type' ).change( function () {
        $( '#sumosubs_payments_count' ).closest( 'p' ).hide();

        if ( 'sumosubs_recurring_fee_discount' === this.value || 'sumosubs_recurring_fee_percent_discount' === this.value ) {
            $( '#sumosubs_payments_count' ).closest( 'p' ).show();
        }
    } ).change();

    // Uploading files
    var file_frame;
    if ( '' !== $( '#sumosubs_logo_attachment_id' ).val() ) {
        $( '#sumosubs_upload_logo_button' ).val( sumosubs_admin_settings_wc_params.paypal_change_logo_button_text );
    }

    $( document ).on( 'click', '#sumosubs_upload_logo_button', function ( event ) {
        event.preventDefault();

        var $el = $( this );
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media( {
            title: $el.data( 'choose' ),
            button: {
                text: $( '#sumosubs_logo_attachment_id' ).val() !== '' ? $el.val() : $el.data( 'update' )
            },
            states: [
                new wp.media.controller.Library( {
                    title: $el.data( 'choose' ),
                    library: wp.media.query( { type: 'image' } ),
                    filterable: 'all',
                    editable: true,
                    suggestedWidth: '90',
                    suggestedHeight: '60'
                } )
            ]
        } );

        // When an image is selected, run a callback.
        file_frame.on( 'select', function () {
            // We set multiple to false so only get one image from the uploader
            attachment = file_frame.state().get( 'selection' ).first().toJSON();

            if ( attachment.type !== 'image' ) {
                alert( sumosubs_admin_settings_wc_params.admin_notice );
                return false;
            }

            if ( attachment.id && attachment.url ) {
                $( '#sumosubs_logo_preview' ).show();
                $( '#sumosubs_logo_attachment' ).hide();

                // Do something with attachment.id and/or attachment.url here
                $( '#sumosubs_logo_preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
                $( '#sumosubs_logo_attachment_id' ).val( attachment.id );
            } else {
                $( '#sumosubs_logo_preview' ).hide();
                $( '#sumosubs_logo_attachment' ).show();
            }
        } );

        // Finally, open the modal
        file_frame.open();
    } );

    $( document ).on( 'click', '#sumosubs_delete_logo', function ( event ) {
        event.preventDefault();

        $( this ).closest( 'div' ).find( '#sumosubs_logo_attachment_id' ).val( '' );
        $( this ).closest( 'div' ).find( '#sumosubs_logo_attachment' ).html( '' );
        $( this ).closest( 'div' ).find( '#sumosubs_logo_preview' ).attr( 'src', '' ).hide();
    } );
} );
