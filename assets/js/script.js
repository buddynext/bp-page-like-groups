/**
 * BuddyPress Page-like Groups JavaScript
 * 
 * Handles page mode functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle page mode settings toggle
        $('#page-mode-enabled, #bp-plg-page-mode-enabled').on('change', function() {
            var $options = $(this).attr('id') === 'bp-plg-page-mode-enabled' 
                ? $('#bp-plg-page-mode-options') 
                : $('#page-mode-options');
            
            if ($(this).is(':checked')) {
                $options.slideDown();
                // Set default if none selected
                if (!$('input[name="posting-restriction"]:checked').length) {
                    var defaultRadio = $(this).attr('id') === 'bp-plg-page-mode-enabled'
                        ? $('#bp-plg-posting-restriction-mods')
                        : $('#posting-restriction-mods');
                    defaultRadio.prop('checked', true);
                }
            } else {
                $options.slideUp();
            }
        });
        
        // Add body class if we're in a page mode group
        if ($('.bp-page-mode-badge').length) {
            $('body').addClass('bp-page-mode-active');
        }
        
        // Handle join request form submission for public page mode groups
        $(document).on('click', '.group-button.join-group', function(e) {
            var $button = $(this);
            var $groupItem = $button.closest('.group-item, #item-header');
            
            // Check if this is a page mode group
            if ($groupItem.find('.bp-page-mode-badge').length) {
                // Add visual feedback
                $button.addClass('loading').prop('disabled', true);
            }
        });
        
        // Handle Request Membership button clicks
        $(document).on('click', 'a.ajax-request-membership', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var href = $button.attr('href');
            
            // Add loading state
            $button.addClass('loading').text('Processing...');
            
            // Redirect to the request membership URL
            window.location.href = href;
        });
        
    });
    
})(jQuery);