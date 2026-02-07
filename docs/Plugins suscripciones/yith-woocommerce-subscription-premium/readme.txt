=== YITH WooCommerce Subscription  ===

Tags: checkout page, recurring billing, subscription billing, subscription box, Subscription Management, subscriptions, paypal subscriptions
Requires at least: 6.7
Tested up to: 6.9
Stable tag: 4.18.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
It allows you to manage recurring payments for product subscription that grant you constant periodical income

== Installation ==
Important: First of all, you have to download and activate WooCommerce plugin, which is mandatory for YITH WooCommerce Subscription to be working.

1. Unzip the downloaded zip file.
2. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.
3. Activate `YITH WooCommerce Subscription` from Plugins page.


= Configuration =
YITH WooCommerce Subscription will add a new tab called "Subscription" in "YIT Plugins" menu item.
There, you will find all YITH plugins with quick access to plugin setting page.

== Changelog ==

 = 4.18.0 Released on 01 Dec 2025 =

 * New: support for WooCommerce 10.4
 * New: support for WordPress 6.9
 * Update: YITH plugin framework

 = 4.17.2 Released on 18 Nov 2025 =

 * Fix: prevent Stripe fees and payouts from being displayed multiple times in order details
 * Fix: prevent Stripe gateway from appearing multiple times in WooCommerce gateway settings
 * Fix: ensure card details are saved for PayPal Payments
 * Fix: resolve WooCommerce Payments 10.2.x integration issue

 = 4.17.1 Released on 23 Oct 2025 =

 * Update: YITH plugin framework

 = 4.17.0 Released on 21 Oct 2025 =
 * New: support for WooCommerce 10.3
 * New: support for WooCommerce Stripe Gateway 10.0
 * New: support for WooCommerce PayPal Payments 3.2.0
 * Update: YITH plugin framework
 * Fix: WooCommerce Stripe SEPA payments and renews
 * Fix: send 'renew reminder' email also for trial subscriptions

 = 4.16.0 Released on 16 Sep 2025 =
 * New: support for WooCommerce 10.2
 * New: support for WooCommerce Payments 9.9
 * New: support for WooCommerce Stripe Gateway 9.9
 * New: support for WooCommerce PayPal Payments 3.1.0
 * Update: YITH plugin framework
 * Fix: query search subscription by ID
 * Fix: datepicker layout issue in subscription list table

 = 4.15.0 Released on 01 Sep 2025 =
 * Fix: WooCommerce Stripe gateway not available for subscription
 * Tweak: double check order meta subscriptions in cron process

 = 4.14.0 Released on 18 Aug 2025 =
 * New: support for WooCommerce 10.1
 * New: support for WooCommerce PayPal Payments 3.0.9
 * New: support for WooCommerce Payments 9.8
 * New: support for WooCommerce Stripe Gateway 9.8
 * Update: YITH plugin framework

 = 4.13.0 Released on 31 Jul 2025 =
 * New: new option to choose whether to calculate the recurring renewal date based on the purchase date or the first renewal date
 * Fix: PHP deprecated creation of dynamic property
 * Fix: unsupported operand types
 * Fix: box editing with limited subscriptions

 = 4.12.0 Released on 02 Jul 2025 =
 * New: support for WooCommerce 10.0
 * New: support for WooCommerce PayPal Payments 3.0.7
 * New: support for WooCommerce Payments 9.6.0
 * Update: YITH plugin framework
 * Dev: new filter 'ywsbs_current_time' to customize current timestamp value

 = 4.11.0 Released on 28 May 2025 =
 * New: support for WooCommerce 9.9
 * Update: YITH plugin framework
 * Tweak: show order number in subscription history metabox

 = 4.10.0 Released on 26 May 2025 =
