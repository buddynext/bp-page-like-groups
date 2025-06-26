<?php
/**
 * Group Edit Screen Integration
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add meta box to group edit screen
 */
add_action( 'bp_groups_admin_meta_boxes', 'bp_plg_add_group_meta_box' );
function bp_plg_add_group_meta_box() {
	add_meta_box(
		'bp_page_mode_settings',
		__( 'Page Mode Settings', 'bp-page-like-groups' ),
		'bp_plg_render_group_meta_box',
		get_current_screen()->id,
		'normal',
		'high'
	);
}

/**
 * Render meta box content
 */
function bp_plg_render_group_meta_box( $group ) {
	$group_id = $group->id;
	
	// Add nonce field
	wp_nonce_field( 'bp_plg_save_group_settings', 'bp_plg_nonce' );
	
	// Include settings template
	include BP_PLG_PLUGIN_DIR . 'templates/admin/group-settings-fields.php';
}

/**
 * Save settings from meta box
 */
add_action( 'bp_group_admin_edit_after', 'bp_plg_save_meta_box_settings', 5 );
function bp_plg_save_meta_box_settings( $group_id ) {
	// Check if our nonce is set
	if ( ! isset( $_POST['bp_plg_nonce'] ) ) {
		return;
	}

	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['bp_plg_nonce'], 'bp_plg_save_group_settings' ) ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Save page mode enabled status
	$page_mode_enabled = isset( $_POST['page-mode-enabled'] ) && $_POST['page-mode-enabled'] == '1';
	groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_ENABLED, $page_mode_enabled );

	if ( $page_mode_enabled ) {
		// Save posting restriction
		if ( isset( $_POST['posting-restriction'] ) ) {
			$restriction = sanitize_text_field( $_POST['posting-restriction'] );
			groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, $restriction );
		} else {
			// Default to mods if not set
			groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, 'mods' );
		}

		// Save settings
		$settings = array(
			'join_requests_need_approval' => isset( $_POST['settings']['join_requests_need_approval'] ) ? 1 : 0,
			'allow_member_discussions' => isset( $_POST['settings']['allow_member_discussions'] ) ? 1 : 0,
			'enable_quick_comments' => isset( $_POST['settings']['enable_quick_comments'] ) ? 1 : 0,
			'show_engagement_stats' => isset( $_POST['settings']['show_engagement_stats'] ) ? 1 : 0,
			'member_can_invite' => isset( $_POST['settings']['member_can_invite'] ) ? 1 : 0,
			'post_approval_required' => isset( $_POST['settings']['post_approval_required'] ) ? 1 : 0,
		);
		
		groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS, $settings );
	}
}

/**
 * Add JavaScript for admin group edit
 */
add_action( 'admin_footer', 'bp_plg_admin_group_edit_scripts' );
function bp_plg_admin_group_edit_scripts() {
	$screen = get_current_screen();
	
	if ( ! $screen || 'toplevel_page_bp-groups' !== $screen->id ) {
		return;
	}
	
	// Only on edit action
	if ( ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) {
		return;
	}
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Move our meta box after the main settings
			var $metaBox = $('#bp_page_mode_settings');
			var $groupSettings = $('#group-settings');
			
			if ($metaBox.length && $groupSettings.length) {
				$metaBox.insertAfter($groupSettings);
			}
			
			// Handle page mode toggle
			$('#page-mode-enabled').on('change', function() {
				if ($(this).is(':checked')) {
					$('#page-mode-options').slideDown();
					if (!$('input[name="posting-restriction"]:checked').length) {
						$('#posting-restriction-mods').prop('checked', true);
					}
				} else {
					$('#page-mode-options').slideUp();
				}
			});
			
			// Add visual indicator when page mode is enabled
			if ($('#page-mode-enabled').is(':checked')) {
				var $title = $('.wrap > h1');
				if ($title.length && !$title.find('.page-mode-indicator').length) {
					$title.append(' <span class="page-mode-indicator" style="background: #0073aa; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px;"><?php esc_html_e( 'Page Mode Active', 'bp-page-like-groups' ); ?></span>');
				}
			}
		});
	</script>
	<?php
}

