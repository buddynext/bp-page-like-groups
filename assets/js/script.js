/**
 * BuddyPress Page-like Groups JavaScript
 * 
 * Handles quick comments and other interactive features
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Debug: Check if quick comment buttons exist
        if ($('.quick-comment-buttons').length) {
            console.log('BuddyPress Page-like Groups: Found ' + $('.quick-comment-buttons').length + ' quick comment button sets');
        } else {
            console.log('BuddyPress Page-like Groups: No quick comment buttons found');
        }
        
        // Handle quick comment buttons
        $(document).on('click', '.quick-comment-buttons button.quick-comment', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $activity = $button.closest('.activity-item');
            var activityId = $activity.attr('id').replace('activity-', '');
            var commentText = $button.attr('data-comment');
            
            // Disable button during processing
            $button.prop('disabled', true).addClass('loading');
            
            // Send AJAX request
            $.ajax({
                url: bpplg.ajax_url,
                type: 'POST',
                data: {
                    action: 'bpplg_quick_comment',
                    activity_id: activityId,
                    comment: commentText,
                    nonce: bpplg.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success feedback
                        bpplgShowFeedback($button, 'success');
                        
                        // Refresh the comment stream if visible
                        var $commentsList = $activity.find('.activity-comments ul');
                        if ($commentsList.length) {
                            // If comments are already shown, reload them
                            bpplgReloadComments(activityId);
                        } else {
                            // Update comment count
                            var $commentCount = $activity.find('.activity-meta a.acomment-reply');
                            if ($commentCount.length) {
                                var currentCount = parseInt($commentCount.text().match(/\d+/) || 0);
                                var newCount = currentCount + 1;
                                var commentText = newCount === 1 ? 'Comment' : 'Comments';
                                $commentCount.html('<span class="comment-count">' + newCount + '</span> ' + commentText);
                            }
                        }
                        
                        // Update engagement stats if visible
                        var $engagementStats = $activity.find('.page-engagement-stats .engagement');
                        if ($engagementStats.length) {
                            var currentEngagement = parseInt($engagementStats.text().match(/\d+/) || 0);
                            $engagementStats.html('<span class="dashicons dashicons-groups"></span> ' + (currentEngagement + 1) + ' engaged');
                        }
                    } else {
                        bpplgShowFeedback($button, 'error');
                    }
                },
                error: function() {
                    bpplgShowFeedback($button, 'error');
                },
                complete: function() {
                    // Re-enable button after a delay
                    setTimeout(function() {
                        $button.prop('disabled', false).removeClass('loading');
                    }, 2000);
                }
            });
        });
        
        // Show feedback for quick comments
        function bpplgShowFeedback($button, type) {
            var feedbackClass = type === 'success' ? 'feedback-success' : 'feedback-error';
            var feedbackText = type === 'success' ? '✓' : '✗';
            
            // Create feedback element
            var $feedback = $('<span class="quick-comment-feedback ' + feedbackClass + '">' + feedbackText + '</span>');
            
            // Position and show feedback
            $button.append($feedback);
            
            // Remove feedback after animation
            setTimeout(function() {
                $feedback.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 1500);
        }
        
        // Reload comments for an activity
        function bpplgReloadComments(activityId) {
            var $activity = $('#activity-' + activityId);
            var $commentsContainer = $activity.find('.activity-comments');
            
            // Show loading state
            $commentsContainer.addClass('loading');
            
            // Use BuddyPress's built-in comment loading if available
            if (typeof bp !== 'undefined' && bp.Nouveau && bp.Nouveau.activity) {
                // For BP Nouveau template pack
                bp.Nouveau.activity.displayComments(activityId);
            } else if (typeof jq !== 'undefined') {
                // For BP Legacy template pack
                jq.post(ajaxurl, {
                    action: 'new_activity_comment',
                    'cookie': bp_get_cookies(),
                    '_wpnonce_new_activity_comment': jq("#_wpnonce_new_activity_comment").val(),
                    'comment_id': 0,
                    'form_id': activityId,
                    'content': ''
                },
                function(response) {
                    $commentsContainer.removeClass('loading');
                    // Parse and update comments if needed
                });
            } else {
                // Fallback: Simple reload by triggering show comments
                $activity.find('.acomment-reply').trigger('click');
                setTimeout(function() {
                    $commentsContainer.removeClass('loading');
                }, 1000);
            }
        }
        
        // Handle view tracking for page mode activities
        if ($('body').hasClass('bp-page-mode-active')) {
            // Track views when activities come into viewport
            var viewedActivities = [];
            
            function trackActivityViews() {
                $('.activity-item').each(function() {
                    var $activity = $(this);
                    var activityId = $activity.attr('id').replace('activity-', '');
                    
                    // Check if activity is in viewport and hasn't been tracked
                    if (isInViewport($activity) && viewedActivities.indexOf(activityId) === -1) {
                        viewedActivities.push(activityId);
                        
                        // Send view tracking request
                        $.post(bpplg.ajax_url, {
                            action: 'bpplg_track_view',
                            activity_id: activityId,
                            nonce: bpplg.nonce
                        });
                    }
                });
            }
            
            // Check if element is in viewport
            function isInViewport($element) {
                var elementTop = $element.offset().top;
                var elementBottom = elementTop + $element.outerHeight();
                var viewportTop = $(window).scrollTop();
                var viewportBottom = viewportTop + $(window).height();
                
                return elementBottom > viewportTop && elementTop < viewportBottom;
            }
            
            // Track views on scroll and initial load
            $(window).on('scroll', _.throttle(trackActivityViews, 250));
            trackActivityViews(); // Initial check
        }
        
        // Enhance quick comment buttons with hover effects
        $(document).on('mouseenter', '.quick-comment-buttons button', function() {
            $(this).addClass('hover');
        }).on('mouseleave', '.quick-comment-buttons button', function() {
            $(this).removeClass('hover');
        });
        
        // Add body class if we're in a page mode group
        if ($('.bp-page-mode-badge').length) {
            $('body').addClass('bp-page-mode-active');
        }
    });
    
})(jQuery);