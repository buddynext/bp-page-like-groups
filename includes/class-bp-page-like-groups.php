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