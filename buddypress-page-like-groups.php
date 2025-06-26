<?php
/**
 * Plugin Name: BuddyPress Page-like Groups
 * Plugin URI: https://wbcomdesigns.com/downloads/buddypress-page-like-groups/
 * Description: Transform BuddyPress groups into Facebook-style Pages where only admins/moderators can post while members can engage through comments, reactions, and discussions
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL2
 * Text Domain: bp-page-like-groups
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.0
 * BuddyPress: 5.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'BP_PLG_VERSION', '1.0.0' );
define( 'BP_PLG_PLUGIN_FILE', __FILE__ );
define( 'BP_PLG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BP_PLG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BP_PLG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation
 */
register_activation_hook( __FILE__, 'bp_page_like_groups_activation' );
function bp_page_like_groups_activation() {
	add_option( 'bp_page_like_groups_version', BP_PLG_VERSION );
	flush_rewrite_rules();
}

/**
 * Plugin deactivation
 */
register_deactivation_hook( __FILE__, 'bp_page_like_groups_deactivation' );
function bp_page_like_groups_deactivation() {
	flush_rewrite_rules();
}

/**
 * Load plugin textdomain
 */
add_action( 'plugins_loaded', 'bp_plg_load_textdomain' );
function bp_plg_load_textdomain() {
	load_plugin_textdomain( 'bp-page-like-groups', false, dirname( BP_PLG_PLUGIN_BASENAME ) . '/languages' );
}

/**
 * Check if BuddyPress is active
 */
function bp_plg_check_buddypress() {
	if ( ! function_exists( 'buddypress' ) ) {
		add_action( 'admin_notices', 'bp_plg_buddypress_required_notice' );
		return false;
	}
	return true;
}

/**
 * Show notice if BuddyPress is not active
 */
function bp_plg_buddypress_required_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'BuddyPress Page-like Groups requires BuddyPress to be installed and activated.', 'bp-page-like-groups' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize plugin
 */
add_action( 'plugins_loaded', 'bp_plg_init', 20 );
function bp_plg_init() {
	if ( ! bp_plg_check_buddypress() ) {
		return;
	}

	// Load required files
	require_once BP_PLG_PLUGIN_DIR . 'includes/class-bp-page-like-groups.php';
	require_once BP_PLG_PLUGIN_DIR . 'includes/functions.php';
	require_once BP_PLG_PLUGIN_DIR . 'includes/hooks.php';
	require_once BP_PLG_PLUGIN_DIR . 'includes/ajax-handlers.php';
	require_once BP_PLG_PLUGIN_DIR . 'includes/settings-integration.php';
	require_once BP_PLG_PLUGIN_DIR . 'includes/feature-implementations.php';
	
	// Load admin files
	if ( is_admin() ) {
		require_once BP_PLG_PLUGIN_DIR . 'admin/admin-functions.php';
		require_once BP_PLG_PLUGIN_DIR . 'admin/group-edit-integration.php';
	}
	
	// Initialize main class
	BP_Page_Like_Groups::get_instance();
}