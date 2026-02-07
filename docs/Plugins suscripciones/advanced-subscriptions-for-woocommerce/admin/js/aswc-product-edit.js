/**
 * Admin JS for subscription products.
 *
 * @package Subscriptions_JC_For_WooCommerce
 */

(function ( $ ) {
	'use strict';

	$( document ).ready(
		function () {

			var dateToday = new Date();
			$(
				function () {
					$( ".aswc_subscription_start_date" ).datepicker(
						{
							showButtonPanel: true,
							dateFormat: 'yy-mm-dd',
							minDate: dateToday
						}
					);
				}
			);

			function aswc_show_subscription_settings_tab(){
				if ( $( '#_aswc_product' ).prop( 'checked' ) ) {

					$( document ).find( '.aswc_product_options' ).show();
					$( document ).find( '.aswc_product_options' ).removeClass( 'active' );
				} else {

					$( document ).find( '.aswc_product_options' ).hide();
					$( document ).find( '#aswc_product_target_section' ).hide();
					$( document ).find( '.general_tab' ).addClass( 'active' );
					$( document ).find( '#general_product_data' ).show();

				}
			}
			aswc_show_subscription_settings_tab();
			$( '#_aswc_product' ).on(
				'change',
				function () {
					aswc_show_subscription_settings_tab();
				}
			);

			/*Subscription interval set*/
			$( '#aswc_subscription_interval' ).on(
				'change',
				function () {
					var current_selection = $( this ).val();
					var expiry_interval   = $( '#aswc_subscription_expiry_interval' );
					if ( current_selection == 'day' ) {
							expiry_interval.empty();
							expiry_interval.append( $( '<option></option>' ).attr( 'value','day' ).text( aswc_product_param.day ) );

					} else if ( current_selection == 'week' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','week' ).text( aswc_product_param.week ) );

					} else if ( current_selection == 'month' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','month' ).text( aswc_product_param.month ) );

					} else if ( current_selection == 'year' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','year' ).text( aswc_product_param.year ) );
					}
				}
			);

			// subscription box.
			$( '#aswc_subscription_box_interval' ).on(
				'change',
				function () {
					var current_selection = $( this ).val();
					var expiry_interval   = $( '#aswc_subscription_box_expiry_interval' );
					if ( current_selection == 'day' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','day' ).text( aswc_product_param.day ) );

					} else if ( current_selection == 'week' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','week' ).text( aswc_product_param.week ) );

					} else if ( current_selection == 'month' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','month' ).text( aswc_product_param.month ) );

					} else if ( current_selection == 'year' ) {
						expiry_interval.empty();
						expiry_interval.append( $( '<option></option>' ).attr( 'value','year' ).text( aswc_product_param.year ) );
					}
				}
			);

			function toggleSubscriptionBoxFields() {
				var setupValue = $( '#aswc_subscription_box_setup' ).val();
				$( '.aswc_subscription_box_products_field' ).toggle( setupValue === 'specific_products' );
				$( '.aswc_subscription_box_categories_field' ).toggle( setupValue === 'specific_categories' );
			}
			$( '#aswc_subscription_box_setup' ).change( toggleSubscriptionBoxFields );
			toggleSubscriptionBoxFields();

			// subscription box.

			/*Expiry interval validation*/
			$( document ).on(
				'submit',
				'#post',
				function (e) {

					var subscription_number = $( '#aswc_subscription_number' ).val();
					var subscription_expiry = $( '#aswc_subscription_expiry_number' ).val();

					var aswc_subscription_box_number        = $( '#aswc_subscription_box_number' ).val();
					var aswc_subscription_box_expiry_number = $( '#aswc_subscription_box_expiry_number' ).val();
					var select_val                             = $( 'select#product-type' ).val();

					if ( aswc_subscription_box_expiry_number && aswc_subscription_box_number && select_val == 'subscription_box' ) {
						subscription_expiry = aswc_subscription_box_expiry_number;
						subscription_number = aswc_subscription_box_number;
					}

					if ( aswc_subscription_box_expiry_number != '') {
						if ( Number( aswc_subscription_box_expiry_number ) < Number( aswc_subscription_box_number ) ) {
							alert( aswc_product_param.expiry_notice );
							jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
							$( '#publish' ).removeClass( 'disabled' );
							e.preventDefault();
						}
					}

					if ( subscription_expiry != '' ) {
						if ( Number( subscription_expiry ) < Number( subscription_number ) ) {
							alert( aswc_product_param.expiry_notice );
							jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
							$( '#publish' ).removeClass( 'disabled' );
							e.preventDefault();
						}
						var subscription_interval                    = $( '#aswc_subscription_expiry_interval' ).val();
						var aswc_subscription_box_expiry_interval = $( '#aswc_subscription_box_expiry_interval' ).val();
						if ( aswc_subscription_box_expiry_interval ) {
							subscription_interval = aswc_subscription_box_expiry_interval;
						}
						aswc_subscription_box_expiry_interval
						if ( subscription_interval == 'day' ) {
							if ( subscription_expiry > 90 ) {
								alert( aswc_product_param.expiry_days_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_interval == 'week' ) {
							if ( subscription_expiry > 52 ) {
								alert( aswc_product_param.expiry_week_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_interval == 'month' ) {
							if ( subscription_expiry > 24 ) {
								alert( aswc_product_param.expiry_month_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_interval == 'year' ) {
							if ( subscription_expiry > 5 ) {
								alert( aswc_product_param.expiry_year_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						}
					}

					/*free trial validation*/
					var subscription_free_trial_number   = $( '#aswc_subscription_free_trial_number' ).val();
					var subscription_free_trial_interval = $( '#aswc_subscription_free_trial_interval' ).val();
					if ( subscription_free_trial_number != '' ) {

						if ( subscription_free_trial_interval == 'day' ) {
							if ( subscription_free_trial_number > 90 ) {
								alert( aswc_product_param.trial_days_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_free_trial_interval == 'week' ) {
							if ( subscription_free_trial_number > 52 ) {
								alert( aswc_product_param.trial_week_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_free_trial_interval == 'month' ) {
							if ( subscription_free_trial_number > 24 ) {
								alert( aswc_product_param.trial_month_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						} else if ( subscription_free_trial_interval == 'year' ) {
							if ( subscription_free_trial_number > 5 ) {
								alert( aswc_product_param.trial_year_notice );
								jQuery( '#publish' ).siblings( 'span' ).removeClass( 'is-active' );
								$( '#publish' ).removeClass( 'disabled' );
								e.preventDefault();
							}
						}
					}

				}
			);

			// Product type specific options.
			$( 'select#product-type' ).change(
				function () {

					var select_val = $( this ).val();
					console.log( select_val );

					if ( 'variable' === select_val ) {
							$( 'input#_aswc_product' ).prop( 'checked', false );
							aswc_show_subscription_settings_tab();
					} else if ( 'grouped' === select_val ) {
						$( 'input#_aswc_product' ).prop( 'checked', false );
						aswc_show_subscription_settings_tab();
					} else if ( 'external' === select_val ) {
						$( 'input#_aswc_product' ).prop( 'checked', false );
						aswc_show_subscription_settings_tab();
					}
				}
			);
			$( document ).on(
				'change',
				'#product-type',
				function () {
					aswc_show_subscription_box_settings_tab();
				}
			);
			aswc_show_subscription_box_settings_tab();

			function aswc_show_subscription_box_settings_tab() {
				if ( $( 'select#product-type' ).length && 'subscription_box' === $( 'select#product-type option:selected' ).val() ) {
					$( document ).find( '.aswc_subscription_box_product_options' ).show();
					$( document ).find( '.aswc_subscription_box_product_options' ).removeClass( 'active' );
					$( document ).find( '.aswc_subscription_box_product_target_section' ).hide();
					$( document ).find( '.aswc_subscription_box_product_target_section' ).removeClass( 'active' );
					$( document ).find( '.aswc_product_options' ).hide();
					$( document ).find( '#aswc_product_target_section' ).hide();
				} else {
					$( document ).find( '.aswc_subscription_box_product_options' ).hide();
					$( document ).find( '#aswc_subscription_box_product_options' ).removeClass( 'active' );
					$( document ).find( '.aswc_subscription_box_product_target_section' ).hide();
					$( document ).find( '.aswc_subscription_box_product_target_section' ).removeClass( 'active' );
				}
			}
			// add selectWoo for multiselect.
			if ( $( '.aswc_learnpress_course' ).length > 0 ) {
				$( '.aswc_learnpress_course' ).selectWoo();
			}

			var urlParams = new URLSearchParams( window.location.search );
			var post_id   = urlParams.get( 'post' );
		}
	);
})( jQuery );
