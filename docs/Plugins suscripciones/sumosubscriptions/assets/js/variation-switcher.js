/* global sumosubs_variation_switcher_params */

jQuery( function( $ ) {
    // sumosubs_variation_switcher_params is required to continue, ensure the object exists
    if ( typeof sumosubs_variation_switcher_params === 'undefined' ) {
        return false;
    }

    var $variation_switcher_div = $( '.sumo_subscription_variation_switcher, .sumo_subscription_details' ).closest( 'div' );

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

    var variation_switcher = {
        /**
         * Manage Variation Switcher UI events.
         */
        init : function() {
            $( document ).on( 'click', '.variation_switch_button', this.loadVariationSwitcher );
            $( document ).on( 'click', '.variation_switch_submit', this.onSubmitVariationSwitch );
            $( document ).on( 'click', '.reset_variation_switch', this.onResetVariationSwitch );
            $( document ).on( 'change', '.variation_attribute_switch_selector', this.onLoadVariationAttributesUponSwitch );
        },
        loadVariationSwitcher : function( evt ) {
            var $this = $( evt.currentTarget );
            var $post_id = $this.data( 'post_id' );

            $this.hide( );

            $( '.variation_attribute_switch_selector_' + $post_id ).show( );
            $( '#variation_switch_submit_' + $post_id ).show( );
        },
        onResetVariationSwitch : function( evt ) {
            var $this = $( evt.currentTarget );
            var $post_id = $this.data( 'post_id' );

            variation_switcher.resetVariationSwitch( '#reset_variation_switch_' + $post_id, $post_id );
            $this.hide( );
        },
        onSubmitVariationSwitch : function( evt ) {
            var $this = $( evt.currentTarget );
            var $post_id = $this.attr( 'data-post_id' );
            var $plan_matched_attributes_key = $this.data( 'plan_matched_attributes_key' );
            var attribute_value_to_switch = [ ];
            var attribute_selected = [ ];

            $.each( $plan_matched_attributes_key, function( key, attribute_value ) {
                var attr_selected = $( '#variation_attribute_selected_' + attribute_value + '_' + $post_id );
                var attr_field = $( '#variation_attribute_switch_selector_' + attribute_value + '_' + $post_id );

                attribute_selected.push( attr_selected.val() );
                attribute_value_to_switch.push( attr_field.val() );
            } );

            if ( $( attribute_value_to_switch ).not( attribute_selected ).length === 0 && $( attribute_selected ).not( attribute_value_to_switch ).length === 0 ) {
                alert( sumosubs_variation_switcher_params.same_variation_message );
                return false;
            }

            $.blockUI.defaults.overlayCSS.cursor = 'wait';
            block( $variation_switcher_div );

            $.ajax( {
                type : 'POST',
                url : sumosubs_variation_switcher_params.wp_ajax_url,
                data : {
                    action : 'sumosubscription_save_swapped_subscription_variation',
                    security : sumosubs_variation_switcher_params.variation_switch_submit_nonce,
                    post_id : $post_id,
                    switched_by : sumosubs_variation_switcher_params.switched_by,
                    plan_matched_attributes_key : $plan_matched_attributes_key,
                    attribute_value_to_switch : attribute_value_to_switch
                },
                success : function( data ) {
                    if ( data === '1' ) {
                        alert( sumosubs_variation_switcher_params.success_message );
                    } else if ( data === '2' ) {
                        alert( sumosubs_variation_switcher_params.notice_message );
                    } else {
                        alert( sumosubs_variation_switcher_params.failure_message );
                    }

                    variation_switcher.resetVariationSwitch( '#reset_variation_switch_' + $post_id, $post_id );
                },
                complete : function() {
                    unblock( $variation_switcher_div );
                    window.location.reload();
                }
            } );
        },
        onLoadVariationAttributesUponSwitch : function( evt ) {
            var $this = $( evt.currentTarget );
            var $this_selected_attribute_value = $this.val( );
            var $this_selected_attribute_key = $this.data( 'selected_attribute_key' );
            var $plan_matched_attributes_key = $this.data( 'plan_matched_attributes_key' );
            var $post_id = $this.data( 'post_id' );
            var this_selected_attributes = [ ], this_selected_attributes_val = [ ];
            var object = { };

            $( '#load_variation_attributes_' + $post_id ).show();
            $( '#reset_variation_switch_' + $post_id ).show();

            if ( $this_selected_attribute_value.indexOf( 'attribute_' ) !== - 1 ) {
                $( '#load_variation_attributes_' + $post_id ).hide();
                return false;
            }

            $.each( $plan_matched_attributes_key, function( index, matched_attribute_key ) {
                var $matched_attribute_value = $( '#variation_attribute_switch_selector_' + matched_attribute_key + '_' + $post_id ).val();

                if ( 'attribute_' + $matched_attribute_value !== matched_attribute_key ) {
                    object[matched_attribute_key] = $matched_attribute_value;
                    this_selected_attributes.push( object );
                    this_selected_attributes_val.push( $matched_attribute_value );
                }
            } );

            $.ajax( {
                type : 'POST',
                url : sumosubs_variation_switcher_params.wp_ajax_url,
                data : {
                    action : 'sumosubscription_get_subscription_variation_attributes_upon_switch',
                    security : sumosubs_variation_switcher_params.variation_swapping_nonce,
                    post_id : $post_id,
                    selected_attributes : this_selected_attributes,
                    selected_attribute_key : $this_selected_attribute_key,
                    selected_attribute_value : $this_selected_attribute_value,
                },
                success : function( plan_matched_variation ) {
                    $( '#load_variation_attributes_' + $post_id ).hide();

                    var filtered_attributes_value = [ ];

                    $.each( $plan_matched_attributes_key, function( index, matched_attribute_key ) {
                        if ( plan_matched_variation !== '' ) {
                            var default_select_caption = sumosubs_variation_switcher_params.i18n_default_attribute_select + matched_attribute_key.replace( 'attribute_', '' ) + ' ..';

                            filtered_attributes_value.push( default_select_caption );

                            $.each( plan_matched_variation, function( index, variations ) {
                                $.each( variations, function( attribute_key, attribute_value ) {
                                    if ( typeof ( attribute_value ) === 'object' ) {
                                        $.each( attribute_value, function( index, each_attr_value ) {
                                            if ( attribute_key === matched_attribute_key && $.inArray( each_attr_value, filtered_attributes_value ) === - 1 ) {
                                                filtered_attributes_value.push( each_attr_value );
                                            }
                                        } );
                                    } else {
                                        if ( attribute_key === matched_attribute_key && $.inArray( attribute_value, filtered_attributes_value ) === - 1 ) {
                                            filtered_attributes_value.push( attribute_value );
                                        }
                                    }
                                } );
                            } );
                            var $elements = $( '#variation_attribute_switch_selector_' + matched_attribute_key + '_' + $post_id );
                            $elements.empty( );

                            if ( filtered_attributes_value === '' ) {
                                $( '#variation_attribute_switch_selector_' + matched_attribute_key + '_' + $post_id ).hide( );
                            } else {
                                $.each( filtered_attributes_value, function( key, value ) {
                                    if ( value !== '' ) {
                                        if ( default_select_caption === value ) {
                                            $elements.append( $( '<option></option>' ).attr( 'value', matched_attribute_key ).text( sumosubs_variation_switcher_params.i18n_default_attribute_select + matched_attribute_key.replace( 'attribute_', '' ) ) );
                                        } else if ( key > 0 ) {
                                            if ( $.inArray( value, this_selected_attributes_val ) !== - 1 ) {
                                                $elements.append( $( '<option></option>' ).attr( 'value', value ).text( value ).prop( 'selected', true ) );
                                            } else {
                                                $elements.append( $( '<option></option>' ).attr( 'value', value ).text( value ) );
                                            }
                                        }
                                    }
                                } );
                            }
                            filtered_attributes_value = [ ];
                        } else {
                            variation_switcher.resetVariationSwitch( '#reset_variation_switch_' + $post_id, $post_id );
                            $( '.reset_variation_switch' ).hide( );
                        }
                    } );
                },
                error : function(  ) {
                    $( '#load_variation_attributes_' + $post_id ).hide();

                    alert( sumosubs_variation_switcher_params.failure_message );
                },
                dataType : 'json',
                async : false
            } );
        },
        resetVariationSwitch : function( evt, $post_id ) {
            var $plan_matched_attributes = $( evt ).data( 'plan_matched_attributes' );
            var $plan_matched_attributes_key = $( evt ).data( 'plan_matched_attributes_key' );
            var filtered_attributes_value = [ ];

            $.each( $plan_matched_attributes_key, function( index, matched_attribute_key ) {
                $.each( $plan_matched_attributes, function( key, options ) {
                    $.each( options, function( attribute_key, attribute_value ) {

                        if ( 'attribute_' + key === matched_attribute_key ) {
                            if ( $.inArray( key, filtered_attributes_value ) === - 1 ) {
                                filtered_attributes_value.push( key );
                            }

                            filtered_attributes_value.push( attribute_value );
                        }
                    } );
                } );
                var $elements = $( '#variation_attribute_switch_selector_' + matched_attribute_key + '_' + $post_id );
                $elements.empty( );

                $.each( filtered_attributes_value, function( key, value ) {
                    if ( key === 0 ) {
                        $elements.append( $( '<option></option>' ).attr( 'value', value ).text( sumosubs_variation_switcher_params.i18n_default_attribute_select + value + ' ..' ) );
                    } else {
                        $elements.append( $( '<option></option>' ).attr( 'value', value ).text( value ) );
                    }
                } );
                filtered_attributes_value = [ ];
            } );
            $( '.variation_attribute_switch_selector_' + $post_id ).show( );
        }
    };

    variation_switcher.init();
} );
