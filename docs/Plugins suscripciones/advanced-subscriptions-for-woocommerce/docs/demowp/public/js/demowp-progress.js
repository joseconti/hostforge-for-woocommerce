/**
 * DemoWP Progress Handler
 *
 * Handles demo creation with real-time progress updates.
 */

(function($) {
    'use strict';

    var DemoWPProgress = {
        progressKey: null,
        pollInterval: null,
        pollDelay: 500, // Poll every 500ms
        isCreating: false,
        initialized: false,
        redirectCountdown: 5,

        /**
         * Initialize
         */
        init: function() {
            // Prevent multiple initializations.
            if (this.initialized) {
                return;
            }
            this.initialized = true;

            // Get progress key from global variable
            if (typeof demowpProgressKey !== 'undefined') {
                this.progressKey = demowpProgressKey;
                this.startCreation();
            }
        },

        /**
         * Start demo creation
         */
        startCreation: function() {
            var self = this;

            if (this.isCreating) {
                return;
            }

            this.isCreating = true;
            this.setStepActive(0);

            // Start the creation process
            $.ajax({
                url: demowpProgress.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_create_demo',
                    nonce: demowpProgress.nonce,
                    progress_key: this.progressKey
                },
                success: function(response) {
                    if (response.success) {
                        // If this is a duplicate request, just ignore it.
                        if (response.data && response.data.status === 'duplicate') {
                            return;
                        }
                        self.handleSuccess(response.data);
                    } else {
                        // If silent flag is set, ignore this error (duplicate request).
                        if (response.data && response.data.silent) {
                            return;
                        }
                        self.handleError(response.data ? response.data.message : demowpProgress.strings.error);
                    }
                },
                error: function() {
                    self.handleError(demowpProgress.strings.error);
                }
            });

            // Start polling for progress
            this.startPolling();
        },

        /**
         * Start polling for progress updates
         */
        startPolling: function() {
            var self = this;

            this.pollInterval = setInterval(function() {
                self.checkProgress();
            }, this.pollDelay);
        },

        /**
         * Stop polling
         */
        stopPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        /**
         * Check progress via AJAX
         */
        checkProgress: function() {
            var self = this;

            $.ajax({
                url: demowpProgress.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_check_progress',
                    nonce: demowpProgress.nonce,
                    progress_key: this.progressKey
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateUI(response.data);
                    }
                }
            });
        },

        /**
         * Update UI with progress data
         */
        updateUI: function(data) {
            // Update progress bar
            $('#demowp-progress-fill').css('width', data.percent + '%');
            $('#demowp-progress-percent').text(data.percent + '%');

            // Update steps
            this.updateSteps(data.current_step, data.steps);

            // Handle completion or error
            if (data.status === 'complete' && data.result) {
                this.stopPolling();
                this.handleSuccess(data.result);
            } else if (data.status === 'error' && data.error) {
                this.stopPolling();
                this.handleError(data.error);
            }
        },

        /**
         * Update step indicators
         */
        updateSteps: function(currentStep, steps) {
            var self = this;

            $('.demowp-step').each(function() {
                var $step = $(this);
                var stepIndex = parseInt($step.data('step'), 10);

                // Remove all state classes
                $step.removeClass('is-pending is-active is-complete');

                if (steps && steps[stepIndex] && steps[stepIndex].completed) {
                    $step.addClass('is-complete');
                } else if (stepIndex === currentStep) {
                    $step.addClass('is-active');
                } else {
                    $step.addClass('is-pending');
                }
            });
        },

        /**
         * Set a specific step as active
         */
        setStepActive: function(stepIndex) {
            $('.demowp-step').removeClass('is-active').addClass('is-pending');
            $('.demowp-step[data-step="' + stepIndex + '"]').removeClass('is-pending').addClass('is-active');
        },

        /**
         * Handle successful creation
         */
        handleSuccess: function(data) {
            var self = this;

            this.stopPolling();

            // Mark all steps complete
            $('.demowp-step').removeClass('is-pending is-active').addClass('is-complete');

            // Update progress bar to 100%
            $('#demowp-progress-fill').css('width', '100%');
            $('#demowp-progress-percent').text('100%');

            // Update page state
            $('body').addClass('is-complete');

            // Update title
            $('#demowp-progress-title').text('Demo Ready!');
            $('#demowp-progress-subtitle').text(demowpProgress.strings.redirecting);

            // Show credentials
            $('#demowp-username').text(data.username);
            $('#demowp-password').text(data.password);
            $('#demowp-credentials').fadeIn(300);

            // Hide progress note
            $('#demowp-progress-note').hide();

            // Start countdown
            this.startRedirectCountdown(data.autologin_url);
        },

        /**
         * Start redirect countdown
         */
        startRedirectCountdown: function(url) {
            var self = this;
            var countdown = this.redirectCountdown;

            var countdownInterval = setInterval(function() {
                countdown--;
                $('#demowp-countdown').text(countdown);

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = url;
                }
            }, 1000);
        },

        /**
         * Handle error
         */
        handleError: function(message) {
            this.stopPolling();

            // Update page state
            $('body').addClass('has-error');

            // Update title
            $('#demowp-progress-title').text('Something went wrong');
            $('#demowp-progress-subtitle').text('');

            // Show error message
            $('#demowp-error-text').text(message);
            $('#demowp-error-message').fadeIn(300);

            // Hide spinner
            $('.demowp-spinner-wrapper').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DemoWPProgress.init();
    });

})(jQuery);