* Fix: checkout error when the subscription order total is zero and the payment method is WooCommerce Stripe

 = 4.9.0 Released on 15 May 2025 =
 * New: support for WooCommerce Stripe Gateway 9.5
 * Fix: incorrect subscription box option value
 * Fix: renewal sync date when charging the full recurring amount at signup
 * Fix: allow renewal orders in "On hold" status to be manually paid through the "My Account" page
 * Fix: subscription export error

 = 4.8.0 Released on 7 Apr 2025 =
 * New: support for WooCommerce 9.8
 * New: support for WordPress 6.8
 * New: support for WooCommerce PayPal Payments 3.0
 * Update: YITH plugin framework
 * Fix: resolved _load_textdomain_just_in_time error
 * Fix: fixed error in subscription product meta-box when the item is not set
 * Fix: correctly register renew failed attempts using the PayPal Payments gateway
 * Fix: renewal orders with "On hold" status can now be manually paid through the "Pay for Order" page
 * Fix: fixed issue with the subscription cancel modal not closing in the "My Account" section

 = 4.7.1 Released on 6 Mar 2025 =
 * Fix: display subscription recurring total in cart/checkout

 = 4.7.0 Released on 3 Mar 2025 =
 * New: support for WooCommerce 9.7
 * New: option to set subscription box as sold individually
 * Fix: next subscription activity date for emails
 * Tweak: improved Stripe source retrieval process for renewal payments
 * Tweak: flag to send "Subscription is going to expire" email once

 = 4.6.2 Released on 20 Feb 2025 =
 * Fix: using 'days' as the recurring period in the synchronization module
 * Fix: updating subscription recurring totals in the WooCommerce cart block when adding or removing coupons
 * Fix: subscription tax rates based on WooCommerce settings
 * Fix: deleting subscriptions only when the order is permanently deleted

 = 4.6.1 Released on 6 Feb 2025 =
 * Fix: compatibility issue with PHP version 7.4

 = 4.6.0 Released on 4 Feb 2025 =
 * New: support for WooCommerce 9.6
 * Update: YITH plugin framework
 * Fix: WooCommerce Stripe SEPA Debit payment
 * Dev: new action 'ywsbs_box_item_after_product_title'

 = 4.5.0 Released on 8 Jan 2025 =
 * New: support for WooPayments 8.7.0

 = 4.4.0 Released on 9 Dec 2024 =
 * New: support for WooCommerce 9.5
 * New: support for WordPress 6.7
 * Update: YITH plugin framework
 * Fix: deprecated usage of class Query in report dashboard
 * Fix: save the correct product price for an item in the Subscription box when adding to cart
 * Fix: delete subscription correctly when the main order is cancelled
 * Dev: new filter 'ywsbs_current_time'

 = 4.3.0 Released on 24 Oct 2024 =
 * New: support for WooCommerce 9.4
 * New: support for WooPayments 8.4
 * Fix: prevent show the "next billing" date in cart/checkout for subscription with an end date set
 * Fix: error using block checkout and subscriptions with free trial period
 * Fix: make sure to trigger activations hooks for modules

 = 4.2.1 Released on 14 Oct 2024 =
 * Fix: error using block checkout and WooCommerce Stripe gateway
 * Dev: added action ywsbs_renew_order_before_set_status before set status to renew order

 = 4.2.0 Released on 09 Oct 2024 =
 * New: support to WooPayments 8.3.0
 * Fix: synchronization module with subscription box

 = 4.1.3 Released on 03 Oct 2024 =
 * Fix: missing subscription box info for order with multiple boxes

 = 4.1.2 Released on 26 Sep 2024 =
 * New: support to WooCommerce PayPal Payments 2.9.1
 * Fix: missing ywsbs_customer_subscription_cancelled_mail from transaction email

 = 4.1.1 Released on 23 Sep 2024 =
 * Fix: tax display on cart/checkout
 * Fix: subscription variation switch options save process
 * Fix: subscription variation switch options labels

 = 4.1.0 Released on 12 Sep 2024 =
 * New: support for WooCommerce 9.3
 * Update: YITH plugin framework
 * Fix: fix wrong coupon amount using cart/checkout blocks

 = 4.0.0 Released on 4 Sep 2024 =
 * New: subscription box module
 * Update: YITH plugin framework
 * Tweak: improved admin panel UX

 = 3.10.0 Released on 21 Aug 2024 =
 * New: support for WooCommerce 9.2
 * New: support for WordPress 6.6
 * Update: YITH plugin framework
 * Fix: disabled payment methods using WooPayments

 = 3.9.1 Released on 15 Jul 2024 =
 * New: support for WooCommerce Stripe Gateway 8.5
 * Fix: prevent double subscription renew payment using WooCommerce Stripe gateway

 = 3.9.0 Released on 9 Jul 2024 =
 * New: support for WooCommerce 9.1
 * Update: YITH plugin framework
 * Fix: double check payment method in "paypal payments" module on subscription's payment completed

 = 3.8.2 Released on 2 Jul 2024 =
 * Fix: subscriptions admin table filters

 = 3.8.1 Released on 1 Jul 2024 =
 * Fix: removed all polyfill.io references

 = 3.8.0 Released on 27 Jun 2024 =
 * New: support for WooCommerce 9.0
 * New: subscription statuses filter for delivery schedules table
 * New: screen options for delivery schedules table
 * Update: YITH plugin framework
 * Tweak: improved admin tables UX

 = 3.7.3 Released on 13 Jun 2024 =
 * Fix: PayPal IPN handler
 * Fix: issue with WooCommerce Stripe SEPA

 = 3.7.2 Released on 30 May 2024 =
 * New: support for WooCommerce Payments 7.7

 = 3.7.1 Released on 29 May 2024 =
 * New: support for WooCommerce Stripe Gateway 8.3
 * Fix: PayPal IPN handler

 = 3.7.0 Released on 23 May 2024 =
 * New: support for WooCommerce 8.9
 * Update: YITH plugin framework

 = 3.6.3 Released on 17 May 2024 =
 * Fix: support for WooPayments 7.6.0

 = 3.6.2 Released on 03 May 2024 =
 * Fix: RedSys gateway availability check

 = 3.6.1 Released on 30 April 2024 =
 * Fix: load WooCommerce Stripe gateway compatibility class

 = 3.6.0 – Released on 29 April 2024 =
 * New: support for WooCommerce 8.8
 * New: support for WordPress 6.5
 * New: support to WooCommerce Gateway Stripe UPE
 * New: admin panel warning when at least a supported payment gateway is not enabled
 * Update: YITH plugin framework
 * Update: language files

 = 3.5.0 – Released on 21 March 2024 =
 * New: support for WooCommerce 8.7
 * Update: YITH plugin framework
 * Update: Language files
 * Fix: subscription renew using WooCommerce PayPal Payments
 * Fix: check if order is vat exempt in renew subscription process
 * Fix: wrong classname in WooPayments gateway integration
 * Tweak: improved support to PHP 8.2

 = 3.4.0 – Released on 20 February 2024 =
 * New: support for WooCommerce 8.6
 * Update: YITH plugin framework
 * Update: Language files
 * Fix: multiple scheduled action for Stripe SEPA subscriptions

 = 3.3.2 – Released on 01 February 2024 =
 * Update: YITH plugin framework
 * Fix: correctly calculate totals when add/remove coupons from subscription admin section

 = 3.3.1 – Released on 18 January 2024 =
 * Fix: missing scripts for admin dashboard

 = 3.3.0 – Released on 11 January 2024 =
 * New: support for WooCommerce 8.5
 * New: support for WooCommerce PayPal Payments 2.4.3
 * New: support for WooCommerce Payments 7.0.0
 * Update: YITH plugin framework
 * Fix: filter woocommerce_order_button_text with WooCommerce Germanized
 * Fix: invalid array index for delivery schedules options
 * Fix: admin staging notice always visible

 = 3.2.0 – Released on 18 December 2023 =
 * New: support for WooCommerce 8.4
 * Update: YITH plugin framework
 * Fix: undefined method get_delivery_date_for_days on Delivery Schedules module
 * Fix: disable payments gateway using WooCommerce checkout block
 * Fix: WooCommerce webhooks support
 * Tweak: improved support for YITH Multi Currency Switcher for WooCommerce

 = 3.1.0 – Released on 16 November 2023 =
 * New: support for WooCommerce 8.3
 * New: support for WordPress 6.4
 * New: support for WooCommerce cart and checkout blocks
 * Update: YITH plugin framework
 * Fix: block creating renewal order for paused subscriptions
 * Fix: fix shipping total for renewal orders if HPOS is enabled
 * Fix: parameters missing in WooCommerce Payments integration class

 = 3.0.1 – Released on 24 October 2023 =
 * Fix: YITH_WC_Subscription_WC_Stripe __construct method

 = 3.0.0 – Released on 23 October 2023 =
 * New: settings panel
 * New: module "Renewal synchronization"
 * New: module "Delivery schedules"
 * Update: YITH plugin framework
 * Tweak: code improvements and refactoring

 = 2.33.0 – Released on 10 October 2023 =
 * New: support for WooCommerce 8.2
 * Update: YITH plugin framework

 = 2.32.1 – Released on 05 October 2023 =
 * Fix: compatibility issue with WooCommerce PayPal Payments

 = 2.32.0 – Released on 11 August 2023 =
 * New: support for WooCommerce 8.1
 * Update: YITH plugin framework
 * Fix: save order item metadata when renewal order has been created
 * Fix: error with the WooCommerce PayPal Payments integration
 * Dev: added action "ywsbs_after_create_renew_order" triggered after a renewal order has been created

 = 2.31.1 – Released on 9 August 2023 =
 * Fix: subscription not fully charged if startup fee is set
 * Update: YITH plugin framework

 = 2.31.0 – Released on 8 August 2023 =
 * New: support for WordPress 6.3
 * New: support for WooCommerce 8.0
 * Update: YITH plugin framework
 * Fix: now, the login at checkout is mandatory only when PayPal Payments for WooCommerce is enabled
 * Fix: fix for prorate and signup fee in change_prices_for_calculation method


