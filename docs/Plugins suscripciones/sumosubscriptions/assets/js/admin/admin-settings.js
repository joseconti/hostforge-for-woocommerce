/* global sumosubs_admin_settings_params, wp, ajaxurl */

jQuery( function( $ ) {

    // sumosubs_admin_settings_params is required to continue, ensure the object exists
    if ( typeof sumosubs_admin_settings_params === 'undefined' ) {
        return false;
    }

    $( '#sumosubs_allow_mixed_checkout' ).change( function() {
        if ( this.checked ) {
            $( '#sumosubs_restrict_multiple_subscriptions_in_checkout' ).closest( 'tr' ).hide();
        } else {
            $( '#sumosubs_restrict_multiple_subscriptions_in_checkout' ).closest( 'tr' ).show();
        }
    } ).change();

    /**
     * Payments Section
     */
    $( '#sumosubs_accept_manual_payments,#sumosubs_disable_auto_payments' ).change( function() {
        if ( ! $( '#sumosubs_accept_manual_payments' ).is( ':checked' ) ) {
            $( '#sumosubs_disable_auto_payments,#sumosubs_subscription_payment_gateway_mode' ).closest( 'tr' ).hide();
            $( '#sumosubs_show_subscription_payment_gateways_when_order_amt_0,#sumosubs_payment_retries_in_overdue,#sumosubs_payment_retries_in_suspend' ).closest( 'tr' ).show();
        } else if ( $( '#sumosubs_accept_manual_payments' ).is( ':checked' ) && $( '#sumosubs_disable_auto_payments' ).is( ':checked' ) ) {
            $( '#sumosubs_disable_auto_payments' ).closest( 'tr' ).show();
            $( '#sumosubs_subscription_payment_gateway_mode,#sumosubs_show_subscription_payment_gateways_when_order_amt_0,#sumosubs_payment_retries_in_overdue,#sumosubs_payment_retries_in_suspend' ).closest( 'tr' ).hide();
        } else {
            $( '#sumosubs_disable_auto_payments,#sumosubs_subscription_payment_gateway_mode' ).closest( 'tr' ).show();
            $( '#sumosubs_subscription_payment_gateway_mode' ).change();
        }
    } ).change();

    $( '#sumosubs_subscription_payment_gateway_mode' ).change( function() {
        if ( 'force-manual' === this.value ) {
            $( '#sumosubs_show_subscription_payment_gateways_when_order_amt_0,#sumosubs_payment_retries_in_overdue,#sumosubs_payment_retries_in_suspend' ).closest( 'tr' ).hide();
        } else {
            $( '#sumosubs_show_subscription_payment_gateways_when_order_amt_0,#sumosubs_payment_retries_in_overdue,#sumosubs_payment_retries_in_suspend' ).closest( 'tr' ).show();
        }
    } );

    /**
     * Display Section
     */
    $( '#sumosubs_show_timezone_in_frontend' ).change( function() {
        $( '#sumosubs_show_timezone_in_frontend_as' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_show_timezone_in_frontend_as' ).closest( 'tr' ).show();
        }
    } ).change();

    /**
     * Pause Section
     */
    $( '#sumosubs_user_wide_pause_for' ).change( function() {
        $( '#sumosubs_user_ids_for_pause' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_roles_for_pause' ).closest( 'tr' ).hide();

        switch ( this.value ) {
            case 'allowed-user-ids':
            case 'restricted-user-ids':
                $( '#sumosubs_user_ids_for_pause' ).closest( 'tr' ).show();
                break;
            case 'allowed-user-roles':
            case 'restricted-user-roles':
                $( '#sumosubs_user_roles_for_pause' ).closest( 'tr' ).show();
                break;
        }
    } );

    $( '#sumosubs_allow_subscribers_to_pause' ).change( function() {
        $( '#sumosubs_allow_subscribers_to_pause_synced' ).closest( 'tr' ).hide();
        $( '#sumosubs_allow_subscribers_to_select_resume_date' ).closest( 'tr' ).hide();
        $( '#sumosubs_max_pause_times_for_subscribers' ).closest( 'tr' ).hide();
        $( '#sumosubs_max_pause_duration_for_subscribers' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_wide_pause_for' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_ids_for_pause' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_roles_for_pause' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_allow_subscribers_to_pause_synced' ).closest( 'tr' ).show();
            $( '#sumosubs_allow_subscribers_to_select_resume_date' ).closest( 'tr' ).show();
            $( '#sumosubs_max_pause_times_for_subscribers' ).closest( 'tr' ).show();
            $( '#sumosubs_max_pause_duration_for_subscribers' ).closest( 'tr' ).show();
            $( '#sumosubs_user_wide_pause_for' ).closest( 'tr' ).show();
            $( '#sumosubs_user_wide_pause_for' ).change();
        }
    } ).change();

    /**
     * Resubscribe Section
     */
    $( '#sumosubs_allow_subscribers_to_resubscribe' ).change( function() {
        $( '#sumosubs_hide_resubscribe_to_subscribers_when' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_hide_resubscribe_to_subscribers_when' ).closest( 'tr' ).show();
        }
    } ).change();

    /**
     * Cancel Section
     */
    $( '#sumosubs_product_wide_cancellation_for' ).change( function() {
        $( '#sumosubs_product_ids_for_cancel' ).closest( 'tr' ).hide();
        $( '#sumosubs_product_cat_ids_for_cancel' ).closest( 'tr' ).hide();

        switch ( this.value ) {
            case 'allowed-product-ids':
            case 'restricted-product-ids':
                $( '#sumosubs_product_ids_for_cancel' ).closest( 'tr' ).show();
                break;
            case 'allowed-product-cat-ids':
            case 'restricted-product-cat-ids':
                $( '#sumosubs_product_cat_ids_for_cancel' ).closest( 'tr' ).show();
                break;
        }
    } );

    $( '#sumosubs_user_wide_cancellation_for' ).change( function() {
        $( '#sumosubs_user_ids_for_cancel' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_roles_for_cancel' ).closest( 'tr' ).hide();

        switch ( this.value ) {
            case 'allowed-user-ids':
            case 'restricted-user-ids':
                $( '#sumosubs_user_ids_for_cancel' ).closest( 'tr' ).show();
                break;
            case 'allowed-user-roles':
            case 'restricted-user-roles':
                $( '#sumosubs_user_roles_for_cancel' ).closest( 'tr' ).show();
                break;
        }
    } );

    $( '#sumosubs_allow_subscribers_to_cancel' ).change( function() {
        $( '#sumosubs_allow_subscribers_to_cancel_after_schedule' ).closest( 'tr' ).hide();
        $( '#sumosubs_cancel_options_for_subscriber' ).closest( 'tr' ).hide();
        $( '#sumosubs_product_wide_cancellation_for' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_wide_cancellation_for' ).closest( 'tr' ).hide();
        $( '#sumosubs_product_ids_for_cancel' ).closest( 'tr' ).hide();
        $( '#sumosubs_product_cat_ids_for_cancel' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_ids_for_cancel' ).closest( 'tr' ).hide();
        $( '#sumosubs_user_roles_for_cancel' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_allow_subscribers_to_cancel_after_schedule' ).closest( 'tr' ).show();
            $( '#sumosubs_cancel_options_for_subscriber' ).closest( 'tr' ).show();
            $( '#sumosubs_product_wide_cancellation_for' ).closest( 'tr' ).show();
            $( '#sumosubs_user_wide_cancellation_for' ).closest( 'tr' ).show();
            $( '#sumosubs_product_wide_cancellation_for,#sumosubs_user_wide_cancellation_for' ).change();
        }
    } ).change();

    /**
     * Advanced Section
     */
    $( '.sumosubs_set_subscription_as_regular_product' ).on( 'click', 'a.add', function() {
        var $rulesTable = $( this ).closest( 'table' ),
                rowID = $rulesTable.find( 'tbody .defined_rule' ).length + 1;

        $( '.spinner' ).addClass( 'is-active' );
        $.ajax( {
            type : 'POST',
            url : ajaxurl,
            data : {
                action : 'sumosubscription_get_subscription_as_regular_product_row',
                security : sumosubs_admin_settings_params.subscription_as_regular_product_row_nonce,
                rowID : rowID
            },
            success : function( row ) {
                if ( typeof row !== 'undefined' ) {
                    $( '<tr class="defined_rule">\n\
                                            <td class="sort"></td>\
                                            <td>' + row.wc_product_search + '</td>\n\
                                            <td>' + row.wc_user_role_multiselect + '</td>\n\
                                            <td><a href="#" class="remove_row button">X</a></td>\
                                    </tr>' ).appendTo( $rulesTable.find( 'tbody' ) );
                    $( document.body ).trigger( 'wc-enhanced-select-init' );
                }
            },
            complete : function() {
                $( '.spinner' ).removeClass( 'is-active' );
            }
        } );
        return false;
    } );

    $( document ).on( 'click', '.sumosubs_set_subscription_as_regular_product a.remove_row', function() {
        $( this ).closest( 'tr' ).remove();
        return false;
    } );

    /**
     * Order Subscription
     */

    $( '#sumosubs_order_subs_product_wide_selection' ).change( function() {
        $( '#sumosubs_order_subs_allowed_product_ids' ).closest( 'tr' ).hide();
        $( '#sumosubs_order_subs_restricted_product_ids' ).closest( 'tr' ).hide();
        $( '#sumosubs_order_subs_allowed_product_cat_ids' ).closest( 'tr' ).hide();
        $( '#sumosubs_order_subs_restricted_product_cat_ids' ).closest( 'tr' ).hide();

        switch ( this.value ) {
            case 'allowed-product-ids':
                $( '#sumosubs_order_subs_allowed_product_ids' ).closest( 'tr' ).show();
                break;
            case 'restricted-product-ids':
                $( '#sumosubs_order_subs_restricted_product_ids' ).closest( 'tr' ).show();
                break;
            case 'allowed-product-cat-ids':
                $( '#sumosubs_order_subs_allowed_product_cat_ids' ).closest( 'tr' ).show();
                break;
            case 'restricted-product-cat-ids':
                $( '#sumosubs_order_subs_restricted_product_cat_ids' ).closest( 'tr' ).show();
                break;
        }
    } ).change();

    $( '#sumosubs_order_subs_subscribe_values' ).change( function() {
        if ( 'userdefined' === this.value ) {
            $( '#sumosubs_order_subs_predefined_subscription_period' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_predefined_subscription_period_interval' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_predefined_subscription_length' ).closest( 'tr' ).hide();

            $( '#sumosubs_order_subs_userdefined_subscription_periods' ).closest( 'tr' ).show();
            $( '#sumosubs_order_subsc_userdefined_subscription_period_intervals' ).closest( 'tr' ).show();
            $( '#sumosubs_order_subs_userdefined_allow_indefinite_subscription_length' ).closest( 'tr' ).show();

            $( '#sumosubs_order_subs_userdefined_allow_indefinite_subscription_length' ).change();
        } else {
            $( '#sumosubs_order_subs_predefined_subscription_period' ).closest( 'tr' ).show();
            $( '#sumosubs_order_subs_predefined_subscription_period_interval' ).closest( 'tr' ).show();
            $( '#sumosubs_order_subs_predefined_subscription_length' ).closest( 'tr' ).show();

            $( '#sumosubs_order_subs_userdefined_subscription_periods' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subsc_userdefined_subscription_period_intervals' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_userdefined_allow_indefinite_subscription_length' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_userdefined_min_subscription_length' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_userdefined_max_subscription_length' ).closest( 'tr' ).hide();
            $( '#sumosubs_order_subs_signupfee' ).closest( 'tr' ).hide();
        }

        $( '#sumosubs_order_subs_charge_signupfee' ).change();
    } ).change();

    $( '#sumosubs_order_subs_userdefined_allow_indefinite_subscription_length' ).change( function() {
        $( '#sumosubs_order_subs_userdefined_min_subscription_length' ).closest( 'tr' ).hide();
        $( '#sumosubs_order_subs_userdefined_max_subscription_length' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_order_subs_userdefined_min_subscription_length' ).closest( 'tr' ).show();
            $( '#sumosubs_order_subs_userdefined_max_subscription_length' ).closest( 'tr' ).show();
        }
    } );

    $( '#sumosubs_order_subs_charge_signupfee' ).change( function() {
        $( '#sumosubs_order_subs_signupfee' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_order_subs_signupfee' ).closest( 'tr' ).show();
        }
    } );

    $( '#sumosubs_order_subs_predefined_subscription_period' ).change( function() {
        var $elements = $( '#sumosubs_order_subs_predefined_subscription_period_interval' );
        $elements.empty();
        var duration_options = { };

        switch ( $( this ).val() ) {
            case 'W':
                var duration_options = sumosubs_admin_settings_params.subscription_week_duration_options;
                break;
            case 'M':
                var duration_options = sumosubs_admin_settings_params.subscription_month_duration_options;
                break;
            case 'Y':
                var duration_options = sumosubs_admin_settings_params.subscription_year_duration_options;
                break;
            default :
                var duration_options = sumosubs_admin_settings_params.subscription_day_duration_options;
                break;
        }

        $.each( duration_options, function( value, key ) {
            $elements.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
        } );
    } );

    $( '#mainform' ).submit( function() {
        if ( $( '#sumosubs_order_subs_allow_in_checkout' ).is( ':checked' ) || $( '#sumosubs_order_subs_allow_in_cart' ).is( ':checked' ) ) {
            if ( 'userdefined' === $( '#sumosubs_order_subs_subscribe_values' ).val() ) {
                if ( $( '#sumosubs_order_subs_userdefined_allow_indefinite_subscription_length' ).is( ':checked' ) ) {
                    if ( '0' !== $( '#sumosubs_order_subs_userdefined_max_subscription_length' ).val() && parseInt( $( '#sumosubs_order_subs_userdefined_max_subscription_length' ).val() ) < parseInt( $( '#sumosubs_order_subs_userdefined_min_subscription_length' ).val() ) ) {
                        alert( sumosubs_admin_settings_params.warning_message_upon_max_recurring_cycle );
                        return false;
                    }
                }

                if ( parseInt( $( '#sumosubs_order_subs_userdefined_min_subscription_period_intervals_D' ).val() ) > parseInt( $( '#sumosubs_order_subs_userdefined_max_subscription_period_intervals_D' ).val() ) ) {
                    alert( sumosubs_admin_settings_params.warning_message_upon_invalid_no_of_days );
                    return false;
                } else if ( parseInt( $( '#sumosubs_order_subs_userdefined_min_subscription_period_intervals_W' ).val() ) > parseInt( $( '#sumosubs_order_subs_userdefined_max_subscription_period_intervals_W' ).val() ) ) {
                    alert( sumosubs_admin_settings_params.warning_message_upon_invalid_no_of_weeks );
                    return false;
                } else if ( parseInt( $( '#sumosubs_order_subs_userdefined_min_subscription_period_intervals_M' ).val() ) > parseInt( $( '#sumosubs_order_subs_userdefined_max_subscription_period_intervals_M' ).val() ) ) {
                    alert( sumosubs_admin_settings_params.warning_message_upon_invalid_no_of_months );
                    return false;
                } else if ( parseInt( $( '#sumosubs_order_subs_userdefined_min_subscription_period_intervals_Y' ).val() ) > parseInt( $( '#sumosubs_order_subs_userdefined_max_subscription_period_intervals_Y' ).val() ) ) {
                    alert( sumosubs_admin_settings_params.warning_message_upon_invalid_no_of_years );
                    return false;
                }
            }
        }

        return true;
    } );

    /**
     * Messages Section
     */
    $( '#sumosubs_show_error_messages_in_product_page' ).change( function() {
        $( '#sumosubs_product_wide_subscription_limit_error_message' ).closest( 'tr' ).hide();
        $( '#sumosubs_site_wide_subscription_limit_error_message' ).closest( 'tr' ).hide();
        $( '#sumosubs_cannot_purchase_regular_with_subscription_product_error_message' ).closest( 'tr' ).hide();
        $( '#sumosubs_cannot_purchase_subscription_with_regular_product_error_message' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_product_wide_subscription_limit_error_message' ).closest( 'tr' ).show();
            $( '#sumosubs_site_wide_subscription_limit_error_message' ).closest( 'tr' ).show();
            $( '#sumosubs_cannot_purchase_regular_with_subscription_product_error_message' ).closest( 'tr' ).show();
            $( '#sumosubs_cannot_purchase_subscription_with_regular_product_error_message' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#sumosubs_show_error_messages_in_cart_page' ).change( function() {
        $( '#sumosubs_product_wide_trial_limit_error_message' ).closest( 'tr' ).hide();
        $( '#sumosubs_site_wide_trial_limit_error_message' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_product_wide_trial_limit_error_message' ).closest( 'tr' ).show();
            $( '#sumosubs_site_wide_trial_limit_error_message' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#sumosubs_show_error_messages_in_pay_for_order_page' ).change( function() {
        $( '#sumosubs_renewal_order_payment_in_paused_error_message' ).closest( 'tr' ).hide();
        $( '#sumosubs_renewal_order_payment_in_pending_cancel_error_message' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_renewal_order_payment_in_paused_error_message' ).closest( 'tr' ).show();
            $( '#sumosubs_renewal_order_payment_in_pending_cancel_error_message' ).closest( 'tr' ).show();
        }
    } ).change();

    $( '#sumosubs_payment_mode_when_switch' ).change( function() {
        $( '#sumosubs_when_switch_prorate_recurring_payment_for' ).closest( 'tr' ).hide();

        if ( 'prorate' === $( this ).val() ) {
            $( '#sumosubs_when_switch_prorate_recurring_payment_for' ).closest( 'tr' ).show();
        }
    } );

    /**
     * Switcher Section
     */
    $( '#sumosubs_allow_switcher' ).change( function() {
        $( '#sumosubs_allow_user_to_switch' ).closest( 'tr' ).hide();
        $( '#sumosubs_switch_based_on' ).closest( 'tr' ).hide();
        $( '#sumosubs_allow_switch_between' ).closest( 'tr' ).hide();
        $( '#sumosubs_payment_mode_when_switch' ).closest( 'tr' ).hide();
        $( '#sumosubs_switch_button_text' ).closest( 'tr' ).hide();
        $( '#sumosubs_when_switch_prorate_recurring_payment_for' ).closest( 'tr' ).hide();
        $( '#sumosubs_when_switch_charge_signup_fee' ).closest( 'tr' ).hide();
        $( '#sumosubs_when_switch_prorate_subscription_length' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_allow_user_to_switch' ).closest( 'tr' ).show();
            $( '#sumosubs_switch_based_on' ).closest( 'tr' ).show();
            $( '#sumosubs_allow_switch_between' ).closest( 'tr' ).show();
            $( '#sumosubs_payment_mode_when_switch' ).closest( 'tr' ).show();
            $( '#sumosubs_switch_button_text' ).closest( 'tr' ).show();
            $( '#sumosubs_when_switch_charge_signup_fee' ).closest( 'tr' ).show();
            $( '#sumosubs_when_switch_prorate_subscription_length' ).closest( 'tr' ).show();
            $( '#sumosubs_payment_mode_when_switch' ).change();
        }
    } ).change();

    /**
     * Synchronization Section
     */
    $( '#sumosubs_allow_synchronization' ).change( function() {
        $( '#sumosubs_sync_based_on' ).closest( 'tr' ).hide();
        $( '#sumosubs_when_sync_show_next_due_date_in_product_page' ).closest( 'tr' ).hide();
        $( '#sumosubs_payment_mode_when_sync' ).closest( 'tr' ).hide();
        $( '#sumosubs_when_sync_prorate_payment_for' ).closest( 'tr' ).hide();
        $( 'input:radio[name=sumosubs_when_sync_prorate_payment_during]' ).closest( 'tr' ).hide();

        if ( this.checked ) {
            $( '#sumosubs_sync_based_on' ).closest( 'tr' ).show();
            $( '#sumosubs_when_sync_show_next_due_date_in_product_page' ).closest( 'tr' ).show();
            $( '#sumosubs_payment_mode_when_sync' ).closest( 'tr' ).show();
            $( '#sumosubs_payment_mode_when_sync' ).change();
        }
    } ).change();

    $( '#sumosubs_payment_mode_when_sync' ).change( function() {
        $( '#sumosubs_when_sync_prorate_payment_for' ).closest( 'tr' ).hide();
        $( 'input:radio[name=sumosubs_when_sync_prorate_payment_during]' ).closest( 'tr' ).hide();

        if ( this.value === 'prorate' ) {
            $( '#sumosubs_when_sync_prorate_payment_for' ).closest( 'tr' ).show();
            $( 'input:radio[name=sumosubs_when_sync_prorate_payment_during]' ).closest( 'tr' ).show();
        }
    } ).change();
} );
