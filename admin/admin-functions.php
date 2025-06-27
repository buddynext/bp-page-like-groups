<?php
/**
 * Admin functions
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add admin menu
 */
add_action( 'admin_menu', 'bp_plg_add_admin_menu', 99 );
function bp_plg_add_admin_menu() {
	add_submenu_page(
		'bp-groups',
		__( 'Page-like Groups', 'bp-page-like-groups' ),
		__( 'Page Mode', 'bp-page-like-groups' ),
		'manage_options',
		'bp-page-like-groups',
		'bp_plg_admin_page'
	);
}

/**
 * Add Page Mode settings to group edit screen
 */
add_action( 'bp_groups_admin_edit_group_settings', 'bp_plg_admin_group_settings' );
function bp_plg_admin_group_settings( $group_id ) {
	?>
	<h2><?php esc_html_e( 'Page Mode Settings', 'bp-page-like-groups' ); ?></h2>
	<?php
	// Make sure $group_id is available in the included template
	$group_id = $group_id;
	// Include the same settings template used in frontend
	include BP_PLG_PLUGIN_DIR . 'templates/admin/group-settings-fields.php';
}

/**
 * Save Page Mode settings from admin
 */
add_action( 'bp_group_admin_edit_after', 'bp_plg_admin_save_group_settings', 10, 1 );
function bp_plg_admin_save_group_settings( $group_id ) {
	// Check if we're saving
	if ( ! isset( $_POST['save'] ) ) {
		return;
	}

	// Check nonce
	check_admin_referer( 'groups_edit_save_' . $group_id );

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
		}

		// Save settings
		$settings = array();
		if ( isset( $_POST['settings'] ) ) {
			foreach ( $_POST['settings'] as $key => $value ) {
				$settings[ sanitize_key( $key ) ] = absint( $value );
			}
		}
		groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS, $settings );
	}

	// Add admin notice
	bp_core_add_admin_notice( __( 'Page Mode settings saved successfully.', 'bp-page-like-groups' ), 'success' );
}

/**
 * Add quick edit action to groups list
 */
add_filter( 'bp_groups_management_row_actions', 'bp_plg_add_group_row_actions', 10, 2 );
function bp_plg_add_group_row_actions( $actions, $group ) {
	$page_mode = bp_plg_is_page_mode_enabled( $group->id );
	
	if ( $page_mode ) {
		$actions['page_mode'] = sprintf(
			'<span style="color: #0073aa;">%s</span>',
			__( 'Page Mode Active', 'bp-page-like-groups' )
		);
	}
	
	return $actions;
}

/**
 * Add bulk actions to groups list
 */
add_filter( 'bp_groups_management_bulk_actions', 'bp_plg_add_bulk_actions' );
function bp_plg_add_bulk_actions( $actions ) {
	$actions['enable_page_mode'] = __( 'Enable Page Mode', 'bp-page-like-groups' );
	$actions['disable_page_mode'] = __( 'Disable Page Mode', 'bp-page-like-groups' );
	return $actions;
}

/**
 * Handle bulk actions
 */
add_action( 'bp_groups_admin_load', 'bp_plg_handle_bulk_actions' );
function bp_plg_handle_bulk_actions() {
	if ( empty( $_REQUEST['action'] ) || empty( $_REQUEST['gid'] ) ) {
		return;
	}

	$action = $_REQUEST['action'];
	$group_ids = wp_parse_id_list( $_REQUEST['gid'] );

	if ( ! in_array( $action, array( 'enable_page_mode', 'disable_page_mode' ), true ) ) {
		return;
	}

	// Check nonce
	check_admin_referer( 'bulk-groups' );

	$updated = 0;
	$value = ( 'enable_page_mode' === $action ) ? 1 : 0;

	foreach ( $group_ids as $group_id ) {
		groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_ENABLED, $value );
		
		// Set default settings when enabling
		if ( $value && ! groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS ) ) {
			$default_settings = array(
				'allow_member_discussions' => true,
			);
			groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_SETTINGS, $default_settings );
			groups_update_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, 'mods' );
		}
		
		$updated++;
	}

	$message = sprintf(
		_n( 'Page Mode updated for %d group.', 'Page Mode updated for %d groups.', $updated, 'bp-page-like-groups' ),
		$updated
	);

	bp_core_redirect( add_query_arg( array(
		'page' => 'bp-groups',
		'updated' => $updated,
		'update_message' => urlencode( $message )
	), admin_url( 'admin.php' ) ) );
}

