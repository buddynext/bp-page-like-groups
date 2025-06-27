<?php
/**
 * Feature Implementations for Page Mode
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
 * 2. FORUM DISCUSSIONS - Allow members to start forum discussions
 */

// Control forum topic creation permissions
add_filter( 'bbp_current_user_can_publish_topics', 'bp_plg_filter_forum_topic_creation', 10, 1 );
add_filter( 'bbp_current_user_can_access_create_topic_form', 'bp_plg_filter_forum_topic_creation', 10, 1 );
function bp_plg_filter_forum_topic_creation( $can_publish ) {
	// Only filter if we're in a group context
	if ( ! bp_is_group() && ! bp_is_current_action( 'forum' ) ) {
		return $can_publish;
	}

	$group_id = bp_get_current_group_id();
	if ( ! $group_id || ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return $can_publish;
	}

	$settings = bp_plg_get_page_mode_settings( $group_id );
	
	// If member discussions are allowed, return default permission
	if ( ! empty( $settings['allow_member_discussions'] ) ) {
		return $can_publish;
	}
	
	// Otherwise, only admins/mods can create topics
	$user_id = bp_loggedin_user_id();
	return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
}

// Add message when members can't create topics
add_action( 'bbp_template_before_single_forum', 'bp_plg_forum_restriction_message' );
function bp_plg_forum_restriction_message() {
	if ( ! bp_is_group() ) {
		return;
	}
	
	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}
	
	$settings = bp_plg_get_page_mode_settings( $group_id );
	$user_id = bp_loggedin_user_id();
	
	// If discussions are not allowed and user is not admin/mod
	if ( empty( $settings['allow_member_discussions'] ) && 
	     ! groups_is_user_admin( $user_id, $group_id ) && 
	     ! groups_is_user_mod( $user_id, $group_id ) ) {
		?>
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'Only administrators and moderators can start new discussions in this forum. You can reply to existing topics.', 'bp-page-like-groups' ); ?></p>
		</div>
		<?php
	}
}

// Hide "Create New Topic" button for restricted users
add_action( 'wp_head', 'bp_plg_hide_forum_create_button' );
function bp_plg_hide_forum_create_button() {
	if ( ! bp_is_group() ) {
		return;
	}
	
	// Check if we're on the forum tab
	if ( ! bp_is_current_action( 'forum' ) ) {
		return;
	}
	
	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return;
	}
	
	$settings = bp_plg_get_page_mode_settings( $group_id );
	$user_id = bp_loggedin_user_id();
	
	if ( empty( $settings['allow_member_discussions'] ) && 
	     ! groups_is_user_admin( $user_id, $group_id ) && 
	     ! groups_is_user_mod( $user_id, $group_id ) ) {
		?>
		<style>
			#new-topic-button,
			.bbp-topic-form,
			#bbp-new-topic {
				display: none !important;
			}
		</style>
		<?php
	}
}

/**
 * 4. PAGE MODE INDICATOR - Visual indicators when Page Mode is active
 */

// Add indicator to activity items posted by admins/mods
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