= 2.30.0 – Released on 21 July 2023 =
* Fix: prevent fatal error when using WooCommerce Payments

 = 2.29.0 – Released on 17 July 2023 =
 * New: support for WooCommerce 7.8
 * Update: YITH plugin framework
 * Fix: fixed report for renew when on the first order there is a fee

= 2.28.0 – Released on 14 June 2023 =
 * New: support for WooCommerce 7.8
 * Update: YITH plugin framework
 * Fix: fixed issue with coupon applied on an existent subscription
 * Fix: when paying a renewal order through WC Stripe, if original credit/debit card was removed, search for an available card through PaymentMethods API instead of Sources API in order to avoid errors
 * Fix: filter subscriptions according to vendor

= 2.27.0 – Released on 11 May 2023 =
 * New: support for WooCommerce 7.7
 * Update: YITH plugin framework
 * Dev: added filter 'ywsbs_no_subscription_text' to customize the text when there are no subscriptions

= 2.26.1 – Released on 14 April 2023 =
 * Fix: prevent fatal error with product variations

 = 2.26.0 – Released on 12 April 2023 =
 * New: support for WooCommerce 7.6
 * New: support for PHP 8.1
 * Update: YITH plugin framework

= 2.25.1 – Released on 23 Mar 2023 =
* Fix: fixed issue with WooCommerce Payments

= 2.25.0 – Released on 10 March 2023 =
 * New: support for WordPress 6.2
 * New: support for WooCommerce 7.5
 * Update: YITH plugin framework
 * Dev: added 'ywsbs_not_process_change_price_html' to change the price to a subscription product

= 2.24.0 – Released on 10 February 2023 =
* New: support for WooCommerce 7.4
* Update: YITH plugin framework

= 2.23.0 – Released on 9 January 2023 =
* New: support for WooCommerce 7.3
* Update: YITH plugin framework

= 2.22.0 – Released on 9 December 2022 =
* New: support for WooCommerce 7.2
* Update: YITH plugin framework

= 2.21.1 – Released on 11 November 2022 =
 * Fix: patched security vulnerability

= 2.21.0 – Released on 7 November 2022 =
* New: support for WordPress 6.1
* New: support for WooCommerce 7.1
* Update: YITH plugin framework
* Fix: clean minutes and seconds from calculated timestamp for monthly synchronization
* Fix: manage gateways also if the order is failed
* Dev: added support for billing_yweu_vat field on renew order

= 2.20.0 – Released on 3 October 2022 =
* New: support for WooCommerce 7.0
* Update: YITH plugin framework

= 2.19.0 – Released on 6 September 2022 =
* New: support for WooCommerce 6.9
* Update: YITH plugin framework
* Fix: fixed issue on delivery date
* Fix: fixed css style issues with Elementor
* Fix: show tax suffix only if cart item has tax

= 2.18.1 – Released on 9 August 2022 =
* Fix: fixed the issue with shipping costs on synchronized subscriptions when the customer creates a new account on the checkout page

= 2.18.0 – Released on 4 August 2022 =
* New: support for WooCommerce 6.8
* Update: YITH plugin framework
* Fix: avoid sending "cancelled" email if the subscriptions status is already cancelled
* Fix: fixed issue with recurring coupons
* Dev: added filter 'ywsbs_subscription_cart_needs_payments' to allow to set if a cart needs of payments or not

