<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription coupons.
 * 
 * @class SUMOSubs_Coupons
 */
class SUMOSubs_Coupons {

    /**
     * Cached coupon error.
     * 
     * @var string 
     */
    protected static $coupon_error;

    /**
     * Coupon error codes.
     */
    const VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS                 = 101;
    const VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE = 102;
    const VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS         = 103;
    const VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS           = 104;
    const INVALID_COUPON                                       = 105;

    /**
     * Init SUMOSubs_Coupons.
     */
    public static function init() {
        add_filter( 'woocommerce_coupon_discount_types', __CLASS__ . '::add_discount_types' );
        add_filter( 'woocommerce_product_coupon_types', __CLASS__ . '::add_product_coupon_types' );
        add_action( 'woocommerce_coupon_options', __CLASS__ . '::add_coupon_fields', 10 );
        add_action( 'woocommerce_coupon_options_save', __CLASS__ . '::save_coupon_fields', 10 );
        add_filter( 'woocommerce_coupon_get_discount_amount', __CLASS__ . '::get_discount_amount', 99, 5 );
        add_filter( 'woocommerce_coupon_is_valid', __CLASS__ . '::validate_subscription_coupon', 99, 3 );
        add_filter( 'woocommerce_coupon_validate_minimum_amount', __CLASS__ . '::validate_minimum_amount', 99, 2 );
        add_filter( 'woocommerce_coupon_error', __CLASS__ . '::add_coupon_error' );
        add_filter( 'sumosubscriptions_alter_subscription_plan_meta', __CLASS__ . '::set_recurring_discount_amount', 10, 2 );
        add_filter( 'sumosubscriptions_get_message_to_display_in_cart_and_checkout', __CLASS__ . '::get_discount_message_to_display_in_cart_and_checkout', 10, 3 );

        add_filter( 'sumosubscriptions_get_renewal_order_line_item_discount_amount', __CLASS__ . '::get_legacy_discount_amount_in_renewal', 10, 5 );
        add_action( 'sumosubscriptions_renewal_order_add_discounts', __CLASS__ . '::add_legacy_discounts_in_renewal', 10, 3 );
    }

    /**
     * Get an array of coupon types for subscription.
     * 
     * @return array
     */
    public static function get_subscription_coupon_types() {
        return array(
            'sumosubs_signupfee_discount'             => __( 'Sign up fee discount', 'sumosubscriptions' ),
            'sumosubs_signupfee_percent_discount'     => __( 'Sign up fee % discount', 'sumosubscriptions' ),
            'sumosubs_recurring_fee_discount'         => __( 'Recurring fee discount', 'sumosubscriptions' ),
            'sumosubs_recurring_fee_percent_discount' => __( 'Recurring fee % discount', 'sumosubscriptions' ),
        );
    }

    /**
     * Check whether the subscription contains recurring coupon.
     * 
     * @param mixed $subscription
     * @return bool
     */
    public static function subscription_contains_recurring_coupon( $subscription ) {
        if ( is_object( $subscription ) ) {
            if ( is_a( $subscription, 'SUMOSubs_Product' ) ) {
                return $subscription->is_subscription() && $subscription->get_coupons() && 1 != $subscription->get_installments();
            } else if ( is_a( $subscription, 'SUMOSubs_Subscription' ) ) {
                return $subscription->get_coupons() && 1 != $subscription->get_installments();
            }
        } else {
            return '1' === $subscription[ 'subscription_status' ] && 1 != $subscription[ 'subscription_recurring' ] && ! empty( $subscription[ 'subscription_discount' ][ 'coupon_code' ] );
        }
        return false;
    }

