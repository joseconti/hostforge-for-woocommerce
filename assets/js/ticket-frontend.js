/**
 * Ticket Frontend JavaScript.
 *
 * Handles ticket creation, replies, cancellation, and KB interactions.
 *
 * @package HostForge
 */
(function () {
	'use strict';

	const config = window.hostforgeTicketFrontend || {};
	const i18n = config.i18n || {};

	/**
	 * Show notice.
	 */
	function showNotice(container, message, type) {
		if (!container) return;

		let notice = container.querySelector('.hf-notice');
		if (!notice) {
			notice = document.createElement('div');
			container.insertBefore(notice, container.firstChild);
		}

		notice.className = 'hf-notice hf-notice--' + type;
		notice.textContent = message;
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
				const wrap = document.querySelector('.woocommerce-MyAccount-content');
				showNotice(wrap, i18n.error || 'An error occurred.', 'error');
			});
	}

	/**
	 * New ticket form.
	 */
	function initNewTicketForm() {
		const form = document.getElementById('hf-new-ticket-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const btn = form.querySelector('.hf-submit-btn');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.submitting || 'Submitting...';
			}

			const data = new FormData(form);

			ajaxPost('hf_frontend_new_ticket', data, function (res) {
				if (res.success && res.data.redirect) {
					window.location.href = res.data.redirect;
				} else {
					showNotice(form, res.data.message || i18n.error, 'error');
					if (btn) {
						btn.disabled = false;
						btn.textContent = i18n.submit || 'Submit Ticket';
					}
				}
			});
		});

		// KB suggestions as user types subject.
		const subjectInput = document.getElementById('hf-ticket-subject');
		const suggestionsDiv = document.getElementById('hf-kb-suggestions');
		let searchTimeout = null;

		if (subjectInput && suggestionsDiv) {
			subjectInput.addEventListener('input', function () {
				clearTimeout(searchTimeout);
				const query = this.value.trim();

				if (query.length < 3) {
					suggestionsDiv.style.display = 'none';
					return;
				}

				searchTimeout = setTimeout(function () {
					const searchData = new FormData();
					searchData.append('action', 'hf_kb_search');
					searchData.append('nonce', config.kbSearchNonce || config.nonce);
					searchData.append('keyword', query);

					fetch(config.ajaxUrl, { method: 'POST', body: searchData })
						.then(function (res) { return res.json(); })
						.then(function (res) {
							if (res.success && res.data.results && res.data.results.length > 0) {
								var html = '<h4>' + (i18n.kbSuggestions || 'Related articles that might help:') + '</h4><ul>';
								res.data.results.forEach(function (article) {
									html += '<li><a href="' + article.url + '" target="_blank">' + article.title + '</a>';
									if (article.excerpt) {
										html += '<br><small>' + article.excerpt + '</small>';
									}
									html += '</li>';
								});
								html += '</ul>';
								suggestionsDiv.innerHTML = html;
								suggestionsDiv.style.display = 'block';
							} else {
								suggestionsDiv.style.display = 'none';
							}
						})
						.catch(function () {
							suggestionsDiv.style.display = 'none';
						});
				}, 500);
			});
		}
	}

	/**
	 * Reply form on ticket detail.
	 */
	function initReplyForm() {
		const form = document.getElementById('hf-frontend-reply-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const textarea = form.querySelector('textarea[name="reply_content"]');
			const content = textarea ? textarea.value.trim() : '';
			if (!content) return;

			const btn = form.querySelector('.hf-submit-btn');
			if (btn) {
				btn.disabled = true;
				btn.textContent = i18n.sending || 'Sending...';
			}

			const data = new FormData(form);

			ajaxPost('hf_frontend_ticket_reply', data, function (res) {
				if (res.success) {
					// Reload to show new reply.
					location.reload();
				} else {
					showNotice(form, res.data.message || i18n.error, 'error');
					if (btn) {
						btn.disabled = false;
						btn.textContent = i18n.sendReply || 'Send Reply';
					}
				}
			});
		});
	}

	/**
	 * Close ticket button.
	 */
	function initCloseTicket() {
		const btn = document.querySelector('.hf-close-ticket-btn');
		if (!btn) return;

		btn.addEventListener('click', function (e) {
			e.preventDefault();

			if (!confirm(i18n.confirmClose || 'Are you sure you want to close this ticket?')) {
				return;
			}

			this.disabled = true;
			this.textContent = i18n.closing || 'Closing...';

			ajaxPost('hf_frontend_cancel_ticket', {
				ticket_id: this.dataset.ticketId,
			}, function (res) {
				if (res.success) {
					location.reload();
				} else {
					showNotice(
						document.querySelector('.woocommerce-MyAccount-content'),
						res.data.message || i18n.error,
						'error'
					);
					btn.disabled = false;
					btn.textContent = i18n.closeTicket || 'Close Ticket';
				}
			});
		});
	}

	/**
	 * KB voting.
	 */
	function initKBVoting() {
		document.querySelectorAll('.hf-kb-vote-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				const articleId = this.dataset.articleId;
				const vote = this.dataset.vote;
				const nonce = this.dataset.nonce || config.nonce;

				this.disabled = true;

				const data = new FormData();
				data.append('action', 'hf_kb_vote');
				data.append('nonce', nonce);
				data.append('article_id', articleId);
				data.append('vote', vote);

				fetch(config.ajaxUrl, { method: 'POST', body: data })
					.then(function (res) { return res.json(); })
					.then(function (res) {
						if (res.success) {
							// Update counts.
							var yesCount = document.querySelector('.hf-kb-vote-yes-count');
							var noCount = document.querySelector('.hf-kb-vote-no-count');
							if (yesCount && res.data.yes !== undefined) {
								yesCount.textContent = res.data.yes;
							}
							if (noCount && res.data.no !== undefined) {
								noCount.textContent = res.data.no;
							}

							// Disable both buttons.
							document.querySelectorAll('.hf-kb-vote-btn').forEach(function (b) {
								b.disabled = true;
							});

							var votingSection = document.querySelector('.hf-kb-voting');
							if (votingSection) {
								showNotice(votingSection, i18n.thankYou || 'Thank you for your feedback!', 'success');
							}
						}
					})
					.catch(function () {
						btn.disabled = false;
					});
			});
		});
	}

	// Init on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		initNewTicketForm();
		initReplyForm();
		initCloseTicket();
		initKBVoting();
	});
})();
