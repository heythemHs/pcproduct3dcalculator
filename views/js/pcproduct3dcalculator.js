/**
 * PC Product 3D Calculator - Front-end JavaScript
 */

(function() {
    'use strict';

    // Module namespace
    var PC3D = window.PC3D || {};

    // Configuration (set from PHP)
    PC3D.config = window.pc3d_config || {};
    PC3D.ajaxUrl = window.pc3d_ajax_url || '';
    PC3D.translations = window.pc3d_translations || {};

    // Current state
    PC3D.currentUpload = null;

    /**
     * Initialize the calculator
     */
    PC3D.init = function() {
        this.bindEvents();
        this.initFileDropZone();
    };

    /**
     * Bind event handlers
     */
    PC3D.bindEvents = function() {
        var self = this;

        // File input change
        $(document).on('change', '.pc3d-file-input', function(e) {
            var file = e.target.files[0];
            if (file) {
                self.handleFileSelect(file);
            }
        });

        // Material change
        $(document).on('change', '.pc3d-material-select', function() {
            self.recalculatePrice();
        });

        // Infill change
        $(document).on('change input', '.pc3d-infill-input', function() {
            self.updateInfillDisplay($(this).val());
            self.recalculatePrice();
        });

        // Add to cart button
        $(document).on('click', '.pc3d-add-to-cart', function(e) {
            e.preventDefault();
            self.addToCart();
        });

        // Delete upload button
        $(document).on('click', '.pc3d-delete-upload', function(e) {
            e.preventDefault();
            var uploadId = $(this).data('upload-id');
            self.deleteUpload(uploadId);
        });

        // Request quote button
        $(document).on('click', '.pc3d-request-quote', function(e) {
            e.preventDefault();
            self.requestQuote();
        });
    };

    /**
     * Initialize file drop zone
     */
    PC3D.initFileDropZone = function() {
        var self = this;
        var dropZone = $('.pc3d-drop-zone');

        if (!dropZone.length) return;

        dropZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('pc3d-drop-zone--active');
        });

        dropZone.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('pc3d-drop-zone--active');
        });

        dropZone.on('drop', function(e) {
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                self.handleFileSelect(files[0]);
            }
        });

        // Click to browse
        dropZone.on('click', function() {
            $(this).find('.pc3d-file-input').trigger('click');
        });
    };

    /**
     * Handle file selection
     */
    PC3D.handleFileSelect = function(file) {
        var self = this;

        // Validate file
        var validation = this.validateFile(file);
        if (!validation.valid) {
            this.showError(validation.error);
            return;
        }

        // Update UI
        this.showFileName(file.name);
        this.showLoading(PC3D.translations.uploading || 'Uploading...');

        // Upload file
        this.uploadFile(file);
    };

    /**
     * Validate file before upload
     */
    PC3D.validateFile = function(file) {
        var maxSize = (PC3D.config.max_file_size || 10) * 1024 * 1024;
        var allowedExtensions = ['stl', 'obj'];

        // Check size
        if (file.size > maxSize) {
            return {
                valid: false,
                error: PC3D.translations.file_too_large || 'File is too large'
            };
        }

        // Check extension
        var extension = file.name.split('.').pop().toLowerCase();
        if (allowedExtensions.indexOf(extension) === -1) {
            return {
                valid: false,
                error: PC3D.translations.invalid_file || 'Invalid file type. Allowed: STL, OBJ'
            };
        }

        return { valid: true };
    };

    /**
     * Upload file to server
     */
    PC3D.uploadFile = function(file) {
        var self = this;
        var formData = new FormData();

        formData.append('action', 'upload');
        formData.append('file', file);
        formData.append('material_id', $('.pc3d-material-select').val() || 0);
        formData.append('infill_percent', $('.pc3d-infill-input').val() || PC3D.config.default_infill || 20);

        $.ajax({
            url: PC3D.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                self.hideLoading();

                if (response.success) {
                    self.currentUpload = response;
                    self.displayResults(response);
                    self.showSuccess(PC3D.translations.success || 'File processed successfully');
                } else {
                    self.showError(response.error || 'Upload failed');
                }
            },
            error: function(xhr, status, error) {
                self.hideLoading();
                self.showError('Network error: ' + error);
            }
        });
    };

    /**
     * Recalculate price with new options
     */
    PC3D.recalculatePrice = function() {
        var self = this;

        if (!this.currentUpload || !this.currentUpload.upload_id) {
            return;
        }

        var materialId = $('.pc3d-material-select').val();
        var infillPercent = $('.pc3d-infill-input').val();

        if (!materialId) {
            return;
        }

        this.showLoading(PC3D.translations.calculating || 'Calculating...');

        $.ajax({
            url: PC3D.ajaxUrl,
            type: 'POST',
            data: {
                action: 'calculate',
                upload_id: this.currentUpload.upload_id,
                material_id: materialId,
                infill_percent: infillPercent
            },
            dataType: 'json',
            success: function(response) {
                self.hideLoading();

                if (response.success) {
                    self.currentUpload = $.extend(self.currentUpload, response);
                    self.displayResults(response);
                } else {
                    self.showError(response.error || 'Calculation failed');
                }
            },
            error: function(xhr, status, error) {
                self.hideLoading();
                self.showError('Network error: ' + error);
            }
        });
    };

    /**
     * Display calculation results
     */
    PC3D.displayResults = function(data) {
        var resultsContainer = $('.pc3d-results');

        if (!resultsContainer.length) return;

        resultsContainer.show();

        // Volume
        if (PC3D.config.show_volume && data.volume_cm3) {
            $('.pc3d-result-volume').text(data.volume_cm3.toFixed(2) + ' cm³').parent().show();
        } else {
            $('.pc3d-result-volume').parent().hide();
        }

        // Weight
        if (PC3D.config.show_weight && data.weight_grams) {
            $('.pc3d-result-weight').text(data.weight_grams.toFixed(2) + ' g').parent().show();
        } else {
            $('.pc3d-result-weight').parent().hide();
        }

        // Price
        if (data.estimated_price) {
            var formattedPrice = data.formatted_price ||
                (PC3D.config.currency_sign + ' ' + data.estimated_price.toFixed(2));
            $('.pc3d-result-price').text(formattedPrice);
            $('.pc3d-price-display').show();
        }

        // Material name
        if (data.material_name) {
            $('.pc3d-result-material').text(data.material_name);
        }

        // Enable add to cart button
        if (data.estimated_price > 0) {
            $('.pc3d-add-to-cart').prop('disabled', false);
        }
    };

    /**
     * Update infill display
     */
    PC3D.updateInfillDisplay = function(value) {
        $('.pc3d-infill-value').text(value + '%');
    };

    /**
     * Add current upload to cart
     */
    PC3D.addToCart = function() {
        var self = this;

        if (!this.currentUpload || !this.currentUpload.upload_id) {
            this.showError(PC3D.translations.upload_first || 'Please upload a file first');
            return;
        }

        var productId = $('[name="pc3d_product_id"]').val() || 0;

        this.showLoading('Adding to cart...');

        $.ajax({
            url: PC3D.ajaxUrl,
            type: 'POST',
            data: {
                action: 'addToCart',
                upload_id: this.currentUpload.upload_id,
                product_id: productId
            },
            dataType: 'json',
            success: function(response) {
                self.hideLoading();

                if (response.success) {
                    self.showSuccess(PC3D.translations.added_to_cart || 'Added to cart');

                    // Update cart counter if possible
                    if (response.cart_count !== undefined) {
                        self.updateCartCounter(response.cart_count);
                    }

                    // Optionally redirect to cart
                    if (response.cart_url) {
                        setTimeout(function() {
                            window.location.href = response.cart_url;
                        }, 1500);
                    }
                } else {
                    self.showError(response.error || 'Failed to add to cart');
                }
            },
            error: function(xhr, status, error) {
                self.hideLoading();
                self.showError('Network error: ' + error);
            }
        });
    };

    /**
     * Delete an upload
     */
    PC3D.deleteUpload = function(uploadId) {
        var self = this;

        if (!confirm('Are you sure you want to delete this upload?')) {
            return;
        }

        $.ajax({
            url: PC3D.ajaxUrl,
            type: 'POST',
            data: {
                action: 'deleteUpload',
                upload_id: uploadId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove from UI
                    $('[data-upload-id="' + uploadId + '"]').closest('.pc3d-upload-item').fadeOut(300, function() {
                        $(this).remove();
                    });

                    // Clear current upload if it was deleted
                    if (self.currentUpload && self.currentUpload.upload_id == uploadId) {
                        self.currentUpload = null;
                        self.resetForm();
                    }
                } else {
                    self.showError(response.error || 'Delete failed');
                }
            },
            error: function(xhr, status, error) {
                self.showError('Network error: ' + error);
            }
        });
    };

    /**
     * Request a quote (without adding to cart)
     */
    PC3D.requestQuote = function() {
        if (!this.currentUpload) {
            this.showError(PC3D.translations.upload_first || 'Please upload a file first');
            return;
        }

        // For now, redirect to contact page with upload ID
        var contactUrl = prestashop.urls.pages.contact;
        if (contactUrl) {
            window.location.href = contactUrl + '?pc3d_upload=' + this.currentUpload.upload_id;
        }
    };

    /**
     * Show file name in UI
     */
    PC3D.showFileName = function(name) {
        $('.pc3d-file-name').text(name).show();
        $('.pc3d-drop-zone-text').hide();
    };

    /**
     * Show loading indicator
     */
    PC3D.showLoading = function(message) {
        var loader = $('.pc3d-loading');
        if (loader.length) {
            loader.find('.pc3d-loading-text').text(message || 'Loading...');
            loader.show();
        }

        // Disable form elements
        $('.pc3d-form :input').prop('disabled', true);
    };

    /**
     * Hide loading indicator
     */
    PC3D.hideLoading = function() {
        $('.pc3d-loading').hide();
        $('.pc3d-form :input').prop('disabled', false);
    };

    /**
     * Show error message
     */
    PC3D.showError = function(message) {
        var alert = $('.pc3d-alert-error');
        if (alert.length) {
            alert.text(message).fadeIn();
            setTimeout(function() {
                alert.fadeOut();
            }, 5000);
        } else {
            alert(message);
        }
    };

    /**
     * Show success message
     */
    PC3D.showSuccess = function(message) {
        var alert = $('.pc3d-alert-success');
        if (alert.length) {
            alert.text(message).fadeIn();
            setTimeout(function() {
                alert.fadeOut();
            }, 3000);
        }
    };

    /**
     * Update cart counter in header
     */
    PC3D.updateCartCounter = function(count) {
        var counter = $('.header .cart-products-count, .blockcart .cart-products-count');
        if (counter.length) {
            counter.text(count);
        }
    };

    /**
     * Reset the form
     */
    PC3D.resetForm = function() {
        $('.pc3d-file-input').val('');
        $('.pc3d-file-name').hide();
        $('.pc3d-drop-zone-text').show();
        $('.pc3d-results').hide();
        $('.pc3d-add-to-cart').prop('disabled', true);
    };

    // Export to global
    window.PC3D = PC3D;

    // Initialize on document ready
    $(document).ready(function() {
        PC3D.init();
    });

})();
