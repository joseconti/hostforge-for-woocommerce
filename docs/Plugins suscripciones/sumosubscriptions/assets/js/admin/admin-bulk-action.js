/* global sumosubs_admin_bulk_action_params, ajaxurl */

jQuery( function ( $ ) {

    // sumosubs_admin_bulk_action_params is required to continue, ensure the object exists
    if ( typeof sumosubs_admin_bulk_action_params === 'undefined' ) {
        return false;
    }

    var get_duration_options = function ( subscription_duration, subscription_duration_length ) {
        var subscription_duration_options = '';

        switch ( subscription_duration.val() ) {
            case 'W':
                subscription_duration_options = sumosubs_admin_bulk_action_params.subscription_week_duration_options;
                break;
            case 'M':
                subscription_duration_options = sumosubs_admin_bulk_action_params.subscription_month_duration_options;
                break;
            case 'Y':
                subscription_duration_options = sumosubs_admin_bulk_action_params.subscription_year_duration_options;
                break;
            default :
                subscription_duration_options = sumosubs_admin_bulk_action_params.subscription_day_duration_options;
                break;
        }

        subscription_duration_length.empty();
        $.each( subscription_duration_options, function ( value, key ) {
            subscription_duration_length.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
        } );
    };

    var bulk_action = {
        pageOnload: true,
        /**
         * Perform Subscription Bulk Action Toggle events.
         */
        init: function ( ) {
            $( document ).on( 'change', '#selected_bulk_type', this.toggleProductOrCategory );
            $( document ).on( 'change', '#subscription_status', this.toggleProductStatus );
            $( document ).on( 'change', '#subscription_trial_status', this.toggleTrial );
            $( document ).on( 'change', '#subscription_signup_fee_status', this.toggleSignup );
            $( document ).on( 'change', '#subscription_trial_type', this.toggleTrialType );
            $( document ).on( 'change', '#subscription_period', this.toggleDuration );
            $( document ).on( 'change', '#subscription_trial_period', this.toggleDuration );
            $( document ).on( 'click', '#bulk_update_products', this.update );
            $( document ).on( 'click', '#bulk_update_subscriptions', this.replaceDeletedProduct );

            $( '#selected_bulk_type,#subscription_status' ).change();
        },
        toggleProductOrCategory: function ( evt ) {
            $( '#selected_products,#selected_product_categories' ).closest( 'tr' ).hide();

            if ( 'selected-products' === $( evt.currentTarget ).val() ) {
                $( '#selected_products' ).closest( 'tr' ).show();
            } else if ( 'selected-categories' === $( evt.currentTarget ).val() ) {
                $( '#selected_product_categories' ).closest( 'tr' ).show();
            }
        },
        toggleProductStatus: function ( evt ) {
            if ( '1' === $( evt.currentTarget ).val() ) {
                $( '#subscription_trial_status,#subscription_trial_type,#subscription_trial_price,#subscription_signup_fee_status,#subscription_signup_fee,#subscription_recurring_cycle,.subscription_duration_wrap,.subscription_trial_duration_wrap' ).closest( 'tr' ).show();
                $( '#subscription_trial_status,#subscription_signup_fee_status' ).change();
            } else {
                $( '#subscription_trial_status,#subscription_trial_type,#subscription_trial_price,#subscription_signup_fee_status,#subscription_signup_fee,#subscription_recurring_cycle,.subscription_duration_wrap,.subscription_trial_duration_wrap' ).closest( 'tr' ).hide();
            }
        },
        toggleTrial: function ( evt ) {
            if ( '1' !== $( '#subscription_status' ).val() || '2' === $( evt.currentTarget ).val() ) {
                $( '#subscription_trial_type,#subscription_trial_price,.subscription_trial_duration_wrap' ).closest( 'tr' ).hide();
            } else {
                $( '#subscription_trial_type,.subscription_trial_duration_wrap' ).closest( 'tr' ).show();
                $( '#subscription_trial_type' ).change();
            }
        },
        toggleSignup: function ( evt ) {
            if ( '1' !== $( '#subscription_status' ).val() || '2' === $( evt.currentTarget ).val() ) {
                $( '#subscription_signup_fee' ).closest( 'tr' ).hide();
            } else {
                $( '#subscription_signup_fee' ).closest( 'tr' ).show();
            }
        },
        toggleTrialType: function ( evt ) {
            if ( 'paid' === $( evt.currentTarget ).val() ) {
                $( '#subscription_trial_price' ).closest( 'tr' ).show();
            } else {
                $( '#subscription_trial_price' ).closest( 'tr' ).hide();
            }
        },
        toggleDuration: function ( evt ) {
            var $duration = $( evt.currentTarget ),
                    duration_length = $duration.is( '#subscription_trial_period' ) ? $( '#subscription_trial_period_value' ) : $( '#subscription_period_value' );

            get_duration_options( $duration, duration_length );

        },
        update: function ( evt ) {
            $( 'tr.bulk-update-products' ).find( '.spinner' ).addClass( 'is-active' );

            $.ajax( {
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'sumosubscription_bulk_update_products',
                    security: sumosubs_admin_bulk_action_params.products_nonce,
                    data: $( evt.currentTarget ).closest( 'table' ).find( ':input' ).serialize(),
                },
                dataType: 'json',
                success: function ( response ) {
                    console.log( response );

                    if ( response.success ) {
                        window.alert( '"' + parseInt( response.data.productsCount ) + '" product(s) are found. Subscription settings for products are updating through the background process. It may take some time to update it so please be patient.' );
                    } else {
                        if ( 0 === response.data.productsCount ) {
                            window.alert( 'No products found to update.' );
                        } else {
                            window.alert( 'Something went wrong while updating the products.' );
                        }
                    }
                    window.location.reload( true );
                },
                complete: function () {
                    $( 'tr.bulk-fields-save-and-update' ).find( '.spinner' ).removeClass( 'is-active' );
                }
            } );
        },
        replaceDeletedProduct : function( evt ) {
            $( 'tr.bulk-update-subscriptions' ).find( '.spinner' ).addClass( 'is-active' );

            $.ajax( {
                type : 'POST',
                url : ajaxurl,
                data : {
                    action : 'sumosubscription_bulk_update_subscriptions',
                    security : sumosubs_admin_bulk_action_params.subscriptions_nonce,
                    data : $( evt.currentTarget ).closest( 'table' ).find( ':input' ).serialize(),
                },
                dataType : 'json',
                success : function( response ) {
                    console.log( response );

                    if ( response.success ) {
                        window.alert( '"' + parseInt( response.data.itemsCount ) + '" subscription(s) are found. Subscription settings for products are updating through the background process. It may take some time to update it so please be patient.' );
                    } else {
                        if ( 0 === response.data.itemsCount ) {
                            window.alert( 'No products found to update.' );
                        } else {
                            window.alert( 'Something went wrong while updating the products.' );
                        }
                    }
                    window.location.reload( true );
                },
                complete : function() {
                    $( 'tr.bulk-fields-save-and-update' ).find( '.spinner' ).removeClass( 'is-active' );
                }
            } );
        },
    };

    bulk_action.init();
} );