/**
 * Add column to groups list table
 */
add_filter( 'bp_groups_list_table_get_columns', 'bp_plg_add_admin_list_column' );
function bp_plg_add_admin_list_column( $columns ) {
	// Add after name column
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[$key] = $value;
		if ( 'name' === $key ) {
			$new_columns['page_mode'] = __( 'Page Mode', 'bp-page-like-groups' );
		}
	}
	return $new_columns;
}

/**
 * Render column content
 */
add_filter( 'bp_groups_admin_get_group_custom_column', 'bp_plg_render_admin_list_column', 10, 3 );
function bp_plg_render_admin_list_column( $value, $column_name, $group ) {
	if ( 'page_mode' !== $column_name ) {
		return $value;
	}

	if ( bp_plg_is_page_mode_enabled( $group->id ) ) {
		$restriction = groups_get_groupmeta( $group->id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';
		
		$value = '<span class="bp-page-mode-active-indicator">';
		$value .= '<span class="dashicons dashicons-megaphone"></span> ';
		
		if ( 'admins' === $restriction ) {
			$value .= __( 'Admins Only', 'bp-page-like-groups' );
		} else {
			$value .= __( 'Admins & Mods', 'bp-page-like-groups' );
		}
		
		$value .= '</span>';
	} else {
		$value = '<span style="color:#999;">—</span>';
	}

	return $value;
}

/**
 * Add quick toggle links
 */
add_filter( 'bp_groups_admin_row_actions', 'bp_plg_add_row_actions', 10, 2 );
function bp_plg_add_row_actions( $actions, $group ) {
	$is_enabled = bp_plg_is_page_mode_enabled( $group->id );
	
	$toggle_url = wp_nonce_url(
		add_query_arg(
			array(
				'page' => 'bp-groups',
				'action' => 'toggle_page_mode',
				'gid' => $group->id,
				'enabled' => $is_enabled ? '0' : '1'
			),
			admin_url( 'admin.php' )
		),
		'toggle_page_mode_' . $group->id
	);
	
	if ( $is_enabled ) {
		$actions['page_mode'] = sprintf(
			'<a href="%s" style="color: #b32d2e;">%s</a>',
			esc_url( $toggle_url ),
			__( 'Disable Page Mode', 'bp-page-like-groups' )
		);
	} else {
		$actions['page_mode'] = sprintf(
			'<a href="%s" style="color: #0073aa;">%s</a>',
			esc_url( $toggle_url ),
			__( 'Enable Page Mode', 'bp-page-like-groups' )
		);
	}
	
	return $actions;
}

/**
 * Handle quick toggle action
 */
add_action( 'bp_groups_admin_load', 'bp_plg_handle_toggle_action', 5 );
function bp_plg_handle_toggle_action() {
	if ( ! isset( $_GET['action'] ) || 'toggle_page_mode' !== $_GET['action'] ) {
		return;
	}
	
	$group_id = isset( $_GET['gid'] ) ? intval( $_GET['gid'] ) : 0;
	$enabled = isset( $_GET['enabled'] ) ? intval( $_GET['enabled'] ) : 0;
	
	if ( ! $group_id ) {
		return;
	}
	
	// Check nonce
	check_admin_referer( 'toggle_page_mode_' . $group_id );
	
	// Update page mode
	groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_ENABLED, $enabled );
	
	// Set default settings when enabling
	if ( $enabled && ! groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS ) ) {
		$default_settings = array(
			'join_requests_need_approval' => true,
			'allow_member_discussions' => true,
			'enable_quick_comments' => true,
			'show_engagement_stats' => true,
			'member_can_invite' => false,
			'post_approval_required' => false
		);
		groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS, $default_settings );
		groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, 'mods' );
	}
	
	// Redirect back with message
	$message = $enabled 
		? __( 'Page Mode enabled successfully.', 'bp-page-like-groups' )
		: __( 'Page Mode disabled successfully.', 'bp-page-like-groups' );
		
	bp_core_redirect( add_query_arg( array(
		'page' => 'bp-groups',
		'updated' => 1,
		'update_message' => urlencode( $message )
	), admin_url( 'admin.php' ) ) );
}