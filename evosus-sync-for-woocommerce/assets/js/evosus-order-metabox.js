/**
 * Evosus Order Metabox JavaScript
 * Handles AJAX interactions for order sync operations
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const EvosusSyncMetabox = {
            orderId: evosusSyncData.orderId,
            nonce: evosusSyncData.nonce,

            init: function() {
                this.bindEvents();
            },

            bindEvents: function() {
                $('#evosus-validate-btn').on('click', this.validateOrder.bind(this));
                $('#evosus-sync-btn').on('click', this.syncOrder.bind(this));
                $('#evosus-verify-btn').on('click', this.verifyReference.bind(this));
                $('#evosus-revalidate-btn').on('click', this.revalidateOrder.bind(this));
                $('#evosus-approve-sync-btn').on('click', this.approveAndSync.bind(this));
                $('.use-sku-btn').on('click', this.useSuggestedSKU.bind(this));
                $('.map-sku-btn').on('click', this.mapManualSKU.bind(this));
            },

            validateOrder: function(e) {
                e.preventDefault();
                const $btn = $(e.currentTarget);

                $btn.prop('disabled', true).html('<span class="evosus-spinner"></span> ' + evosusSyncData.i18n.validating);
                $('#evosus-validation-info').show();

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'evosus_validate_order',
                        nonce: this.nonce,
                        order_id: this.orderId
                    },
                    success: (response) => {
                        $btn.prop('disabled', false).html('&#128269; ' + evosusSyncData.i18n.checkOrder);

                        if (response.success) {
                            if (response.data.valid) {
                                this.showMessage('success', evosusSyncData.i18n.validationSuccess);
                                $('#evosus-validation-info').hide();
                            } else {
                                this.showMessage('error', evosusSyncData.i18n.validationIssues);
                                setTimeout(() => location.reload(), 2000);
                            }
                        } else {
                            this.showMessage('error', evosusSyncData.i18n.validationFailed + ': ' + response.data.message);
                            $('#evosus-validation-info').hide();
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).html('&#128269; ' + evosusSyncData.i18n.checkOrder);
                        this.showMessage('error', evosusSyncData.i18n.networkError);
                        $('#evosus-validation-info').hide();
                    }
                });
            },

            syncOrder: function(e) {
                e.preventDefault();

                if (!confirm(evosusSyncData.i18n.confirmSync)) {
                    return;
                }

                const $btn = $(e.currentTarget);
                $btn.prop('disabled', true).html('<span class="evosus-spinner"></span> ' + evosusSyncData.i18n.syncing);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'evosus_sync_from_order',
                        nonce: this.nonce,
                        order_id: this.orderId
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showMessage('success',
                                evosusSyncData.i18n.syncSuccess +
                                '<br>' + evosusSyncData.i18n.evosusOrderId + ': <strong>' + response.data.evosus_order_id + '</strong>' +
                                '<br>' + evosusSyncData.i18n.wcOrderNumber + ' ' + response.data.wc_order_number + ' ' + evosusSyncData.i18n.addedToPO
                            );
                            setTimeout(() => location.reload(), 3000);
                        } else if (response.data.needs_review) {
                            this.showMessage('info', evosusSyncData.i18n.needsReview);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            $btn.prop('disabled', false).html('&#10145; ' + evosusSyncData.i18n.addToEvosus);
                            this.showMessage('error', evosusSyncData.i18n.syncFailed + ': ' + response.data.message);
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).html('&#10145; ' + evosusSyncData.i18n.addToEvosus);
                        this.showMessage('error', evosusSyncData.i18n.networkError);
                    }
                });
            },

            verifyReference: function(e) {
                e.preventDefault();
                const $btn = $(e.currentTarget);

                $btn.prop('disabled', true).text(evosusSyncData.i18n.verifying);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'evosus_verify_cross_reference',
                        nonce: this.nonce,
                        order_id: this.orderId
                    },
                    success: (response) => {
                        $btn.prop('disabled', false).html('&#128269; ' + evosusSyncData.i18n.verifyReference);

                        const $resultDiv = $('#evosus-verify-result');

                        if (response.success) {
                            if (response.data.verified) {
                                $resultDiv.removeClass().addClass('evosus-message-success')
                                    .html('&#9989; <strong>' + evosusSyncData.i18n.verified + '</strong><br>' +
                                        evosusSyncData.i18n.evosusOrderId + ': <code>' + response.data.evosus_order_id + '</code><br>' +
                                        evosusSyncData.i18n.poNumber + ': <code>' + response.data.evosus_po_number + '</code><br>' +
                                        evosusSyncData.i18n.wcOrderNumber + ': <code>' + response.data.wc_order_number + '</code>')
                                    .show();
                            } else {
                                $resultDiv.removeClass().addClass('evosus-message-error')
                                    .html('&#10060; <strong>' + evosusSyncData.i18n.mismatch + '</strong><br>' +
                                        evosusSyncData.i18n.poNumber + ': <code>' + response.data.evosus_po_number + '</code><br>' +
                                        evosusSyncData.i18n.wcOrderNumber + ': <code>' + response.data.wc_order_number + '</code><br>' +
                                        evosusSyncData.i18n.checkInEvosus)
                                    .show();
                            }
                        } else {
                            $resultDiv.removeClass().addClass('evosus-message-error')
                                .html('&#10060; ' + response.data.message)
                                .show();
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).html('&#128269; ' + evosusSyncData.i18n.verifyReference);
                        this.showMessage('error', evosusSyncData.i18n.verificationError);
                    }
                });
            },

            revalidateOrder: function(e) {
                e.preventDefault();
                const $btn = $(e.currentTarget);

                $btn.prop('disabled', true).text(evosusSyncData.i18n.checking);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'evosus_validate_order',
                        nonce: this.nonce,
                        order_id: this.orderId
                    },
                    success: (response) => {
                        if (response.success && response.data.valid) {
                            this.showMessage('success', evosusSyncData.i18n.issuesResolved);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            location.reload();
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).html('&#128257; ' + evosusSyncData.i18n.recheckOrder);
                        this.showMessage('error', evosusSyncData.i18n.errorChecking);
                    }
                });
            },

            approveAndSync: function(e) {
                e.preventDefault();

                if (!confirm(evosusSyncData.i18n.confirmApprove)) {
                    return;
                }

                const $btn = $(e.currentTarget);
                $btn.prop('disabled', true).text(evosusSyncData.i18n.syncing);

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'evosus_sync_from_order',
                        nonce: this.nonce,
                        order_id: this.orderId,
                        skip_validation: true
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showMessage('success', evosusSyncData.i18n.syncSuccess);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            $btn.prop('disabled', false).html('&#10004; ' + evosusSyncData.i18n.approveAndAdd);
                            this.showMessage('error', evosusSyncData.i18n.syncFailed + ': ' + response.data.message);
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).html('&#10004; ' + evosusSyncData.i18n.approveAndAdd);
                        this.showMessage('error', evosusSyncData.i18n.networkError);
                    }
                });
            },

            useSuggestedSKU: function(e) {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const itemId = $btn.data('item-id');
                const newSku = $btn.data('new-sku');

                $btn.prop('disabled', true).text(evosusSyncData.i18n.mapping);

                this.mapSku(itemId, newSku, $btn);
            },

            mapManualSKU: function(e) {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const itemId = $btn.data('item-id');
                const $input = $('.manual-sku-input[data-item-id="' + itemId + '"]');
                const newSku = $input.val().trim();

                if (!newSku) {
                    alert(evosusSyncData.i18n.enterSKU);
                    return;
                }

                $btn.prop('disabled', true).text(evosusSyncData.i18n.mapping);

                this.mapSku(itemId, newSku, $btn);
            },

            mapSku: function(itemId, newSku, $btn) {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'map_order_sku',
                        nonce: this.nonce,
                        order_id: this.orderId,
                        item_id: itemId,
                        new_sku: newSku
                    },
                    success: (response) => {
                        if (response.success) {
                            $btn.text('&#10004; ' + evosusSyncData.i18n.mapped).css('background', '#28a745');
                            this.showMessage('success', evosusSyncData.i18n.skuMapped);
                        } else {
                            $btn.prop('disabled', false).text(evosusSyncData.i18n.tryAgain);
                            this.showMessage('error', evosusSyncData.i18n.error + ': ' + response.data.message);
                        }
                    },
                    error: () => {
                        $btn.prop('disabled', false).text(evosusSyncData.i18n.tryAgain);
                        this.showMessage('error', evosusSyncData.i18n.networkError);
                    }
                });
            },

            showMessage: function(type, message) {
                const $messageDiv = $('#evosus-sync-message');
                const className = 'evosus-message-' + type;
                $messageDiv.removeClass().addClass(className).html(message).show();
            }
        };

        // Initialize if we're on an order page
        if (typeof evosusSyncData !== 'undefined') {
            EvosusSyncMetabox.init();
        }
    });

})(jQuery);
