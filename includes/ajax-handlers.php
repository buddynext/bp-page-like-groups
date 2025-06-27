<?php
/**
 * AJAX handlers for BuddyPress Page-like Groups
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Handle report post AJAX request
 */
add_action( 'wp_ajax_bpplg_report_post', 'bp_plg_ajax_report_post' );
function bp_plg_ajax_report_post() {
	// Check nonce
	check_ajax_referer( 'bpplg-nonce', 'nonce' );

	// Validate inputs
	$activity_id = isset( $_POST['activity_id'] ) ? intval( $_POST['activity_id'] ) : 0;
	$reason = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : '';
	$details = isset( $_POST['details'] ) ? sanitize_textarea_field( $_POST['details'] ) : '';
	$user_id = bp_loggedin_user_id();

	// Validate user is logged in
	if ( ! $user_id ) {
		wp_send_json_error( array( 
			'message' => __( 'You must be logged in to report content.', 'bp-page-like-groups' ) 
		) );
	}

	// Validate activity exists
	$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
	if ( empty( $activity['activities'] ) ) {
		wp_send_json_error( array( 
			'message' => __( 'Activity not found.', 'bp-page-like-groups' ) 
		) );
	}

	// Get existing reports
	$reports = bp_activity_get_meta( $activity_id, 'page_post_reports', true );
	if ( ! is_array( $reports ) ) {
		$reports = array();
	}

	// Check if user already reported this
	foreach ( $reports as $report ) {
		if ( $report['reporter_id'] == $user_id ) {
			wp_send_json_error( array( 
				'message' => __( 'You have already reported this post.', 'bp-page-like-groups' ) 
			) );
		}
	}

	// Create report data
	$report_data = array(
		'reporter_id' => $user_id,
		'reporter_name' => bp_core_get_user_displayname( $user_id ),
		'activity_id' => $activity_id,
		'reason' => $reason,
		'details' => $details,
		'reported_at' => current_time( 'mysql' ),
		'status' => 'pending'
	);

	// Add to reports array
	$reports[] = $report_data;

	// Save reports
	bp_activity_update_meta( $activity_id, 'page_post_reports', $reports );

	// Get activity author and group admins for notification
	$activity_obj = $activity['activities'][0];
	$group_id = $activity_obj->item_id;
	
	// Notify group admins
	if ( $group_id ) {
		$group_admins = groups_get_group_admins( $group_id );
		foreach ( $group_admins as $admin ) {
			bp_notifications_add_notification( array(
				'user_id' => $admin->user_id,
				'item_id' => $activity_id,
				'secondary_item_id' => $user_id,
				'component_name' => 'groups',
				'component_action' => 'page_post_reported',
				'date_notified' => bp_core_current_time(),
				'is_new' => 1,
			) );
		}
	}

	// Hook for other plugins to handle reports
	do_action( 'bp_plg_activity_reported', $report_data, $activity_id, $group_id );

	// Auto-hide if threshold reached
	$report_threshold = apply_filters( 'bp_plg_report_threshold', 5, $group_id );
	if ( count( $reports ) >= $report_threshold ) {
		// Hide the activity
		bp_activity_hide( $activity_id );
		
		// Log moderation action
		bp_activity_update_meta( $activity_id, 'auto_hidden', true );
		bp_activity_update_meta( $activity_id, 'hidden_date', current_time( 'mysql' ) );
	}

	wp_send_json_success( array( 
		'message' => __( 'Thank you for your report. We will review it shortly.', 'bp-page-like-groups' ),
		'report_count' => count( $reports )
	) );
}