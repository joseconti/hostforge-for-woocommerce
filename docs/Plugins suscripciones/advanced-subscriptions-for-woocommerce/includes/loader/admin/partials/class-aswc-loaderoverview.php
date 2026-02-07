<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Aswc_LoaderOverview' ) ) {

	/**
	 * This is construct of overview section.
	 *
	 * @name Aswc_LoaderOverview
	 * @since      1.0.0
	 * @category Class
	 */
	class Aswc_LoaderOverview {
		/**
		 * Create class for overview.
		 *
		 * @access public
		 */
		public function __construct() {
			add_action( 'aswc_overview_feature_description', array( $this, 'aswc_overview_feature_description' ) );
			add_action( 'aswc_overview_keywords_description', array( $this, 'aswc_overview_keywords_description' ) );
		}

			/**
			 * This function is used to show features description in overview section.
			 *
			 * @name aswc_overview_feature_description
			 * @since    1.0.0
			 */
		public function aswc_overview_feature_description() {
			?>
					<ul class="aswc-overview__features-list">
						<li><?php esc_html_e( 'Create subscriptions for variation type products', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Set and change subscriptions plan expiry date', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Automatic retrial and cancellation of subscriptions plan', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Upgrade or downgrade subscription plans by users', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Exclusive coupon types', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Pause Subscription plans', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Email notifications for reminders', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'API of course for details on a mobile app', 'advanced-subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Export active subscriptions ', 'advanced-subscriptions-for-woocommerce' ); ?></li>
					</ul>
				<?php
		}

			/**
			 * This function is used to show keywords section in overview section.
			 *
			 * @name aswc_overview_keywords_description
			 * @since    1.0.0
			 */
		public function aswc_overview_keywords_description() {
			?>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Set-Start-And-Due-Date.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Set Start And Due Date', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin has settings to enable users to start a subscription from a certain date for a subscription plan. Users can change the subscription plan based on dates, months, or years as allowed by the admin.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Automatic-Payment-Retrial,-Cancel-Subscription-Plans.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Automatic Payment Retrial, Cancel Subscription Plans', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Merchants can allow automatic subscription payment retry on failed attempts. After a set number of failed payment attempts, the subscription plan will be aborted automatically.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Subscription-Coupons.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Subscription Coupons', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Different coupon types apply on subscription plans, i.e., Initial Fee Discount Type, Initial percentage discount Type, Recurring Product Discount Type, and Recurring Product percentage discount type. Users can apply them.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Handle-Proration.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Handle Proration', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin can enable the users to upgrade and downgrade their subscription plans. Users can easily upgrade or downgrade their variable product subscription plans for a set period whenever they want. The Advanced Subscriptions For WooCommerce plugin can handle proration on recurring payments and sign-up fees.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Pause--Hold-and-Re-Activate-Subscription-Plans.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Pause / Hold and Re-Activate Subscription Plans', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin gets the settings allowing users to start subscriptions from a specific date of a month. Users can pause and restart their subscription plans after putting them on hold for a particular time.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Email-notifications-for-Admin-and-Users.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Email notifications for Admin and Users.', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin can enable email notification for all the activity related to subscription plans. There are email notification options are.,Subscription plan is going to expire, Subscription payment has been made, Subscription plan has been Paused/On-Hold, Subscription plan has been Resumed', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Manual-and-Automatic-Subscription-Payment-Option.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Manual and Automatic Subscription Payment Option.', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin can set manual or automatic subscription payment options. Advanced Subscriptions For WooCommerce will send an automated invoice to the customer payable through a link in the manual subscription payment. Offline payment COD is also available.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/Export-Subscription-Plan-Details.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'Export Subscription Plan Details.', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin can export all active subscription plans in a file and view all subscription renewal orders under the Subscription table tab.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/API-for-Mobile-App.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'API for Mobile App.', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'APIs, of course, have been provided in Advanced Subscriptions For WooCommerce to let the admin get subscription details on mobile apps quickly.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="aswc-overview__keywords-item">
						<div class="aswc-overview__keywords-card">
							<div class="aswc-overview__keywords-text">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/images/View-Subscriptions-Renewal-Order-for-Particular-Subscription.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="aswc-overview__keywords-heading"><?php esc_html_e( 'View Subscriptions Renewal Order for Particular Subscription.', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
								<p class="aswc-overview__keywords-description">
								<?php esc_html_e( 'Admin can view all renewal orders for each subscription. It will help them to identify the number of recurring payments completed for a particular subscription plan.', 'advanced-subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
				<?php
		}
	}
}

return new Aswc_LoaderOverview();
