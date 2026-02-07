/* global sumosubs_myaccount_params */

jQuery( function( $ ) {
    // sumosubs_myaccount_params is required to continue, ensure the object exists
    if ( typeof sumosubs_myaccount_params === 'undefined' ) {
        return false;
    }

    var $page = $( '.woocommerce-MyAccount-content' ).closest( 'div' );

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

    var my_account = {
        /**
         * Manage My Subscription Table Editable UI events.
         */
        init : function() {
            my_account.table = $( 'table.sumo_subscription_details' );
            my_account.subscription_id = my_account.table.data( 'subscription_id' );
            this.importDatePicker( '#auto-resume-subscription-on', { maxDate : my_account.table.find( 'tr.subscription_pause_r_resume input' ).data( 'resume_before' ) } );
            this.importDatePicker( '#subscription-cancel-scheduled-on', { maxDate : my_account.table.data( 'next_payment_date' ) } );

            $( document ).on( 'click', '.subscription-action', this.initButtonAction );
            $( document ).on( 'change', '#subscription-cancel-selector', this.uponToggleCancelMethod );
            $( document ).on( 'click', '#prevent-more-subscription-notes', this.preventMoreSubscriptionNotes );
        },
        initButtonAction : function( evt ) {
            var $this = $( evt.currentTarget );

            switch ( $this.data( 'action' ) ) {
                case 'pause':
                    $( '.subscription_resume_date' ).slideDown();
                    break;
                case 'pause-submit':
                    if ( 'yes' === my_account.table.data( 'is_synced' ) ) {
                        if ( ! confirm( sumosubs_myaccount_params.warning_message_before_pause ) ) {
                            return false;
                        }
                    }

                    if ( sumosubs_myaccount_params.allow_user_to_select_resume_date ) {
                        my_account.requestAction( 'pause', {
                            auto_resume_on : 'undefined' === typeof $( '#auto-resume-subscription-on' ).val() ? my_account.table.find( 'tr.subscription_pause_r_resume input' ).data( 'resume_before' ) : $( '#auto-resume-subscription-on' ).val()
                        } );
                    } else {
                        my_account.requestAction( 'pause' );
                    }
                    break;
                case 'cancel':
                    if ( $.inArray( my_account.table.data( 'subscription_status' ), Array( 'Trial', 'Active' ) ) !== - 1 ) {
                        $this.slideUp();

                        if ( sumosubs_myaccount_params.subscriber_has_single_cancel_method ) {
                            if ( $.inArray( $( '#subscription-cancel-selector' ).val(), Array( 'immediate', 'end_of_billing_cycle' ) ) !== - 1 ) {
                                $this.slideDown();
                                my_account.mayBePopUpWarningMessageUponRequestingCancel( $( '#subscription-cancel-selector' ).val() );
                            }
                        } else {
                            $( '#subscription-cancel-selector, #subscription-cancel-submit' ).slideDown();
                        }

                        if ( $( '#subscription-cancel-selector' ).val() === 'scheduled_date' ) {
                            $( '#subscription-cancel-submit, #subscription-cancel-scheduled-on' ).slideDown();
                        }
                    } else {
                        my_account.mayBePopUpWarningMessageUponRequestingCancel( 'immediate' );
                    }
                    break;
                case 'cancel-submit':
                    my_account.mayBePopUpWarningMessageUponRequestingCancel( $( '#subscription-cancel-selector' ).val() );
                    break;
                case 'cancel-revoke':
                    if ( window.confirm( sumosubs_myaccount_params.warning_message_upon_revoking_cancel ) ) {
                        my_account.requestAction( 'cancel-revoke' );
                    }
                    break;
                case 'turnoff-auto':
                    if ( window.confirm( sumosubs_myaccount_params.warning_message_upon_turnoff_automatic_payments ) ) {
                        my_account.requestAction( 'turnoff-auto' );
                    }
                    break;
                case 'quantity-change':
                    my_account.requestAction( 'quantity-change', {
                        quantity : $( '#subscription_qty' ).val()
                    } );
                    break;
                default:
                    my_account.requestAction( $this.data( 'action' ) );
                    break;
            }
            return false;
        },
        importDatePicker : function( $field, $data ) {
            $data = $data || { };

            my_account.table.find( $field ).datepicker( {
                minDate : 0,
                maxDate : $data.maxDate,
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

            if ( 'scheduled_date' === $this.val() ) {
                $( '#subscription-cancel-scheduled-on' ).slideDown();
            } else {
                $( '#subscription-cancel-scheduled-on' ).slideUp();
            }
        },
        mayBePopUpWarningMessageUponRequestingCancel : function( cancel_method_requested ) {
            switch ( cancel_method_requested ) {
                case 'immediate':
                    if ( window.confirm( sumosubs_myaccount_params.warning_message_upon_immediate_cancel ) ) {
                        my_account.requestAction( 'cancel-immediate' );
                        return true;
                    }
                    break;
                case 'end_of_billing_cycle':
                    if ( window.confirm( sumosubs_myaccount_params.warning_message_upon_at_the_end_of_billing_cancel ) ) {
                        my_account.requestAction( 'cancel-at-the-end-of-billing-cycle' );
                        return true;
                    }
                    break;
                case 'scheduled_date':
                    if ( $( '#subscription-cancel-scheduled-on' ).val() === '' ) {
                        window.alert( sumosubs_myaccount_params.warning_message_upon_invalid_date );
                        return false;
                    }

                    if ( window.confirm( sumosubs_myaccount_params.warning_message_upon_on_the_scheduled_date_cancel ) ) {
                        my_account.requestAction( 'cancel-on-scheduled-date', {
                            scheduled_date_to_cancel : $( '#subscription-cancel-scheduled-on' ).val()
                        } );
                        return true;
                    }
                    break;
            }
            return false;
        },
        requestAction : function( $request, $data ) {
            $data = $data || { };

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $page );

            $.ajax( {
                type : 'POST',
                url : sumosubs_myaccount_params.wp_ajax_url,
                dataType : 'json',
                data : $.extend( {
                    action : 'sumosubscription_subscriber_request',
                    security : sumosubs_myaccount_params.wp_nonce,
                    subscription_id : my_account.subscription_id,
                    request : $request,
                    requested_by : 'subscriber',
                }, $data ),
                success : function( data ) {
                    unblock( $page );

                    if ( 'undefined' !== typeof data.notice && '' !== data.notice ) {
                        window.alert( data.notice )
                    }
                    if ( 'undefined' !== typeof data.redirect && data.redirect !== false ) {
                        window.location.href = data.redirect;
                    }
                },
            } );
        },
        preventMoreSubscriptionNotes : function( evt ) {
            var $this = $( evt.currentTarget );

            if ( $this.attr( 'data-flag' ) === 'more' ) {
                $this.text( sumosubs_myaccount_params.show_less_notes_label );
                $this.attr( 'data-flag', 'less' );

                $( '.sumo_alert_box' ).slideDown();
            } else {
                $this.text( sumosubs_myaccount_params.show_more_notes_label );
                $this.attr( 'data-flag', 'more' );

                $( '.sumo_alert_box' ).css( 'display', 'none' );
                $( '.default_subscription_notes0' ).slideDown();
                $( '.default_subscription_notes1' ).slideDown();
                $( '.default_subscription_notes2' ).slideDown();
            }
        }
    };

    my_account.init();
} );
