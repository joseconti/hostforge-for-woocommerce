/**
 * HostForge Server Manager Admin JS.
 *
 * Handles: save server, test connection, fetch packages,
 * delete server, auth method toggle, new group toggle.
 *
 * @package HostForge
 */

(function () {
	'use strict';

	const config = window.hostforgeServer || {};

	/**
	 * Show a notice message.
	 *
	 * @param {string} message Notice text.
	 * @param {string} type    'success', 'error', or 'info'.
	 */
	function showNotice(message, type) {
		const container = document.getElementById('hf-server-notices');
		if (!container) return;

		const notice = document.createElement('div');
		notice.className = `notice notice-${type} is-dismissible`;
		notice.innerHTML = `<p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>`;

		container.innerHTML = '';
		container.appendChild(notice);

		notice.querySelector('.notice-dismiss').addEventListener('click', function () {
			notice.remove();
		});

		// Auto-dismiss after 5 seconds.
		setTimeout(() => notice.remove(), 5000);
	}

	/**
	 * Toggle auth method fields.
	 */
	function toggleAuthFields() {
		const method = document.getElementById('hf-auth-method');
		if (!method) return;

		const tokenFields = document.querySelectorAll('.hf-auth-token');
		const passwordFields = document.querySelectorAll('.hf-auth-password');

		if (method.value === 'token') {
			tokenFields.forEach((el) => (el.style.display = ''));
			passwordFields.forEach((el) => (el.style.display = 'none'));
		} else {
			tokenFields.forEach((el) => (el.style.display = 'none'));
			passwordFields.forEach((el) => (el.style.display = ''));
		}
	}

	/**
	 * Toggle new group input.
	 */
	function setupNewGroupToggle() {
		const btn = document.getElementById('hf-toggle-new-group');
		const input = document.getElementById('hf-new-group');
		const select = document.getElementById('hf-server-group');

		if (!btn || !input || !select) return;

		btn.addEventListener('click', function () {
			const isHidden = input.classList.contains('hf-hidden');
			if (isHidden) {
				input.classList.remove('hf-hidden');
				select.classList.add('hf-hidden');
				btn.textContent = config.i18n?.cancel || 'Cancel';
			} else {
				input.classList.add('hf-hidden');
				select.classList.remove('hf-hidden');
				input.value = '';
				btn.textContent = config.i18n?.newGroup || 'New Group';
			}
		});
	}

	/**
	 * Handle save server form.
	 */
	function setupSaveServer() {
		const form = document.getElementById('hf-server-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const hostname = document.getElementById('hf-hostname');
			const panelType = document.getElementById('hf-panel-type');

			if (!hostname || !hostname.value.trim()) {
				showNotice(config.i18n?.requiredHostname || 'Hostname is required.', 'error');
				hostname?.focus();
				return;
			}

			if (!panelType || !panelType.value) {
				showNotice('Panel type is required.', 'error');
				panelType?.focus();
				return;
			}

			const saveBtn = document.getElementById('hf-save-server');
			if (saveBtn) {
				saveBtn.disabled = true;
				saveBtn.textContent = config.i18n?.saving || 'Saving...';
			}

			const formData = new FormData();
			formData.append('action', 'hf_save_server');
			formData.append('nonce', config.nonce);
			formData.append('server_id', form.querySelector('[name="server_id"]')?.value || '0');
			formData.append('name', document.getElementById('hf-server-name')?.value || '');
			formData.append('panel_type', panelType.value);
			formData.append('hostname', hostname.value.trim());
			formData.append('port', document.getElementById('hf-port')?.value || '');
			formData.append('auth_method', document.getElementById('hf-auth-method')?.value || 'token');
			formData.append('api_token', document.getElementById('hf-api-token')?.value || '');
			formData.append('username', document.getElementById('hf-username')?.value || '');
			formData.append('password', document.getElementById('hf-password')?.value || '');
			formData.append('max_accounts', document.getElementById('hf-max-accounts')?.value || '0');
			formData.append('nameservers', document.getElementById('hf-nameservers')?.value || '');
			formData.append('notes', form.querySelector('[name="notes"]')?.value || '');

			// Handle server group (existing or new).
			const newGroup = document.getElementById('hf-new-group');
			const groupSelect = document.getElementById('hf-server-group');
			if (newGroup && !newGroup.classList.contains('hf-hidden') && newGroup.value.trim()) {
				formData.append('server_group', newGroup.value.trim());
			} else if (groupSelect) {
				formData.append('server_group', groupSelect.value);
			}

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((res) => res.json())
				.then((res) => {
					if (res.success) {
						showNotice(res.data.message, 'success');
						if (res.data.redirect && !form.querySelector('[name="server_id"]')?.value) {
							window.location.href = res.data.redirect;
						}
					} else {
						showNotice(res.data?.message || config.i18n?.error || 'Error', 'error');
					}
				})
				.catch(() => {
					showNotice(config.i18n?.error || 'An error occurred.', 'error');
				})
				.finally(() => {
					if (saveBtn) {
						saveBtn.disabled = false;
						saveBtn.textContent = form.querySelector('[name="server_id"]')?.value > 0
							? 'Update Server'
							: 'Add Server';
					}
				});
		});
	}

	/**
	 * Handle test connection button.
	 */
	function setupTestConnection() {
		const btn = document.getElementById('hf-test-connection');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const resultEl = document.getElementById('hf-test-result');
			btn.disabled = true;
			btn.textContent = config.i18n?.testing || 'Testing...';
			if (resultEl) resultEl.textContent = '';

			const formData = new FormData();
			formData.append('action', 'hf_test_server_connection');
			formData.append('nonce', config.nonce);

			const form = document.getElementById('hf-server-form');
			const serverId = form?.querySelector('[name="server_id"]')?.value || '0';

			if (serverId && parseInt(serverId) > 0) {
				formData.append('server_id', serverId);
			} else {
				formData.append('panel_type', document.getElementById('hf-panel-type')?.value || '');
				formData.append('hostname', document.getElementById('hf-hostname')?.value || '');
				formData.append('port', document.getElementById('hf-port')?.value || '');
				formData.append('auth_method', document.getElementById('hf-auth-method')?.value || 'token');
				formData.append('api_token', document.getElementById('hf-api-token')?.value || '');
				formData.append('username', document.getElementById('hf-username')?.value || '');
				formData.append('password', document.getElementById('hf-password')?.value || '');
			}

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((res) => res.json())
				.then((res) => {
					if (res.success) {
						if (resultEl) {
							resultEl.textContent = res.data.message;
							resultEl.className = 'hf-result-success';
						}
						showNotice(config.i18n?.testSuccess || 'Connection successful!', 'success');
					} else {
						if (resultEl) {
							resultEl.textContent = res.data?.message || 'Failed';
							resultEl.className = 'hf-result-error';
						}
						showNotice(config.i18n?.testFailed || 'Connection failed.', 'error');
					}
				})
				.catch(() => {
					if (resultEl) {
						resultEl.textContent = config.i18n?.error || 'Error';
						resultEl.className = 'hf-result-error';
					}
				})
				.finally(() => {
					btn.disabled = false;
					btn.textContent = 'Test Connection';
				});
		});
	}

	/**
	 * Handle fetch packages button.
	 */
	function setupFetchPackages() {
		const btn = document.getElementById('hf-fetch-packages');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const serverId = btn.dataset.serverId;
			const resultEl = document.getElementById('hf-fetch-result');

			if (!serverId) return;

			btn.disabled = true;
			btn.textContent = config.i18n?.fetching || 'Fetching...';
			if (resultEl) resultEl.textContent = '';

			const formData = new FormData();
			formData.append('action', 'hf_fetch_server_packages');
			formData.append('nonce', config.nonce);
			formData.append('server_id', serverId);

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((res) => res.json())
				.then((res) => {
					if (res.success) {
						if (resultEl) {
							resultEl.textContent = res.data.message;
							resultEl.className = 'hf-result-success';
						}
						showNotice(config.i18n?.fetchSuccess || 'Packages fetched.', 'success');

						// Reload to update list.
						setTimeout(() => window.location.reload(), 1500);
					} else {
						if (resultEl) {
							resultEl.textContent = res.data?.message || 'Failed';
							resultEl.className = 'hf-result-error';
						}
					}
				})
				.catch(() => {
					if (resultEl) {
						resultEl.textContent = config.i18n?.error || 'Error';
						resultEl.className = 'hf-result-error';
					}
				})
				.finally(() => {
					btn.disabled = false;
					btn.textContent = 'Fetch Packages';
				});
		});
	}

	/**
	 * Handle delete server buttons.
	 */
	function setupDeleteServer() {
		document.addEventListener('click', function (e) {
			const btn = e.target.closest('.hf-delete-server');
			if (!btn) return;

			e.preventDefault();

			if (!confirm(config.i18n?.confirmDelete || 'Are you sure?')) {
				return;
			}

			const serverId = btn.dataset.serverId;
			if (!serverId) return;

			const formData = new FormData();
			formData.append('action', 'hf_delete_server');
			formData.append('nonce', config.nonce);
			formData.append('server_id', serverId);

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((res) => res.json())
				.then((res) => {
					if (res.success && res.data.redirect) {
						window.location.href = res.data.redirect;
					}
				});
		});
	}

	/**
	 * Initialize on DOM ready.
	 */
	document.addEventListener('DOMContentLoaded', function () {
		toggleAuthFields();
		setupNewGroupToggle();
		setupSaveServer();
		setupTestConnection();
		setupFetchPackages();
		setupDeleteServer();

		// Listen for auth method changes.
		const authMethod = document.getElementById('hf-auth-method');
		if (authMethod) {
			authMethod.addEventListener('change', toggleAuthFields);
		}
	});
})();
