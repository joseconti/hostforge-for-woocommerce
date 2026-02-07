/* global sumosubs_checkout_params */

jQuery( function( $ ) {
    // sumosubs_checkout_params is required to continue, ensure the object exists
    if ( typeof sumosubs_checkout_params === 'undefined' ) {
        return false;
    }

    var block_wrapper = function() {
        if ( 'cart' === sumosubs_checkout_params.current_page ) {
            return $( 'div.cart_totals' );
        } else {
            return $( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table' ).closest( 'form' );
        }
    };

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

    var checkout = {
        /**
         * Manage Cart/Checkout page events.
         */
        sync : false,
        isSubscribed : false,
        subscriptionDuration : null,
        subscriptionDurationValue : null,
        subscriptionRecurring : null,
        init : function() {
            this.onPageLoad();

            $( document ).on( 'change', '#sumo_order_subscription_status', this.toggleOrderSubscription );
            $( document ).on( 'change', '#sumo_order_subscription_duration', this.toggleOrderSubscriptionDuration );
            $( document ).on( 'change', '#sumo_order_subscription_duration_value', this.toggleOrderSubscriptionDurationValue );
            $( document ).on( 'change', '#sumo_order_subscription_recurring', this.toggleOrderSubscriptionRecurring );
            $( document ).on( 'updated_wc_div', this.mayBeDisplayOrderSubscription );
            $( document ).on( 'updated_cart_totals', this.mayBeDisplayOrderSubscription );
        },
        onPageLoad : function() {
            if ( 'checkout' === sumosubs_checkout_params.current_page ) {
                checkout.sync = true;
            }

            this.getOrderSubscription();
        },
        toggleOrderSubscription : function( evt ) {
            evt.preventDefault();
            checkout.sync = false;
            checkout.getOrderSubscription();
        },
        toggleOrderSubscriptionDuration : function( evt ) {
            evt.preventDefault();
            var duration_options = { };
            var $elements = $( '#sumo_order_subscription_duration_value' );
            $elements.empty();

            switch ( $( evt.currentTarget ).val() ) {
                case 'W':
                    duration_options = sumosubs_checkout_params.subscription_week_duration_options;
                    break;
                case 'M':
                    duration_options = sumosubs_checkout_params.subscription_month_duration_options;
                    break;
                case 'Y':
                    duration_options = sumosubs_checkout_params.subscription_year_duration_options;
                    break;
                default :
                    duration_options = sumosubs_checkout_params.subscription_day_duration_options;
                    break;
            }

            $.each( duration_options, function( value, key ) {
                $elements.append( $( '<option></option>' ).attr( 'value', value ).text( key ) );
            } );

            checkout.updateCheckout();
        },
        toggleOrderSubscriptionDurationValue : function( evt ) {
            evt.preventDefault();
            checkout.updateCheckout();
        },
        toggleOrderSubscriptionRecurring : function( evt ) {
            evt.preventDefault();
            checkout.updateCheckout();
        },
        mayBeDisplayOrderSubscription : function() {
            $( '.sumo_order_subscription_subscribe_duration' ).slideUp();
            $( '.sumo_order_subscription_subscribe_length' ).slideUp();

            if ( $( '#sumo_order_subscription_status' ).is( ':checked' ) ) {
                $( '.sumo_order_subscription_subscribe_duration' ).slideDown();
                $( '.sumo_order_subscription_subscribe_length' ).slideDown();
            }
        },
        getOrderSubscription : function() {
            checkout.mayBeDisplayOrderSubscription();
            checkout.updateCheckout();
        },
        populateSubscriptionPlan : function() {
            checkout.isSubscribed = $( '#sumo_order_subscription_status' ).is( ':checked' );
            checkout.subscriptionDuration = sumosubs_checkout_params.default_order_subscription_duration;
            checkout.subscriptionDurationValue = sumosubs_checkout_params.default_order_subscription_duration_value;
            checkout.subscriptionRecurring = sumosubs_checkout_params.default_order_subscription_installment;

            if ( sumosubs_checkout_params.can_user_select_plan ) {
                checkout.subscriptionDuration = $( '#sumo_order_subscription_duration' ).val();
                checkout.subscriptionDurationValue = $( '#sumo_order_subscription_duration_value' ).val();
                checkout.subscriptionRecurring = 'undefined' === typeof $( '#sumo_order_subscription_recurring' ).val() ? '0' : $( '#sumo_order_subscription_recurring' ).val();
            }
        },
        updateCheckout : function() {
            checkout.populateSubscriptionPlan();

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( block_wrapper() );

            $.ajax( {
                type : 'POST',
                url : sumosubs_checkout_params.wp_ajax_url,
                dataType : 'text',
                async : sumosubs_checkout_params.load_ajax_synchronously ? false : ( checkout.sync ? false : true ),
                data : {
                    action : 'sumosubscription_checkout_order_subscription',
                    security : sumosubs_checkout_params.update_order_subscription_nonce,
                    subscribed : checkout.isSubscribed ? 'yes' : 'no',
                    subscription_duration : checkout.subscriptionDuration,
                    subscription_duration_value : checkout.subscriptionDurationValue,
                    subscription_recurring : checkout.subscriptionRecurring
                },
                success : function() {
                    if ( 'checkout' === sumosubs_checkout_params.current_page ) {
                        checkout.forceRenderSignupFormIfGuest();

                        $( document.body ).trigger( 'update_checkout' );
                    }
                    
                    $( document.body ).trigger( 'sumosubs_order_subscription_updated' );
                },
                complete : function() {
                    unblock( block_wrapper() );

                    if ( $( '.woocommerce-cart-form' ).length && 'cart' === sumosubs_checkout_params.current_page ) {
                        $( document.body ).trigger( 'wc_update_cart' );
                    }

                    checkout.clearCache();
                }
            } );
        },
        forceRenderSignupFormIfGuest : function() {
            if ( sumosubs_checkout_params.is_user_logged_in ) {
                return false;
            }

            if ( $( 'p.create-account' ).length ) {
                $( 'p.create-account' ).show();
                $( 'div.create-account' ).slideUp();
            }

            if ( sumosubs_checkout_params.maybe_prevent_from_hiding_guest_signup_form && sumosubs_checkout_params.can_user_subscribe_for_order ) {
                $( 'div.create-account' ).slideUp();
            }

            if ( checkout.isSubscribed ) {
                if ( $( 'p.create-account' ).length ) {
                    $( 'p.create-account' ).hide();
                }
                if ( $( 'div.create-account' ).length ) {
                    $( 'div.create-account' ).slideDown();
                }
            }
        },
        clearCache : function() {
            checkout.sync = false;
            checkout.isSubscribed = false;
            checkout.subscriptionDuration = null;
            checkout.subscriptionDurationValue = null;
            checkout.subscriptionRecurring = null;
        }
    };

    //may be Order Subscription is subscribed.
    if ( sumosubs_checkout_params.can_user_subscribe_for_order ) {
        checkout.init();
    }
} );
