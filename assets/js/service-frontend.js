/**
 * Service Frontend JavaScript.
 *
 * Handles SSO, password change, cancellation, upgrade and usage stats
 * on the My Account hosting-services page.
 *
 * @package HostForge
 */
(function () {
	'use strict';

	const config = window.hostforgeServiceFront || {};

	/**
	 * Show a notice to the user.
	 */
	function showNotice(message, type) {
		const container = document.getElementById('hf-frontend-notice');
		if (!container) return;

		container.className = type === 'error' ? 'woocommerce-error' : 'woocommerce-message';
		container.textContent = message;
		container.style.display = 'block';

		container.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	/**
	 * Make an AJAX request.
	 */
	function ajaxRequest(action, data) {
		const formData = new FormData();
		formData.append('action', action);
		formData.append('nonce', config.nonce);

		Object.keys(data).forEach(function (key) {
			formData.append(key, data[key]);
		});

		return fetch(config.ajaxUrl, {
			method: 'POST',
			body: formData,
		}).then(function (res) { return res.json(); });
	}

	/**
	 * SSO — Login to Control Panel.
	 */
	function initSSO() {
		document.querySelectorAll('.hf-sso-btn').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();

				const serviceId = this.dataset.serviceId;
				const i18n = config.i18n || {};

				this.disabled = true;
				this.textContent = i18n.loading || 'Loading...';

				ajaxRequest('hf_service_sso', { service_id: serviceId })
					.then(function (res) {
						if (res.success && res.data.url) {
							window.open(res.data.url, '_blank');
						} else {
							showNotice(res.data.message || i18n.error, 'error');
						}

						btn.disabled = false;
						btn.textContent = 'Login to Control Panel';
					})
					.catch(function () {
						showNotice(i18n.error, 'error');
						btn.disabled = false;
					});
			});
		});
	}

	/**
	 * Change Password Form.
	 */
	function initChangePassword() {
		document.querySelectorAll('.hf-change-password-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();

				const serviceId = this.dataset.serviceId;
				const passwordInput = this.querySelector('[name="new_password"]');
				const newPassword = passwordInput.value;
				const i18n = config.i18n || {};

				if (newPassword.length < 8) {
					showNotice(i18n.passwordMinLength, 'error');
					return;
				}

				const btn = this.querySelector('button[type="submit"]');
				btn.disabled = true;
				btn.textContent = i18n.loading || 'Loading...';

				ajaxRequest('hf_service_change_password', {
					service_id: serviceId,
					new_password: newPassword,
				})
					.then(function (res) {
						if (res.success) {
							showNotice(i18n.passwordChanged, 'success');
							passwordInput.value = '';
						} else {
							showNotice(res.data.message || i18n.error, 'error');
						}

						btn.disabled = false;
						btn.textContent = 'Change Password';
					})
					.catch(function () {
						showNotice(i18n.error, 'error');
						btn.disabled = false;
					});
			});
		});
	}

	/**
	 * Cancel Request Form.
	 */
	function initCancelRequest() {
		document.querySelectorAll('.hf-cancel-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();

				const i18n = config.i18n || {};

				if (!confirm(i18n.confirmCancel)) {
					return;
				}

				const serviceId = this.dataset.serviceId;
				const reason = this.querySelector('[name="reason"]').value;
				const btn = this.querySelector('button[type="submit"]');

				btn.disabled = true;
				btn.textContent = i18n.loading || 'Loading...';

				ajaxRequest('hf_service_cancel_request', {
					service_id: serviceId,
					reason: reason,
				})
					.then(function (res) {
						if (res.success) {
							showNotice(i18n.cancelSuccess, 'success');
							form.style.display = 'none';
						} else {
							showNotice(res.data.message || i18n.error, 'error');
							btn.disabled = false;
							btn.textContent = 'Request Cancellation';
						}
					})
					.catch(function () {
						showNotice(i18n.error, 'error');
						btn.disabled = false;
					});
			});
		});
	}

	/**
	 * Upgrade/Downgrade Form.
	 */
	function initUpgradeRequest() {
		document.querySelectorAll('.hf-upgrade-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();

				const serviceId = this.dataset.serviceId;
				const newPackage = this.querySelector('[name="new_package"]').value;
				const i18n = config.i18n || {};

				if (!newPackage) {
					showNotice(i18n.error, 'error');
					return;
				}

				const btn = this.querySelector('button[type="submit"]');
				btn.disabled = true;
				btn.textContent = i18n.loading || 'Loading...';

				ajaxRequest('hf_service_upgrade_request', {
					service_id: serviceId,
					new_package: newPackage,
				})
					.then(function (res) {
						if (res.success) {
							showNotice(i18n.upgradeSuccess, 'success');
						} else {
							showNotice(res.data.message || i18n.error, 'error');
						}

						btn.disabled = false;
						btn.textContent = 'Request Change';
					})
					.catch(function () {
						showNotice(i18n.error, 'error');
						btn.disabled = false;
					});
			});
		});
	}

	/**
	 * Load Usage Stats.
	 */
	function initUsageStats() {
		const container = document.getElementById('hf-usage-container');
		if (!container) return;

		const serviceId = container.dataset.serviceId;

		ajaxRequest('hf_service_usage', { service_id: serviceId })
			.then(function (res) {
				if (res.success && res.data.usage) {
					renderUsageStats(container, res.data.usage);
				} else {
					container.innerHTML = '<p class="hf-muted">Could not load usage data.</p>';
				}
			})
			.catch(function () {
				container.innerHTML = '<p class="hf-muted">Could not load usage data.</p>';
			});
	}

	/**
	 * Render usage stats into the container.
	 */
	function renderUsageStats(container, usage) {
		let html = '<div class="hf-usage-grid">';

		const items = [
			{ key: 'disk_used', label: 'Disk Usage', max_key: 'disk_limit', unit: 'MB' },
			{ key: 'bandwidth_used', label: 'Bandwidth', max_key: 'bandwidth_limit', unit: 'MB' },
			{ key: 'email_accounts', label: 'Email Accounts', max_key: 'email_limit', unit: '' },
			{ key: 'databases', label: 'Databases', max_key: 'database_limit', unit: '' },
			{ key: 'subdomains', label: 'Subdomains', max_key: 'subdomain_limit', unit: '' },
			{ key: 'addon_domains', label: 'Addon Domains', max_key: 'addon_domain_limit', unit: '' },
		];

		items.forEach(function (item) {
			const used = parseFloat(usage[item.key]) || 0;
			const max = parseFloat(usage[item.max_key]) || 0;
			const unit = item.unit;
			const pct = max > 0 ? Math.min(Math.round((used / max) * 100), 100) : 0;
			const barClass = pct > 90 ? 'hf-usage-item__bar-fill--danger' : (pct > 70 ? 'hf-usage-item__bar-fill--warning' : '');

			if (used > 0 || max > 0) {
				html += '<div class="hf-usage-item">';
				html += '<span class="hf-usage-item__label">' + item.label + '</span>';

				if (max > 0) {
					html += '<span class="hf-usage-item__value">' + used + (unit ? ' ' + unit : '') + ' / ' + max + (unit ? ' ' + unit : '') + '</span>';
					html += '<div class="hf-usage-item__bar"><div class="hf-usage-item__bar-fill ' + barClass + '" style="width:' + pct + '%"></div></div>';
				} else {
					html += '<span class="hf-usage-item__value">' + used + (unit ? ' ' + unit : '') + '</span>';
				}

				html += '</div>';
			}
		});

		html += '</div>';
		container.innerHTML = html;
	}

	// Init on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initSSO();
		initChangePassword();
		initCancelRequest();
		initUpgradeRequest();
		initUsageStats();
	});
})();