= 2.17.0 – Released on 12 July 2022 =
* New: support for WooCommerce 6.7
* Update: YITH plugin framework
* Fix: fixed issue YITH Multi Currency Switcher for WooCommerce
* Dev: Switched to react-router-dom 6.3, according to WooCommerce changes for version 6.7

= 2.16.1 – Released on 24 June 2022 =
Fix: fixed issue with shipping costs on renewal
Fix: fixed renew order scheduling a day before for payments with PayPal Standard

= 2.16.0 – Released on 13 June 2022 =
* New: support for WooCommerce 6.6
* Update: YITH plugin framework
* Fix: fixed issue with the tax suffix if the tax are not enabled
* Fix: fixed issue with search subscription field
* Fix: fixed wrong amount shown for subscription product using  YITH Multi Currency Switcher for WooCommerce

= 2.15.1 – Released on 27 May 2022 =
* Fix: fixed issue with WooCommerce Payments

= 2.15 – Released on 9 May 2022 =
* New: support for WordPress 6.0
* New: support for WooCommerce 6.5
* Update: YITH plugin framework
* Fix: fixed issue when switching from Stripe to Stripe SEPA with WooCommerce Stripe Gateway plugin
* Fix: fixed issue with WooCommerce Payments

= 2.14 – Released on 20 April 2022 =
* New: Added Turkish translation
* Fix: fixed issues with WooCommerce Payments 4.0
* Fix: fixed issue about the empty shipping address for synchronized subscriptions

= 2.13 – Released on 11 April 2022 =
* New: support for WooCommerce 6.4
* Update: YITH plugin framework
* Fix: fixed issue with WooCommerce Payments
* Fix: fixed issue fix recipient customer emails

= 2.12.2 – Released on 18 March 2022 =
* Fix: prevent fatal error when using WooCommerce Payments
* Fix: prevent multiple payments using Stripe SEPA
* Fix: prevent double orders created due to multiple PayPal IPN received

= 2.12.1 – Released on 28 February 2022 =
* Fix: fixed issue during saving options on the Customization tab

= 2.12.0 – Released on 24 February 2022 =
* New: support for WooCommerce 6.3
* Update: YITH plugin framework
* Fix: fixed issue with the customer emails when the "send to admin" option is checked
* Fix: fixed style with YITH Proteo theme

= 2.11.0 – Released on 9 February 2022 =
* New: support for WooCommerce 6.2
* Update: YITH plugin framework
* Fix: fix for WooCommerce Payments integration
* Fix: issue fix issue with YITH Multi Currency Switcher for WooCommerce and synchronization settings
* Dev: added filter 'ywsbs_validate_site_url' to avoid the validation of site url

= 2.10.0 – Released on 25 January 2022 =
* New: support for WordPress 5.9
* Update: YITH plugin framework

= 2.9.0 – Released on 12 January 2022 =
* New: support for WooCommerce 6.1
* Update: YITH plugin framework
* Fix: Fix to prevent fatal error with YITH Multi Currency Switcher for WooCommerce
* Fix: Fix to prevent subscription purchase if there is another active also in other languages

= 2.8.0 – Released on 2 December 2021 =
* New: support for WooCommerce 6.0
* New: support for Stripe SEPA Direct Debit gateway with WooCommerce Stripe Gateway plugin
* Fix: issues with WooCommerce Stripe Gateway source payment
* Fix: failed attempts counter not updating
* Fix: prevent fatal error on report due to no longer existing products
* Fix: load or copy billing/shipping info inside the subscription editor
* Dev: added filter 'ywsbs_is_limited' to filter if a product is a limited subscription

= 2.7.0 – Released on 26 October 2021 =
* New: support for WooCommerce 5.9
* Fix: fixed issue with multiple payments with WooCommerce Stripe gateway
* Fix: fixed tax calculation on renew order
* Fix: fixed select2 issue with YITH WooCommerce Catalog Mode

= 2.6.0 – Released on 6 October 2021 =
* New: support for WooCommerce 5.8
* Tweak: hidden "first payment" label if order does not contain a subscription
* Update: YITH plugin framework
* Fix: fixed issue with WooCommerce Stripe Gateway 5.6.1
* Fix: fixed issue with YITH Multi Currency Switcher for WooCommerce

= 2.5.1 – Released on 27 September 2021 =
* Update: YITH plugin framework
* Fix: debug info feature removed for all logged in users

= 2.5.0 - Released on 7 September 2021 =
* New: support for WooCommerce 5.7
* Update: YITH plugin framework
* Fix: issue with WPML plugin during the translation of product
* Fix: resume date after a pause period
* Fix: fixed eWay issue when the subscription has status trial
* Fix: fixed delivery schedule table error when product is deleted
* Dev: added filter 'ywsbs_csv_labels' to customize the cvs column headers
* Dev: added a new filter 'ywsbs_change_trial_period' to change on the fly the trial period
* Dev: added the hooks 'ywsbs_subscription_downgrade_process' and 'ywsbs_subscription_upgrade_process' to trigger downgrade and upgrade process

= 2.4.2 - Released on 5 August 2021 =
* New: support for WooCommerce 5.6
* Update: YITH plugin framework
* Fix: fixed input number of product editor for trial settings
* Fix: fixed price calculation during the subscription switch
* Fix: fixed synchronization renew date during the subscription switch
* Fix: fixed issue with WooCommerce Payments Gateway
* Fix: issue with WooCommerce Stripe Gateway when the renew order has total equals to zero
* Fix: fixed resubscribe button for limited subscription products

= 2.4.1 - Released on 1 July 2021 =
* Tweak: Wp List style
* Fix: issue on Subscription Dashboard

