( ( ) => {
    'use strict';

    var external_plugins = window["wp"]["plugins"];
    var external_element = window["wp"]["element"];
    var external_blocks = window["wp"]["blocks"];
    var external_blockEditor = window["wp"]["blockEditor"];
    var external_i18n = window["wp"]["i18n"];
    var external_data = window["wp"]["data"];
    var external_compose = window["wp"]["compose"];
    var external_components = window["wp"]["components"];
    var external_primitives = window["wp"]["primitives"];
    var external_htmlEntities = window["wp"]["htmlEntities"];
    var external_wc_blocksCheckout = window["wc"]["blocksCheckout"];
    var external_wc_priceFormat = window["wc"]["priceFormat"];
    var external_wc_settings = window["wc"]["wcSettings"];
    var external_wc_BlocksRegistry = window["wc"]["wcBlocksRegistry"];
    
    const paymentMethod = external_wc_settings.getPaymentMethodData( "sumo_paypal_reference_txns" ),
            label = external_htmlEntities.decodeEntities( null !== paymentMethod ? paymentMethod.title : "" ),
            description = external_htmlEntities.decodeEntities( null !== paymentMethod ? paymentMethod.description : "" );

    const paymentMethodContent = function( e ) {
        return external_element.createElement( external_element.RawHTML, null, description );
    };
        
    var callBack = {
        ourData : null,
        isOurs : function( e ) {
            // Bail out early.
            if ( undefined === e['sumosubscriptions'] ) {
                return false;
            }

            callBack.ourData = e['sumosubscriptions'];
            return true;
        },
        cartFilters : {
            cartItemClass : function( v, e, a ) {
                if ( callBack.isOurs( e ) && callBack.ourData.is_sumosubs ) {
                    v = 'sumosubs';
                }

                return v;
            }
        },
        cartElements : {
            init : function( e ) {
                if ( callBack.isOurs( e.extensions ) && callBack.ourData.is_sumosubs ) {
                    return external_element.createElement( callBack.cartElements.recurringInfo.init );
                }

                return null;
            },
            recurringInfo : {
                init : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumosubs-order-subscription-recurring-panel" },
                            external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                                className : "sumosubs-order-subscription-recurring-panel__title",
                                label : external_i18n.__( "Recurring Totals:", 'sumosubscriptions' ),
                                value : external_element.createElement( external_element.RawHTML, null, callBack.ourData.order_subscription_recurring_info )
                            } ) );
                }
            }
        },
        cartBlocks : {
            init : function( e ) {
                if ( callBack.isOurs( e.extensions ) && callBack.ourData.order_subscribe_enabled ) {
                    return external_element.createElement( callBack.cartBlocks.orderSubscribe.init, e );
                }

                return null;
            },
            orderSubscribe : {
                cartSchema : JSON.parse( "{\"name\":\"woocommerce/cart-order-summary-sumosubs-order-subscribe-block\",\"icon\":\"schedule\",\"keywords\":[\"subscribe\"],\"version\":\"1.0.0\",\"title\":\"Order Subscription\",\"description\":\"Shows the order subscribe form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"sumosubscriptions\",\"apiVersion\":2}" ),
                checkoutSchema : JSON.parse( "{\"name\":\"woocommerce/checkout-order-summary-sumosubs-order-subscribe-block\",\"icon\":\"schedule\",\"keywords\":[\"subscribe\"],\"version\":\"1.0.0\",\"title\":\"Order Subscription\",\"description\":\"Shows the order subscribe form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"sumosubscriptions\",\"apiVersion\":2}" ),
                isLoading : false,
                setLoading : null,
                init : function( e ) {
                    return external_element.createElement( external_element.Fragment, null,
                            external_element.createElement( callBack.cartBlocks.orderSubscribe.form, null ) );
                },
                form : function() {
                    [ callBack.cartBlocks.orderSubscribe.isLoading, callBack.cartBlocks.orderSubscribe.setLoading ] = external_element.useState( false );

                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumosubs-order-subscribe-form-wrapper " + ( callBack.cartBlocks.orderSubscribe.isLoading ? "sumosubs-component--disabled" : "" ) },
                            external_element.createElement( external_element.RawHTML, null, callBack.ourData.order_subscribe_form_html ) );
                },
            }
        },
        render : function( ) {
            return external_element.createElement( external_element.Fragment, null,
                    external_element.createElement( external_wc_blocksCheckout.ExperimentalOrderMeta, null,
                            external_element.createElement( callBack.cartElements.init, null ) ) );
        }
    };

    jQuery( document ).on( 'sumosubs_order_subscription_updated', function( e ) {
        callBack.cartBlocks.orderSubscribe.setLoading( true );
        jQuery( '.sumosubs-order-subscribe-form-wrapper select,.sumosubs-order-subscribe-form-wrapper input' ).prop( 'disabled', true );
        external_wc_blocksCheckout.extensionCartUpdate( {
            namespace : 'sumosubscriptions',
            data : {
                action : 'refresh_cart'
            }
        } ).then( function( e ) {
            callBack.isOurs( e.extensions );

            if ( ! callBack.ourData.is_sumosubs ) {
                jQuery( '.sumosubs-order-subscribe-form-wrapper .sumo_order_subscription_subscribe_duration' ).slideUp();
                jQuery( '.sumosubs-order-subscribe-form-wrapper .sumo_order_subscription_subscribe_length' ).slideUp();
            }
        } ).finally( function() {
            callBack.cartBlocks.orderSubscribe.setLoading( false );
            jQuery( '.sumosubs-order-subscribe-form-wrapper select,.sumosubs-order-subscribe-form-wrapper input' ).prop( 'disabled', false );
        } );
    } );

    external_wc_blocksCheckout.registerCheckoutFilters( 'sumosubscriptions', {
        cartItemClass : callBack.cartFilters.cartItemClass
    } );

    external_wc_blocksCheckout.registerCheckoutBlock( {
        metadata : callBack.cartBlocks.orderSubscribe.cartSchema,
        component : callBack.cartBlocks.init
    } );

    external_wc_blocksCheckout.registerCheckoutBlock( {
        metadata : callBack.cartBlocks.orderSubscribe.checkoutSchema,
        component : callBack.cartBlocks.init
    } );

    external_plugins.registerPlugin( "sumosubscriptions", {
        render : callBack.render,
        scope : "woocommerce-checkout"
    } );
    
    external_wc_BlocksRegistry.registerPaymentMethod( {
        name : "sumo_paypal_reference_txns",
        label : label,
        ariaLabel : label,
        content : external_element.createElement( paymentMethodContent ),
        edit : external_element.createElement( paymentMethodContent ),
        canMakePayment : ( e ) => {
            return null !== paymentMethod ? paymentMethod.is_available : false;
        },
        supports : {
            features : null !== paymentMethod ? paymentMethod.supports : [ ]
        }
    } );
    
} )( );