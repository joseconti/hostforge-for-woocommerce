/* global sumosubs_admin_params */

jQuery( function( $ ) {

    // sumosubs_admin_params is required to continue, ensure the object exists
    if ( typeof sumosubs_admin_params === 'undefined' ) {
        return false;
    }

    var is_blocked = function( $node ) {
        return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
    };

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function( $node ) {
        if ( ! is_blocked( $node ) ) {
            $node.addClass( 'processing' ).block( {
                message : null,
                overlayCSS : {
                    background : '#fff',
                    opacity : 0.6
                }
            } );
        }
    };

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function( $node ) {
        $node.removeClass( 'processing' ).unblock();
    };

    var $notes_div = $( '#sumosubscription_log_information' ).closest( 'div' );
    var $cancel_method_div = $( '#sumosubscription_cancel_methods, #sumosubscription_details' ).closest( 'div' );

    var edit_subscription = {
        /**
         * Manage Subscription Editable UI events.
         */
        init : function() {
            this.triggerOnPageLoad();

            $( document ).on( 'click', '.sumo_add_note', this.addSubscriptionNote );
            $( document ).on( 'click', '.sumo_delete_note', this.deleteSubscriptionNote );
            $( document ).on( 'click', '#sumo_view_unpaid_renewal_order', this.viewUnpaidRenewalOrder );
            $( document ).on( 'change', '.sumo_subscription_cancel_method_via', this.uponToggleCancelMethod );
            $( document ).on( 'click', '.sumo_submit_subscription_cancel_request', this.submitCancelRequest );
            $( document ).on( 'click', '.sumo_revoke_subscription_cancel_request', this.revokeCancelRequest );
            $( 'form' ).on( 'submit', this.confirmBeforePause );
            $( document ).on( 'click', 'button.subscription_start_schedule', this.toggleSchedule );
        },
        triggerOnPageLoad : function() {
            this.importDatepicker();
            $( '#sumo_unpaid_renewal_order' ).hide();
            $( '#subscription_start_date' ).datepicker( {
                minDate : 0,
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true,
            } );
        },
        uponToggleCancelMethod : function( evt ) {
            var $this = $( evt.currentTarget );

            $( '.sumo_submit_subscription_cancel_request' ).slideUp();
            $( '#sumo_subscription_cancel_scheduled_on' ).slideUp();

            if ( $.inArray( $this.val(), Array( 'immediate', 'end_of_billing_cycle', 'scheduled_date' ) ) !== - 1 ) {
                $( '.sumo_submit_subscription_cancel_request' ).slideDown();
            }
            if ( $this.val() === 'scheduled_date' ) {
                $( '#sumo_subscription_cancel_scheduled_on' ).slideDown();
                $( '#sumo_subscription_cancel_scheduled_on' ).datepicker( {
                    minDate : 0,
                    maxDate : $( '#sumo_subscription_data' ).data( 'next_payment_date' ),
                    changeMonth : true,
                    dateFormat : 'yy-mm-dd',
                    numberOfMonths : 1,
                    showButtonPanel : true,
                    defaultDate : '',
                    showOn : 'focus',
                    buttonImageOnly : true,
                } );
            }
        },
        submitCancelRequest : function( evt ) {
            var $this = $( evt.currentTarget );
            var $subscription_id = $this.data( 'subscription_id' );

            switch ( $( '.sumo_subscription_cancel_method_via' ).val() ) {
                case 'immediate':
                    if ( window.confirm( sumosubs_admin_params.warning_message_upon_immediate_cancel ) ) {
                        edit_subscription.requestCancelMethod( $subscription_id, 'immediate' );
                        return true;
                    }
                    break;
                case 'end_of_billing_cycle':
                    if ( window.confirm( sumosubs_admin_params.warning_message_upon_at_the_end_of_billing_cancel ) ) {
                        edit_subscription.requestCancelMethod( $subscription_id, 'end_of_billing_cycle' );
                        return true;
                    }
                    break;
                case 'scheduled_date':
                    if ( $( '#sumo_subscription_cancel_scheduled_on' ).val() === '' ) {
                        window.alert( sumosubs_admin_params.warning_message_upon_invalid_date );
                        return false;
                    }

                    if ( window.confirm( sumosubs_admin_params.warning_message_upon_on_the_scheduled_date_cancel ) ) {
                        edit_subscription.requestCancelMethod( $subscription_id, 'scheduled_date' );
                        return true;
                    }
                    break;
            }
            return false;
        },
        revokeCancelRequest : function() {
            if ( window.confirm( sumosubs_admin_params.warning_message_upon_revoking_cancel ) ) {
                return true;
            }
            return false;
        },
        requestCancelMethod : function( subscription_id, requested_method ) {
            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $cancel_method_div );

            $.ajax( {
                type : 'POST',
                url : sumosubs_admin_params.wp_ajax_url,
                dataType : 'json',
                data : {
                    action : 'sumosubscription_cancel_request',
                    security : sumosubs_admin_params.cancel_request_nonce,
                    cancel_method_requested_by : 'admin',
                    subscription_id : subscription_id,
                    cancel_method_requested : requested_method,
                    scheduled_date : $( '#sumo_subscription_cancel_scheduled_on' ).val()
                },
                success : function() {
                    unblock( $cancel_method_div );
                    location.reload();
                }
            } );
        },
        addSubscriptionNote : function( evt ) {
            evt.preventDefault();
            var $content = $( '#add_subscription_note' ).val();
            var $post_id = $( evt.currentTarget ).attr( 'data-id' );

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $notes_div );

            $.ajax( {
                type : 'POST',
                url : sumosubs_admin_params.wp_ajax_url,
                data : {
                    action : 'sumosubscription_add_subscription_note',
                    security : sumosubs_admin_params.add_note_nonce,
                    content : $content,
                    post_id : $post_id
                },
                success : function( data ) {
                    $( 'ul.subscription_notes' ).prepend( data );
                    $( '#add_subscription_note' ).val( '' );
                },
                complete : function() {
                    unblock( $notes_div );
                }
            } );
        },
        deleteSubscriptionNote : function( evt ) {
            var $this = $( evt.currentTarget );
            var $note_to_delete = $this.parent().parent().attr( 'rel' );

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $notes_div );

            $.ajax( {
                type : 'POST',
                url : sumosubs_admin_params.wp_ajax_url,
                data : {
                    action : 'sumosubscription_delete_subscription_note',
                    security : sumosubs_admin_params.delete_note_nonce,
                    delete_id : $note_to_delete
                },
                success : function( data ) {
                    if ( data === true ) {
                        $this.parent().parent().remove();
                    }
                },
                complete : function() {
                    unblock( $notes_div );
                }
            } );
            return false;
        },
        viewUnpaidRenewalOrder : function( evt ) {
            var $this = $( evt.currentTarget );

            $this.text( 'Hide' );

            if ( $( '#sumo_unpaid_renewal_order' ).is( ':visible' ) ) {
                $this.text( sumosubs_admin_params.view_renewal_orders_text );
            }

            $( '#sumo_unpaid_renewal_order' ).slideToggle( 'fast' );
            return false;
        },
        confirmBeforePause : function() {
            if ( 'yes' === sumosubs_admin_params.is_synced && 'Pause' === $( 'select#subscription_status' ).val() ) {
                if ( ! confirm( sumosubs_admin_params.warning_message_before_pause ) ) {
                    return false;
                }
            }
        },
        toggleSchedule : function( evt ) {
            evt.preventDefault();
            $( this ).hide();
            $( 'span.subscription_start_schedule_date_picker' ).show();
        },
        importDatepicker : function() {
            $( '.date-picker' ).datepicker( {
                minDate : 0,
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true
            } );
            $( '#sumo_subscription_from_date' ).datepicker( {
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true,
                onClose : function( selectedDate ) {
                    var maxDate = new Date( Date.parse( selectedDate ) );
                    maxDate.setDate( maxDate.getDate() + 1 );
                    $( '#sumo_subscription_to_date' ).datepicker( 'option', 'minDate', maxDate );
                }
            } );
            $( '#sumo_subscription_to_date' ).datepicker( {
                changeMonth : true,
                dateFormat : 'yy-mm-dd',
                numberOfMonths : 1,
                showButtonPanel : true,
                defaultDate : '',
                showOn : 'focus',
                buttonImageOnly : true,
            } );
        }
    };

    edit_subscription.init();
} );
