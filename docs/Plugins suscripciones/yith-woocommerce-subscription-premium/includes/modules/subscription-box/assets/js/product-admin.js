/**
 * Product subscription box admin handler
 *
 * @since 4.0.0
 * @package YITH WooCommerce Subscriptions
 */

jQuery(function ($) {

	const SubscriptionBoxHandler = function () {

		this.stepsWrapper = $('.ywsbs-box-steps');
		this.stepAddTrigger = $('.ywsbs-box-step-add');
		this.stepTemplate = wp.template('ywsbs-box-step');
		// Textarea tmce editor modal.
		this.editorId = 'ywsbs_box_steps_text_editor';
		this.editorTemplate = wp.template('ywsbs-box-editor-field');
		this.editorModal = null;

		this.init = () => {

			$(document.body).on('woocommerce-product-type-change', this.panelVisibility);
			$(document.body).on('change', 'select[name="_ywsbs_box_discount_type"]', this.toggleDiscountType);

			this.panelVisibility();

			// Handle box steps.
			if ( this.stepsWrapper.length ) {
				this.stepAddTrigger.on('click', this.addStep);
				$(document.body).on('click', '.delete-step', this.stepDelete);
				$(document.body).on('ywsbs_box_step_added ywsbs_box_step_deleted', this.updateStepContentOption);
				$(document.body).on('change', '#_ywsbs_box_steps_content_1', {handler: this}, this.addStepVisibilityToggle);
				$(document.body).on('click', '.edit-step', this.stepVisibilityToggle);
				// Special editor textarea field.
				$(document.body).on('click', '.ywsbs_box_steps_text_editor_trigger', {handler: this}, this.openTextareaEditor);
				$(document.body).on('click', '.save-step-text', {handler: this}, this.saveTextareaEditor);
				// Init steps.
				this.initSteps();
			}
		}

		this.panelVisibility = () => {

			let type = $('select[name="product-type"]').val(),
				subInput = $('input[name="_ywsbs_subscription"]'),
				trialCheck = $('input[name="_ywsbs_enable_trial"]');

			if ( 'ywsbs-subscription-box' === type ) {
				subInput.prop('checked', true).change();
				// Hide trial options
				trialCheck.removeAttr('checked').change()
				trialCheck.closest('.ywsbs-product-metabox-field').hide();
			} else {
				trialCheck.closest('.ywsbs-product-metabox-field').show();
			}
		}

		this.toggleDiscountType = function (ev) {
			let typeSelect = $(this),
				discountInput = typeSelect.siblings('input[name="_ywsbs_box_discount_value"]');

			if ( 'percentage' === typeSelect.val() ) {
				discountInput
					.removeClass('wc_input_price')
					.attr({type: 'number', min: '0', max: '100', step: '1'});
			} else {
				discountInput
					.addClass('wc_input_price')
					.attr('type', 'text')
					.removeAttr('min').removeClass('max').removeAttr('step');
			}
		}

		// STEPS METHODS
		this.initSteps = () => {
			let steps = this.stepsWrapper.data('steps');
			if ( _.isEmpty(steps) ) {
				// Add an empty step and return
				this.addStep();
				return false;
			}

			for (const [id, step] of Object.entries(steps)) {
				step.id = id;
				typeof step === 'object' && this.addStep(step);
			}
		}

		this.addStep = (step = {}) => {
			// Set the index.
			step.index = this.getStepIndex();
			step.id = step.id || step.index;
			this.stepAddTrigger.before(this.stepTemplate(step));

			this.initStepFields(step.id);

			$(document.body).trigger('ywsbs_box_step_added');
		}

		this.getStepIndex = () => {
			return this.stepsWrapper.children('.ywsbs-box-step').length + 1; // always start with 1
		}

		this.initStepFields = (id) => {
			const inputs = $(`.ywsbs-box-step[data-id="${id}"] :input`);

			for (input of inputs) {
				let value = $(input).data('value');
				if ( value ) {
					if ( 'checkbox' === $(input).attr('type') ) {
						'yes' === value ? $(input).val('yes').attr('checked', 'checked') : $(input).removeAttr('checked');
					} else if ( $(input).hasClass('yith-post-search') || $(input).hasClass('yith-term-search') ) {
						for (const [key, label] of Object.entries(value)) {
							$(input).append(new Option(label, key, true, true));
						}
					} else {
						$(input).val(value);
						'hidden' === input.type && this.updateStepTextPreview(input.id, value);
					}
				}

				$(input).removeAttr('data-value').change();
			}

			jQuery(document)
				.trigger('yith_fields_init')
				.trigger('yith-plugin-fw-tips-init');
		}

		this.updateStepTextPreview = (target, content) => {
			const iframe = $(`#${target}_preview`);
			if ( !iframe.length ) {
				return false;
			}

			if ( !iframe.hasClass('initialized') ) {
				iframe.contents().find('head').append('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
				const iframeCss = tinyMCEPreInit.mceInit[this.editorId].content_css.split(',');
				for (const css of iframeCss) {
					iframe.contents().find('head').append('<link rel="stylesheet" type="text/css" href="' + css + '">');
				}

				iframe.addClass('initialized');
			}

			iframe.contents().find('body').html(content);
		}

		this.openTextareaEditor = function (ev) {
			ev.preventDefault();

			const handler = ev.data.handler;
			const fieldInput = $(this).find('input');
			// Get label and description.
			const title = fieldInput.closest('fieldset').find('legend').text();
			const description = fieldInput.closest('fieldset').find('.description').text();

			handler.editorModal = yith.ui.modal({
				width: 600,
				title: title + '<p class="description">' + description + '</p>',
				content: handler.editorTemplate({}),
				footer: '<button class="yith-plugin-fw__button--primary yith-plugin-fw__button--xl save-step-text" data-target="' + fieldInput.attr('id') + '">' + ywsbsProductAdmin.buttonLabel + '</button>',
				classes: {
					wrap: 'ywsbs-box-step-text-editor'
				},
				allowWpMenu: false,
				onCreate: function () {
					if ( typeof tinyMCE == 'undefined' || typeof tinyMCEPreInit == 'undefined' ) {
						return;
					}

					// Set value.
					$('#ywsbs_box_steps_text_editor').val(fieldInput.val() ?? '').change();

					const mce = tinyMCEPreInit.mceInit[handler.editorId];
					tinyMCE.init(mce);
					tinyMCE.execCommand('mceRemoveEditor', true, handler.editorId);
					tinyMCE.execCommand('mceAddEditor', true, handler.editorId);

					const qt = tinyMCEPreInit.qtInit[handler.editorId];
					quicktags(qt);
					QTags._buttonsInit();

					// fix display issue.
					$('#ywsbs_box_steps_text_editor-html').click();
					$('#ywsbs_box_steps_text_editor-tmce').click();
				},
				onClose: function () {
					handler.editorModal = null;
				}
			})
		}

		this.saveTextareaEditor = function (ev) {
			ev.preventDefault();

			const handler = ev.data.handler;
			if ( null === handler.editorModal ) {
				return false;
			}

			const target = $(this).data('target');
			// switch to html to get the correct value.
			$('#ywsbs_box_steps_text_editor-html').click();
			const value = $('textarea#ywsbs_box_steps_text_editor').val();
			// set hidden input.
			$(`input#${target}`).val(value);
			// update preview
			handler.updateStepTextPreview(target, value);

			handler.editorModal.close();
		}

		this.stepVisibilityToggle = function (ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();

			let step = $(this).closest('.ywsbs-box-step');
			step.toggleClass('opened').find('.ywsbs-step-settings').slideToggle();
		}

		this.updateStepContentOption = () => {
			let contentSelect = $("select[id*=_ywsbs_box_steps_content]"),
				contentOption = contentSelect.find("option[value=all_products]");

			if ( contentSelect.length > 1 ) {
				contentOption.attr('disabled', 'disabled').hide();
			} else {
				contentOption.removeAttr('disabled').show();
			}

			contentSelect.map((index, select) => {
				if ( null === $(select).val() ) {
					let value = $(select).find('option').filter(':not(:disabled)').first().attr('value');
					$(select).val(value).change();
				}
			});

		}

		this.addStepVisibilityToggle = function (ev) {
			if ( 'all_products' === $(this).val() ) {
				ev.data.handler.stepAddTrigger.hide();
			} else {
				ev.data.handler.stepAddTrigger.show();
			}
		}

		this.stepDelete = function (ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();

			let step = $(this).closest('.ywsbs-box-step');
			step.fadeOut(400, function () {
				$(this).remove();

				$(document.body).trigger('ywsbs_box_step_deleted');
			});
		}

		return init();
	}

	// START
	SubscriptionBoxHandler();

});