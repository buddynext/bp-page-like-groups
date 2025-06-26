<?php
/**
 * Main plugin class
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class
 */
class BP_Page_Like_Groups {

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
		// Hook into BuddyPress
		add_action( 'bp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Add hooks
		$this->add_hooks();
		
		// Setup integrations
		$this->setup_integrations();
	}

	/**
	 * Add all hooks and filters
	 */
	private function add_hooks() {
		// Group creation/settings form - Add to existing settings
		add_action( 'bp_after_group_settings_creation_step', array( $this, 'add_page_mode_fields' ) );
		add_action( 'bp_after_group_settings_admin', array( $this, 'add_page_mode_fields' ) );
		
		// Alternative hooks for different themes/versions
		add_action( 'bp_before_group_settings_creation_step', array( $this, 'add_page_mode_fields_before' ) );
		add_action( 'bp_before_group_settings_admin', array( $this, 'add_page_mode_fields_before' ) );

		// Save settings - Hook into the existing save process
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
		
		// Fallback for themes that might not have the above hook
		add_action( 'bp_activity_entry_content', array( $this, 'add_activity_meta_buttons_fallback' ), 20 );
		add_action( 'bp_after_activity_entry_comments', array( $this, 'add_activity_meta_buttons_legacy' ) );

		// Member request moderation
		add_filter( 'bp_groups_auto_accept_membership_requests', array( $this, 'maybe_moderate_join_requests' ), 10, 2 );
	}

	/**
	 * Setup integrations with other Wbcom plugins
	 */
	private function setup_integrations() {
		// Only add integration hooks if the respective plugins exist
		
		// Integration with BuddyPress Moderation
		if ( class_exists( 'BP_Moderation' ) ) {
			add_filter( 'bp_moderation_group_settings', array( $this, 'add_moderation_settings' ) );
		}

		// Integration with BuddyPress Polls  
		if ( function_exists( 'bp_polls_init' ) ) {
			add_filter( 'bp_polls_group_support', array( $this, 'enable_polls_for_page_groups' ), 10, 2 );
		}

		// Integration with BuddyPress Reactions
		if ( class_exists( 'BP_Reactions' ) ) {
			add_filter( 'bp_reactions_group_support', array( $this, 'customize_reactions_for_pages' ), 10, 2 );
		}
	}

	/**
	 * Add moderation settings for page mode groups
	 */
	public function add_moderation_settings( $settings ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id || ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return $settings;
		}

		$settings['page_mode'] = array(
			'auto_moderate_non_admin_posts' => true,
			'require_approval_for_first_post' => true,
			'flag_threshold' => 3,
		);

