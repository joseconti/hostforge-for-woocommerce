/**
 * Domain Manager Frontend JavaScript.
 *
 * Handles domain search in checkout and domain management
 * in My Account (nameservers, DNS, auto-renew, EPP).
 *
 * @package HostForge
 */

(function () {
	'use strict';

	// Config from either checkout or My Account localization.
	const checkoutConfig = window.hfDomainCheckout || {};
	const frontendConfig = window.hfDomainFrontend || {};

	/**
	 * Make an AJAX request.
	 */
	function ajaxRequest(action, data, nonce, onSuccess, onError) {
		const ajaxUrl = checkoutConfig.ajaxUrl || frontendConfig.ajaxUrl;
		const formData = new FormData();
		formData.append('action', action);
		formData.append('nonce', nonce);

		for (const key in data) {
			if (Array.isArray(data[key])) {
				data[key].forEach((val) => formData.append(key + '[]', val));
			} else {
				formData.append(key, data[key]);
			}
		}

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then((res) => res.json())
			.then((result) => {
				if (result.success) {
					onSuccess(result.data);
				} else {
					const msg = result.data?.message || 'An error occurred.';
					if (onError) onError(msg);
				}
			})
			.catch(() => {
				if (onError) onError('An error occurred.');
			});
	}

	/**
	 * Initialize checkout domain search.
	 */
	function initCheckoutDomainSearch() {
		const container = document.getElementById('hf-domain-checkout');
		if (!container) return;

		const i18n = checkoutConfig.i18n || {};

		// Toggle sections based on radio selection.
		const radios = container.querySelectorAll('input[name="hf_domain_action"]');
		radios.forEach((radio) => {
			radio.addEventListener('change', function () {
				document.getElementById('hf-domain-register-section').style.display = 'none';
				document.getElementById('hf-domain-transfer-section').style.display = 'none';
				document.getElementById('hf-domain-existing-section').style.display = 'none';

				switch (this.value) {
					case 'register':
						document.getElementById('hf-domain-register-section').style.display = 'block';
						break;
					case 'transfer':
						document.getElementById('hf-domain-transfer-section').style.display = 'block';
						break;
					case 'existing':
						document.getElementById('hf-domain-existing-section').style.display = 'block';
						break;
				}
			});
		});

		// Domain search.
		const searchBtn = document.getElementById('hf-domain-search-btn');
		if (searchBtn) {
			searchBtn.addEventListener('click', function () {
				const keyword = document.getElementById('hf_domain_keyword').value.trim();
				if (!keyword) return;

				const resultsDiv = document.getElementById('hf-domain-search-results');
				resultsDiv.style.display = 'block';
				resultsDiv.innerHTML = '<p>' + (i18n.searching || 'Searching...') + '</p>';

				ajaxRequest(
					'hf_domain_search',
					{ keyword: keyword },
					checkoutConfig.nonce,
					function (data) {
						renderSearchResults(data.results, resultsDiv);
					},
					function (msg) {
						resultsDiv.innerHTML = '<p class="hf-domain-error">' + msg + '</p>';
					}
				);
			});

			// Allow Enter key in search field.
			const keywordInput = document.getElementById('hf_domain_keyword');
			if (keywordInput) {
				keywordInput.addEventListener('keypress', function (e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						searchBtn.click();
					}
				});
			}
		}
	}

	/**
	 * Render domain search results.
	 */
	function renderSearchResults(results, container) {
		const i18n = checkoutConfig.i18n || {};

		if (!results || !results.length) {
			container.innerHTML = '<p>' + (i18n.noResults || 'No results found.') + '</p>';
			return;
		}

		let html = '';
		results.forEach(function (result) {
			const availClass = result.available ? 'hf-domain-result--available' : 'hf-domain-result--unavailable';
			const statusText = result.available
				? (result.premium ? i18n.premium : i18n.available)
				: i18n.unavailable;
			const statusClass = result.available ? 'hf-badge--success' : 'hf-badge--muted';
			const price = result.available && result.register_price
				? parseFloat(result.register_price).toFixed(2) + ' ' + (result.currency || 'USD') + (i18n.perYear || '/year')
				: '';

			html += '<div class="hf-domain-result ' + availClass + '" data-domain="' + result.domain + '">';
			html += '<span class="hf-domain-result__name">' + result.domain + '</span>';
			html += '<span class="hf-domain-result__price">' + price + '</span>';
			html += '<span class="hf-domain-result__status hf-badge ' + statusClass + '">' + statusText + '</span>';

			if (result.available) {
				html += '<button type="button" class="button hf-select-domain">' + (i18n.select || 'Select') + '</button>';
			}

			html += '</div>';
		});

		container.innerHTML = html;

		// Bind select buttons.
		container.querySelectorAll('.hf-select-domain').forEach(function (btn) {
			btn.addEventListener('click', function () {
				// Deselect previous.
				container.querySelectorAll('.hf-domain-result--selected').forEach(function (el) {
					el.classList.remove('hf-domain-result--selected');
					const selBtn = el.querySelector('.hf-select-domain');
					if (selBtn) selBtn.textContent = i18n.select || 'Select';
				});

				// Select this one.
				const row = this.closest('.hf-domain-result');
				row.classList.add('hf-domain-result--selected');
				this.textContent = i18n.selected || 'Selected';

				// Update hidden field.
				document.getElementById('hf_domain_name').value = row.dataset.domain;
			});
		});
	}

	/**
	 * Initialize My Account domain management handlers.
	 */
	function initMyAccountDomains() {
		if (!frontendConfig.nonce) return;

		const i18n = frontendConfig.i18n || {};

		// Auto-renew toggle.
		document.querySelectorAll('.hf-domain-auto-renew').forEach(function (toggle) {
			toggle.addEventListener('change', function () {
				ajaxRequest(
					'hf_frontend_toggle_domain_auto_renew',
					{
						domain_id: this.dataset.domainId,
						auto_renew: this.checked ? 'yes' : 'no',
					},
					frontendConfig.nonce,
					function () {},
					function (msg) { alert(msg); }
				);
			});
		});

		// Save nameservers.
		document.querySelectorAll('.hf-save-nameservers').forEach(function (btn) {
			btn.addEventListener('click', function () {
				const form = this.closest('.hf-nameservers-form');
				const domainId = form.dataset.domainId;
				const inputs = form.querySelectorAll('input[name="nameservers[]"]');
				const nameservers = [];

				inputs.forEach(function (input) {
					if (input.value.trim()) nameservers.push(input.value.trim());
				});

				btn.disabled = true;
				btn.textContent = i18n.saving || 'Saving...';

				ajaxRequest(
					'hf_frontend_save_nameservers',
					{ domain_id: domainId, nameservers: nameservers },
					frontendConfig.nonce,
					function (data) {
						btn.disabled = false;
						btn.textContent = i18n.saved || 'Saved';
						showFrontendNotice(form, data.message, 'success');
					},
					function (msg) {
						btn.disabled = false;
						btn.textContent = 'Save Nameservers';
						showFrontendNotice(form, msg, 'error');
					}
				);
			});
		});

		// Add DNS record.
		document.querySelectorAll('.hf-frontend-add-dns').forEach(function (btn) {
			btn.addEventListener('click', function () {
				const form = this.closest('.hf-frontend-dns-form');

				ajaxRequest(
					'hf_frontend_save_dns_record',
					{
						domain_id: form.dataset.domainId,
						record_type: form.querySelector('.hf-dns-type').value,
						host: form.querySelector('.hf-dns-host').value || '@',
						value: form.querySelector('.hf-dns-value-input').value,
						ttl: form.querySelector('.hf-dns-ttl').value || 3600,
					},
					frontendConfig.nonce,
					function () { location.reload(); },
					function (msg) { alert(msg); }
				);
			});
		});

		// Delete DNS record.
		document.querySelectorAll('.hf-frontend-delete-dns').forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (!confirm(i18n.confirmDelete || 'Delete this record?')) return;

				const row = this.closest('tr');

				ajaxRequest(
					'hf_frontend_delete_dns_record',
					{
						domain_id: this.dataset.domainId,
						record_id: this.dataset.recordId,
					},
					frontendConfig.nonce,
					function () { row.remove(); },
					function (msg) { alert(msg); }
				);
			});
		});

		// Request EPP code.
		document.querySelectorAll('.hf-request-epp').forEach(function (btn) {
			btn.addEventListener('click', function () {
				const resultDiv = this.nextElementSibling;

				ajaxRequest(
					'hf_frontend_request_epp_code',
					{ domain_id: this.dataset.domainId },
					frontendConfig.nonce,
					function (data) {
						resultDiv.style.display = 'block';
						resultDiv.textContent = data.message;
					},
					function (msg) {
						resultDiv.style.display = 'block';
						resultDiv.textContent = msg;
					}
				);
			});
		});
	}

	/**
	 * Show a notice in a form.
	 */
	function showFrontendNotice(container, message, type) {
		let notice = container.querySelector('.hf-frontend-notice');
		if (!notice) {
			notice = document.createElement('div');
			notice.className = 'hf-frontend-notice';
			container.appendChild(notice);
		}

		notice.style.display = 'block';
		notice.className = 'hf-frontend-notice hf-frontend-notice--' + type;
		notice.textContent = message;

		if ('success' === type) {
			setTimeout(function () {
				notice.style.display = 'none';
			}, 5000);
		}
	}

	// Initialize on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initCheckoutDomainSearch();
		initMyAccountDomains();
	});
})();
