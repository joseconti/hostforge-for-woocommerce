/**
 * Service Admin JavaScript.
 *
 * Handles manual service actions and automation settings.
 *
 * @package HostForge
 */
(function () {
	'use strict';

	const config = window.hostforgeService || {};

	/**
	 * Show a notice message.
	 */
	function showNotice(containerId, message, type) {
		const container = document.getElementById(containerId);
		if (!container) return;

		container.className = 'notice notice-' + type;
		container.querySelector('p').textContent = message;
		container.style.display = 'block';

		container.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	/**
	 * Handle service action buttons (suspend, unsuspend, terminate).
	 */
	function initServiceActions() {
		document.querySelectorAll('.hf-service-action-btn').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();

				const action = this.dataset.action;
				const serviceId = this.dataset.serviceId;
				const i18n = config.i18n || {};

				let confirmMsg = '';
				if (action === 'suspend') confirmMsg = i18n.confirmSuspend;
				else if (action === 'unsuspend') confirmMsg = i18n.confirmUnsuspend;
				else if (action === 'terminate') confirmMsg = i18n.confirmTerminate;

				if (confirmMsg && !confirm(confirmMsg)) {
					return;
				}

				this.disabled = true;
				this.textContent = i18n.processing || 'Processing...';

				const data = new FormData();
				data.append('action', 'hf_service_action');
				data.append('nonce', config.nonce);
				data.append('service_id', serviceId);
				data.append('service_action', action);

				fetch(config.ajaxUrl, {
					method: 'POST',
					body: data,
				})
					.then(function (res) { return res.json(); })
					.then(function (res) {
						if (res.success) {
							showNotice('hf-service-notice', res.data.message, 'success');
							setTimeout(function () { location.reload(); }, 2000);
						} else {
							showNotice('hf-service-notice', res.data.message || i18n.error, 'error');
							btn.disabled = false;
							btn.textContent = action.charAt(0).toUpperCase() + action.slice(1) + ' Service';
						}
					})
					.catch(function () {
						showNotice('hf-service-notice', i18n.error, 'error');
						btn.disabled = false;
					});
			});
		});
	}

	/**
	 * Handle automation settings form.
	 */
	function initAutomationSettings() {
		const form = document.getElementById('hf-automation-settings-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const i18n = config.i18n || {};
			const btn = document.getElementById('hf-save-automation-settings');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.saving || 'Saving...';
			}

			const data = new FormData();
			data.append('action', 'hf_save_automation_settings');
			data.append('nonce', config.nonce);

			const checkbox = form.querySelector('[name="provision_on_processing"]');
			data.append('provision_on_processing', checkbox && checkbox.checked ? 'yes' : 'no');
			data.append('auto_suspend_days', form.querySelector('[name="auto_suspend_days"]').value);
			data.append('auto_terminate_days', form.querySelector('[name="auto_terminate_days"]').value);
			data.append('password_length', form.querySelector('[name="password_length"]').value);

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: data,
			})
				.then(function (res) { return res.json(); })
				.then(function (res) {
					if (res.success) {
						showNotice('hf-settings-notice', res.data.message, 'success');
					} else {
						showNotice('hf-settings-notice', res.data.message || i18n.error, 'error');
					}

					if (btn) {
						btn.disabled = false;
						btn.textContent = i18n.saved ? i18n.saved.replace('saved', 'Save Settings') : 'Save Settings';
					}
				})
				.catch(function () {
					showNotice('hf-settings-notice', i18n.error, 'error');
					if (btn) btn.disabled = false;
				});
		});
	}

	// Init on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initServiceActions();
		initAutomationSettings();
	});
})();
