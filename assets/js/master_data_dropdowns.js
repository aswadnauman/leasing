/**
 * Master Data Dropdowns Handler
 * Provides standardized Select2 initialization for all master data dropdowns
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for all master data dropdowns with class 'select2-master'
    $('.select2-master').select2({
        placeholder: "Select...",
        allowClear: true,
        ajax: {
            url: window.location.href.split('?')[0], // Current page
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'search_master',
                    type: $(this).data('type'),
                    q: params.term
                };
            },
            processResults: function (data) {
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
    
    // Initialize Select2 for client dropdowns with class 'select2-client'
    $('.select2-client').select2({
        placeholder: "Select client...",
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
                return {
                    results: $.map(data, function(item) {
                        return {
                            id: item.client_id,
                            text: item.full_name
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1
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
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });
    
    // Initialize Select2 for recovery person dropdowns with class 'select2-recovery'
    $('.select2-recovery').select2({
        placeholder: "Select recovery person...",
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
                return {
                    results: $.map(data, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1
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
                return {
                    results: data.results || data
                };
            },
            cache: true
        },
        minimumInputLength: 1
    });
});