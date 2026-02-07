/* global sumosubs_admin_product_params */

jQuery( function( $ ) {

    // sumosubs_admin_product_params is required to continue, ensure the object exists
    if ( typeof sumosubs_admin_product_params === 'undefined' ) {
        return false;
    }

    var get_duration_options = function( subscription_duration, subscription_duration_length ) {
        var subscription_duration_options = '';

        switch ( subscription_duration.val() ) {
            case 'W':
                subscription_duration_options = sumosubs_admin_product_params.subscription_week_duration_options;
                break;
            case 'M':
                subscription_duration_options = sumosubs_admin_product_params.subscription_month_duration_options;
                break;
            case 'Y':
                subscription_duration_options = sumosubs_admin_product_params.subscription_year_duration_options;
                break;
            default :
                subscription_duration_options = sumosubs_admin_product_params.subscription_day_duration_options;
                break;
        }

        subscription_duration_length.empty();
        $.each( subscription_duration_options, function( value, key ) {
            subscription_duration_length.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
        } );
    };

    var get_sync_duration_options = function( subscription_duration, sync_duration, sync_duration_length ) {
        var sync_duration_options = '', sync_duration_length_options = '';

        switch ( subscription_duration.val() ) {
            case 'W':
                sync_duration_options = sumosubs_admin_product_params.synced_subscription_week_duration_options;
                break;
            case 'M':
                sync_duration_options = sumosubs_admin_product_params.synced_subscription_month_duration_options;
                sync_duration_length_options = sumosubs_admin_product_params.synced_subscription_month_duration_value_options;
                break;
            case 'Y':
                sync_duration_options = sumosubs_admin_product_params.synced_subscription_year_duration_options;
                sync_duration_length_options = sumosubs_admin_product_params.synced_subscription_year_duration_value_options;
                break;
        }

        sync_duration.empty();
        sync_duration_length.empty();

        $.each( sync_duration_options, function( value, key ) {
            sync_duration.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
        } );

        if ( '' !== sync_duration_length_options ) {
            $.each( sync_duration_length_options, function( value, key ) {
                sync_duration_length.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
            } );
        }
    };

    var product = {
        /**
         * Initialize Subscription Product settings UI events.
         */
        init : function() {
            this.trigger_on_page_load();

            $( document ).on( 'change', '#product-type', this.on_change_product_type );
            $( document ).on( 'woocommerce_variations_save_variations_button', this.validate_save_variations );
            $( 'form#post' ).on( 'submit', this.validate_on_submit );
        },
        trigger_on_page_load : function( ) {
            this.load_product_type( $( '#product-type' ).val() );
        },
        on_change_product_type : function( evt ) {
            product.load_product_type( $( evt.currentTarget ).val() );
        },
        load_product_type : function( $product_type ) {
            $product_type = $product_type || '';

            switch ( $product_type ) {
                case 'variable':
                    $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function( evt ) {
                        product.load_variations( evt );
                    } );

                    $( document.body ).on( 'woocommerce_variations_added', function( evt, qty ) {
                        product.load_variations( evt, qty );
                    } );
                    break;
                default:
                    simple_product.init();
                    break;
            }
        },
        load_variations : function( evt, qty ) {
            qty = qty || 0;

            var $wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
                    total_variations_count = parseInt( $wrapper.attr( 'data-total' ), 10 ) + qty;

            for ( var i = 0; i < sumosubs_admin_product_params.variations_per_page; i ++ ) {
                ( function( i ) {
                    variation_product.init( i );
                } )( i );
            }
        },
        validate_on_submit : function() {
            switch ( $( '#product-type' ).val() ) {
                case 'variable':
                    return product.validate_save_variations();
                    break;
                default:
                    return product.validate_save_product();
                    break;
            }
            return true;
        },
        validate_save_product : function( variation_index ) {
            variation_index = variation_index || 0;

            var mayBeVariation = 'variable' === $( '#product-type' ).val() ? variation_index : '',
                    mayBeVariationString = '' === mayBeVariation ? '' : '[' + mayBeVariation + ']',
                    errFields = [ ],
                    prevField,
                    availableErrFields = [
                        $( '#sumo_xtra_time_to_charge_full_fee' + mayBeVariation ),
                        $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + mayBeVariation ),
                    ];

            if ( '1' === $( '[name="sumo_susbcription_status' + mayBeVariationString + '"]' ).val() ) {
                var $is_synced = false;

                switch ( $( '[name="sumo_susbcription_period' + mayBeVariationString + '"]' ).val() ) {
                    case 'Y':
                    case 'M':
                        $is_synced = '0' !== $( '[name="sumo_synchronize_period_value' + mayBeVariationString + '"]' ).val();
                        break;
                    case 'W':
                        $is_synced = '0' !== $( '[name="sumo_synchronize_period' + mayBeVariationString + '"]' ).val();
                        break;
                }

                if ( $is_synced ) {
                    var $cutoff;
                    if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' + mayBeVariation ).val() ) {
                        $cutoff = $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + mayBeVariation );
                    } else {
                        $cutoff = $( '#sumo_xtra_time_to_charge_full_fee' + mayBeVariation );
                    }

                    if ( parseInt( $cutoff.val() ) > parseInt( $cutoff.attr( 'max' ) ) ) {
                        if ( '' !== mayBeVariation ) {
                            $cutoff.closest( '.wc-metabox > .wc-metabox-content' ).show();
                        }
                        prevField = $( '.sumo_synchronize_duration_fields' + mayBeVariation );
                        errFields.push( $cutoff );
                    }
                }
            }
            return product.needs_update( errFields, prevField, availableErrFields );
        },
        validate_save_variations : function() {

            var $wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
                    total_variations_count = parseInt( $wrapper.attr( 'data-total' ), 10 ),
                    variations = $( '#variable_product_options' ).find( '.woocommerce_variations .variation-needs-update' ),
                    needs_update = true;

            $( 'li.variations_tab a' ).trigger( 'click' );
            $( '.wc-metaboxes-wrapper, .expand_all' ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).hide();

            for ( var i = 0; i < sumosubs_admin_product_params.variations_per_page; i ++ ) {
                ( function( i ) {
                    if ( ! product.validate_save_product( i ) ) {
                        variations.removeClass( 'variation-needs-update' );
                        needs_update = false;
                        return false;
                    }
                } )( i );
            }
            if ( ! needs_update ) {
                $( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
            }
            return needs_update;
        },
        needs_update : function( errFields, prevField, resetErrFields, errNoticeField ) {
            resetErrFields = resetErrFields || [ ];
            errNoticeField = errNoticeField || '';
            var needs_update = true, error_text = '';

            if ( errFields.length === 0 ) {
                return needs_update;
            }
            if ( $.isArray( resetErrFields ) ) {
                $.each( resetErrFields, function( index, reseterrField ) {
                    reseterrField.css( { "border" : '' } );
                } );
            }

            if ( $.isArray( errFields ) ) {
                $.each( errFields, function( index, errField ) {
                    needs_update = false;
                    errField.css( { "border" : '#FF0000 1px solid' } );
                    error_text = 'testing';
                } );

                if ( ! needs_update ) {
                    if ( '' !== errNoticeField ) {
                        errNoticeField.html( error_text ).css( 'color', 'red' );
                    }

                    $( 'html,body' ).animate( {
                        scrollTop : prevField.offset().top
                    }, 1200 );
                }
            } else {
                if ( errFields.is( ':visible' ) ) {
                    needs_update = false;
                    errFields.css( { "border" : '#FF0000 1px solid' } );
                }

                if ( ! needs_update ) {
                    $( 'html,body' ).animate( {
                        scrollTop : prevField.offset().top
                    }, 1200 );
                }
            }
            return needs_update;
        },
        sortable : function( $table ) {
            $( $table ).sortable( {
                items : 'tr',
                cursor : 'move',
                axis : 'y',
                handle : 'td.sort',
                scrollSensitivity : 40,
                helper : function( event, ui ) {
                    ui.children().each( function() {
                        $( this ).width( $( this ).width() );
                    } );
                    ui.css( 'left', '0' );
                    return ui;
                },
                start : function( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop : function( event, ui ) {
                    ui.item.removeAttr( 'style' );
                }
            } );
        },
    };

    var simple_product = {
        /**
         * Subscription Product Actions.
         */
        init : function() {
            this.trigger_on_page_load();

            $( document ).on( 'change', '#sumo_susbcription_status', this.toggle_product_status );
            $( document ).on( 'change', '#sumo_susbcription_trial_enable_disable', this.toggle_trial_status );
            $( document ).on( 'change', '#sumo_susbcription_signusumoee_enable_disable', this.toggle_signup_status );
            $( document ).on( 'change', '#sumo_enable_additional_digital_downloads', this.toggle_additional_digital_downloads_status );
            $( document ).on( 'change', '#sumo_susbcription_fee_type_selector', this.toggle_trial_type );
            $( document ).on( 'change', '#sumo_susbcription_period', this.toggle_duration );
            $( document ).on( 'change', '#sumo_trial_period', this.toggle_duration );
            $( document ).on( 'change', '#sumo_synchronize_period', this.toggle_sync_duration );
            $( document ).on( 'change', '#sumo_susbcription_period_value', this.toggle_duration_length );
            $( document ).on( 'change', '#sumo_synchronize_period_value', this.toggle_sync_duration_length );
            $( document ).on( 'change', '#sumo_subscribed_after_sync_date_type', this.toggle_sync_cutoff_time );
        },
        trigger_on_page_load : function() {
            this.get_subscription_settings( $( '#sumo_susbcription_status' ).val() );
        },
        toggle_product_status : function( evt ) {
            simple_product.get_subscription_settings( $( evt.currentTarget ).val() );
        },
        toggle_trial_status : function( evt ) {
            simple_product.get_trial( $( evt.currentTarget ).val() );
        },
        toggle_signup_status : function( evt ) {
            simple_product.get_signup( $( evt.currentTarget ).val() );
        },
        toggle_additional_digital_downloads_status : function( evt ) {
            simple_product.get_downloadable_products( $( evt.currentTarget ).is( ':checked' ) ? '1' : '2' );
        },
        toggle_trial_type : function( evt ) {
            simple_product.get_trial_type( $( evt.currentTarget ).val() );
        },
        toggle_duration : function( evt ) {
            simple_product.get_duration( $( evt.currentTarget ), true );
        },
        toggle_sync_duration : function( evt ) {
            simple_product.get_sync_advanced_fields();
        },
        toggle_duration_length : function( evt ) {
            if ( 'M' === $( '#sumo_susbcription_period' ).val() ) {
                simple_product.get_month_sync_duration( true );
            }
            simple_product.get_sync_advanced_fields();
        },
        toggle_sync_duration_length : function( evt ) {
            simple_product.get_sync_advanced_fields();
        },
        toggle_sync_cutoff_time : function( evt ) {
            simple_product.get_sync_advanced_fields();
        },
        get_subscription_settings : function( $status ) {
            $status = $status || '';

            if ( '1' === $status ) {
                $( '.sumosubscription_simple' ).show();
                simple_product.get_duration( $( '#sumo_susbcription_period' ) );
                simple_product.get_trial( $( '#sumo_susbcription_trial_enable_disable' ).val() );
                simple_product.get_signup( $( '#sumo_susbcription_signusumoee_enable_disable' ).val() );
                simple_product.get_downloadable_products( $( '#sumo_enable_additional_digital_downloads' ).is( ':checked' ) ? '1' : '2' );
            } else {
                $( '.sumosubscription_simple' ).hide();
            }
        },
        get_duration : function( $duration, set_options ) {
            set_options = set_options || false;
            var duration_length = $duration.is( '#sumo_trial_period' ) ? $( '#sumo_trial_period_value' ) : $( '#sumo_susbcription_period_value' );

            switch ( $duration.val() ) {
                case 'W':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '#sumo_susbcription_period' ) ) {
                        if ( set_options ) {
                            get_sync_duration_options( $duration, $( '#sumo_synchronize_period' ), $( '#sumo_synchronize_period_value' ) );
                        }

                        $( '.sumo_synchronize_duration_fields' ).show();
                        $( '#sumo_synchronize_period_value' ).hide();
                        $( '#sumo_synchronize_period' ).show();
                    }
                    break;
                case 'M':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '#sumo_susbcription_period' ) ) {
                        simple_product.get_month_sync_duration( set_options );
                    }
                    break;
                case 'Y':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '#sumo_susbcription_period' ) ) {
                        if ( set_options ) {
                            get_sync_duration_options( $duration, $( '#sumo_synchronize_period' ), $( '#sumo_synchronize_period_value' ) );
                        }

                        $( '.sumo_synchronize_duration_fields' ).show();
                        $( '#sumo_synchronize_period_value' ).show();
                        $( '#sumo_synchronize_period' ).show();

                        if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                            $( '#sumo_synchronize_period' ).hide();
                        }
                    }
                    break;
                default:
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '#sumo_susbcription_period' ) ) {
                        $( '.sumo_synchronize_duration_fields' ).hide();
                    }
                    break;
            }
            simple_product.get_sync_advanced_fields();
        },
        get_signup : function( $status ) {
            $status = $status || '';
            $( '.sumo_signup_price_field' ).hide();

            if ( $status === '1' || $status === '3' ) {
                $( '.sumo_signup_price_field' ).show();
            }
        },
        get_trial : function( $status ) {
            $status = $status || '';

            $( '.sumo_susbcription_fee_type_selector_field' ).hide();
            $( '.sumo_trial_price_field' ).hide();
            $( '.sumo_trial_period_field' ).hide();
            $( '.sumo_trial_period_value_field' ).hide();

            if ( $status === '1' || $status === '3' ) {
                $( '.sumo_susbcription_fee_type_selector_field' ).show();
                $( '.sumo_trial_period_field' ).show();
                $( '.sumo_trial_period_value_field' ).show();
                simple_product.get_trial_type( $( '#sumo_susbcription_fee_type_selector' ).val() );
            }
        },
        get_downloadable_products : function( $status ) {
            $status = $status || '';
            $( '.sumo_choose_downloadable_products_field' ).hide();

            if ( $status === '1' ) {
                $( '.sumo_choose_downloadable_products_field' ).show();
            }
        },
        get_trial_type : function( $type ) {
            $type = $type || '';

            if ( $type === 'free' ) {
                $( '.sumo_trial_price_field' ).hide();
            } else if ( $type === 'paid' ) {
                $( '.sumo_trial_price_field' ).show();
            }
        },
        get_month_sync_duration : function( set_options ) {
            set_options = set_options || false;

            if ( set_options ) {
                get_sync_duration_options( $( '#sumo_susbcription_period' ), $( '#sumo_synchronize_period' ), $( '#sumo_synchronize_period_value' ) );
            }

            $( '.sumo_synchronize_duration_fields' ).hide();
            $( '#sumo_synchronize_period_value' ).show();
            $( '#sumo_synchronize_period' ).hide();

            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                $( '.sumo_synchronize_duration_fields' ).show();
                $( '#sumo_synchronize_period' ).hide();
            } else {
                if ( ( 12 % $( '#sumo_susbcription_period_value' ).val() === 0 ) || $( '#sumo_susbcription_period_value' ).val() === '24' ) {
                    $( '.sumo_synchronize_duration_fields' ).show();
                    $( '#sumo_synchronize_period' ).show();
                }
            }
        },
        get_sync_advanced_fields : function() {
            var $subscription_period_value = parseInt( $( '#sumo_susbcription_period_value' ).val() );

            $( '#sumo_xtra_time_to_charge_full_fee' ).removeAttr( 'max' );
            $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).removeAttr( 'max' );
            $( '.sumo_subscribed_after_sync_date_type_fields' ).hide();
            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' ).hide();
            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' ).hide();
            $( '.sumo_synchronize_start_year_fields' ).hide();

            switch ( $( '#sumo_susbcription_period' ).val() ) {
                case 'Y':
                    if ( $( '#sumo_synchronize_period_value' ).val() > 0 ) {
                        $( '.sumo_synchronize_start_year_fields' ).show();
                        $( '.sumo_subscribed_after_sync_date_type_fields' ).show();
                        $( '#sumo_synchronize_start_year' ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', $subscription_period_value * 365 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', $subscription_period_value * 365 );
                            }
                        }
                    }
                    break;
                case 'M':
                    if ( $( '#sumo_synchronize_period_value' ).val() > 0 ) {
                        $( '.sumo_subscribed_after_sync_date_type_fields' ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', $subscription_period_value * 28 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', $subscription_period_value * 28 );
                            }
                        }

                        if ( 'exact-date-r-day' === sumosubs_admin_product_params.synchronize_mode ) {
                            if ( ( 12 % $( '#sumo_susbcription_period_value' ).val() === 0 ) || $( '#sumo_susbcription_period_value' ).val() === '24' ) {
                                $( '.sumo_synchronize_start_year_fields' ).show();
                                $( '#sumo_synchronize_start_year' ).show();
                            } else {
                                $( '.sumo_subscribed_after_sync_date_type_fields' ).hide();
                                $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' ).hide();
                                $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' ).hide();
                            }
                        }
                    }
                    break;
                case 'W':
                    if ( $( '#sumo_synchronize_period' ).val() > 0 ) {
                        $( '.sumo_subscribed_after_sync_date_type_fields' ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', 7 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' ).attr( 'max', $subscription_period_value * 7 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', 7 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' ).attr( 'max', $subscription_period_value * 7 );
                            }
                        }
                    }
                    break;
            }
        },
    };

    var variation_product = {
        /**
         * Subscription Variation Actions.
         */
        init : function( variation_row_index ) {
            this.trigger_on_page_load( variation_row_index );

            $( document ).on( 'change', '[name="sumo_susbcription_status[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_product_status );
            $( document ).on( 'change', '[name="sumo_susbcription_trial_enable_disable[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_trial_status );
            $( document ).on( 'change', '[name="sumo_susbcription_signusumoee_enable_disable[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_signup_status );
            $( document ).on( 'change', '[name="sumo_enable_additional_digital_downloads[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_additional_digital_downloads_status );
            $( document ).on( 'change', '[name="sumo_susbcription_fee_type_selector[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_trial_type );
            $( document ).on( 'change', '[name="sumo_susbcription_period[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_duration );
            $( document ).on( 'change', '[name="sumo_trial_period[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_duration );
            $( document ).on( 'change', '[name="sumo_synchronize_period[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_sync_duration );
            $( document ).on( 'change', '[name="sumo_susbcription_period_value[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_duration_length );
            $( document ).on( 'change', '[name="sumo_synchronize_period_value[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_sync_duration_length );
            $( document ).on( 'change', '[name="sumo_subscribed_after_sync_date_type[' + variation_row_index + ']"]', { i : variation_row_index }, this.toggle_sync_cutoff_time );
        },
        trigger_on_page_load : function( i ) {
            this.get_subscription_settings( $( '[name="sumo_susbcription_status[' + i + ']"]' ).val(), i );
        },
        toggle_product_status : function( evt ) {
            variation_product.get_subscription_settings( $( evt.currentTarget ).val(), evt.data.i );
        },
        toggle_trial_status : function( evt ) {
            variation_product.get_trial( $( evt.currentTarget ).val(), evt.data.i );
        },
        toggle_signup_status : function( evt ) {
            variation_product.get_signup( $( evt.currentTarget ).val(), evt.data.i );
        },
        toggle_additional_digital_downloads_status : function( evt ) {
            variation_product.get_downloadable_products( $( evt.currentTarget ).is( ':checked' ) ? '1' : '2', evt.data.i );
        },
        toggle_trial_type : function( evt ) {
            variation_product.get_trial_type( $( evt.currentTarget ).val(), evt.data.i );
        },
        toggle_duration : function( evt ) {
            variation_product.get_duration( $( evt.currentTarget ), evt.data.i, true );
        },
        toggle_sync_duration : function( evt ) {
            variation_product.get_sync_advanced_fields( evt.data.i );
        },
        toggle_duration_length : function( evt ) {
            if ( 'M' === $( '[name="sumo_susbcription_period[' + evt.data.i + ']"]' ).val() ) {
                variation_product.get_month_sync_duration( evt.data.i, true );
            }
            variation_product.get_sync_advanced_fields( evt.data.i );
        },
        toggle_sync_duration_length : function( evt ) {
            variation_product.get_sync_advanced_fields( evt.data.i );
        },
        toggle_sync_cutoff_time : function( evt ) {
            variation_product.get_sync_advanced_fields( evt.data.i );
        },
        get_subscription_settings : function( $status, i ) {
            $status = $status || '';

            if ( '1' === $status ) {
                $( '.sumosubscription_variable' + i ).show();
                variation_product.get_duration( $( '[name="sumo_susbcription_period[' + i + ']"]' ), i );
                variation_product.get_trial( $( '[name="sumo_susbcription_trial_enable_disable[' + i + ']"]' ).val(), i );
                variation_product.get_signup( $( '[name="sumo_susbcription_signusumoee_enable_disable[' + i + ']"]' ).val(), i );
                variation_product.get_downloadable_products( $( '[name="sumo_enable_additional_digital_downloads[' + i + ']"]' ).is( ':checked' ) ? '1' : '2', i );
            } else {
                $( '.sumosubscription_variable' + i ).hide();
            }
        },
        get_duration : function( $duration, i, set_options ) {
            set_options = set_options || false;
            var duration_length = $duration.is( '[name="sumo_trial_period[' + i + ']"]' ) ? $( '[name="sumo_trial_period_value[' + i + ']"]' ) : $( '[name="sumo_susbcription_period_value[' + i + ']"]' );

            switch ( $duration.val() ) {
                case 'W':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '[name="sumo_susbcription_period[' + i + ']"]' ) ) {
                        if ( set_options ) {
                            get_sync_duration_options( $duration, $( '[name="sumo_synchronize_period[' + i + ']"]' ), $( '[name="sumo_synchronize_period_value[' + i + ']"]' ) );
                        }

                        $( '.sumo_synchronize_duration_fields' + i ).show();
                        $( '[name="sumo_synchronize_period_value[' + i + ']"]' ).hide();
                        $( '[name="sumo_synchronize_period[' + i + ']"]' ).show();
                    }
                    break;
                case 'M':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '[name="sumo_susbcription_period[' + i + ']"]' ) ) {
                        variation_product.get_month_sync_duration( i, set_options );
                    }
                    break;
                case 'Y':
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '[name="sumo_susbcription_period[' + i + ']"]' ) ) {
                        if ( set_options ) {
                            get_sync_duration_options( $duration, $( '[name="sumo_synchronize_period[' + i + ']"]' ), $( '[name="sumo_synchronize_period_value[' + i + ']"]' ) );
                        }

                        $( '.sumo_synchronize_duration_fields' + i ).show();
                        $( '[name="sumo_synchronize_period_value[' + i + ']"]' ).show();
                        $( '[name="sumo_synchronize_period[' + i + ']"]' ).show();

                        if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                            $( '[name="sumo_synchronize_period[' + i + ']"]' ).hide();
                        }
                    }
                    break;
                default:
                    if ( set_options ) {
                        get_duration_options( $duration, duration_length );
                    }

                    if ( $duration.is( '[name="sumo_susbcription_period[' + i + ']"]' ) ) {
                        $( '.sumo_synchronize_duration_fields' + i ).hide();
                    }
                    break;
            }
            variation_product.get_sync_advanced_fields( i );
        },
        get_signup : function( $status, i ) {
            $status = $status || '';
            $( '[name="sumo_signup_price[' + i + ']"]' ).parent().hide();

            if ( $status === '1' || $status === '3' ) {
                $( '[name="sumo_signup_price[' + i + ']"]' ).parent().show();
            }
        },
        get_trial : function( $status, i ) {
            $status = $status || '';

            $( '[name="sumo_susbcription_fee_type_selector[' + i + ']"]' ).parent().hide();
            $( '[name="sumo_trial_price[' + i + ']"]' ).parent().hide();
            $( '[name="sumo_trial_period[' + i + ']"]' ).parent().hide();
            $( '[name="sumo_trial_period_value[' + i + ']"]' ).parent().hide();

            if ( $status === '1' || $status === '3' ) {
                $( '[name="sumo_susbcription_fee_type_selector[' + i + ']"]' ).parent().show();
                $( '[name="sumo_trial_period[' + i + ']"]' ).parent().show();
                $( '[name="sumo_trial_period_value[' + i + ']"]' ).parent().show();
                variation_product.get_trial_type( $( '[name="sumo_susbcription_fee_type_selector[' + i + ']"]' ).val(), i );
            }
        },
        get_downloadable_products : function( $status, i ) {
            $status = $status || '';
            $( '.sumo_choose_downloadable_products_field' + i ).hide();

            if ( $status === '1' ) {
                $( '.sumo_choose_downloadable_products_field' + i ).show();
            }
        },
        get_trial_type : function( $type, i ) {
            $type = $type || '';

            if ( $type === 'free' ) {
                $( '[name="sumo_trial_price[' + i + ']"]' ).parent().hide();
            } else if ( $type === 'paid' ) {
                $( '[name="sumo_trial_price[' + i + ']"]' ).parent().show();
            }
        },
        get_month_sync_duration : function( i, set_options ) {
            set_options = set_options || false;

            if ( set_options ) {
                get_sync_duration_options( $( '[name="sumo_susbcription_period[' + i + ']"]' ), $( '[name="sumo_synchronize_period[' + i + ']"]' ), $( '[name="sumo_synchronize_period_value[' + i + ']"]' ) );
            }

            $( '[name="sumo_synchronize_period_value[' + i + ']"]' ).show();
            $( '[name="sumo_synchronize_period[' + i + ']"]' ).hide();
            $( '.sumo_synchronize_duration_fields' + i ).hide();

            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                $( '.sumo_synchronize_duration_fields' + i ).show();
                $( '[name="sumo_synchronize_period[' + i + ']"]' ).hide();
            } else {
                if ( ( 12 % $( '[name="sumo_susbcription_period_value[' + i + ']"]' ).val() === 0 ) || $( '[name="sumo_susbcription_period_value[' + i + ']"]' ).val() === '24' ) {
                    $( '.sumo_synchronize_duration_fields' + i ).show();
                    $( '[name="sumo_synchronize_period[' + i + ']"]' ).show();
                }
            }
        },
        get_sync_advanced_fields : function( i ) {
            var $subscription_period_value = parseInt( $( '[name="sumo_susbcription_period_value[' + i + ']"]' ).val() );

            $( '#sumo_xtra_time_to_charge_full_fee' + i ).removeAttr( 'max' );
            $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).removeAttr( 'max' );
            $( '.sumo_subscribed_after_sync_date_type_fields' + i ).hide();
            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' + i ).hide();
            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' + i ).hide();
            $( '.sumo_synchronize_start_year_fields' + i ).hide();

            switch ( $( '[name="sumo_susbcription_period[' + i + ']"]' ).val() ) {
                case 'Y':
                    if ( $( '[name="sumo_synchronize_period_value[' + i + ']"]' ).val() > 0 ) {
                        $( '.sumo_synchronize_start_year_fields' + i ).show();
                        $( '.sumo_subscribed_after_sync_date_type_fields' + i ).show();
                        $( '#sumo_synchronize_start_year' + i ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' + i ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', $subscription_period_value * 365 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', $subscription_period_value * 365 );
                            }
                        }

                        if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                            $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', 28 );
                        } else {
                            $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', $subscription_period_value * 365 );
                        }
                    }
                    break;
                case 'M':
                    if ( $( '[name="sumo_synchronize_period_value[' + i + ']"]' ).val() > 0 ) {
                        $( '.sumo_subscribed_after_sync_date_type_fields' + i ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' + i ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', $subscription_period_value * 28 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', 28 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', $subscription_period_value * 28 );
                            }
                        }

                        if ( 'exact-date-r-day' === sumosubs_admin_product_params.synchronize_mode ) {
                            if ( ( 12 % $( '[name="sumo_susbcription_period_value[' + i + ']"]' ).val() === 0 ) || $( '[name="sumo_susbcription_period_value[' + i + ']"]' ).val() === '24' ) {
                                $( '.sumo_synchronize_start_year_fields' + i ).show();
                                $( '#sumo_synchronize_start_year' + i ).show();
                            } else {
                                $( '.sumo_subscribed_after_sync_date_type_fields' + i ).hide();
                                $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' + i ).hide();
                                $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' + i ).hide();
                            }
                        }
                    }
                    break;
                case 'W':
                    if ( $( '[name="sumo_synchronize_period[' + i + ']"]' ).val() > 0 ) {
                        $( '.sumo_subscribed_after_sync_date_type_fields' + i ).show();

                        if ( 'cutoff-time-to-not-renew-nxt-subs-cycle' === $( '#sumo_subscribed_after_sync_date_type' + i ).val() ) {
                            $( '.sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', 7 );
                            } else {
                                $( '#sumo_cutoff_time_to_not_renew_nxt_subs_cycle' + i ).attr( 'max', $subscription_period_value * 7 );
                            }
                        } else {
                            $( '.sumo_xtra_time_to_charge_full_fee_in_sync_fields' + i ).show();

                            if ( 'first-occurrence' === sumosubs_admin_product_params.synchronize_mode ) {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', 7 );
                            } else {
                                $( '#sumo_xtra_time_to_charge_full_fee' + i ).attr( 'max', $subscription_period_value * 7 );
                            }
                        }
                    }
                    break;
            }
        },
    };

    product.init();

} );
