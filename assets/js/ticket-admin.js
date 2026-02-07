/**
 * Ticket Admin JavaScript.
 *
 * Handles ticket management, replies, departments, canned responses, and KB.
 *
 * @package HostForge
 */
(function () {
	'use strict';

	const config = window.hostforgeTicket || {};
	const i18n = config.i18n || {};

	/**
	 * Show notice.
	 */
	function showNotice(message, type) {
		let notice = document.querySelector('.hf-admin-notice');
		if (!notice) {
			notice = document.createElement('div');
			notice.className = 'hf-admin-notice';
			const wrap = document.querySelector('.wrap');
			if (wrap) {
				wrap.insertBefore(notice, wrap.querySelector('hr.wp-header-end') || wrap.firstChild.nextSibling);
			}
		}
		notice.className = 'notice notice-' + type + ' is-dismissible';
		notice.innerHTML = '<p>' + message + '</p>';
		notice.style.display = 'block';
		notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	/**
	 * AJAX helper.
	 */
	function ajaxPost(action, data, callback) {
		const formData = new FormData();
		formData.append('action', action);
		formData.append('nonce', config.nonce);

		if (data instanceof FormData) {
			for (const [key, value] of data.entries()) {
				formData.append(key, value);
			}
		} else {
			Object.keys(data).forEach(function (key) {
				formData.append(key, data[key]);
			});
		}

		fetch(config.ajaxUrl, { method: 'POST', body: formData })
			.then(function (res) { return res.json(); })
			.then(callback)
			.catch(function () {
				showNotice(i18n.error || 'An error occurred.', 'error');
			});
	}

	/**
	 * Ticket reply form.
	 */
	function initReplyForm() {
		const form = document.getElementById('hf-reply-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const textarea = form.querySelector('textarea[name="reply_content"]');
			const content = textarea ? textarea.value.trim() : '';
			if (!content) return;

			const btn = form.querySelector('.hf-reply-submit');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.sending || 'Sending...';
			}

			const data = new FormData(form);

			ajaxPost('hf_ticket_reply', data, function (res) {
				if (res.success) {
					// Append reply to thread.
					const thread = document.getElementById('hf-replies-thread');
					if (thread && res.data.reply_html) {
						thread.insertAdjacentHTML('beforeend', res.data.reply_html);
					}
					if (textarea) textarea.value = '';
					showNotice(i18n.replySent || 'Reply sent.', 'success');
				} else {
					showNotice(res.data.message || i18n.error, 'error');
				}

				if (btn) {
					btn.disabled = false;
					btn.textContent = i18n.sendReply || 'Send Reply';
				}
			});
		});

		// Canned response insertion.
		const cannedSelect = form.querySelector('.hf-canned-select');
		if (cannedSelect) {
			cannedSelect.addEventListener('change', function () {
				const selected = this.options[this.selectedIndex];
				const content = selected ? selected.dataset.content : '';
				const textarea = form.querySelector('textarea[name="reply_content"]');
				if (textarea && content) {
					textarea.value = content;
				}
				this.selectedIndex = 0;
			});
		}
	}

	/**
	 * Status update.
	 */
	function initStatusUpdate() {
		const btn = document.querySelector('.hf-update-status-btn');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const select = document.querySelector('select[name="ticket_status"]');
			if (!select) return;

			this.disabled = true;
			ajaxPost('hf_ticket_update_status', {
				ticket_id: this.dataset.ticketId,
				status: select.value,
			}, function (res) {
				if (res.success) {
					showNotice(i18n.statusUpdated || 'Status updated.', 'success');
					setTimeout(function () { location.reload(); }, 1000);
				} else {
					showNotice(res.data.message || i18n.error, 'error');
					btn.disabled = false;
				}
			});
		});
	}

	/**
	 * Priority update.
	 */
	function initPriorityUpdate() {
		const btn = document.querySelector('.hf-update-priority-btn');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const select = document.querySelector('select[name="ticket_priority"]');
			if (!select) return;

			this.disabled = true;
			ajaxPost('hf_ticket_update_status', {
				ticket_id: this.dataset.ticketId,
				priority: select.value,
			}, function (res) {
				if (res.success) {
					showNotice(i18n.updated || 'Updated.', 'success');
					setTimeout(function () { location.reload(); }, 1000);
				} else {
					showNotice(res.data.message || i18n.error, 'error');
					btn.disabled = false;
				}
			});
		});
	}

	/**
	 * Assign ticket.
	 */
	function initAssign() {
		const btn = document.querySelector('.hf-assign-btn');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const select = document.querySelector('select[name="assigned_to"]');
			if (!select) return;

			this.disabled = true;
			ajaxPost('hf_ticket_assign', {
				ticket_id: this.dataset.ticketId,
				assigned_to: select.value,
			}, function (res) {
				if (res.success) {
					showNotice(i18n.assigned || 'Ticket assigned.', 'success');
				} else {
					showNotice(res.data.message || i18n.error, 'error');
				}
				btn.disabled = false;
			});
		});
	}

	/**
	 * New ticket form.
	 */
	function initNewTicket() {
		const form = document.getElementById('hf-new-ticket-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const btn = form.querySelector('.hf-submit-btn');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.creating || 'Creating...';
			}

			const data = new FormData(form);

			ajaxPost('hf_save_ticket', data, function (res) {
				if (res.success && res.data.redirect) {
					window.location.href = res.data.redirect;
				} else {
					showNotice(res.data.message || i18n.error, 'error');
					if (btn) {
						btn.disabled = false;
						btn.textContent = i18n.createTicket || 'Create Ticket';
					}
				}
			});
		});
	}

	/**
	 * Department management.
	 */
	function initDepartments() {
		const form = document.getElementById('hf-department-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const data = new FormData(form);
			ajaxPost('hf_save_department', data, function (res) {
				if (res.success) {
					showNotice(i18n.saved || 'Saved.', 'success');
					setTimeout(function () { location.reload(); }, 1000);
				} else {
					showNotice(res.data.message || i18n.error, 'error');
				}
			});
		});

		// Edit buttons.
		document.querySelectorAll('.hf-edit-department').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				form.querySelector('[name="department_id"]').value = this.dataset.id;
				form.querySelector('[name="name"]').value = this.dataset.name;
				var desc = form.querySelector('[name="description"]');
				if (desc) desc.value = this.dataset.description || '';
			});
		});

		// Delete buttons.
		document.querySelectorAll('.hf-delete-department').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!confirm(i18n.confirmDelete || 'Are you sure?')) return;

				ajaxPost('hf_delete_department', {
					department_id: this.dataset.id,
				}, function (res) {
					if (res.success) {
						showNotice(i18n.deleted || 'Deleted.', 'success');
						setTimeout(function () { location.reload(); }, 1000);
					} else {
						showNotice(res.data.message || i18n.error, 'error');
					}
				});
			});
		});
	}

	/**
	 * Canned responses management.
	 */
	function initCannedResponses() {
		const form = document.getElementById('hf-canned-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const data = new FormData(form);
			ajaxPost('hf_save_canned_response', data, function (res) {
				if (res.success) {
					showNotice(i18n.saved || 'Saved.', 'success');
					setTimeout(function () { location.reload(); }, 1000);
				} else {
					showNotice(res.data.message || i18n.error, 'error');
				}
			});
		});

		// Edit buttons.
		document.querySelectorAll('.hf-edit-canned').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				form.querySelector('[name="response_id"]').value = this.dataset.id;
				form.querySelector('[name="title"]').value = this.dataset.title;
				form.querySelector('[name="content"]').value = this.dataset.content;
			});
		});

		// Delete buttons.
		document.querySelectorAll('.hf-delete-canned').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!confirm(i18n.confirmDelete || 'Are you sure?')) return;

				ajaxPost('hf_delete_canned_response', {
					response_id: this.dataset.id,
				}, function (res) {
					if (res.success) {
						showNotice(i18n.deleted || 'Deleted.', 'success');
						setTimeout(function () { location.reload(); }, 1000);
					} else {
						showNotice(res.data.message || i18n.error, 'error');
					}
				});
			});
		});

		// Insert merge tag on click.
		document.querySelectorAll('.hf-merge-tag').forEach(function (tag) {
			tag.addEventListener('click', function () {
				const textarea = form.querySelector('[name="content"]');
				if (textarea) {
					const pos = textarea.selectionStart || textarea.value.length;
					const text = this.textContent;
					textarea.value = textarea.value.substring(0, pos) + text + textarea.value.substring(pos);
					textarea.focus();
					textarea.selectionStart = textarea.selectionEnd = pos + text.length;
				}
			});
		});
	}

	/**
	 * KB article management.
	 */
	function initKBArticle() {
		const form = document.getElementById('hf-kb-article-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const btn = form.querySelector('.hf-submit-btn');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.saving || 'Saving...';
			}

			const data = new FormData(form);

			ajaxPost('hf_save_kb_article', data, function (res) {
				if (res.success) {
					showNotice(i18n.saved || 'Saved.', 'success');
					if (res.data.redirect) {
						setTimeout(function () {
							window.location.href = res.data.redirect;
						}, 1000);
					}
				} else {
					showNotice(res.data.message || i18n.error, 'error');
				}
				if (btn) {
					btn.disabled = false;
					btn.textContent = i18n.save || 'Save';
				}
			});
		});

		// Delete button.
		const deleteBtn = document.querySelector('.hf-delete-article');
		if (deleteBtn) {
			deleteBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!confirm(i18n.confirmDelete || 'Are you sure?')) return;

				ajaxPost('hf_delete_kb_article', {
					article_id: this.dataset.id,
				}, function (res) {
					if (res.success && res.data.redirect) {
						window.location.href = res.data.redirect;
					} else {
						showNotice(res.data.message || i18n.error, 'error');
					}
				});
			});
		}
	}

	/**
	 * KB list delete buttons.
	 */
	function initKBList() {
		document.querySelectorAll('.hf-delete-kb-article').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!confirm(i18n.confirmDelete || 'Are you sure?')) return;

				ajaxPost('hf_delete_kb_article', {
					article_id: this.dataset.id,
				}, function (res) {
					if (res.success) {
						showNotice(i18n.deleted || 'Deleted.', 'success');
						setTimeout(function () { location.reload(); }, 1000);
					} else {
						showNotice(res.data.message || i18n.error, 'error');
					}
				});
			});
		});
	}

	/**
	 * Flag toggle.
	 */
	function initFlagToggle() {
		const btn = document.querySelector('.hf-flag-btn');
		if (!btn) return;

		btn.addEventListener('click', function () {
			const ticketId = this.dataset.ticketId;
			const flagged = this.classList.contains('hf-flag-btn--active') ? '0' : '1';

			ajaxPost('hf_ticket_update_status', {
				ticket_id: ticketId,
				flagged: flagged,
			}, function (res) {
				if (res.success) {
					btn.classList.toggle('hf-flag-btn--active');
				}
			});
		});
	}

	// Init on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initReplyForm();
		initStatusUpdate();
		initPriorityUpdate();
		initAssign();
		initNewTicket();
		initDepartments();
		initCannedResponses();
		initKBArticle();
		initKBList();
		initFlagToggle();
	});
})();
