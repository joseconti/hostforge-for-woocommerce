/**
 * Domain Manager Admin JavaScript.
 *
 * Handles AJAX interactions for domain management, DNS records,
 * TLD pricing, and registrar settings.
 *
 * @package HostForge
 */

(function () {
	'use strict';

	const config = window.hostforgeDomain || {};

	/**
	 * Make an AJAX request.
	 */
	function ajaxRequest(action, data, onSuccess, onError) {
		const formData = new FormData();
		formData.append('action', action);
		formData.append('nonce', config.nonce);

		for (const key in data) {
			if (Array.isArray(data[key])) {
				data[key].forEach((val) => formData.append(key + '[]', val));
			} else {
				formData.append(key, data[key]);
			}
		}

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then((response) => response.json())
			.then((result) => {
				if (result.success) {
					onSuccess(result.data);
				} else {
					const msg = result.data?.message || config.i18n.error;
					if (onError) {
						onError(msg);
					} else {
						showNotice(msg, 'error');
					}
				}
			})
			.catch(() => {
				if (onError) {
					onError(config.i18n.error);
				} else {
					showNotice(config.i18n.error, 'error');
				}
			});
	}

	/**
	 * Show a notice message.
	 */
	function showNotice(message, type, container) {
		const target = container || document.getElementById('hf-action-result') || document.getElementById('hf-registrar-result');
		if (!target) return;

		target.style.display = 'block';
		target.className = 'hf-notice hf-notice--' + type;
		target.textContent = message;

		if ('success' === type) {
			setTimeout(() => {
				target.style.display = 'none';
			}, 5000);
		}
	}

	/**
	 * Initialize domain detail page handlers.
	 */
	function initDomainDetail() {
		// Sync domain.
		const syncBtn = document.getElementById('hf-sync-domain');
		if (syncBtn) {
			syncBtn.addEventListener('click', function () {
				const domainId = this.dataset.domainId;
				this.disabled = true;
				this.textContent = config.i18n.syncing;

				ajaxRequest(
					'hf_sync_domain',
					{ domain_id: domainId },
					(data) => {
						showNotice(data.message, 'success');
						syncBtn.disabled = false;
						syncBtn.textContent = config.i18n.synced;
						setTimeout(() => location.reload(), 1500);
					},
					(msg) => {
						showNotice(msg, 'error');
						syncBtn.disabled = false;
						syncBtn.textContent = 'Sync with Registrar';
					}
				);
			});
		}

		// Auto-renew toggle.
		const autoRenewToggle = document.getElementById('hf-auto-renew-toggle');
		if (autoRenewToggle) {
			autoRenewToggle.addEventListener('change', function () {
				ajaxRequest('hf_toggle_domain_auto_renew', {
					domain_id: this.dataset.domainId,
					auto_renew: this.checked ? 'yes' : 'no',
				}, (data) => showNotice(data.message, 'success'));
			});
		}

		// Lock toggle.
		const lockToggle = document.getElementById('hf-lock-toggle');
		if (lockToggle) {
			lockToggle.addEventListener('change', function () {
				ajaxRequest('hf_toggle_domain_lock', {
					domain_id: this.dataset.domainId,
					lock: this.checked ? 'yes' : 'no',
				}, (data) => showNotice(data.message, 'success'));
			});
		}

		// Save nameservers.
		const saveNsBtn = document.getElementById('hf-save-nameservers');
		if (saveNsBtn) {
			saveNsBtn.addEventListener('click', function () {
				const form = document.getElementById('hf-nameservers-form');
				const domainId = form.dataset.domainId;
				const inputs = form.querySelectorAll('input[name="nameservers[]"]');
				const nameservers = [];

				inputs.forEach((input) => {
					if (input.value.trim()) {
						nameservers.push(input.value.trim());
					}
				});

				this.disabled = true;
				this.textContent = config.i18n.saving;

				ajaxRequest(
					'hf_save_nameservers',
					{ domain_id: domainId, nameservers: nameservers },
					(data) => {
						showNotice(data.message, 'success');
						saveNsBtn.disabled = false;
						saveNsBtn.textContent = config.i18n.saved;
					},
					(msg) => {
						showNotice(msg, 'error');
						saveNsBtn.disabled = false;
						saveNsBtn.textContent = 'Save Nameservers';
					}
				);
			});
		}

		// Get EPP Code.
		const eppBtn = document.getElementById('hf-get-epp');
		if (eppBtn) {
			eppBtn.addEventListener('click', function () {
				ajaxRequest('hf_get_epp_code', {
					domain_id: this.dataset.domainId,
				}, (data) => showNotice(data.message, 'success'));
			});
		}

		// Renew domain.
		const renewBtn = document.getElementById('hf-renew-domain');
		if (renewBtn) {
			renewBtn.addEventListener('click', function () {
				if (!confirm(config.i18n.confirmRenew)) return;

				ajaxRequest('hf_manual_renew_domain', {
					domain_id: this.dataset.domainId,
				}, (data) => showNotice(data.message, 'success'));
			});
		}
	}

	/**
	 * Initialize DNS record handlers.
	 */
	function initDnsHandlers() {
		// Add DNS record.
		const addDnsBtn = document.getElementById('hf-add-dns-record');
		if (addDnsBtn) {
			addDnsBtn.addEventListener('click', function () {
				const form = document.getElementById('hf-dns-add-form');

				ajaxRequest('hf_save_dns_record', {
					domain_id: form.dataset.domainId,
					record_type: document.getElementById('hf-dns-type').value,
					host: document.getElementById('hf-dns-host').value || '@',
					value: document.getElementById('hf-dns-value').value,
					ttl: document.getElementById('hf-dns-ttl').value || 3600,
					priority: document.getElementById('hf-dns-priority').value || 0,
				}, () => location.reload());
			});
		}

		// Delete DNS record.
		document.querySelectorAll('.hf-delete-dns').forEach((btn) => {
			btn.addEventListener('click', function () {
				if (!confirm(config.i18n.confirmDelete)) return;

				const row = this.closest('tr');
				const form = document.getElementById('hf-dns-add-form');

				ajaxRequest('hf_delete_dns_record', {
					domain_id: form.dataset.domainId,
					record_id: this.dataset.recordId,
				}, () => {
					row.remove();
					showNotice('DNS record deleted.', 'success');
				});
			});
		});

		// Sync DNS.
		const syncDnsBtn = document.getElementById('hf-sync-dns');
		if (syncDnsBtn) {
			syncDnsBtn.addEventListener('click', function () {
				ajaxRequest('hf_sync_dns', {
					domain_id: this.dataset.domainId,
				}, () => location.reload());
			});
		}
	}

	/**
	 * Initialize TLD pricing handlers.
	 */
	function initTldPricing() {
		const modal = document.getElementById('hf-tld-modal');
		if (!modal) return;

		// Add TLD button.
		const addBtn = document.getElementById('hf-add-tld-btn');
		if (addBtn) {
			addBtn.addEventListener('click', function () {
				document.getElementById('hf-tld-id').value = '0';
				document.getElementById('hf-tld-name').value = '';
				document.getElementById('hf-tld-register').value = '';
				document.getElementById('hf-tld-renew').value = '';
				document.getElementById('hf-tld-transfer').value = '';
				document.getElementById('hf-tld-currency').value = 'USD';
				document.getElementById('hf-tld-active').checked = true;
				document.getElementById('hf-tld-modal-title').textContent = 'Add TLD';
				modal.style.display = 'flex';
			});
		}

		// Edit TLD buttons.
		document.querySelectorAll('.hf-edit-tld').forEach((btn) => {
			btn.addEventListener('click', function () {
				document.getElementById('hf-tld-id').value = this.dataset.id;
				document.getElementById('hf-tld-name').value = this.dataset.tld;
				document.getElementById('hf-tld-register').value = this.dataset.register;
				document.getElementById('hf-tld-renew').value = this.dataset.renew;
				document.getElementById('hf-tld-transfer').value = this.dataset.transfer;
				document.getElementById('hf-tld-currency').value = this.dataset.currency;
				document.getElementById('hf-tld-active').checked = '1' === this.dataset.active;
				document.getElementById('hf-tld-modal-title').textContent = 'Edit TLD';
				modal.style.display = 'flex';
			});
		});

		// Cancel modal.
		const cancelBtn = document.getElementById('hf-cancel-tld');
		if (cancelBtn) {
			cancelBtn.addEventListener('click', function () {
				modal.style.display = 'none';
			});
		}

		// Save TLD.
		const saveBtn = document.getElementById('hf-save-tld');
		if (saveBtn) {
			saveBtn.addEventListener('click', function () {
				ajaxRequest('hf_save_tld_pricing', {
					id: document.getElementById('hf-tld-id').value,
					tld: document.getElementById('hf-tld-name').value,
					register_price: document.getElementById('hf-tld-register').value,
					renew_price: document.getElementById('hf-tld-renew').value,
					transfer_price: document.getElementById('hf-tld-transfer').value,
					currency: document.getElementById('hf-tld-currency').value,
					is_active: document.getElementById('hf-tld-active').checked ? 1 : 0,
				}, () => location.reload());
			});
		}

		// Delete TLD.
		document.querySelectorAll('.hf-delete-tld').forEach((btn) => {
			btn.addEventListener('click', function () {
				if (!confirm(config.i18n.confirmDelete)) return;

				ajaxRequest('hf_delete_tld_pricing', {
					id: this.dataset.id,
				}, () => this.closest('tr').remove());
			});
		});

		// Import TLDs.
		const importBtn = document.getElementById('hf-import-tld-pricing');
		if (importBtn) {
			importBtn.addEventListener('click', function () {
				this.disabled = true;
				this.textContent = config.i18n.importing;

				ajaxRequest(
					'hf_import_tld_pricing',
					{},
					(data) => {
						importBtn.textContent = config.i18n.imported;
						setTimeout(() => location.reload(), 1000);
					},
					(msg) => {
						showNotice(msg, 'error');
						importBtn.disabled = false;
						importBtn.textContent = 'Import Common TLDs';
					}
				);
			});
		}
	}

	/**
	 * Initialize registrar settings handlers.
	 */
	function initRegistrarSettings() {
		// Save settings.
		const saveBtn = document.getElementById('hf-save-registrar-settings');
		if (saveBtn) {
			saveBtn.addEventListener('click', function () {
				const form = document.getElementById('hf-registrar-settings-form');
				const domainForm = document.getElementById('hf-domain-settings-form');

				const data = {
					active_registrar: 'namecheap',
					api_user: form.querySelector('#hf-api-user').value,
					api_key: form.querySelector('#hf-api-key').value,
					username: form.querySelector('#hf-username').value,
					client_ip: form.querySelector('#hf-client-ip').value,
					sandbox: form.querySelector('#hf-sandbox').checked ? 'yes' : 'no',
				};

				if (domainForm) {
					data.auto_register = domainForm.querySelector('#hf-auto-register').checked ? 'yes' : 'no';
					data.auto_renew_days = domainForm.querySelector('#hf-renew-days').value;
					data.expiry_reminder_days = domainForm.querySelector('#hf-reminder-days').value;
					data.default_nameservers = domainForm.querySelector('#hf-default-ns').value;
				}

				this.disabled = true;
				this.textContent = config.i18n.saving;

				ajaxRequest(
					'hf_save_registrar_settings',
					data,
					(result) => {
						showNotice(result.message, 'success', document.getElementById('hf-registrar-result'));
						saveBtn.disabled = false;
						saveBtn.textContent = config.i18n.saved;
					},
					(msg) => {
						showNotice(msg, 'error', document.getElementById('hf-registrar-result'));
						saveBtn.disabled = false;
						saveBtn.textContent = 'Save Settings';
					}
				);
			});
		}

		// Test connection.
		const testBtn = document.getElementById('hf-test-registrar');
		if (testBtn) {
			testBtn.addEventListener('click', function () {
				this.disabled = true;
				this.textContent = config.i18n.testing;

				ajaxRequest(
					'hf_test_registrar',
					{},
					(result) => {
						showNotice(result.message, 'success', document.getElementById('hf-registrar-result'));
						testBtn.disabled = false;
						testBtn.textContent = 'Test Connection';
					},
					(msg) => {
						showNotice(msg, 'error', document.getElementById('hf-registrar-result'));
						testBtn.disabled = false;
						testBtn.textContent = 'Test Connection';
					}
				);
			});
		}
	}

	// Initialize on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initDomainDetail();
		initDnsHandlers();
		initTldPricing();
		initRegistrarSettings();
	});
})();