= 2.4.0 - Released on 28 June 2021 =
* New: support for WordPress 5.8
* New: support for WooCommerce 5.5
* New: support for WooCommerce Payments
* New: support for WooCommerce eWAY Gateway
* New: support for YITH Multi Currency Switcher for WooCommerce
* New: REST API
* New: Subscription topic inside WooCommerce Webhooks

= 2.3.2 - Released on 20 May 2021 =
* New: support for WooCommerce Amazon Pay Plugin
* New: support for WooCommerce 5.4
* Update: YITH plugin framework
* Fix: fixed issue with subscriptions created from the backend in orders with more than one subscription product
* Fix: removed pending subscriptions from the net total calculation on report
* Fix: fixed issue with customers in report
* Fix: fixed conflicts on "My Account" page with BuddyPress plugin
* Dev: added new filter 'yith_ywsbs_billing_fields'

= 2.3.1 - Released on 5 May 2021 =
* Fix: fixed issues with Gutenberg block

= 2.3 - Released on 4 May 2021 =
* New: Dashboard with reports for subscriptions, products and subscribers
* New: Recurring limit per coupon
* New: Add or remove a recurring coupon from a subscription
* New: Subscription creation from the backend
* New: support for WooCommerce 5.3
* Update: YITH plugin framework

= 2.2.7 - Released on 13 April 2021 =
* New: support for WooCommerce 5.2
* Update: YITH plugin framework
* Fix: fixed issues with php8
* Fix: Added the product item meta in the delivery scheduled table
* Dev: Added filters 'ywsbs_single_sync_message' and 'ywsbs_recurring_price_html' and 'ywsbs_show_max_length'

= 2.2.6 - Released on 24 February 2021 =
* New: support for WordPress 5.7
* New: support for WooCommerce 5.1
* Update: YITH plugin framework
* Fix: fixed issues with limited product
* Fix: issue with scheduled subscriptions

= 2.2.5 - Released on 4 February 2021 =
* New: support for WooCommerce 5.0
* Update: YITH plugin framework
* Fix: fixed synchronization information inside the product page
* Fix: fixed issue on delivery schedules
* Dev: added filters 'ywsbs_is_limited', 'ywsbs_needs_flushing'

= 2.2.4 - Released on 11 Jan 2021 =
* Update: Plugin framework
* Fix: Fixed the issue which occurs during the renew order generation if the tax rate changed

= 2.2.3 - Released on 29 Dec 2020 =
* New: Support for WooCommerce 4.9
* Update: Plugin framework
* Fix: Trial option issue on product page editor
* Fix: Subscription total on cart/checkout and my account page
* Fix: Enable limit option not deactivating

= 2.2.2 - Released on 1 Dec 2020 =
* New: Support for WooCommerce 4.8
* New: Download shipping list
* Update: Plugin framework
* Tweak: Subscription options in product editor page

= 2.2.1 - Released on 24 Nov 2020 =
* Update: Plugin framework
* Fix: Issue with WooCommerce Stripe Gateway plugin during recurring payment
* Fix: Translation string issue inside single product editor
* Fix: Fixed delivery scheduled email
* Fix: Fixed shipping for synchronized subscriptions
* Dev: Added new filter ywsbs_enable_subscription_on_product to disable the subscription fields on product editor

= 2.2.0 - Released on 29 Oct 2020 =
* New: Support for WordPress 5.6
* New: Support for WooCommerce 4.7
* New: Delivery Schedules
* New: Integration with YITH WooCommerce Sequential Order Number
* New: Option to use the plugin in staging mode
* Tweak: Enabled the subscriptions editor if renewed manually
* Fix: Synchronize payment option when using a language different than English


= 2.1.0 - Released on 28 Sep 2020 =
* New: Synchronize recurring payments
* New: Export all Subscriptions to a csv file
* New: Support for WooCommerce 4.6
* Update: Plugin framework
* Fix: Fixed issue with YITH Donations for WooCommerce Premium integration
* Dev: Added filter 'ywsbs_add_to_cart_variation_label' and 'yith_subscription_add_to_cart_text'

= 2.0.4 - Released on 16 Sep 2020 =
* New: Support for WooCommerce 4.5
* Update: Plugin framework
* Fix: Status change after that the renew order is created
* Fix: Default value for manually renew gateways option

= 2.0.3 - Released on 10 Aug 2020 =
* New: Support for WordPress 5.5
* New: Support for WooCommerce 4.4
* Tweak: Added an additional scheduled action to check if the renewal order has been paid
* Update: Plugin framework
* Fix: Prevent error inside the subscription email template
* Fix: Prevent fatal error on edit subscription page if the subscription product has been deleted

= 2.0.2 - Released on 15 July 2020 =
Fix: Fixed error with YITH WooCommerce Multi Vendor
Fix: Fixed css style on product page editor

= 2.0.1 - Released on 14 July 2020 =
New: Added Greek translation
Fix: Fixed check for available gateways
Fix: Fixed error with YITH WooCommerce Multi Vendor

= 2.0.0 - Released on 7 July 2020 =
* New: Support for WooCommerce 4.3
* New: Plugin options panel layout
* New: Plugin options inside product editor
* New: Subscription detail layout on My Account page
* New: Added two styles for the actions "pause subscription" and "cancel subscription" in My Account page
* New: Subscription Switch feature
* New: Limit subscription feature
* New: Resubscribe button feature
* New: Custom text and color for Trial and Fee on subscription product
* New: Custom colors for subscription status
* New: Resume recurring totals amount on cart and checkout page
* New: Show/Hide next billing date on recurring totals amount
* New: Custom text to replace "Place order" button label if the cart contains a subscription product
* New: Show/Hide total subscription length and total amount on cart and checkout page
* New: Added a new style to show the subscriptions on Thank you page layout
* New: Option to choose if a user can add only one or more subscription products to cart
* New: Option to set 'pause subscription' globally that can be overridden in single product page
* New: Option to enable the shop manager to allow to access and edit the plugin options and subscriptions
* New: Subscription Plans Gutenberg Block
* Tweak: Improved scheduled events with WooCommerce Action Scheduler since WooCommerce 4.0
* Tweak: Improved subscription detail page on backend
* Update: Language files
* Update: Plugin framework
* Fix: Fixed PDT Payment Issue with PayPal Standard
* Fix: Refund issue with WooCommerce Stripe Gateway

