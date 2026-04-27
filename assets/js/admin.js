/**
 * WooCommerce Print Documents - Admin JavaScript
 */

(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    function initAdmin() {
        initPrintButtons();
        initEmailButtons();
        initPreviewModal();
        initInvoiceFields();
        initBulkActions();
    }

    /**
     * Initialize print buttons
     */
    function initPrintButtons() {
        $(document).on('click', '.wcp-print-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var type = $btn.data('type');
            var orderId = $btn.data('order');
            var nonce = $btn.data('nonce') || wcp_admin.nonce;
            
            var printUrl = wcp_admin.ajax_url + '?action=wcp_print_document&type=' + encodeURIComponent(type) + '&order_id=' + orderId + '&nonce=' + nonce;
            
            // Open print in new window
            window.open(printUrl, '_blank');
        });
    }

    /**
     * Initialize email buttons
     */
    function initEmailButtons() {
        $(document).on('click', '.wcp-email-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var type = $btn.data('type');
            var orderId = $btn.data('order');
            
            if (confirm(wcp_admin.i18n.confirm_email || 'Send this document to the customer?')) {
                $.ajax({
                    url: wcp_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcp_email_document',
                        type: type,
                        order_id: orderId,
                        nonce: wcp_admin.nonce
                    },
                    beforeSend: function() {
                        $btn.prop('disabled', true).addClass('loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(wcp_admin.i18n.email_sent || 'Document sent successfully!');
                        } else {
                            alert(response.data && response.data.message || wcp_admin.i18n.email_failed || 'Failed to send email.');
                        }
                    },
                    error: function() {
                        alert(wcp_admin.i18n.email_failed || 'Failed to send email.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).removeClass('loading');
                    }
                });
            }
        });
    }

    /**
     * Initialize preview modal
     */
    function initPreviewModal() {
        var $modal = $('#wcp-preview-modal');
        var $iframe = $('#wcp-preview-frame');
        var $close = $modal.find('.close');
        
        $(document).on('click', '.wcp-preview-btn', function(e) {
            e.preventDefault();
            
            var type = $(this).data('type');
            var previewUrl = wcp_admin.ajax_url + '?action=wcp_preview_document&type=' + type + '&nonce=' + wcp_admin.nonce;
            
            $iframe.attr('src', previewUrl);
            $modal.show();
        });
        
        $close.on('click', function() {
            $modal.hide();
            $iframe.attr('src', '');
        });
        
        $(document).on('click', function(e) {
            if (e.target === $modal[0]) {
                $modal.hide();
                $iframe.attr('src', '');
            }
        });
        
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                $modal.hide();
                $iframe.attr('src', '');
            }
        });
    }

    /**
     * Initialize invoice fields
     */
    function initInvoiceFields() {
        $(document).on('click', '#wcp_save_invoice', function() {
            var $btn = $(this);
            var invoiceNumber = $('#wcp_invoice_number').val();
            var orderId = $btn.data('order') || $('#wcp_invoice_number').closest('.wcp-order-actions').find('[name="order_id"]').val();
            
            if (!orderId) {
                var orderIdInput = $('.wcp-meta-box').closest('.postbox').find('[data-order-id]');
                if (orderIdInput.length) {
                    orderId = orderIdInput.data('order-id');
                }
            }
            
            // Try to get order ID from URL or page
            var urlParams = new URLSearchParams(window.location.search);
            var postId = urlParams.get('post');
            
            if (!orderId && postId) {
                orderId = postId;
            }
            
            if (!orderId) {
                // Find order ID from the edit page
                var editForm = $('form#post');
                if (editForm.length) {
                    orderId = editForm.find('input[name="post_ID"]').val();
                }
            }
            
            $.ajax({
                url: wcp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcp_save_invoice_number',
                    invoice_number: invoiceNumber,
                    order_id: orderId,
                    nonce: wcp_admin.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        alert(wcp_admin.i18n.invoice_saved || 'Invoice number saved!');
                    } else {
                        alert(response.data && response.data.message || wcp_admin.i18n.save_failed || 'Failed to save.');
                    }
                },
                error: function() {
                    alert(wcp_admin.i18n.save_failed || 'Failed to save.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        });
        
        $(document).on('click', '#wcp_save_date', function() {
            var $btn = $(this);
            var invoiceDate = $('#wcp_invoice_date').val();
            
            // Try to get order ID
            var urlParams = new URLSearchParams(window.location.search);
            var orderId = urlParams.get('post');
            
            $.ajax({
                url: wcp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcp_save_invoice_date',
                    invoice_date: invoiceDate,
                    order_id: orderId,
                    nonce: wcp_admin.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        alert(wcp_admin.i18n.date_saved || 'Invoice date saved!');
                    } else {
                        alert(response.data && response.data.message || wcp_admin.i18n.save_failed || 'Failed to save.');
                    }
                },
                error: function() {
                    alert(wcp_admin.i18n.save_failed || 'Failed to save.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        });
    }

    /**
     * Initialize bulk actions
     */
    function initBulkActions() {
        // Bulk print from orders list
        $(document).on('click', '.wcp-bulk-print-link', function(e) {
            e.preventDefault();
            
            var orderId = $(this).data('order');
            
            // Open bulk print modal or redirect to orders page with bulk actions
            window.location.href = 'edit.php?post_type=shop_order&wcp_bulk_print=' + orderId;
        });
        
        // Handle bulk action dropdown
        if ($('.wcp-doc-links').length) {
            // Add bulk action to dropdown
            var $bulkActions = $('select[name="action"]');
            if ($bulkActions.length) {
                $bulkActions.find('option[value="wcp_print"]').remove();
                $bulkActions.append('<option value="wcp_print">' + (wcp_admin.i18n.bulk_print || 'Print Documents') + '</option>');
            }
            
            var $bulkActionsBottom = $('select[name="action2"]');
            if ($bulkActionsBottom.length) {
                $bulkActionsBottom.find('option[value="wcp_print"]').remove();
                $bulkActionsBottom.append('<option value="wcp_print">' + (wcp_admin.i18n.bulk_print || 'Print Documents') + '</option>');
            }
        }
    }

    /**
     * Document type selection
     */
    $(document).on('change', '#wcp_document_type', function() {
        var type = $(this).val();
        var $previewBtn = $('.wcp-preview-btn');
        
        if ($previewBtn.length) {
            $previewBtn.data('type', type);
        }
    });

    /**
     * Upload logo
     */
    $(document).on('click', '#wcp_upload_logo', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $input = $('#wcp_logo_url');
        
        // Create media frame
        var fileFrame = wp.media({
            title: wcp_admin.i18n.select_logo || 'Select Logo',
            button: {
                text: wcp_admin.i18n.use_logo || 'Use as Logo'
            },
            multiple: false
        });
        
        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        });
        
        fileFrame.open();
    });

    /**
     * Document table row toggle
     */
    $(document).on('click', '.wcp-document-row input[type="checkbox"]', function() {
        var $row = $(this).closest('.wcp-document-row');
        var isChecked = $(this).is(':checked');
        
        if (isChecked) {
            $row.addClass('active');
        } else {
            $row.removeClass('active');
        }
    });

    // Initialize on document ready
    $(document).ready(initAdmin);

})(jQuery);