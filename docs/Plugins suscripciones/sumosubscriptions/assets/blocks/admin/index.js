( () => {
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
    var external_wc_blocksCheckout = window["wc"]["blocksCheckout"];
    var external_wc_priceFormat = window["wc"]["priceFormat"];
    var external_wc_settings = window["wc"]["wcSettings"];

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
        cartBlocks : {
            orderSubscribe : {
                cartSchema : JSON.parse( "{\"name\":\"woocommerce/cart-order-summary-sumosubs-order-subscribe-block\",\"icon\":\"schedule\",\"keywords\":[\"subscribe\"],\"version\":\"1.0.0\",\"title\":\"Order Subscription\",\"description\":\"Shows the order subscribe form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"sumosubscriptions\",\"apiVersion\":2}" ),
                checkoutSchema : JSON.parse( "{\"name\":\"woocommerce/checkout-order-summary-sumosubs-order-subscribe-block\",\"icon\":\"schedule\",\"keywords\":[\"subscribe\"],\"version\":\"1.0.0\",\"title\":\"Order Subscription\",\"description\":\"Shows the order subscribe form.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"sumosubscriptions\",\"apiVersion\":2}" ),
                init : function() {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsWrapper, { className : "sumosubs-order-subscribe-form-wrapper" },
                            external_element.createElement( callBack.cartBlocks.orderSubscribe.subscribeOption ) );
                },
                edit : function( e ) {
                    return external_element.createElement( "div", external_blockEditor.useBlockProps(),
                            external_element.createElement( callBack.cartBlocks.orderSubscribe.init ) );
                },
                save : function( e ) {
                    return external_element.createElement( "div", external_blockEditor.useBlockProps.save() );
                },
                subscribeOption : function( e ) {
                    return external_element.createElement( external_wc_blocksCheckout.TotalsItem, {
                        className : "sumosubs-order-subscribe-form-wrapper__subscribe-option",
                        label : external_element.createElement( external_wc_blocksCheckout.CheckboxControl, {
                            className : "sumosubs-subscribe-order",
                            disabled : true
                        }, external_i18n.__( "Enable Order Subscription", 'sumosubscriptions' ) ) } );
                }
            }
        }
    };

    // Register Block in the Editor.
    external_blocks.registerBlockType( callBack.cartBlocks.orderSubscribe.cartSchema.name, {
        title : callBack.cartBlocks.orderSubscribe.cartSchema.title, // Localize title using wp.i18n.__()
        version : callBack.cartBlocks.orderSubscribe.cartSchema.version,
        description : callBack.cartBlocks.orderSubscribe.cartSchema.description,
        category : callBack.cartBlocks.orderSubscribe.cartSchema.category, // Category Options: common, formatting, layout, widgets, embed
        supports : callBack.cartBlocks.orderSubscribe.cartSchema.supports,
        icon : callBack.cartBlocks.orderSubscribe.cartSchema.icon, // Dashicons Options – https://goo.gl/aTM1DQ
        keywords : callBack.cartBlocks.orderSubscribe.cartSchema.keywords, // Limit to 3 Keywords / Phrases
        parent : callBack.cartBlocks.orderSubscribe.cartSchema.parent,
        textdomain : callBack.cartBlocks.orderSubscribe.cartSchema.textdomain,
        apiVersion : callBack.cartBlocks.orderSubscribe.cartSchema.apiVersion,
        attributes : callBack.cartBlocks.orderSubscribe.cartSchema.attributes, // Attributes set for each piece of dynamic data used in your block
        edit : callBack.cartBlocks.orderSubscribe.edit, // Determines what is displayed in the editor
        save : callBack.cartBlocks.orderSubscribe.save // Determines what is displayed on the frontend
    } );

    external_blocks.registerBlockType( callBack.cartBlocks.orderSubscribe.checkoutSchema.name, {
        title : callBack.cartBlocks.orderSubscribe.checkoutSchema.title, // Localize title using wp.i18n.__()
        version : callBack.cartBlocks.orderSubscribe.checkoutSchema.version,
        description : callBack.cartBlocks.orderSubscribe.checkoutSchema.description,
        category : callBack.cartBlocks.orderSubscribe.checkoutSchema.category, // Category Options: common, formatting, layout, widgets, embed
        supports : callBack.cartBlocks.orderSubscribe.checkoutSchema.supports,
        icon : callBack.cartBlocks.orderSubscribe.checkoutSchema.icon, // Dashicons Options – https://goo.gl/aTM1DQ
        keywords : callBack.cartBlocks.orderSubscribe.checkoutSchema.keywords, // Limit to 3 Keywords / Phrases
        parent : callBack.cartBlocks.orderSubscribe.checkoutSchema.parent,
        textdomain : callBack.cartBlocks.orderSubscribe.checkoutSchema.textdomain,
        apiVersion : callBack.cartBlocks.orderSubscribe.checkoutSchema.apiVersion,
        attributes : callBack.cartBlocks.orderSubscribe.checkoutSchema.attributes, // Attributes set for each piece of dynamic data used in your block
        edit : callBack.cartBlocks.orderSubscribe.edit, // Determines what is displayed in the editor
        save : callBack.cartBlocks.orderSubscribe.save // Determines what is displayed on the frontend
    } );
} )();