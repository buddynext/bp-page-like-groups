<?php
/**
 * Feature Implementations for each Page Mode option
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * 1. POSTING RESTRICTIONS - Who can publish posts?
 * Options: Administrators and Moderators / Administrators Only
 */

// Hide post form for non-authorized users
add_action( 'bp_before_group_activity_post_form', 'bp_plg_check_posting_permission', 1 );
function bp_plg_check_posting_permission() {
	if ( ! bp_is_group() ) {
		return;
	}

	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}

	$user_id = bp_loggedin_user_id();
	if ( ! bp_plg_user_can_post( $user_id, $group_id ) ) {
		// Hide the form with CSS
		echo '<style>#whats-new-form { display: none !important; }</style>';
		
		// Show the restriction message (already implemented)
		return;
	}
}

// Filter activity posting via AJAX
add_filter( 'bp_activity_post_pre_validate', 'bp_plg_validate_activity_post', 10, 2 );
function bp_plg_validate_activity_post( $valid, $args ) {
	if ( ! $valid || ! isset( $args['object'] ) || 'groups' !== $args['object'] ) {
		return $valid;
	}

	$group_id = isset( $args['item_id'] ) ? intval( $args['item_id'] ) : 0;
	if ( ! $group_id || ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return $valid;
	}

	$user_id = bp_loggedin_user_id();
	if ( ! bp_plg_user_can_post( $user_id, $group_id ) ) {
		bp_core_add_message( __( 'You do not have permission to post in this group.', 'bp-page-like-groups' ), 'error' );
		return false;
	}

	return $valid;
}

/**
 * 2. QUICK REACTIONS - Enable quick reactions (Like, Love, Thanks)
 * This is already implemented in ajax-handlers.php and templates/activity-meta-buttons.php
 */

// Ensure quick reactions only show when enabled
add_filter( 'bp_plg_show_quick_reactions', 'bp_plg_should_show_quick_reactions', 10, 2 );
function bp_plg_should_show_quick_reactions( $show, $group_id ) {
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return false;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	return ! empty( $settings['enable_quick_comments'] );
}

/**
 * 3. ENGAGEMENT STATS - Show view counts and engagement stats
 */

// Track activity views
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_plg_track_activity_view' );
function bp_plg_track_activity_view() {
	if ( ! bp_is_group() ) {
		return;
	}

	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	if ( empty( $settings['show_engagement_stats'] ) ) {
		return;
	}

	// Track the view
	$activity_id = bp_current_action();
	if ( $activity_id && is_numeric( $activity_id ) ) {
		bp_plg_increment_activity_views( $activity_id );
	}
}

