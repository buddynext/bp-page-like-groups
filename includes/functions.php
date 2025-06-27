<?php
/**
 * Helper functions for BuddyPress Page-like Groups - Simplified
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Check if page mode is enabled for a group
 *
 * @param int $group_id Group ID
 * @return bool
 */
function bp_plg_is_page_mode_enabled( $group_id ) {
	return (bool) groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_ENABLED, true );
}

/**
 * Get page mode settings for a group
 *
 * @param int $group_id Group ID
 * @return array
 */
function bp_plg_get_page_mode_settings( $group_id ) {
	$defaults = array(
		'join_requests_need_approval' => true,
		'allow_member_discussions' => true,
		'enable_quick_comments' => false, // Removed feature - keep for compatibility
		'show_engagement_stats' => false, // Removed feature - keep for compatibility
		'member_can_invite' => false,
		'post_approval_required' => false
	);

	$settings = groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS, true );
	
	return wp_parse_args( $settings, $defaults );
}

/**
 * Check if user can post in group
 *
 * @param int $user_id User ID
 * @param int $group_id Group ID
 * @return bool
 */
function bp_plg_user_can_post( $user_id, $group_id ) {
	// If page mode is not enabled, use default BuddyPress behavior
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return groups_is_user_member( $user_id, $group_id );
	}

	// Site admins can always post
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	$restriction = groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';

	// Only admins can post
	if ( 'admins' === $restriction ) {
		return groups_is_user_admin( $user_id, $group_id );
	}

	// Admins and mods can post
	return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
}

/**
 * Check if group has forums enabled
 *
 * @param int $group_id Group ID (optional, defaults to current group)
 * @return bool
 */
function bp_plg_group_has_forum_enabled( $group_id = 0 ) {
	// If no group ID provided, get current group
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	// Check if bbPress is active
	if ( ! class_exists( 'bbPress' ) ) {
		return false;
	}

	// Check if BuddyPress group forums are enabled
	if ( ! bp_is_active( 'forums' ) ) {
		return false;
	}

	// Check if this specific group has forum enabled
	$group = groups_get_group( $group_id );
	if ( ! $group ) {
		return false;
	}

	// Get forum_id meta
	$forum_id_data = groups_get_groupmeta( $group_id, 'forum_id' );
	
	if ( ! $forum_id_data ) {
		return false;
	}

	// If it's already an array, use it; otherwise unserialize
	if ( is_array( $forum_id_data ) ) {
		$forum_ids = $forum_id_data;
	} else {
		$forum_ids = maybe_unserialize( $forum_id_data );
	}

	// Check if we have valid forum IDs
	if ( ! is_array( $forum_ids ) || empty( $forum_ids ) ) {
		return false;
	}

	// Get the first forum ID
	$forum_id = reset( $forum_ids );

	// Check if the forum exists and is published
	if ( function_exists( 'bbp_get_forum' ) ) {
		$forum = bbp_get_forum( $forum_id );
		if ( ! $forum || 'publish' !== $forum->post_status ) {
			return false;
		}
	}

	return true;
}

/**
 * Get the forum URL for a group
 *
 * @param int $group_id Group ID
 * @return string|false Forum URL or false if forums not enabled
 */
function bp_plg_get_group_forum_url( $group_id = 0 ) {
	if ( ! bp_plg_group_has_forum_enabled( $group_id ) ) {
		return false;
	}

	$group = groups_get_group( $group_id ?: bp_get_current_group_id() );
	if ( ! $group ) {
		return false;
	}

	return bp_get_group_url( $group ) . 'forum/';
}