/**
 * HostForge Admin Scripts
 *
 * @package HostForge
 */

(function () {
	'use strict';

	/**
	 * Initialize module toggles.
	 */
	function initModuleToggles() {
		const toggles = document.querySelectorAll('.hf-module-toggle');

		toggles.forEach(function (toggle) {
			toggle.addEventListener('change', function () {
				const moduleId = this.dataset.moduleId;
				const activate = this.checked;
				const card = this.closest('.hostforge-module-card');
				const badge = card ? card.querySelector('.hf-badge') : null;

				// Confirm deactivation.
				if (!activate && hostforgeAdmin.i18n.confirmDeactivate) {
					if (!confirm(hostforgeAdmin.i18n.confirmDeactivate)) {
						this.checked = true;
						return;
					}
				}

				// Send AJAX request.
				const formData = new FormData();
				formData.append('action', 'hf_toggle_module');
				formData.append('nonce', hostforgeAdmin.nonce);
				formData.append('module_id', moduleId);
				formData.append('activate', activate ? 'true' : 'false');

				fetch(hostforgeAdmin.ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				})
					.then(function (response) {
						return response.json();
					})
					.then(function (data) {
						if (data.success) {
							if (card) {
								card.classList.toggle('is-active', activate);
							}
							if (badge) {
								badge.textContent = activate ? 'Active' : 'Inactive';
								badge.className = 'hf-badge ' + (activate ? 'hf-badge--success' : 'hf-badge--inactive');
							}
						} else {
							// Revert toggle.
							toggle.checked = !activate;
							alert(data.data && data.data.message ? data.data.message : hostforgeAdmin.i18n.error);
						}
					})
					.catch(function () {
						// Revert toggle on error.
						toggle.checked = !activate;
						alert(hostforgeAdmin.i18n.error);
					});
			});
		});
	}

	/**
	 * Initialize log context toggles.
	 */
	function initLogContextToggles() {
		const buttons = document.querySelectorAll('.hf-toggle-context');

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				const targetId = this.dataset.target;
				const target = document.getElementById(targetId);

				if (target) {
					const isHidden = target.style.display === 'none';
					target.style.display = isHidden ? 'block' : 'none';
					this.textContent = isHidden ? 'Hide context' : 'Show context';
				}
			});
		});
	}

	/**
	 * Initialize on DOM ready.
	 */
	document.addEventListener('DOMContentLoaded', function () {
		initModuleToggles();
		initLogContextToggles();
	});
})();