= 1.7.8 - Released on 26 May 2020 =
* New: Support for WooCommerce 4.2
* New: Added French translation
* Update: Plugin framework
* Fix: Fixed update shipping address issue

= 1.7.7 - Released on 4 May 2020 =
* New: Support for WooCommerce 4.1
* Tweak: Added payment method title to renewal orders
* Update: Plugin framework
* Fix: Flush rewrite rule fix
* Dev: added new filter ywsbs_calculate_order_totals_condition to fix a problem in a customer site with EU VAT

= 1.7.6 - Released on 9 March, 2020 =
* New: Support for WordPress 5.4
* New: Support for WooCommerce 4.0
* New: Added "Enable Shop Manager" option
* Update: Plugin framework
* Fix: Prevent fatal error if the subscription order is null
* Dev: Added new action 'ywsbs_before_subscription_deleted'
* Dev: Added 'ywsbs_skipped_price_html_filter' filter

= 1.7.5 - Released on 19 February, 2020 =
* Update: Spanish language
* Fix: Fix undefined index tnx_type
* Fix: Add full log for renew order processing
* Fix: Address fields fix
* Fix: Process_payment function for WC Stripe compatibility
* Fix: Check on cart_item['data']
* Dev: Added filter ywsbs_free_trial_label to change the trial text

= 1.7.4 - Released on 24 December, 2019 =
* New: Support for WooCommerce 3.9
* Update: Plugin framework
* Fix: Fixed responsive subscription table on my account
* Dev: Added new filters 'ywsbs_force_multiple_subscriptions' and 'ywsbs_subscription_subtotal_html'

= 1.7.3 - Released on 26 November, 2019 =
* Update: Plugin framework

= 1.7.2 - Released on 30 October, 2019 =
* Update: Plugin framework

= 1.7.1 - Released on 23 October, 2019 =
* New: Support for WordPress 5.3
* New: Support for WooCommerce 3.8
* Update: Italian language
* Update: Plugin framework
* Fix: Load payment gateways before call pay renew order action
* Fix: Capability issues

= 1.7.0 - Released on 03 October, 2019 =
* Tweak: Improved the debug logging
* Tweak: Added method to make renews payable when customer visit pay page from gateway confirmation email
* Tweak: Added order item meta in subscription email and metabox
* Tweak: Added warning on backend, when the admin try to change manually the status of a renew order
* Update: Language Files
* Fix: Fixed PayPal enabled check
* Dev: Added the filter 'ywsbs_load_assets'

= 1.6.0 - Released on 01 August, 2019 =
* New: Support for WooCommerce 3.7
* Update: Plugin framework
* Update: Italian language
* Fix: Fixed issue with the renew remind email
* Dev: Added filter 'ywsbs_check_order_before_pay_renew_order'
* Dev: Added the action 'ywsbs_before_pay_renew_order'
* Dev: Add action yith_suborder_renew_created to synchronize suborder with the parent

= 1.5.9 - Released on 29 May, 2019 =
* Update: Plugin framework
* Update: Language files
* Fix: Fixed with trial subscription and PayPal
* Fix: Fixed option name in subscriptions-related template
* Fix: Check renew order before payment
* Dev: Added meta is_a_renew on Multi Vendor suborder
* Dev: Save subscr_id meta when a ipn is received
* Dev: Cancel the subscription if parent_order is null

= 1.5.8 - Released on 16 April, 2019 =
* Tweak: Added a delay before cancel the subscriptions don't renewed
* Fix: Warning errors at backend under specific conditions

= 1.5.7 - Released on 09 April, 2019 =
* New: Support for WooCommerce 3.6
* Update: Plugin framework
* Update: Language files
* Update: Translation for the string 'Cancel subscription now'
* Fix: Set order_args in WooCommerce session, to fix Stripe issue
* Fix: Coupon amount
* Fix: Numeric issues
* Dev: Added filter 'ywsbs_use_date_format'

= 1.5.6 - Released on 26 Feb, 2019 =
* Update: Plugin framework
* Update: Language files
* Fix: Subscription status translation
* Fix: Fixed typo
* Fix: Subscription field show if check virtual or downloadable checkbox at first time
* Fix: Check if subscription has renews in the payment done email
* Fix: Recurring payment total in renew order email

= 1.5.5 - Released on 05 Feb, 2019 =
* Update: Plugin framework
* Update: Language files
* Tweak: added check if order contains subscriptions to not execute ywcsb_after_calculate_totals
* Fix: Fixed possible error under specific conditions
* Fix: Fixed add to cart validation
* Fix: $max_length array with localized index,
* Fix: Fixed a non numeric value on max_length field
* Fix: Fixed recurrent coupon on variation
* Dev: Added the filter 'ywsbs_register_panel_position'

= 1.5.4 - Released on 06 December, 2018 =
* New: Support for WordPress 5.0
* Update: Plugin framework
* Fix: Fixed wrong total subscription amount if price per is greater then one
* Fix: Moved transaction check to valid IPN handling

= 1.5.3 - Released on 23 October, 2018 =
* New: Integration with YITH WooCommerce Account Funds from version 1.1.2
* New: Integration with WooCommerce Stripe Gateway 4.1.12
* Update: Language files
* Update: Plugin framework

