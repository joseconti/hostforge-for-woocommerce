/**
 * DemoWP Admin JavaScript
 */

(function($) {
    'use strict';

    var DemoWPAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Delete single demo
            $(document).on('click', '.demowp-delete-demo', this.handleDeleteDemo);

            // Cleanup all demos
            $(document).on('click', '#demowp-cleanup-all', this.handleCleanupAll);

            // Block demo
            $(document).on('click', '.demowp-block-demo', this.handleBlockDemo);

            // Unblock demo
            $(document).on('click', '.demowp-unblock-demo', this.handleUnblockDemo);
        },

        /**
         * Handle delete demo click
         */
        handleDeleteDemo: function(e) {
            e.preventDefault();

            var $button = $(this);
            var cloneId = $button.data('clone-id');
            var $row = $button.closest('tr');

            if (!confirm(demowpAdmin.strings.confirmDelete)) {
                return;
            }

            // Disable button and show loading
            $button.prop('disabled', true).text(demowpAdmin.strings.deleting);
            $row.addClass('demowp-deleting');

            $.ajax({
                url: demowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_delete_demo',
                    nonce: demowpAdmin.nonce,
                    clone_id: cloneId
                },
                success: function(response) {
                    if (response.success) {
                        $row.addClass('demowp-deleted');
                        setTimeout(function() {
                            $row.remove();
                            DemoWPAdmin.checkEmptyTable();
                        }, 300);
                    } else {
                        alert(response.data ? response.data.message : demowpAdmin.strings.error);
                        $row.removeClass('demowp-deleting');
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert(demowpAdmin.strings.error);
                    $row.removeClass('demowp-deleting');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },

        /**
         * Handle cleanup all click
         */
        handleCleanupAll: function(e) {
            e.preventDefault();

            var $button = $(this);

            if (!confirm(demowpAdmin.strings.confirmCleanup)) {
                return;
            }

            $button.prop('disabled', true).text(demowpAdmin.strings.cleaningUp);

            $.ajax({
                url: demowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_cleanup_all',
                    nonce: demowpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show empty state
                        location.reload();
                    } else {
                        alert(response.data ? response.data.message : demowpAdmin.strings.error);
                        $button.prop('disabled', false).text('Delete All Demos');
                    }
                },
                error: function() {
                    alert(demowpAdmin.strings.error);
                    $button.prop('disabled', false).text('Delete All Demos');
                }
            });
        },

        /**
         * Check if table is empty and show empty state
         */
        checkEmptyTable: function() {
            var $table = $('.demowp-demos-table');
            var $rows = $table.find('tbody tr');

            if ($rows.length === 0) {
                location.reload();
            } else {
                // Update count
                var count = $rows.length;
                var text = count === 1 ? count + ' active demo' : count + ' active demos';
                $('.demowp-table-footer').text(text);
            }
        },

        /**
         * Handle block demo click
         */
        handleBlockDemo: function(e) {
            e.preventDefault();

            var $button = $(this);
            var cloneId = $button.data('clone-id');
            var $row = $button.closest('tr');
            var originalText = $button.text();

            $button.prop('disabled', true).text(demowpAdmin.strings.blocking);

            $.ajax({
                url: demowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_block_demo',
                    nonce: demowpAdmin.nonce,
                    clone_id: cloneId
                },
                success: function(response) {
                    if (response.success) {
                        // Update button to Unblock
                        $button
                            .removeClass('demowp-block-demo')
                            .addClass('demowp-unblock-demo')
                            .text(demowpAdmin.strings.unblock)
                            .prop('disabled', false);

                        // Update badge to Blocked
                        $row.find('.column-expires .demowp-badge')
                            .removeClass('demowp-badge-active demowp-badge-expired')
                            .addClass('demowp-badge-blocked')
                            .text('Blocked');
                    } else {
                        alert(response.data ? response.data.message : demowpAdmin.strings.error);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(demowpAdmin.strings.error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Handle unblock demo click
         */
        handleUnblockDemo: function(e) {
            e.preventDefault();

            var $button = $(this);
            var cloneId = $button.data('clone-id');
            var $row = $button.closest('tr');
            var originalText = $button.text();

            $button.prop('disabled', true).text(demowpAdmin.strings.unblocking);

            $.ajax({
                url: demowpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'demowp_unblock_demo',
                    nonce: demowpAdmin.nonce,
                    clone_id: cloneId
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to get fresh expiration data
                        location.reload();
                    } else {
                        alert(response.data ? response.data.message : demowpAdmin.strings.error);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert(demowpAdmin.strings.error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DemoWPAdmin.init();
    });

})(jQuery);
