/**
 * Master Data Dropdowns Handler
 * Provides standardized Select2 initialization for all master data dropdowns
 * 
 * Fixes:
 * - All dropdowns use AJAX with minimumInputLength: 0 to show all options on focus
 * - Proper error handling for processResults
 * - Consistent {results: [...]} response format expected
 */

$(document).ready(function() {
    // Initialize Select2 for all master data dropdowns with class 'select2-master'
    $('.select2-master').select2({
        placeholder: "Click to select or search...",
        allowClear: true,
        ajax: {
            url: window.location.href.split('?')[0], // Current page
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_master',
                    type: $(this).data('type'),
                    q: params.term || ''  // Ensure q is always defined
                };
            },
            processResults: function (data, params) {
                // Ensure we handle the response correctly
                if (!data) {
                    return { results: [] };
                }
                
                var results = data.results || data;
                if (!Array.isArray(results)) {
                    results = [];
                }
                
                return {
                    results: results,
                    pagination: {
                        more: (params.page || 0) * 20 < (data.total_count || 0)
                    }
                };
            },
            error: function(xhr, status, error) {
                console.error('AJAX error fetching master data:', error);
            },
            cache: true
        },
        minimumInputLength: 0 // Show all options when opened
    });
    
    // No need to reinitialize here as it's handled in clients.php
    
    // Initialize Select2 for client dropdowns with class 'select2-client'
    $('.select2-client').select2({
        placeholder: "Search and select client...",
        allowClear: true,
        ajax: {
            url: window.location.href.split('?')[0], // Current page
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search',
                    q: params.term
                };
            },
            processResults: function (data) {
                // Handle both formats: with "results" key and without
                var results = data.results || data;
                return {
                    results: $.map(results, function(item) {
                        return {
                            id: item.client_id || item.id,
                            text: item.full_name || item.text
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });
    
    // Initialize Select2 for product dropdowns with class 'select2-product'
    $('.select2-product').select2({
        placeholder: "Select product...",
        allowClear: true,
        ajax: {
            url: 'products.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'select2_search',
                    q: params.term
                };
            },
            processResults: function (data) {
                // Handle both formats: with "results" key and without
                if (data.results) {
                    return {
                        results: data.results
                    };
                }
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });
    
    // Initialize Select2 for recovery person dropdowns with class 'select2-recovery'
    $('.select2-recovery').select2({
        placeholder: "Search and select recovery person...",
        allowClear: true,
        ajax: {
            url: window.location.href.split('?')[0], // Current page
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_master',
                    type: 'recovery_person',
                    q: params.term
                };
            },
            processResults: function (data) {
                // Handle both formats: with "results" key and without
                var results = data.results || data;
                return {
                    results: $.map(results, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });
    
    // Initialize Select2 for lease dropdowns with class 'select2-lease'
    $('.select2-lease').select2({
        placeholder: "Select lease...",
        allowClear: true,
        ajax: {
            url: window.location.href.split('?')[0],
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_master',
                    type: 'lease',
                    q: params.term
                };
            },
            processResults: function (data) {
                // Handle both formats: with "results" key and without
                if (data.results) {
                    return {
                        results: data.results
                    };
                }
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });
});