= 1.5.2 - Released on 23 October, 2018 =
* Update: Language files
* Update: Plugin framework
* Fix: Possible fatal error with YITH WooCommerce Event Tickets

= 1.5.1 - Released on 12 October, 2018 =
* New: Support for WooCommerce 3.5
* Fix: Deleting meta data not save the changes in the order
* Fix: Delete subscription error
* Dev: Added actions 'ywsbs_before_add_to_cart_subscription' and 'ywsbs_after_add_to_cart_subscription'

= 1.5.0 - Released on 05 October, 2018 =
* Fix: Possible fatal error under particular conditions.

= 1.4.9 - Released on 26 Sep, 2018 =
* Update: Plugin framework
* Fix: Some code notices

= 1.4.8 - Released on 10 Sep, 2018 =
* Fix: Fixed standard PayPal transaction id registration on renew order.
* Fix: Issues with PHP 7.2.
* Fix: Issues with Renew Reminder email.
* Fix: Fixed pause and cancellation issue.

= 1.4.7 - Released on 28 August, 2018 =
* Update: Language files
* Update: Plugin framework
* Fix: Multiple subscription products on cart
* Fix: Renew Reminder cron
* Fix: Email templates
* Fix: Show subscription total on the checkout page if setting max length option.
* Fix: IPN log of posted arguments
* Dev: Added filter 'ywsbs_add_shipping_cost_order_renew'

= 1.4.6 - Released on 30 Jul, 2018 =
* New: Integration with YITH Stripe Connect for WooCommerce from version 1.1.0
* New: Added payment method column and filter on Subscription List Table
* New: Added new option to show "Renew Now" button on My Account > Orders: if a renew order has at least one failed payment (not for all gateways)
* Update: Language files
* Fix: The "Pause" button was only being displayed to admin.

= 1.4.5 - Released on 09 Jul, 2018 =
* New: Edit details from subscription admin edit page ( only for subscription paid with YITH PayPal Express Checkout for WooCommerce )
* New: Added subscription number in order list
* Update: Plugin framework
* Update: Language files
* Fix: My Account Errors
* Fix: HTML Price when Max Length option is set
* Fix: My account endpoint
* Fix: Fixed columns in order list
* Fix: Fix api handler + refund for recurring payment standard PayPal

= 1.4.4 - Released on 29 May, 2018 =
* Update: Plugin framework
* Fix: The user can't cancel its subscription from My account page

= 1.4.3 - Released on 25 May, 2018 =
* Tweak: Support for GDPR compliance
* Update: Plugin framework
* Update: Localization files

= 1.4.2 - Released on 21 May, 2018 =
* Fix: Status of Subscription color in backend
* Fix: Subscription Email settings

= 1.4.1 - Released on 15 May, 2018 =
* Fix: Javascript Error on single product page
* Fix: Text domain wrong in two string

= 1.4.0 - Released on 15 May, 2018 =
* New: Support for WordPress 4.9.6
* New: Support for WooCommerce 3.4
* New: Privacy settings option
* New: Retain pending subscriptions option
* New: Retain cancelled subscriptions option
* New: One time shipping option in product editor Shipping tab
* New: Billing and Shipping Customer information editable by Administrator
* New: Shipping Customer information editable by Customer
* Update: Plugin framework
* Update: Localization files
* Fix: Order total calculation
* Fix: Activity log
* Fix: YITH WooCommerce Multi Vendor integration - fix for YITH WooCommerce Multi Vendor shipping method
* Fix: YITH WooCommerce Multi Vendor integration - vendor suborders *missing* the order note for new subscriptions
* Fix: YITH WooCommerce Multi Vendor integration - Added shipping cost in renew order (for vendors)
* Fix: Renew subscription from suborder
* Dev: New actions 'ywcsb_admin_subscription_data_after_billing_address' & 'ywcsb_admin_subscription_data_after_shipping_address'

= 1.3.2 - Released on 06 April 2018 =
* New: Integration with YITH WooCommerce Affiliate 1.2.4
* New: Dutch translation
* Tweak: Change hook prettyPhoto
* Update: Plugin framework
* Fix: Fixed get_price_html
* Fix: Order meta data
* Fix: Fixed taxes calculation when switching subscription
* Fix: Restore cart after cancel payment


= 1.3.1 - Released on 31 January 2018 =
* New: Support for WooCommerce 3.3.0
* Update: Plugin framework
* Fix: Issue when PayPal payment is cancelled

= 1.3.0 - Released on 29 January 2018 =
* New: Support for WooCommerce 3.3.0 RC2
* Update: Plugin framework
* Fix: Shipping taxes removed form WC settings
* Fix: PayPal IPN issue with PHP 7.1
* Fix: Prevent fatal error for WooCommerce < 3.0.0
* Dev: Added hook 'ywcsb_after_calculate_totals'
* Dev: Added hook 'ywsbs_change_prices'
* Dev: Added hook 'ywsbs_change_price_in_cart_html'
* Dev: Added hook 'ywsbs_change_price_current_in_cart_html'
* Dev: Added hook 'ywsbs_change_subtotal_price_in_cart_html'
* Dev: Added hook 'ywsbs_change_subtotal_price_current_in_cart_html'
* Dev: Added hook 'ywsbs_signup_fee_label'

= 1.2.9 - Released on 15 November 2017 =
* Fix: Javascript error in single product page
* Fix: WooCommerce Coupon when a subscription is on cart

= 1.2.8 - Released on 7 November 2017 =
* New: Support for WooCommerce 3.2.3
* Fix: Discount calculation when a Custom Coupon is applied
* Fix: Shipping Calculation for renew orders

