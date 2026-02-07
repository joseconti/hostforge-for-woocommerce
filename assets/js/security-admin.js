/**
 * Security Admin JavaScript.
 *
 * Handles AJAX operations for security settings, IP blocking/unblocking.
 *
 * @package HostForge
 */

( function() {
	'use strict';

	/**
	 * Show a notice message.
	 *
	 * @param {string} containerId Notice container ID.
	 * @param {string} message     Message text.
	 * @param {string} type        Notice type (success or error).
	 */
	function showNotice( containerId, message, type ) {
		var container = document.getElementById( containerId );

		if ( ! container ) {
			return;
		}

		container.className = 'notice notice-' + type;
		container.innerHTML = '<p>' + message + '</p>';
		container.style.display = 'block';

		setTimeout( function() {
			container.style.display = 'none';
		}, 5000 );
	}

	/**
	 * Toggle spinner visibility.
	 *
	 * @param {string}  spinnerId Spinner element ID.
	 * @param {boolean} show      Whether to show or hide.
	 */
	function toggleSpinner( spinnerId, show ) {
		var spinner = document.getElementById( spinnerId );

		if ( spinner ) {
			spinner.classList.toggle( 'is-active', show );
		}
	}

	/**
	 * Handle security settings form submission.
	 */
	var settingsForm = document.getElementById( 'hf-security-settings-form' );

	if ( settingsForm ) {
		settingsForm.addEventListener( 'submit', function( e ) {
			e.preventDefault();

			toggleSpinner( 'hf-security-spinner', true );

			var formData = new FormData( settingsForm );
			formData.append( 'action', 'hf_save_security_settings' );
			formData.append( 'nonce', hfSecurity.nonce );

			fetch( hfSecurity.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( data ) {
				toggleSpinner( 'hf-security-spinner', false );

				if ( data.success ) {
					showNotice( 'hf-security-notice', hfSecurity.i18n.saved, 'success' );
				} else {
					showNotice( 'hf-security-notice', data.data.message || hfSecurity.i18n.error, 'error' );
				}
			} )
			.catch( function() {
				toggleSpinner( 'hf-security-spinner', false );
				showNotice( 'hf-security-notice', hfSecurity.i18n.error, 'error' );
			} );
		} );
	}

	/**
	 * Handle IP block form submission.
	 */
	var blockForm = document.getElementById( 'hf-block-ip-form' );

	if ( blockForm ) {
		blockForm.addEventListener( 'submit', function( e ) {
			e.preventDefault();

			toggleSpinner( 'hf-block-ip-spinner', true );

			var formData = new FormData( blockForm );
			formData.append( 'action', 'hf_block_ip' );
			formData.append( 'nonce', hfSecurity.nonce );

			fetch( hfSecurity.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( data ) {
				toggleSpinner( 'hf-block-ip-spinner', false );

				if ( data.success ) {
					showNotice( 'hf-block-ip-notice', hfSecurity.i18n.blocked, 'success' );
					setTimeout( function() {
						window.location.reload();
					}, 1000 );
				} else {
					showNotice( 'hf-block-ip-notice', data.data.message || hfSecurity.i18n.error, 'error' );
				}
			} )
			.catch( function() {
				toggleSpinner( 'hf-block-ip-spinner', false );
				showNotice( 'hf-block-ip-notice', hfSecurity.i18n.error, 'error' );
			} );
		} );
	}

	/**
	 * Handle IP unblock buttons.
	 */
	document.addEventListener( 'click', function( e ) {
		var button = e.target.closest( '.hf-unblock-ip' );

		if ( ! button ) {
			return;
		}

		var ip = button.getAttribute( 'data-ip' );

		if ( ! ip ) {
			return;
		}

		if ( ! confirm( hfSecurity.i18n.confirmUnblock ) ) {
			return;
		}

		button.disabled = true;

		var formData = new FormData();
		formData.append( 'action', 'hf_unblock_ip' );
		formData.append( 'nonce', hfSecurity.nonce );
		formData.append( 'ip_address', ip );

		fetch( hfSecurity.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
		.then( function( response ) {
			return response.json();
		} )
		.then( function( data ) {
			if ( data.success ) {
				var row = button.closest( 'tr' );
				if ( row ) {
					row.remove();
				}
			} else {
				button.disabled = false;
				alert( data.data.message || hfSecurity.i18n.error );
			}
		} )
		.catch( function() {
			button.disabled = false;
			alert( hfSecurity.i18n.error );
		} );
	} );
} )();
