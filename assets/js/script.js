/**
 * BuddyPress Page-like Groups JavaScript
 * 
 * Handles page mode functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle page mode settings toggle
        $('#page-mode-enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#page-mode-options').slideDown();
                // Set default if none selected
                if (!$('input[name="posting-restriction"]:checked').length) {
                    $('#posting-restriction-mods').prop('checked', true);
                }
            } else {
                $('#page-mode-options').slideUp();
            }
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
        
        // Add body class if we're in a page mode group
        if ($('.bp-page-mode-badge').length) {
            $('body').addClass('bp-page-mode-active');
        }
        
        // Handle view tracking for page mode activities - REMOVED FEATURE
        // Engagement stats feature has been removed from this version
        
        // Handle join request form submission for public page mode groups
        $(document).on('click', '.group-button.join-group', function(e) {
            var $button = $(this);
            var $groupItem = $button.closest('.group-item, #item-header');
            
            // Check if this is a page mode group with join approval required
            if ($groupItem.find('.bp-page-mode-badge').length && 
                $button.text().indexOf('Request Membership') !== -1) {
                
                // Add visual feedback
                $button.addClass('loading').prop('disabled', true);
            }
        });
        
    });
    
})(jQuery);