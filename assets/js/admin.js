(function ($) {
    'use strict';

    // Store form references
    const $validationForm = $('#validation-form');
    const $settingsForm = $('#validation-settings-form');

    // Store input references
    const $address = $('#address');
    const $apiKey = $('#google_address_validation_api_key');
    const $enableCache = $('#google_address_validation_enable_cache');
    const $cacheDuration = $('#google_address_validation_cache_duration');

    /**
     * Initialize the admin interface
     */
    function init() {
        bindEvents();
        setupInputValidation();
        setupCacheDurationToggle();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Validate forms before submission
        $validationForm.on('submit', validateValidationForm);
        $settingsForm.on('submit', validateSettingsForm);

        // Clear validation messages on input
        $address.on('input', clearValidation);
        $apiKey.on('input', clearValidation);
        $cacheDuration.on('input', clearValidation);
    }

    /**
     * Set up input validation
     */
    function setupInputValidation() {
        // Add custom validation styling
        $('input[type="text"], input[type="number"]').on('invalid', function () {
            $(this).addClass('validation-error');
        }).on('input', function () {
            $(this).removeClass('validation-error');
        });
    }

    /**
     * Set up cache duration toggle based on cache enable checkbox
     */
    function setupCacheDurationToggle() {
        function toggleCacheDuration() {
            $cacheDuration.prop('disabled', !$enableCache.prop('checked'));
        }

        $enableCache.on('change', toggleCacheDuration);
        toggleCacheDuration(); // Initial state
    }

    /**
     * Validate address validation form
     */
    function validateValidationForm(e) {
        clearValidation();

        if (!$address.val().trim()) {
            e.preventDefault();
            showError('Please enter an address');
            $address.focus();
            return false;
        }
        return true;
    }

    /**
     * Validate settings form
     */
    function validateSettingsForm(e) {
        clearValidation();

        if (!$apiKey.val().trim()) {
            e.preventDefault();
            showError('Please enter your API key');
            $apiKey.focus();
            return false;
        }

        if ($enableCache.prop('checked')) {
            const duration = parseInt($cacheDuration.val(), 10);
            if (isNaN(duration) || duration < 300) {
                e.preventDefault();
                showError('Cache duration must be at least 300 seconds');
                $cacheDuration.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * Show error message
     */
    function showError(message) {
        const $error = $('<div>', {
            class: 'notice notice-error is-dismissible',
            html: $('<p>', {text: message})
        });

        $('.address-validation-test .notice').remove(); // Remove any existing notices
        $('.address-validation-test h1').after($error);

        // Add dismiss button functionality
        const $button = $('<button>', {
            type: 'button',
            class: 'notice-dismiss',
            html: $('<span>', {class: 'screen-reader-text', text: 'Dismiss this notice.'})
        }).appendTo($error);

        $button.on('click', function () {
            $error.fadeOut(200, function () {
                $(this).remove();
            });
        });
    }

    /**
     * Clear validation messages
     */
    function clearValidation() {
        $('.address-validation-test .notice').remove();
        $(this).removeClass('validation-error');
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);