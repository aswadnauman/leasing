/**
 * Select2 Dropdown Initializer
 * 
 * Centralized dropdown initialization to prevent conflicts between global and modal-specific Select2 instances.
 * Use this module to consistently initialize dropdowns across all pages without duplicate initialization.
 * 
 * Pattern:
 * - Modal-based pages (clients.php): Initialize on modal show/hide events
 * - Form-based pages (lease_registration.php): Initialize on document ready
 * 
 * NEVER include master_data_dropdowns.js when using this initializer
 */

const Select2DropdownInitializer = {
    /**
     * Helper function to escape HTML and prevent XSS
     */
    escapeHtml: function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * Get AJAX configuration for search_master endpoint
     * @param {string} entityType - Type of entity to search (client, product, recovery_person, outlet, etc)
     * @param {string} ajaxUrl - Optional custom AJAX URL (defaults to current page)
     */
    getAjaxConfig: function(entityType, ajaxUrl) {
        return {
            url: ajaxUrl || window.location.href.split('?')[0],
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    action: 'search_master',
                    type: entityType,
                    q: params.term || ''
                };
            },
            processResults: function(data) {
                if (!data) return { results: [] };
                const results = data.results || data;
                return { results: Array.isArray(results) ? results : [] };
            }
        };
    },

    /**
     * Initialize client dropdowns (.select2-client)
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     * @param {Object} options - Optional custom Select2 options
     */
    initializeClientDropdowns: function(context, options) {
        const defaults = {
            placeholder: "Search and select client...",
            allowClear: true,
            ajax: this.getAjaxConfig('client'),
            minimumInputLength: 0
        };
        
        $(context || document).find('.select2-client').each(function() {
            const customOptions = Object.assign({}, defaults, options || {});
            
            // Destroy existing instance if present
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            $(this).select2(customOptions);
        });
    },

    /**
     * Initialize recovery person dropdowns (.select2-recovery)
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     * @param {Object} options - Optional custom Select2 options
     */
    initializeRecoveryDropdowns: function(context, options) {
        const defaults = {
            placeholder: "Search and select recovery person...",
            allowClear: true,
            ajax: this.getAjaxConfig('recovery_person'),
            minimumInputLength: 0
        };
        
        $(context || document).find('.select2-recovery').each(function() {
            const customOptions = Object.assign({}, defaults, options || {});
            
            // Destroy existing instance if present
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            $(this).select2(customOptions);
        });
    },

    /**
     * Initialize product dropdowns (.select2-product)
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     * @param {Object} options - Optional custom Select2 options
     */
    initializeProductDropdowns: function(context, options) {
        const defaults = {
            placeholder: "Search and select product...",
            allowClear: true,
            ajax: this.getAjaxConfig('product'),
            minimumInputLength: 0
        };
        
        $(context || document).find('.select2-product').each(function() {
            const customOptions = Object.assign({}, defaults, options || {});
            
            // For dropdowns within containers (like modal or product-row),
            // ensure dropdown stays within parent container
            if ($(this).closest('.product-row').length > 0) {
                customOptions.dropdownParent = $(this).closest('.product-row');
            }
            
            // Destroy existing instance if present
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            $(this).select2(customOptions);
        });
    },

    /**
     * Initialize outlet/master dropdowns (.select2-master)
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     * @param {Object} options - Optional custom Select2 options
     */
    initializeOutletDropdowns: function(context, options) {
        const defaults = {
            placeholder: "Select...",
            allowClear: true,
            ajax: this.getAjaxConfig('outlet'),
            minimumInputLength: 0
        };
        
        $(context || document).find('.select2-master').each(function() {
            // Get entity type from data attribute or default to outlet
            const entityType = $(this).data('type') || 'outlet';
            const customOptions = Object.assign({}, defaults, options || {});
            customOptions.ajax = Select2DropdownInitializer.getAjaxConfig(entityType);
            
            // Destroy existing instance if present
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            $(this).select2(customOptions);
        });
    },

    /**
     * Initialize all dropdown types at once
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     */
    initializeAllDropdowns: function(context) {
        this.initializeOutletDropdowns(context);
        this.initializeClientDropdowns(context);
        this.initializeRecoveryDropdowns(context);
        this.initializeProductDropdowns(context);
    },

    /**
     * Destroy and reinitialize a specific dropdown (useful for Edit forms)
     * @param {jQuery|HTMLElement} dropdown - The dropdown element to reinitialize
     * @param {string} type - Type of dropdown (client, product, recovery, outlet)
     * @param {*} selectedValue - Optional value to select after reinitialization
     */
    reinitializeDropdown: function(dropdown, type, selectedValue) {
        const $dropdown = $(dropdown);
        
        // Destroy existing Select2 instance
        if ($dropdown.hasClass('select2-hidden-accessible')) {
            $dropdown.select2('destroy');
        }
        
        // Reinitialize based on type
        switch(type) {
            case 'client':
                this.initializeClientDropdowns($dropdown.parent());
                break;
            case 'product':
                this.initializeProductDropdowns($dropdown.parent());
                break;
            case 'recovery':
                this.initializeRecoveryDropdowns($dropdown.parent());
                break;
            case 'outlet':
            case 'master':
                this.initializeOutletDropdowns($dropdown.parent());
                break;
            default:
                this.initializeAllDropdowns($dropdown.parent());
        }
        
        // Set selected value if provided
        if (selectedValue) {
            $dropdown.val(selectedValue).trigger('change');
        }
    },

    /**
     * Clean up all Select2 instances within a container
     * @param {jQuery|HTMLElement} context - Element or jQuery object to search within
     */
    destroyAllDropdowns: function(context) {
        $(context || document).find('.select2-client, .select2-product, .select2-recovery, .select2-master').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
    },

    /**
     * Setup modal dropdown lifecycle (for Bootstrap modals)
     * @param {string} modalId - ID of the Bootstrap modal
     * @param {boolean} reinitializeOnShow - If true, reinitialize dropdowns when modal shows
     */
    setupModalDropdownLifecycle: function(modalId, reinitializeOnShow) {
        reinitializeOnShow = reinitializeOnShow !== false; // default true
        
        const $modal = $(document.getElementById(modalId));
        const self = this;
        
        // Initialize on show
        $modal.on('show.bs.modal', function() {
            if (reinitializeOnShow) {
                self.destroyAllDropdowns($modal);
                self.initializeAllDropdowns($modal);
            }
        });
        
        // Cleanup on hide
        $modal.on('hide.bs.modal', function() {
            self.destroyAllDropdowns($modal);
        });
    },

    /**
     * Setup form dropdown initialization (initialize on document ready)
     * Automatically called when domContentLoaded fires
     */
    setupFormDropdowns: function() {
        this.initializeAllDropdowns(document);
    }
};

// Auto-initialize on document ready if this script is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only auto-initialize if running in a non-modal context
    // Modal pages should call Select2DropdownInitializer methods manually
    if (!document.querySelector('[class*="modal"]')) {
        Select2DropdownInitializer.setupFormDropdowns();
    }
});