		return $settings;
	}

	/**
	 * Enable polls for page mode groups (admin/mod only)
	 */
	public function enable_polls_for_page_groups( $enabled, $group_id ) {
		if ( bp_plg_is_page_mode_enabled( $group_id ) ) {
			$user_id = bp_loggedin_user_id();
			return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
		}
		return $enabled;
	}

	/**
	 * Customize reactions for page mode
	 */
	public function customize_reactions_for_pages( $reactions, $group_id ) {
		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return $reactions;
		}

		$page_reactions = array(
			'announce' => array(
				'emoji' => '📢',
				'label' => __( 'Announcement', 'bp-page-like-groups' ),
				'admin_only' => true
			),
			'official' => array(
				'emoji' => '✅', 
				'label' => __( 'Official', 'bp-page-like-groups' ),
				'admin_only' => true
			)
		);

		return array_merge( $reactions, $page_reactions );
	}

	/**
	 * Add page mode fields to group settings
	 */
	public function add_page_mode_fields() {
		// Get the template file path
		$template_path = BP_PLG_PLUGIN_DIR . 'templates/admin/group-settings-fields.php';
		
		// Check if template exists
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback: Display inline if template not found
			$this->display_inline_settings();
		}
	}
	
	/**
	 * Add page mode fields before standard fields (alternative hook)
	 */
	public function add_page_mode_fields_before() {
		// Only run if not already displayed
		static $displayed = false;
		if ( $displayed ) {
			return;
		}
		$displayed = true;
		
		// Close any open fieldset first
		echo '</fieldset><fieldset class="bp-page-mode-fieldset">';
		$this->add_page_mode_fields();
		echo '</fieldset><fieldset>';
	}
	
	/**
	 * Display settings inline as fallback
	 */
	private function display_inline_settings() {
		$group_id = bp_get_current_group_id();
		$page_mode_enabled = bp_plg_is_page_mode_enabled( $group_id );
		?>
		<hr style="margin: 30px 0;" />
		<div class="bp-page-mode-settings-wrapper">
			<h4><?php esc_html_e( 'Page Mode Settings (Optional)', 'bp-page-like-groups' ); ?></h4>
			<label>
				<input type="checkbox" name="page-mode-enabled" value="1" <?php checked( $page_mode_enabled ); ?> />
				<?php esc_html_e( 'Enable Page Mode - Transform this group into a Facebook Page-style community', 'bp-page-like-groups' ); ?>
			</label>
		</div>
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

		return bp_plg_user_can_post( $user_id, $group_id );
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

		return bp_plg_user_can_post( $user_id, $group_id );
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

		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return;
		}

		if ( bp_plg_user_can_post( $user_id, $group_id ) ) {
			return;
		}

		include BP_PLG_PLUGIN_DIR . 'templates/member-restriction-message.php';

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
		
		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return;
		}

		include BP_PLG_PLUGIN_DIR . 'templates/page-mode-badge.php';
	}

	/**
	 * Add activity meta buttons for engagement
	 */
	public function add_activity_meta_buttons() {
		if ( ! bp_is_group() ) {
			return;
		}

		$group_id = bp_get_current_group_id();
		
		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return;
		}

		include BP_PLG_PLUGIN_DIR . 'templates/activity-meta-buttons.php';
	}
	
	/**
	 * Fallback method for themes without bp_activity_entry_meta hook
	 */
	public function add_activity_meta_buttons_fallback() {
		// Only use this if buttons haven't been displayed yet
		static $displayed = array();
		$activity_id = bp_get_activity_id();
		
		if ( isset( $displayed[$activity_id] ) ) {
			return;
		}
		
		// Check if we should display
		if ( ! bp_is_group() || ! doing_action( 'bp_activity_entry_content' ) ) {
			return;
		}
		
		$group_id = bp_get_current_group_id();
		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return;
		}
		
		// Check if the buttons were already added by primary hook
		if ( has_action( 'bp_activity_entry_meta', array( $this, 'add_activity_meta_buttons' ) ) ) {
			return;
		}
		
		$displayed[$activity_id] = true;
		echo '<div class="bp-plg-activity-buttons-fallback">';
		include BP_PLG_PLUGIN_DIR . 'templates/activity-meta-buttons.php';
		echo '</div>';
	}
	
	/**
	 * Legacy method for older themes
	 */
	public function add_activity_meta_buttons_legacy() {
		// Similar check for legacy themes
		if ( ! bp_is_group() || ! bp_is_single_activity() ) {
			return;
		}
		
		$group_id = bp_get_current_group_id();
		if ( ! bp_plg_is_page_mode_enabled( $group_id ) ) {
			return;
		}
		
		echo '<div class="bp-plg-activity-buttons-legacy">';
		include BP_PLG_PLUGIN_DIR . 'templates/activity-meta-buttons.php';
		echo '</div>';
	}

	/**
	 * Maybe moderate join requests for page groups
	 */
	public function maybe_moderate_join_requests( $auto_accept, $group ) {
		if ( ! bp_plg_is_page_mode_enabled( $group->id ) ) {
			return $auto_accept;
		}

		$settings = bp_plg_get_page_mode_settings( $group->id );
		
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
			BP_PLG_PLUGIN_URL . 'assets/css/style.css', 
			array(), 
			BP_PLG_VERSION 
		);

		// Add inline CSS for backward compatibility
		wp_add_inline_style( 'bp-page-like-groups', '
			/* Ensure Page Mode settings don\'t interfere with core group settings */
			.group-settings fieldset:not(.bp-page-mode-settings) {
				/* Preserve existing fieldset styles */
			}
			
			/* Page Mode specific styles */
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
		' );

		wp_enqueue_script( 
			'bp-page-like-groups', 
			BP_PLG_PLUGIN_URL . 'assets/js/script.js', 
			array( 'jquery' ), 
			BP_PLG_VERSION, 
			true 
		);

		wp_localize_script( 'bp-page-like-groups', 'bpplg', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'bpplg-nonce' ),
			'i18n' => array(
				'loading' => __( 'Loading...', 'bp-page-like-groups' ),
				'error' => __( 'An error occurred. Please try again.', 'bp-page-like-groups' ),
			)
		) );
	}
}