    /**
     * Get the coupon error for the responding error code.
     * 
     * @param int $err_code
     * @return string
     */
    protected static function get_coupon_error( $err_code ) {
        $err = '';
        switch ( $err_code ) {
            case self::INVALID_COUPON:
                $err = __( 'Sorry, this coupon is not valid.', 'sumosubscriptions' );
                break;
            case self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS:
                $err = __( 'Sorry, this coupon is valid only for subscription products.', 'sumosubscriptions' );
                break;
            case self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE:
                $err = __( 'Sorry, this coupon is valid only for subscription products with signup fee.', 'sumosubscriptions' );
                break;
            case self::VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS:
                $err = __( 'Sorry, this coupon is valid only for subscription orders.', 'sumosubscriptions' );
                break;
            case self::VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS:
                $err = __( 'Sorry, this coupon is valid only for initial subscription orders with signup fee.', 'sumosubscriptions' );
                break;
        }

        return $err;
    }

    /**
     * Get the recurring discounted amount.
     * 
     * @param array $applied_discounts
     * @param float $discounting_amount
     * @param int $item_quantity
     * @return float
     */
    public static function get_recurring_discount_amount( $applied_discounts, $discounting_amount, $item_quantity = 1 ) {
        $coupon_amount   = 0;
        $limit_usage_qty = 0;
        $applied_count   = 0;
        $discount_amount = 0;

        if ( $discounting_amount > 0 ) {
            foreach ( $applied_discounts as $coupon_code ) {
                $coupon         = new WC_Coupon( $coupon_code );
                $payments_count = absint( $coupon->get_meta( '_sumosubs_payments_count' ) );

                if ( 1 === $payments_count ) {
                    continue;
                }

                if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                    $coupon_amount += ( float ) $coupon->get_amount();
                } else if ( $coupon->is_type( 'sumosubs_recurring_fee_percent_discount' ) ) {
                    $coupon_amount += ( ( float ) $coupon->get_amount() * ( $discounting_amount / 100 ) );
                }

                if ( null !== $coupon->get_limit_usage_to_x_items() ) {
                    $limit_usage_qty = $coupon->get_limit_usage_to_x_items();
                }

                $apply_quantity  = $limit_usage_qty && ( $limit_usage_qty - $applied_count ) < $item_quantity ? $limit_usage_qty - $applied_count : $item_quantity;
                $apply_quantity  = max( 0, $apply_quantity );
                $discount_amount += $limit_usage_qty ? ( $coupon_amount / $item_quantity ) * $apply_quantity : $coupon_amount;
                $applied_count   += $apply_quantity;
            }
        }

        $discounted_amount = max( 0, $discounting_amount - $discount_amount );
        if ( $discounted_amount < 0 ) {
            $discounted_amount = 0;
        }