= 1.2.7 - Released on 17 October 2017 =
* New: Support for WooCommerce 3.2.1
* Fix: Label on subscription product price

= 1.2.6 - Released on 12 October 2017 =
* New: Support for WooCommerce 3.2.0 Rc2
* New: German translation
* New: Added option ''Disable the reduction of stock in the renew order'
* Fix: Order item prices with YITH WooCommerce Product Add-Ons
* Fix: Prettyphoto.css removed font rules
* Fix: Subscription Status localization
* Fix: Removed the vendor taxonomy box in subscription CTP page
* Fix: Multiple coupons on cart
* Dev: Filter 'ywsbs_price_check'
* Dev: Added a check on content cart item key after order processed

= 1.2.5 - Released on 19 August 2017 =
* Fix: Create renew order manually
* Fix: changed plain text to html Subscription Status email

= 1.2.4 - Released on 16 August 2017 =
* New: Support for WooCommerce 3.1.0
* Update: Plugin framework
* Fix: Renew order shipping rate
* Fix: Compatibility with YITH WooCommerce Product Add-ons Premium 1.2.6
* Fix: Trial status after order complete
* Fix: Variation subscription price
* Fix: customer search on subscriptions list table
* Fix: wrong text domain in string
* Fix: subscription meta containing one subscription only
* Fix: Misspelled strings
* Dev: Added hook 'ywsbs_get_recurring_totals'

= 1.2.3 - Released on 26 May 2017 =
* Fix: Check on ipn_track_id due to Paypal issue
* Fix: Renew order fix

= 1.2.2 - Released on 05 May 2017 =
* Fix: Fixed renew shipping costs

= 1.2.1 - Released on 28 April 2017 =
* New: Support for WooCommerce 3.0.4
* New: Set customer notes in the renew from parent order
* Update: Plugin framework
* Fix: Custom billing and shipping address in the renew order
* Fix: Compatibility with php 5.4
* Dev: Changed endpoint hook
* Dev: Changed start time of cron jobs

= 1.2.0 - Released on 31 March 2017 =
* New: Support for WooCommerce 3.0 RC 2
* Update: Plugin Core
* Fix: Subtotal price on cart
* Dev: Added 'ywsbs_renew_subscription' action

= 1.1.7 - Released on 19 December 2016 =
* New: A new method of class 'YITH_WC_Subscription' that return the list of user's subscription
* New: Compatibility with YITH WooCommerce Product Add-ons Premium 1.2.0.8
* New: Support for WordPress 4.7
* New: Date picker in the metabox of subscription info in the backend
* New: Filter 'ywsbs_order_formatted_line_subtotal'
* Tweak: Price with taxes in the subscription related to an order
* Fix: The switch of subscriptions from the free to premium version
* Fix: "Max duration of pauses days" isn't saved properly
* Fix: Issues when the order of a subscription is deleted

= 1.1.6 - Released on 04 October 2016 =
* New: Filter 'ywsbs_trigger_email_before' to change the time to send the email before the expiration
* New: Italian translation
* New: Spanish translation
* Update: Plugin framework
* Fix: Coupons behavior when there's a trial period
* Fix: String localization


= 1.1.5 - Released on 20 July 2016 =
* New: Action to create a renewal order in subscription administrator details
* Tweak: Failed register payments
* Fix: Localization strings for trial period
* Fix: Activity log timestamp


= 1.1.4 - Released on 13 June 2016 =
* New: hook for actions on my subscriptions table
* New: Support for WooCommerce 2.6 RC1
* Update: Plugin framework

= 1.1.3 =
* New: Option Delete Subscription is extend also if the main order is deleted
* New: Email to customer when a payment fails
* Tweak: Changed method to retrieve billing and shipping info of a subscription
* Tweak: Customer info in the email
* Fix: Method is add_params_to_available_variation() and can_be_cancelled()
* Fix: Downgrade/upgrade variations


= 1.1.2 =
* Fix: Paypal IPN Request Fix when the renew order is not present
* Fix: Minor bugs


= 1.1.1 =
* Fix: Few missing and unknown textdomains
* Fix: Minor bugs

= 1.1.0 =
* New: Compatibility with YITH WooCommerce Stripe
* New: Paypal IPN validation amounts
* New: In On-hold orders list failed attempts
* New: Failed Attempts in Subscription list
* New: Enabled possibility to move in Trash or Delete subscriptions
* New: Options Overdue pending payment subscriptions after x hour(s)
* New: Suspend pending payment subscriptions after x hour(s)
* New: Option to Suspend a subscription if a recurring payment fail
* New: In Administrator Subscription Detail added the action "Active Subscription"
* New: In Administrator Subscription Detail added the action "Suspend Subscription"
* New: In Administrator Subscription Detail added the action "Overdue Subscription"
* New: In Administrator Subscription Detail added the action "Cancel Subscription"
* New: In Administrator Subscription Detail added the action "Cancel Subscription Now"
* New: In Administrator Subscriptions List Table added the views of subscription status
* New: Search box in Administrator Subscriptions List Table to search for ID or Product Name
* New: Option Delete subscription if the main order is cancelled
* Update: Dates in subscription details
* Fix: Admin can't add subscription if YITH WooCommerce Multi Vendor is enabled
* Fix: Start date if a Paypal Payment Method is 'echeck'
* Fix: Localization issue

= 1.0.1 =
* New: Compatibility with Wordpress 4.4
* New: Compatibility with WooCommerce 2.5 beta 3
* Update: Plugin framework
* Fix: Minor bugs

= 1.0.0 =
* Initial release

== Suggestions ==
If you have any suggestions concerning how to improve YITH WooCommerce Subscription, you can [write to us](mailto:plugins@yithemes.com "Your Inspiration Themes"), so that we can improve YITH WooCommerce Subscription.