/**
 * Add settings link to plugins page
 */
add_filter( 'plugin_action_links_' . BP_PLG_PLUGIN_BASENAME, 'bp_plg_plugin_action_links' );
function bp_plg_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'admin.php?page=bp-page-like-groups' ),
		__( 'Settings', 'bp-page-like-groups' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Admin page content
 */
function bp_plg_admin_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BuddyPress Page-like Groups', 'bp-page-like-groups' ); ?></h1>
		
		<?php
		// Show admin notices
		if ( isset( $_GET['updated'] ) && isset( $_GET['update_message'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( urldecode( $_GET['update_message'] ) ); ?></p>
			</div>
			<?php
		}
		?>
		
		<div class="bp-plg-admin-content">
			<div class="bp-plg-admin-header">
				<h2><?php esc_html_e( 'Overview', 'bp-page-like-groups' ); ?></h2>
				<p><?php esc_html_e( 'Transform BuddyPress groups into Facebook-style Pages where only administrators and moderators can create posts.', 'bp-page-like-groups' ); ?></p>
			</div>
			
			<div class="bp-plg-admin-section">
				<h3><?php esc_html_e( 'Quick Stats', 'bp-page-like-groups' ); ?></h3>
				<?php bp_plg_admin_stats(); ?>
			</div>
			
			<div class="bp-plg-admin-section">
				<h3><?php esc_html_e( 'Groups with Page Mode Enabled', 'bp-page-like-groups' ); ?></h3>
				<?php bp_plg_admin_groups_list(); ?>
			</div>
			
			<div class="bp-plg-admin-section">
				<h3><?php esc_html_e( 'How to Use', 'bp-page-like-groups' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Go to any group\'s settings page (frontend or admin)', 'bp-page-like-groups' ); ?></li>
					<li><?php esc_html_e( 'Enable "Page Mode" in the settings', 'bp-page-like-groups' ); ?></li>
					<li><?php esc_html_e( 'Choose who can post (Admins only or Admins & Moderators)', 'bp-page-like-groups' ); ?></li>
					<li><?php esc_html_e( 'Configure member engagement options like forum discussions', 'bp-page-like-groups' ); ?></li>
				</ol>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Display groups with page mode enabled
 */
function bp_plg_admin_groups_list() {
	global $wpdb;
	$bp = buddypress();
	
	$groups = $wpdb->get_results( $wpdb->prepare(
		"SELECT g.*, gm.meta_value as page_mode 
		FROM {$bp->groups->table_name} g
		INNER JOIN {$bp->groups->table_name_groupmeta} gm ON g.id = gm.group_id
		WHERE gm.meta_key = %s AND gm.meta_value = '1'
		ORDER BY g.date_created DESC
		LIMIT 20",
		BP_Page_Like_Groups::META_KEY_ENABLED
	) );
	
	if ( $groups ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Group Name', 'bp-page-like-groups' ); ?></th>
					<th><?php esc_html_e( 'Posting Restriction', 'bp-page-like-groups' ); ?></th>
					<th><?php esc_html_e( 'Members', 'bp-page-like-groups' ); ?></th>
					<th><?php esc_html_e( 'Created', 'bp-page-like-groups' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'bp-page-like-groups' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $groups as $group ) : 
					$group_obj = groups_get_group( $group->id );
					$restriction = groups_get_groupmeta( $group->id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( bp_get_group_url( $group_obj ) ); ?>"><?php echo esc_html( $group->name ); ?></a></strong>
						</td>
						<td>
							<?php echo 'admins' === $restriction ? esc_html__( 'Admins Only', 'bp-page-like-groups' ) : esc_html__( 'Admins & Mods', 'bp-page-like-groups' ); ?>
						</td>
						<td><?php echo groups_get_total_member_count( $group->id ); ?></td>
						<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $group->date_created ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( bp_get_group_admin_permalink( $group_obj ) ); ?>"><?php esc_html_e( 'Edit', 'bp-page-like-groups' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	} else {
		echo '<p>' . esc_html__( 'No groups have page mode enabled yet.', 'bp-page-like-groups' ) . '</p>';
	}
}

/**
 * Display admin stats
 */
function bp_plg_admin_stats() {
	global $wpdb;
	$bp = buddypress();
	
	// Count groups with page mode
	$page_groups = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT group_id) 
		FROM {$bp->groups->table_name_groupmeta}
		WHERE meta_key = %s AND meta_value = '1'",
		BP_Page_Like_Groups::META_KEY_ENABLED
	) );
	
	// Count by restriction type
	$admin_only = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT gm1.group_id) 
		FROM {$bp->groups->table_name_groupmeta} gm1
		JOIN {$bp->groups->table_name_groupmeta} gm2 ON gm1.group_id = gm2.group_id
		WHERE gm1.meta_key = %s AND gm1.meta_value = '1'
		AND gm2.meta_key = %s AND gm2.meta_value = 'admins'",
		BP_Page_Like_Groups::META_KEY_ENABLED,
		BP_Page_Like_Groups::META_KEY_RESTRICTION
	) );
	
	$mods_allowed = $page_groups - $admin_only;
	
	?>
	<div class="bp-plg-stats">
		<div class="stat-box">
			<h4><?php esc_html_e( 'Total Page Groups', 'bp-page-like-groups' ); ?></h4>
			<p class="stat-number"><?php echo intval( $page_groups ); ?></p>
		</div>
		<div class="stat-box">
			<h4><?php esc_html_e( 'Admin-Only Posting', 'bp-page-like-groups' ); ?></h4>
			<p class="stat-number"><?php echo intval( $admin_only ); ?></p>
		</div>
		<div class="stat-box">
			<h4><?php esc_html_e( 'Admin & Mod Posting', 'bp-page-like-groups' ); ?></h4>
			<p class="stat-number"><?php echo intval( $mods_allowed ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Enqueue admin styles
 */
add_action( 'admin_enqueue_scripts', 'bp_plg_enqueue_admin_styles' );
function bp_plg_enqueue_admin_styles( $hook ) {
	// Only load on our pages
	if ( ! in_array( $hook, array( 'buddypress_page_bp-page-like-groups', 'toplevel_page_bp-groups' ), true ) ) {
		return;
	}
	
	wp_add_inline_style( 'bp-admin-common', '
		/* Page Mode Admin Styles */
		.bp-plg-admin-content {
			max-width: 1200px;
			margin-top: 20px;
		}
		.bp-plg-admin-header {
			background: #fff;
			padding: 20px;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			margin-bottom: 20px;
		}
		.bp-plg-admin-section {
			background: #fff;
			padding: 20px;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			margin-bottom: 20px;
		}
		.bp-plg-stats {
			display: flex;
			gap: 20px;
			margin-top: 20px;
			flex-wrap: wrap;
		}
		.stat-box {
			background: #f0f0f1;
			padding: 20px;
			border-radius: 5px;
			min-width: 150px;
			text-align: center;
			flex: 1;
			border: 1px solid #dcdcde;
		}
		.stat-box h4 {
			margin: 0 0 10px 0;
			color: #1d2327;
			font-size: 14px;
		}
		.stat-number {
			font-size: 2.5em;
			font-weight: 600;
			color: #2271b1;
			margin: 0;
			line-height: 1;
		}
		
		/* Group edit page styles */
		.groups-php .page-mode-section,
		.groups-php .bp-plg-page-mode-section {
			background: #f6f7f7;
			padding: 15px;
			margin: 15px 0;
			border: 1px solid #dcdcde;
			border-radius: 4px;
		}
		.groups-php .page-mode-section h4,
		.groups-php .bp-plg-page-mode-section h4 {
			margin-top: 0;
			margin-bottom: 15px;
			color: #1d2327;
		}
		.groups-php .page-mode-section label,
		.groups-php .bp-plg-page-mode-section label {
			display: block;
			margin-bottom: 10px;
		}
		.groups-php .page-mode-section .description,
		.groups-php .bp-plg-page-mode-section .bp-plg-description {
			display: block;
			margin-top: 5px;
			color: #646970;
			font-size: 13px;
			font-style: normal;
		}
		.groups-php .page-mode-description,
		.groups-php .bp-plg-page-mode-description {
			background: #f0f6fc;
			border: 1px solid #72aee6;
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 4px;
		}
		.groups-php #page-mode-options,
		.groups-php #bp-plg-page-mode-options {
			margin-left: 20px;
			margin-top: 15px;
		}
		
		/* Groups list table */
		.wp-list-table .column-page_mode {
			width: 120px;
		}
		.bp-page-mode-active-indicator {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			color: #0073aa;
			font-weight: 500;
		}
	' );
}