        return $discounted_amount;
    }

    /**
     * Register our coupon types for the discount.
     * 
     * @param array $discount_types
     * @return array
     */
    public static function add_discount_types( $discount_types ) {
        return is_array( $discount_types ) ? array_merge( $discount_types, self::get_subscription_coupon_types() ) : $discount_types;
    }

    /**
     * Register our coupon types for the product.
     * 
     * @param array $product_coupon_types
     * @return array
     */
    public static function add_product_coupon_types( $product_coupon_types ) {
        return is_array( $product_coupon_types ) ? array_merge( $product_coupon_types, array_keys( self::get_subscription_coupon_types() ) ) : $product_coupon_types;
    }

    /**
     * Adds custom fields to the coupon data form.
     */
    public static function add_coupon_fields( $id ) {
        $coupon = new WC_Coupon( $id );
        woocommerce_wp_text_input( array(
            'id'          => 'sumosubs_payments_count',
            'label'       => __( 'Active for x installments', 'sumosubscriptions' ),
            'placeholder' => __( 'Unlimited installments', 'sumosubscriptions' ),
            'description' => __( 'Coupon will be applied to the given number of installments. Parent order also will be considered as an installment.', 'sumosubscriptions' ),
            'desc_tip'    => true,
            'data_type'   => 'decimal',
            'value'       => $coupon->get_meta( '_sumosubs_payments_count' ),
        ) );
    }

    /**
     * Saves our custom coupon fields.
     *
     * @param int $id The coupon's ID.
     */
    public static function save_coupon_fields( $id ) {
        if ( empty( $_POST[ 'woocommerce_meta_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ 'woocommerce_meta_nonce' ] ) ), 'woocommerce_save_data' ) ) {
            return;
        }

        if ( isset( $_POST[ 'sumosubs_payments_count' ] ) ) {
            $coupon = new WC_Coupon( $id );
            $coupon->add_meta_data( '_sumosubs_payments_count', wc_clean( wp_unslash( $_POST[ 'sumosubs_payments_count' ] ) ), true );
            $coupon->save();
        }
    }

    /**
     * Calculate the discount amount.
     * 
     * @param float $discount
     * @param float $discounting_amount
     * @param WC_Order_Item $item
     * @param bool $single
     * @param WC_Coupon $coupon
     * @return float
     */
    public static function get_discount_amount( $discount, $discounting_amount, $item, $single, $coupon ) {
        if ( is_a( $item, 'WC_Order_Item' ) ) {
            if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) && sumo_order_contains_subscription( $item->get_order() ) ) {
                if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                    $discount = min( $coupon->get_amount(), $discounting_amount );
                    $discount = $single ? $discount : $discount * $item->get_quantity();
                } else {
                    $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 );
                }
            }
        } else {
            if ( ! is_a( $coupon, 'WC_Coupon' ) || empty( $item[ 'product_id' ] ) || ! $coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
                return $discount;
            }

            $product_id        = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
            $subscription_plan = self::cart_contains_subscription( $product_id );

            if ( $subscription_plan ) {
                if ( '1' === $subscription_plan[ 'trial_status' ] && sumo_can_purchase_subscription_trial( $product_id ) && '1' !== $subscription_plan[ 'signup_status' ] ) {
                    return $discount;
                }

                if ( '1' === $subscription_plan[ 'signup_status' ] && $coupon->is_type( array( 'sumosubs_signupfee_discount', 'sumosubs_signupfee_percent_discount' ) ) ) {
                    $discounting_amount = floatval( $subscription_plan[ 'signup_fee' ] );

                    if ( $coupon->is_type( 'sumosubs_signupfee_discount' ) ) {
                        $discount = min( $coupon->get_amount(), $discounting_amount );
                        $discount = $single ? $discount : $discount * $item[ 'quantity' ];
                    } else {
                        $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 );
                    }
                } else if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                    $discounting_amount -= SUMOSubs_Order_Subscription::is_subscribed() ? 0 : floatval( $subscription_plan[ 'signup_fee' ] );

                    if ( $coupon->is_type( 'sumosubs_recurring_fee_discount' ) ) {
                        $discount = min( $coupon->get_amount(), $discounting_amount );
                        $discount = $single ? $discount : $discount * $item[ 'quantity' ];
                    } else {
                        $discount = ( float ) $coupon->get_amount() * ( $discounting_amount / 100 );
                    }
                }

                $discount = round( min( $discount, $discounting_amount ), wc_get_rounding_precision() );
            }
        }

        return $discount;
    }

    /**
     * Check whether the applied coupon is valid to use.
     * 
     * @param bool $valid
     * @param WC_Coupon $coupon
     * @param WC_Discounts $discount
     * @return bool
     */
    public static function validate_subscription_coupon( $valid, $coupon, $discount = null ) {
        $order_item = false;
        $validate   = false;

        if ( is_a( $discount, 'WC_Discounts' ) ) {
            $discount_items = $discount->get_items();
            if ( is_array( $discount_items ) && ! empty( $discount_items ) ) {
                $order_item = current( $discount_items );
                $validate   = true;
            }
        } else {
            $validate = true;
        }

        if ( $validate && $coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
            self::$coupon_error = '';

            if ( isset( $order_item->object ) && is_a( $order_item->object, 'WC_Order_Item' ) ) {
                if ( ! sumo_order_contains_subscription( $order_item->object->get_order() ) ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_RECURRING_SUBSCRIPTION_ORDERS );
                } else if ( $coupon->is_type( array( 'sumosubs_signupfee_discount', 'sumosubs_signupfee_percent_discount' ) ) ) {
                    self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_INITIAL_SUBSCRIPTION_ORDERS );
                }
            } elseif ( ! self::cart_contains_subscription() ) {
                self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS );
            } else if ( $coupon->is_type( array( 'sumosubs_signupfee_discount', 'sumosubs_signupfee_percent_discount' ) ) && ! self::cart_contains_subscription( 'signup_fee' ) ) {
                self::$coupon_error = self::get_coupon_error( self::VALID_ONLY_FOR_SUBSCRIPTION_PRODUCTS_WITH_SIGNUP_FEE );
            }

            if ( ! empty( self::$coupon_error ) ) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Pass discount amount valid while applying recurring discount type.
     * 
     * @param bool $throw_err
     * @param WC_Coupon $coupon
     * @return bool
     */
    public static function validate_minimum_amount( $throw_err, $coupon ) {
        if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) ) {
            $throw_err = false;
        }

        return $throw_err;
    }

    /**
     * Add the cached coupon error.
     * 
     * @param string $err
     * @return string
     */
    public static function add_coupon_error( $err ) {
        if ( ! empty( self::$coupon_error ) ) {
            return self::$coupon_error;
        }

        return $err;
    }

    /**
     * Pass the recurring coupon code applied to subscription.
     * 
     * @param array $subscription_meta
     * @param int $subscription_id
     * @return array
     */
    public static function set_recurring_discount_amount( $subscription_meta, $subscription_id ) {
        if ( $subscription_id ) {
            if ( sumosubs_recurring_fee_has_changed( $subscription_id ) ) {
                $subscription_meta[ 'subscription_discount' ] = '';
            } else if ( ! isset( $subscription_meta[ 'subscription_discount' ][ 'coupon_code' ] ) || empty( $subscription_meta[ 'subscription_discount' ][ 'coupon_code' ] ) ) {
                $parent_order_id = absint( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
                $parent_order    = $parent_order_id ? wc_get_order( $parent_order_id ) : false;

                if ( $parent_order ) {
                    foreach ( $parent_order->get_items( 'coupon' ) as $item ) {
                        if ( $item && $item->get_code() ) {
                            $coupon = new WC_Coupon( $item->get_code() );

                            if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                                if ( empty( $subscription_meta[ 'subscription_discount' ] ) ) {
                                    $subscription_meta[ 'subscription_discount' ] = array();
                                }

                                $subscription_meta[ 'subscription_discount' ][ 'coupon_code' ][] = $item->get_code();
                            }
                        }
                    }
                }
            }

            return $subscription_meta;
        }

        if ( ! empty( WC()->cart->cart_contents ) ) {
            $applied_coupons = WC()->cart->get_applied_coupons();

            if ( ! empty( $applied_coupons ) ) {
                foreach ( $applied_coupons as $coupon_code ) {
                    $coupon = new WC_Coupon( $coupon_code );

                    if ( $coupon->is_type( array( 'sumosubs_recurring_fee_discount', 'sumosubs_recurring_fee_percent_discount' ) ) ) {
                        $subscription_meta[ 'subscription_discount' ][ 'coupon_code' ][] = $coupon_code;
                    }
                }
            }
        }

        return $subscription_meta;
    }

    /**
     * Get the recurring discounted amount to display.
     * 
     * @param array $applied_discounts
     * @param float $discounting_amount
     * @param int $qty
     * @param string $currency
     * @param int $subscription_id
     * @return string
     */
    public static function get_recurring_discount_amount_to_display( $applied_discounts, $discounting_amount, $qty = 1, $currency = false, $subscription_id = 0 ) {
        $discounted_amount = self::get_recurring_discount_amount( $applied_discounts, $discounting_amount, $qty );
        $subscription      = sumo_get_subscription( $subscription_id );
        $display           = false;

        if ( $subscription && empty( $subscription->get_installments() ) ) {
            $display = true;
        }

        if ( is_numeric( $subscription_id ) && $subscription_id > 0 ) {
            foreach ( $applied_discounts as $coupon_code ) {
                $coupon           = new WC_Coupon( $coupon_code );
                $limited_payments = absint( $coupon->get_meta( '_sumosubs_payments_count' ) );
                $renewal_orders   = get_post_meta( $subscription_id, 'sumo_get_every_renewal_ids', true );
                $renewal_orders   = $renewal_orders ? $renewal_orders : array();

                if ( empty( $limited_payments ) || $limited_payments >= count( $renewal_orders ) + 1 ) {
                    $display = true;
                }
            }
        }

        if ( $currency ) {
            $discounted_amount = sumo_format_subscription_price( $discounted_amount, array( 'currency' => $currency ) );
        } else {
            $discounted_amount = wc_price( $discounted_amount );
        }

        return $display ? str_replace( '[renewal_fee_after_discount]', $discounted_amount, SUMOSubs_Admin_Options::get_option( 'discounted_renewal_amount_strings' ) ) : '';
    }

    /**
     * Get the recurring discounted amount to display in cart and checkout.
     * 
     * @param string $message
     * @param type $subscription
     * @param array $cart_item
     * @return string
     */
    public static function get_discount_message_to_display_in_cart_and_checkout( $message, $subscription, $cart_item ) {
        $applied_coupons = WC()->cart->get_applied_coupons();
        if ( empty( $applied_coupons ) ) {
            return $message;
        }

        $display = false;
        foreach ( $applied_coupons as $coupon_code ) {
            $coupon           = new WC_Coupon( $coupon_code );
            $limited_payments = absint( $coupon->get_meta( '_sumosubs_payments_count' ) );

            if ( 1 !== $limited_payments ) {
                $display = true;
            }
        }

        if ( ! $display ) {
            return $message;
        }

        $discounted_recurring_fee = null;
        if ( self::subscription_contains_recurring_coupon( $subscription ) ) {
            $discounting_amount       = is_numeric( $discounted_recurring_fee ) ? $discounted_recurring_fee : $subscription->get_recurring_amount();
            $discounting_amount       *= $cart_item[ 'quantity' ];
            $discounted_recurring_fee = self::get_recurring_discount_amount( $subscription->get_coupons(), $discounting_amount, $cart_item[ 'quantity' ] );
        }

        if ( is_numeric( $discounted_recurring_fee ) ) {
            $message .= str_replace( '[renewal_fee_after_discount]', wc_price( $discounted_recurring_fee ), SUMOSubs_Admin_Options::get_option( 'discounted_renewal_amount_strings' ) );
        }

        return $message;
    }

    /**
     * Get the legacy discount amount from the parent order for the renewal order.
     * 
     * @param float $discount_amount
     * @param WC_Order_Item $line_item
     * @param int $item_qty
     * @param int $subscription_id
     * @param array $subscription_plan
     * @return float 
     */
    public static function get_legacy_discount_amount_in_renewal( $discount_amount, $line_item, $item_qty, $subscription_id, $subscription_plan ) {
        if (
                'yes' === get_post_meta( $subscription_id, 'sumo_coupon_in_renewal_order', true ) &&
                self::can_apply_legacy_coupon_to_renewal( $subscription_id ) &&
                ! self::subscription_contains_recurring_coupon( $subscription_plan )
        ) {
            $discount_amount = ( $line_item[ 'line_subtotal' ] - $line_item[ 'line_total' ] ) / $item_qty;
        }

        return $discount_amount;
    }

    /**
     * Add the legacy discounts from the parent order to the renewal order.
     * 
     * @param WC_Order $renewal_order
     * @param WC_Order $parent_order
     * @param int $subscription_id
     */
    public static function add_legacy_discounts_in_renewal( $renewal_order, $parent_order, $subscription_id ) {
        if ( ! self::can_apply_legacy_coupon_to_renewal( $subscription_id ) ) {
            return;
        }

        if ( 'yes' !== get_post_meta( $subscription_id, 'sumo_coupon_in_renewal_order', true ) ) {
            return;
        }

        $coupons = $parent_order->get_items( 'coupon' );
        if ( ! $coupons ) {
            return;
        }

        foreach ( $coupons as $coupon ) {
            $_coupon = new WC_Coupon( $coupon[ 'code' ] );
            if ( $_coupon->is_type( array_keys( self::get_subscription_coupon_types() ) ) ) {
                continue;
            }

            $item = new WC_Order_Item_Coupon();
            $item->set_props( array(
                'code'         => $coupon[ 'code' ],
                'discount'     => $coupon[ 'discount' ],
                'discount_tax' => $coupon[ 'discount_tax' ],
            ) );
            $item->save();
            $renewal_order->add_item( $item );

            /**
             * After renewal order coupon item is added.
             * 
             * @since 1.0
             */
            do_action( 'sumosubscriptions_renewal_order_discount_item_added', $item->get_id(), $renewal_order, $parent_order );
        }
    }

    /**
     * Can apply legacy coupon to the subscription renewal?
     * 
     * @param int $subscription_id
     * @return bool
     */
    protected static function can_apply_legacy_coupon_to_renewal( $subscription_id ) {
        $selected_type = get_post_meta( $subscription_id, 'sumo_coupon_in_renewal_order_applicable_for', true );
        if ( empty( $selected_type ) ) {
            return false;
        }

        $fixed_renewals_to_apply = '2' === get_post_meta( $subscription_id, 'sumo_apply_coupon_discount', true );
        $fixed_no_of_renewals    = absint( get_post_meta( $subscription_id, 'no_of_sumo_selected_renewal_order_discount', true ) );
        $renewal_orders          = get_post_meta( $subscription_id, 'sumo_get_every_renewal_ids', true );
        $renewal_orders_count    = is_array( $renewal_orders ) ? count( $renewal_orders ) : 0;

        if ( $fixed_renewals_to_apply && $renewal_orders_count >= $fixed_no_of_renewals ) {
            return false;
        }

        $user = get_user_by( 'id', get_post_meta( $subscription_id, 'sumo_get_user_id', true ) );
        if ( ! $user ) {
            return false;
        }

        switch ( $selected_type ) {
            case 'include_users':
                return in_array( $user->data->user_email, ( array ) get_post_meta( $subscription_id, 'sumo_selected_user_emails_for_renewal_order_discount', true ) );
                break;
            case 'exclude_users':
                return ( ! in_array( $user->data->user_email, ( array ) get_post_meta( $subscription_id, 'sumo_selected_user_emails_for_renewal_order_discount', true ) ) );
                break;
            case 'include_user_role':
                return ( isset( $user->roles[ 0 ] ) && in_array( $user->roles[ 0 ], ( array ) get_post_meta( $subscription_id, 'sumo_selected_user_roles_for_renewal_order_discount', true ) ) );
                break;
            case 'exclude_user_role':
                return ( isset( $user->roles[ 0 ] ) && ! in_array( $user->roles[ 0 ], ( array ) get_post_meta( $subscription_id, 'sumo_selected_user_roles_for_renewal_order_discount', true ) ) );
                break;
        }

        return true;
    }

    /**
     * Check cart contains subscription from the given context.
     * 
     * @param mixed $context
     * @return mixed
     */
    protected static function cart_contains_subscription( $context = '' ) {
        if ( empty( WC()->cart->cart_contents ) ) {
            return false;
        }

        if ( SUMOSubs_Order_Subscription::is_subscribed() ) {
            $subscription_plan = sumo_get_subscription_plan();

            if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
                return false;
            }

            if ( is_numeric( $context ) ) {
                return $subscription_plan;
            } else if ( 'signup_fee' === $context ) {
                if ( '1' === $subscription_plan[ 'signup_status' ] ) {
                    return true;
                }
            } else {
                return true;
            }
        } else {
            foreach ( WC()->cart->cart_contents as $cart_item ) {
                if ( empty( $cart_item[ 'product_id' ] ) ) {
                    continue;
                }

                $subscription_plan = sumo_get_subscription_plan( 0, ( $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ) );
                if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
                    continue;
                }

                if ( is_numeric( $context ) ) {
                    if ( $context == $subscription_plan[ 'subscription_product_id' ] ) {
                        return $subscription_plan;
                    }
                } else if ( 'signup_fee' === $context ) {
                    if ( '1' === $subscription_plan[ 'signup_status' ] ) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }
}

SUMOSubs_Coupons::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Coupon extends SUMOSubs_Coupons {
    
}
