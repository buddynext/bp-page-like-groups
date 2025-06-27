<?php
/**
 * Additional hooks and filters
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add body class for page mode groups
 */
add_filter( 'body_class', 'bp_plg_add_body_class' );
function bp_plg_add_body_class( $classes ) {
	if ( bp_is_group() && bp_plg_is_page_mode_enabled( bp_get_current_group_id() ) ) {
		$classes[] = 'bp-page-mode-active';
		$classes[] = 'bp-page-like-group';
	}
	return $classes;
}

/**
 * Filter group activity query for page mode groups
 */
add_filter( 'bp_activity_get_user_join_filter', 'bp_plg_filter_activity_query', 10, 2 );
function bp_plg_filter_activity_query( $sql, $args ) {
	// Only filter for group activity
	if ( ! bp_is_group() || empty( $args['filter']['object'] ) || 'groups' !== $args['filter']['object'] ) {
		return $sql;
	}

	$group_id = bp_get_current_group_id();
	if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
		return $sql;
	}

	// Add custom filtering for page mode groups if needed
	return apply_filters( 'bp_plg_activity_query', $sql, $args, $group_id );
}

/**
 * Modify activity comment settings for page mode
 */
add_filter( 'bp_activity_can_comment_reply', 'bp_plg_filter_comment_reply', 10, 2 );
function bp_plg_filter_comment_reply( $can_comment, $comment ) {
	// Allow comment replies in page mode groups
	if ( bp_is_group() && bp_plg_is_page_mode_enabled( bp_get_current_group_id() ) ) {
		return bp_is_user_logged_in();
	}
	return $can_comment;
}

/**
 * Filter notification settings for page mode groups
 */
add_filter( 'bp_groups_notification_new_update_action', 'bp_plg_filter_notification_action', 10, 2 );
function bp_plg_filter_notification_action( $action, $group_id ) {
	if ( bp_plg_is_page_mode_enabled( $group_id ) ) {
		$action['text'] = __( 'New announcement in', 'bp-page-like-groups' );
	}
	return $action;
}