// Track activity list views via AJAX
add_action( 'wp_ajax_bp_plg_track_list_view', 'bp_plg_ajax_track_list_view' );
add_action( 'wp_ajax_nopriv_bp_plg_track_list_view', 'bp_plg_ajax_track_list_view' );
function bp_plg_ajax_track_list_view() {
	$activity_ids = isset( $_POST['activity_ids'] ) ? array_map( 'intval', $_POST['activity_ids'] ) : array();
	$group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;

	if ( ! $group_id || ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		wp_die();
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	if ( empty( $settings['show_engagement_stats'] ) ) {
		wp_die();
	}

	foreach ( $activity_ids as $activity_id ) {
		bp_plg_increment_activity_views( $activity_id );
	}

	wp_send_json_success();
}

/**
 * 4. FORUM DISCUSSIONS - Allow members to start forum discussions
 */

// Control forum posting permissions
add_filter( 'bbp_current_user_can_publish_topics', 'bp_plg_filter_forum_posting', 10, 1 );
function bp_plg_filter_forum_posting( $can_publish ) {
	if ( ! bp_is_group() ) {
		return $can_publish;
	}

	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return $can_publish;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	
	// If member discussions are not allowed, only admins/mods can post
	if ( empty( $settings['allow_member_discussions'] ) ) {
		$user_id = bp_loggedin_user_id();
		return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
	}

	return $can_publish;
}

// Show/hide forum tab based on settings
add_filter( 'bp_group_forum_enable', 'bp_plg_maybe_disable_forum_tab', 10, 2 );
function bp_plg_maybe_disable_forum_tab( $enable, $group ) {
	if ( ! bp_plg_is_page_mode_enabled( $group->id ) ) {
		return $enable;
	}

	$settings = bp_plg_get_page_mode_settings( $group->id );
	
	// If discussions are not allowed and user is not admin/mod, hide forum tab
	if ( empty( $settings['allow_member_discussions'] ) ) {
		$user_id = bp_loggedin_user_id();
		if ( ! groups_is_user_admin( $user_id, $group->id ) && ! groups_is_user_mod( $user_id, $group->id ) ) {
			return false;
		}
	}

	return $enable;
}

/**
 * 5. JOIN REQUESTS - Require approval for all join requests
 */

// Force membership requests even for public groups
add_filter( 'bp_group_auto_join', 'bp_plg_prevent_auto_join', 10, 2 );
function bp_plg_prevent_auto_join( $auto_join, $group ) {
	if ( ! bp_plg_is_page_mode_enabled( $group->id ) ) {
		return $auto_join;
	}

	$settings = bp_plg_get_page_mode_settings( $group->id );
	
	// If approval is required, prevent auto-join
	if ( ! empty( $settings['join_requests_need_approval'] ) ) {
		return false;
	}

	return $auto_join;
}

// Modify join button behavior
add_filter( 'bp_get_group_join_button', 'bp_plg_modify_join_button', 10, 2 );
function bp_plg_modify_join_button( $button, $group ) {
	if ( ! bp_plg_is_page_mode_enabled( $group->id ) ) {
		return $button;
	}

	$settings = bp_plg_get_page_mode_settings( $group->id );
	
	if ( ! empty( $settings['join_requests_need_approval'] ) && 'public' === $group->status ) {
		// Change button to "Request Membership" even for public groups
		if ( isset( $button['id'] ) && 'join_group' === $button['id'] ) {
			$button['link_text'] = __( 'Request Membership', 'bp-page-like-groups' );
			$button['link_title'] = __( 'Request to join this group', 'bp-page-like-groups' );
		}
	}

	return $button;
}

// Handle join requests for public Page Mode groups
add_action( 'groups_join_group', 'bp_plg_handle_join_request', 5, 2 );
function bp_plg_handle_join_request( $group_id, $user_id ) {
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	$group = groups_get_group( $group_id );
	
	if ( ! empty( $settings['join_requests_need_approval'] ) && 'public' === $group->status ) {
		// Remove the user from group
		groups_leave_group( $group_id, $user_id );
		
		// Create a membership request instead
		groups_send_membership_request( $user_id, $group_id );
		
		// Redirect with message
		bp_core_add_message( __( 'Your membership request has been sent to the group administrator.', 'bp-page-like-groups' ) );
		bp_core_redirect( bp_get_group_permalink( $group ) );
		exit;
	}
}

/**
 * 6. MEMBER INVITES - Allow members to invite others
 */

// Control who can send invites
add_filter( 'bp_groups_user_can_send_invites', 'bp_plg_filter_invite_capability', 10, 3 );
function bp_plg_filter_invite_capability( $can_send, $group_id, $user_id ) {
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return $can_send;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	
	// If member invites are disabled, only admins/mods can invite
	if ( empty( $settings['member_can_invite'] ) ) {
		return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
	}

	return $can_send;
}

// Hide invite tab for regular members if disabled
add_action( 'bp_actions', 'bp_plg_maybe_hide_invite_tab' );
function bp_plg_maybe_hide_invite_tab() {
	if ( ! bp_is_group() ) {
		return;
	}

	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	$user_id = bp_loggedin_user_id();
	
	// If member invites are disabled and user is not admin/mod, remove invite nav
	if ( empty( $settings['member_can_invite'] ) && 
	     ! groups_is_user_admin( $user_id, $group_id ) && 
	     ! groups_is_user_mod( $user_id, $group_id ) ) {
		
		bp_core_remove_subnav_item( bp_get_current_group_slug(), 'send-invites' );
	}
}

/**
 * 7. PAGE MODE INDICATOR - Visual indicators when Page Mode is active
 */

// Add indicator to activity items
add_filter( 'bp_get_activity_action', 'bp_plg_add_activity_indicator', 10, 2 );
function bp_plg_add_activity_indicator( $action, $activity ) {
	if ( 'groups' !== $activity->component || ! $activity->item_id ) {
		return $action;
	}

	if ( ! bp_plg_is_page_mode_enabled( $activity->item_id ) ) {
		return $action;
	}

	// Add page icon to admin/mod posts
	$user_id = $activity->user_id;
	if ( groups_is_user_admin( $user_id, $activity->item_id ) || 
	     groups_is_user_mod( $user_id, $activity->item_id ) ) {
		$icon = '<span class="page-post-indicator" title="' . esc_attr__( 'Official Page Post', 'bp-page-like-groups' ) . '">📢</span> ';
		$action = $icon . $action;
	}

	return $action;
}

// Add "Follow" button for Page Mode groups instead of "Join"
add_filter( 'bp_get_group_join_button', 'bp_plg_maybe_add_follow_button', 20, 2 );
function bp_plg_maybe_add_follow_button( $button, $group ) {
	if ( ! bp_plg_is_page_mode_enabled( $group->id ) ) {
		return $button;
	}

	// Optional: Change "Join" to "Follow" for better Page semantics
	if ( isset( $button['link_text'] ) && __( 'Join Group', 'buddypress' ) === $button['link_text'] ) {
		$button['link_text'] = __( 'Follow Page', 'bp-page-like-groups' );
		$button['link_title'] = __( 'Follow this page', 'bp-page-like-groups' );
	}

	return $button;
}