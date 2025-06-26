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

/**
 * Main plugin class
 */
class BP_Page_Like_Groups {

	/**
	 * Plugin version
	 */
	const VERSION = '1.0.0';

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Meta keys
	 */
	const META_KEY_ENABLED = '_group_page_mode_enabled';
	const META_KEY_RESTRICTION = '_group_posting_restriction';
	const META_KEY_SETTINGS = '_group_page_mode_settings';

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Check if BuddyPress is active
		if ( ! function_exists( 'buddypress' ) ) {
			add_action( 'admin_notices', array( $this, 'buddypress_required_notice' ) );
			return;
		}

		// Hook into BuddyPress
		add_action( 'bp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Load text domain
		load_plugin_textdomain( 'bp-page-like-groups', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Add hooks
		$this->add_hooks();
	}

	/**
	 * Add all hooks and filters
	 */
	private function add_hooks() {
		// Group creation/settings form
		add_action( 'bp_after_group_settings_creation_step', array( $this, 'add_page_mode_fields' ) );
		add_action( 'bp_after_group_settings_admin', array( $this, 'add_page_mode_fields' ) );

		// Save settings
		add_action( 'groups_create_group_step_save_group-settings', array( $this, 'save_page_mode_settings' ) );
		add_action( 'groups_group_settings_edited', array( $this, 'save_page_mode_settings' ) );

		// Filter activity post form display
		add_filter( 'bp_activity_can_post', array( $this, 'check_group_posting_permission' ) );

		// Modify activity post form
		add_action( 'bp_before_group_activity_post_form', array( $this, 'maybe_show_restriction_message' ) );

		// Filter AJAX posting capability
		add_filter( 'bp_activity_user_can_post', array( $this, 'filter_ajax_posting_capability' ), 10, 2 );

		// Add custom CSS and JS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Group header badge
		add_action( 'bp_group_header_meta', array( $this, 'add_page_mode_badge' ) );

		// Activity meta buttons (for engagement features)
		add_action( 'bp_activity_entry_meta', array( $this, 'add_activity_meta_buttons' ) );

		// Member request moderation
		add_filter( 'bp_groups_auto_accept_membership_requests', array( $this, 'maybe_moderate_join_requests' ), 10, 2 );

		// Admin features
		if ( is_admin() ) {
			add_filter( 'bp_groups_list_table_get_columns', array( $this, 'add_admin_column' ) );
			add_filter( 'bp_groups_admin_get_group_custom_column', array( $this, 'render_admin_column' ), 10, 3 );
		}

		// AJAX handlers
		add_action( 'wp_ajax_bpplg_quick_comment', array( $this, 'ajax_quick_comment' ) );
		add_action( 'wp_ajax_bpplg_report_post', array( $this, 'ajax_report_post' ) );
		
		// Integrations
		$this->setup_integrations();
	}

	/**
	 * Setup integrations with other Wbcom plugins
	 */
	private function setup_integrations() {
		// Integration with Wbcom's BuddyPress Moderation
		add_filter( 'bp_moderation_group_settings', array( $this, 'add_moderation_settings' ) );

		// Integration with Wbcom's BuddyPress Polls
		add_filter( 'bp_polls_group_support', array( $this, 'enable_polls_for_page_groups' ) );

		// Integration with Wbcom's BuddyPress Reactions
		add_filter( 'bp_reactions_group_support', array( $this, 'customize_reactions_for_pages' ) );
	}

	/**
	 * Check if page mode is enabled for a group
	 */
	public function is_page_mode_enabled( $group_id ) {
		return (bool) groups_get_groupmeta( $group_id, self::META_KEY_ENABLED, true );
	}

	/**
	 * Get page mode settings for a group
	 */
	public function get_page_mode_settings( $group_id ) {
		$defaults = array(
			'join_requests_need_approval' => true,
			'allow_member_discussions' => true,
			'enable_quick_comments' => true,
			'show_engagement_stats' => true,
			'member_can_invite' => false,
			'post_approval_required' => false
		);

		$settings = groups_get_groupmeta( $group_id, self::META_KEY_SETTINGS, true );
		
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Check if user can post in group
	 */
	public function bp_group_user_can_post( $user_id, $group_id ) {
		// If page mode is not enabled, use default BuddyPress behavior
		if ( ! $this->is_page_mode_enabled( $group_id ) ) {
			return groups_is_user_member( $user_id, $group_id );
		}

		// Site admins can always post
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$restriction = groups_get_groupmeta( $group_id, self::META_KEY_RESTRICTION, true ) ?: 'mods';

		// Only admins can post
		if ( 'admins' === $restriction ) {
			return groups_is_user_admin( $user_id, $group_id );
		}

		// Admins and mods can post
		return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
	}

	/**
	 * Add page mode fields to group settings
	 */
	public function add_page_mode_fields() {
		$group_id = bp_get_current_group_id();
		$page_mode_enabled = $this->is_page_mode_enabled( $group_id );
		$restriction = groups_get_groupmeta( $group_id, self::META_KEY_RESTRICTION, true ) ?: 'mods';
		$settings = $this->get_page_mode_settings( $group_id );
		?>
		<fieldset class="group-create-page-mode radio">
			<legend><?php esc_html_e( 'Page Mode Settings', 'bp-page-like-groups' ); ?></legend>
			
			<div class="page-mode-description">
				<p><?php esc_html_e( 'Page Mode transforms your group into a Facebook Page-style community where only administrators and moderators can create posts, while all members can engage through comments and reactions.', 'bp-page-like-groups' ); ?></p>
				<p><strong><?php esc_html_e( 'Perfect for:', 'bp-page-like-groups' ); ?></strong> <?php esc_html_e( 'Announcements, News Updates, Brand Communities, Official Pages', 'bp-page-like-groups' ); ?></p>
			</div>

			<label for="page-mode-enabled" class="page-mode-main-toggle">
				<input type="checkbox" name="page-mode-enabled" id="page-mode-enabled" value="1" <?php checked( $page_mode_enabled, true ); ?> />
				<strong><?php esc_html_e( 'Enable Page Mode', 'bp-page-like-groups' ); ?></strong>
			</label>

			<div id="page-mode-options" style="<?php echo $page_mode_enabled ? '' : 'display:none;'; ?>">
				
				<!-- Posting Permissions -->
				<div class="page-mode-section">
					<h4><?php esc_html_e( 'Who can publish posts?', 'bp-page-like-groups' ); ?></h4>
					
					<label for="posting-restriction-mods">
						<input type="radio" name="posting-restriction" id="posting-restriction-mods" value="mods" <?php checked( $restriction, 'mods' ); ?> />
						<?php esc_html_e( 'Administrators and Moderators', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Both admins and mods can publish content', 'bp-page-like-groups' ); ?></span>
					</label>

					<label for="posting-restriction-admins">
						<input type="radio" name="posting-restriction" id="posting-restriction-admins" value="admins" <?php checked( $restriction, 'admins' ); ?> />
						<?php esc_html_e( 'Administrators Only', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Only group admins can publish content', 'bp-page-like-groups' ); ?></span>
					</label>
				</div>

				<!-- Member Engagement Settings -->
				<div class="page-mode-section">
					<h4><?php esc_html_e( 'Member Engagement', 'bp-page-like-groups' ); ?></h4>
					
					<label>
						<input type="checkbox" name="settings[allow_member_discussions]" value="1" <?php checked( $settings['allow_member_discussions'], true ); ?> />
						<?php esc_html_e( 'Allow Member Discussions', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Members can start discussions in a separate tab (not in main feed)', 'bp-page-like-groups' ); ?></span>
					</label>

					<label>
						<input type="checkbox" name="settings[enable_quick_comments]" value="1" <?php checked( $settings['enable_quick_comments'], true ); ?> />
						<?php esc_html_e( 'Enable Quick Comments', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Show quick comment buttons (Like, Thanks, etc.)', 'bp-page-like-groups' ); ?></span>
					</label>

					<label>
						<input type="checkbox" name="settings[show_engagement_stats]" value="1" <?php checked( $settings['show_engagement_stats'], true ); ?> />
						<?php esc_html_e( 'Show Engagement Stats', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Display view counts and engagement metrics on posts', 'bp-page-like-groups' ); ?></span>
					</label>
				</div>

				<!-- Membership Settings -->
				<div class="page-mode-section">
					<h4><?php esc_html_e( 'Membership Control', 'bp-page-like-groups' ); ?></h4>
					
					<label>
						<input type="checkbox" name="settings[join_requests_need_approval]" value="1" <?php checked( $settings['join_requests_need_approval'], true ); ?> />
						<?php esc_html_e( 'All join requests need approval', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Even for public groups, members must be approved', 'bp-page-like-groups' ); ?></span>
					</label>

					<label>
						<input type="checkbox" name="settings[member_can_invite]" value="1" <?php checked( $settings['member_can_invite'], false ); ?> />
						<?php esc_html_e( 'Members can invite others', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Allow regular members to invite friends', 'bp-page-like-groups' ); ?></span>
					</label>
				</div>

				<!-- Content Moderation -->
				<div class="page-mode-section">
					<h4><?php esc_html_e( 'Content Moderation', 'bp-page-like-groups' ); ?></h4>
					
					<label>
						<input type="checkbox" name="settings[post_approval_required]" value="1" <?php checked( $settings['post_approval_required'], true ); ?> />
						<?php esc_html_e( 'Moderator posts need admin approval', 'bp-page-like-groups' ); ?>
						<span class="description"><?php esc_html_e( 'Require admin approval for moderator posts', 'bp-page-like-groups' ); ?></span>
					</label>
				</div>

			</div>
		</fieldset>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
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
			});
		</script>
		<?php
	}

	/**
	 * Save page mode settings
	 */
	public function save_page_mode_settings( $group_id ) {
		// Verify permissions
		if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
			return;
		}

		// Save page mode enabled status
		$page_mode_enabled = isset( $_POST['page-mode-enabled'] ) && $_POST['page-mode-enabled'] == '1';
		groups_update_groupmeta( $group_id, self::META_KEY_ENABLED, $page_mode_enabled );

		if ( $page_mode_enabled ) {
			// Save posting restriction
			if ( isset( $_POST['posting-restriction'] ) ) {
				$restriction = sanitize_text_field( $_POST['posting-restriction'] );
				groups_update_groupmeta( $group_id, self::META_KEY_RESTRICTION, $restriction );
			}

			// Save settings
			$settings = array();
			if ( isset( $_POST['settings'] ) ) {
				foreach ( $_POST['settings'] as $key => $value ) {
					$settings[ sanitize_key( $key ) ] = absint( $value );
				}
			}
			groups_update_groupmeta( $group_id, self::META_KEY_SETTINGS, $settings );
		}
	}

	/**
	 * Check if user can post in current group
	 */
	public function check_group_posting_permission( $can_post ) {
		if ( ! bp_is_group() ) {
			return $can_post;
		}

		$group_id = bp_get_current_group_id();
		$user_id = bp_loggedin_user_id();

		return $this->bp_group_user_can_post( $user_id, $group_id );
	}

	/**
	 * Filter AJAX posting capability
	 */
	public function filter_ajax_posting_capability( $can_post, $user_id ) {
		if ( ! isset( $_POST['object'] ) || 'groups' !== $_POST['object'] ) {
			return $can_post;
		}

		$group_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
		
		if ( ! $group_id ) {
			return $can_post;
		}

		return $this->bp_group_user_can_post( $user_id, $group_id );
	}

	/**
	 * Show restriction message if user cannot post
	 */
	public function maybe_show_restriction_message() {
		if ( ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_current_group_id();
		$user_id = bp_loggedin_user_id();

		if ( ! $this->is_page_mode_enabled( $group_id ) ) {
			return;
		}

		if ( $this->bp_group_user_can_post( $user_id, $group_id ) ) {
			return;
		}

		$settings = $this->get_page_mode_settings( $group_id );
		?>
		<div class="bp-page-mode-member-actions">
			<div class="bp-feedback info">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'This is a Page-style group. Only administrators can create posts, but you can engage by commenting and reacting to posts.', 'bp-page-like-groups' ); ?></p>
			</div>

			<?php if ( $settings['allow_member_discussions'] ) : ?>
				<div class="member-discussion-prompt">
					<p><?php esc_html_e( 'Want to start a discussion?', 'bp-page-like-groups' ); ?></p>
					<a href="<?php echo esc_url( bp_get_group_url( groups_get_current_group() ) . 'discussions/' ); ?>" class="button">
						<?php esc_html_e( 'Go to Discussions', 'bp-page-like-groups' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php

		// Hide the post form
		echo '<style>#whats-new-form { display: none !important; }</style>';
	}

	/**
	 * Add page mode badge to group header
	 */
	public function add_page_mode_badge() {
		if ( ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_current_group_id();
		
		if ( ! $this->is_page_mode_enabled( $group_id ) ) {
			return;
		}
		?>
		<span class="bp-page-mode-badge" title="<?php esc_attr_e( 'This is a Page-style group where only admins/mods can post', 'bp-page-like-groups' ); ?>">
			<span class="dashicons dashicons-megaphone"></span>
			<?php esc_html_e( 'Page', 'bp-page-like-groups' ); ?>
		</span>
		<?php
	}

	/**
	 * Add activity meta buttons for engagement
	 */
	public function add_activity_meta_buttons() {
		if ( ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_current_group_id();
		
		if ( ! $this->is_page_mode_enabled( $group_id ) ) {
			return;
		}

		$settings = $this->get_page_mode_settings( $group_id );

		// Show engagement stats
		if ( $settings['show_engagement_stats'] ) {
			$activity_id = bp_get_activity_id();
			$views = (int) bp_activity_get_meta( $activity_id, 'page_post_views', true );
			$engagement = (int) bp_activity_get_meta( $activity_id, 'page_post_engagement', true );
			?>
			<span class="page-engagement-stats">
				<span class="views">
					<span class="dashicons dashicons-visibility"></span>
					<?php printf( esc_html__( '%d views', 'bp-page-like-groups' ), $views ); ?>
				</span>
				<?php if ( $engagement > 0 ) : ?>
					<span class="engagement">
						<span class="dashicons dashicons-groups"></span>
						<?php printf( esc_html__( '%d engaged', 'bp-page-like-groups' ), $engagement ); ?>
					</span>
				<?php endif; ?>
			</span>
			<?php
		}

		// Quick comment buttons
		if ( $settings['enable_quick_comments'] && bp_activity_can_comment() ) : ?>
			<div class="quick-comment-buttons">
				<button class="quick-comment" data-comment="👍"><?php esc_html_e( 'Like', 'bp-page-like-groups' ); ?></button>
				<button class="quick-comment" data-comment="❤️"><?php esc_html_e( 'Love', 'bp-page-like-groups' ); ?></button>
				<button class="quick-comment" data-comment="🙏"><?php esc_html_e( 'Thanks', 'bp-page-like-groups' ); ?></button>
			</div>
		<?php endif;
	}

	/**
	 * Maybe moderate join requests for page groups
	 */
	public function maybe_moderate_join_requests( $auto_accept, $group ) {
		if ( ! $this->is_page_mode_enabled( $group->id ) ) {
			return $auto_accept;
		}

		$settings = $this->get_page_mode_settings( $group->id );
		
		// If setting is enabled, don't auto-accept
		if ( $settings['join_requests_need_approval'] ) {
			return false;
		}

		return $auto_accept;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! bp_is_group() && ! bp_is_groups_component() ) {
			return;
		}

		wp_enqueue_style( 
			'bp-page-like-groups', 
			plugin_dir_url( __FILE__ ) . 'assets/style.css', 
			array(), 
			self::VERSION 
		);

		wp_enqueue_script( 
			'bp-page-like-groups', 
			plugin_dir_url( __FILE__ ) . 'assets/script.js', 
			array( 'jquery' ), 
			self::VERSION, 
			true 
		);

		wp_localize_script( 'bp-page-like-groups', 'bpplg', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'bpplg-nonce' ),
		) );

		// Inline CSS for basic styling
		wp_add_inline_style( 'bp-page-like-groups', '
			.bp-page-mode-badge {
				display: inline-flex;
				align-items: center;
				gap: 5px;
				padding: 5px 12px;
				margin-left: 10px;
				background: #0073aa;
				color: #fff;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
				border-radius: 3px;
			}
			.bp-page-mode-badge .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}
			.page-mode-description {
				background: #f1f1f1;
				padding: 15px;
				margin-bottom: 20px;
				border-radius: 5px;
			}
			.page-mode-section {
				background: #fafafa;
				padding: 15px;
				margin: 15px 0;
				border-radius: 3px;
				border: 1px solid #e5e5e5;
			}
			.page-mode-section h4 {
				margin-top: 0;
				margin-bottom: 15px;
				color: #23282d;
			}
			.page-mode-section label {
				display: block;
				margin-bottom: 10px;
			}
			.page-mode-section .description {
				display: block;
				margin-top: 5px;
				color: #666;
				font-size: 13px;
				font-style: italic;
			}
			.bp-page-mode-member-actions {
				margin: 20px 0;
			}
			.member-discussion-prompt {
				text-align: center;
				padding: 20px;
				background: #f9f9f9;
				border-radius: 5px;
				margin-top: 15px;
			}
			.page-engagement-stats {
				display: inline-flex;
				gap: 15px;
				margin-left: 15px;
				color: #666;
				font-size: 13px;
			}
			.page-engagement-stats span {
				display: inline-flex;
				align-items: center;
				gap: 3px;
			}
			.quick-comment-buttons {
				display: inline-flex;
				gap: 10px;
				margin-left: 15px;
			}
			.quick-comment-buttons button {
				padding: 3px 10px;
				font-size: 12px;
				border: 1px solid #ddd;
				background: #f7f7f7;
				cursor: pointer;
				border-radius: 3px;
			}
			.quick-comment-buttons button:hover {
				background: #fff;
				border-color: #999;
			}
		' );
	}

	/**
	 * Add admin column
	 */
	public function add_admin_column( $columns ) {
		$columns['page_mode'] = __( 'Page Mode', 'bp-page-like-groups' );
		return $columns;
	}

	/**
	 * Render admin column
	 */
	public function render_admin_column( $value, $column_name, $group ) {
		if ( 'page_mode' !== $column_name ) {
			return $value;
		}

		if ( $this->is_page_mode_enabled( $group->id ) ) {
			$restriction = groups_get_groupmeta( $group->id, self::META_KEY_RESTRICTION, true ) ?: 'mods';
			$icon = '<span class="dashicons dashicons-megaphone" style="color:#0073aa;"></span> ';
			
			switch ( $restriction ) {
				case 'admins':
					$value = $icon . __( 'Admins Only', 'bp-page-like-groups' );
					break;
				case 'mods':
				default:
					$value = $icon . __( 'Admins & Mods', 'bp-page-like-groups' );
					break;
			}
		} else {
			$value = '<span style="color:#ccc;">—</span>';
		}

		return $value;
	}

	/**
	 * AJAX handler for quick comments
	 */
	public function ajax_quick_comment() {
		check_ajax_referer( 'bpplg-nonce', 'nonce' );

		$activity_id = intval( $_POST['activity_id'] );
		$comment_text = sanitize_text_field( $_POST['comment'] );

		if ( ! bp_activity_can_comment() ) {
			wp_die( -1 );
		}

		$comment_id = bp_activity_new_comment( array(
			'activity_id' => $activity_id,
			'content' => $comment_text,
			'parent_id' => false
		) );

		if ( $comment_id ) {
			// Update engagement count
			$engagement = (int) bp_activity_get_meta( $activity_id, 'page_post_engagement', true );
			bp_activity_update_meta( $activity_id, 'page_post_engagement', $engagement + 1 );

			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Show notice if BuddyPress is not active
	 */
	public function buddypress_required_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'BuddyPress Page-like Groups requires BuddyPress to be installed and activated.', 'bp-page-like-groups' ); ?></p>
		</div>
		<?php
	}
}

// Initialize plugin
add_action( 'plugins_loaded', array( 'BP_Page_Like_Groups', 'get_instance' ) );

/**
 * Plugin activation
 */
register_activation_hook( __FILE__, 'bp_page_like_groups_activation' );
function bp_page_like_groups_activation() {
	add_option( 'bp_page_like_groups_version', BP_Page_Like_Groups::VERSION );
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
 * Track post views for page groups
 */
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_page_like_groups_track_views' );
function bp_page_like_groups_track_views() {
	if ( ! bp_is_group() ) {
		return;
	}

	$plugin = BP_Page_Like_Groups::get_instance();
	$group_id = bp_get_current_group_id();
	
	if ( ! $plugin->is_page_mode_enabled( $group_id ) ) {
		return;
	}

	$activity_id = bp_current_action();
	$views = (int) bp_activity_get_meta( $activity_id, 'page_post_views', true );
	bp_activity_update_meta( $activity_id, 'page_post_views', $views + 1 );
}