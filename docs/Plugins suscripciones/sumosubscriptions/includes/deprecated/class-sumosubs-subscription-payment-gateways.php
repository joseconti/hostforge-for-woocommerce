<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Handle Subscription Payment Gateways
 * 
 * @class SUMO_Subscription_PaymentGateways
 * @deprecated since version 13.7
 */
class SUMO_Subscription_PaymentGateways extends SUMOSubs_Payment_Gateways {

	/**
	 * Check if Automatic payment gateways enabled
	 *
	 * @return bool 
	 */
	public static function auto_payment_gateways_enabled() {
		return self::auto_payments_alone_enabled() ;
	}

	/**
	 * Check whether the gateway requires automatic renewals in checkout.
	 *
	 * @param string $gateway_id
	 * @return bool
	 */
	public static function customer_has_chosen_auto_payment_mode_in( $gateway_id ) {
		return self::gateway_requires_auto_renewals( $gateway_id ) ;
	}

	/**
	 * Get the gateway mode for subscription payment gateways.
	 *
	 * @param string $description
	 * @param string $gateway_id
	 * @return string
	 */
	public static function set_payment_mode_switcher( $description, $gateway_id ) {
		return self::maybe_get_gateway_description_with_payment_mode( $description, $gateway_id ) ;
	}
}
