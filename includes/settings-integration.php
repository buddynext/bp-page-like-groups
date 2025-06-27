<?php
/**
 * Settings Integration for BuddyPress Page-like Groups
 * Ensures settings appear in group settings forms
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add settings fields directly after group settings form fields
 */
add_action( 'bp_after_group_settings_admin', 'bp_plg_add_settings_to_admin_form', 999 );
add_action( 'bp_after_group_settings_creation_step', 'bp_plg_add_settings_to_create_form', 999 );

function bp_plg_add_settings_to_admin_form() {
	if ( bp_is_group_admin_screen( 'group-settings' ) ) {
		bp_plg_display_page_mode_settings();
	}
}

function bp_plg_add_settings_to_create_form() {
	if ( bp_is_group_creation_step( 'group-settings' ) ) {
		bp_plg_display_page_mode_settings();
	}
}

/**
 * Display the Page Mode settings following BuddyPress UI standards
 */
function bp_plg_display_page_mode_settings() {
	$group_id = bp_get_current_group_id();
	$page_mode_enabled = bp_plg_is_page_mode_enabled( $group_id );
	$restriction = groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';
	$settings = bp_plg_get_page_mode_settings( $group_id );
	$has_forum = bp_plg_group_has_forum_enabled( $group_id );
	?>
	
	<fieldset class="group-create-privacy group-page-mode">
		<legend><?php esc_html_e( 'Page Mode Settings', 'bp-page-like-groups' ); ?></legend>
		
		<p class="group-setting-info"><?php esc_html_e( 'Page Mode transforms your group into a Facebook Page-style community where only administrators and moderators can create posts, while all members can engage through comments.', 'bp-page-like-groups' ); ?></p>
		
		<p class="bp-page-mode-perfect-for"><strong><?php esc_html_e( 'Perfect for:', 'bp-page-like-groups' ); ?></strong> <?php esc_html_e( 'Announcements, News Updates, Brand Communities, Official Pages', 'bp-page-like-groups' ); ?></p>
		
		<div class="radio">
			<label for="page-mode-enabled">
				<input type="checkbox" name="page-mode-enabled" id="page-mode-enabled" value="1" <?php checked( $page_mode_enabled, true ); ?> />
				<strong><?php esc_html_e( 'Enable Page Mode for this group', 'bp-page-like-groups' ); ?></strong>
			</label>
		</div>
		
		<div id="page-mode-options" class="page-mode-options" style="<?php echo $page_mode_enabled ? '' : 'display:none;'; ?>">
			
			<!-- Who can publish posts -->
			<div class="group-setting-section">
				<h4><?php esc_html_e( 'Who can publish posts?', 'bp-page-like-groups' ); ?></h4>
				
				<div class="radio">
					<label for="posting-restriction-mods">
						<input type="radio" name="posting-restriction" id="posting-restriction-mods" value="mods" <?php checked( $restriction, 'mods' ); ?> />
						<?php esc_html_e( 'Administrators and Moderators', 'bp-page-like-groups' ); ?>
					</label>
				</div>
				
				<div class="radio">
					<label for="posting-restriction-admins">
						<input type="radio" name="posting-restriction" id="posting-restriction-admins" value="admins" <?php checked( $restriction, 'admins' ); ?> />
						<?php esc_html_e( 'Administrators Only', 'bp-page-like-groups' ); ?>
					</label>
				</div>
			</div>
			
			<!-- Member Engagement -->
			<div class="group-setting-section">
				<h4><?php esc_html_e( 'Member Engagement', 'bp-page-like-groups' ); ?></h4>
				
				<?php if ( $has_forum ) : ?>
				<div class="checkbox">
					<label for="settings-allow-member-discussions">
						<input type="checkbox" name="settings[allow_member_discussions]" id="settings-allow-member-discussions" value="1" <?php checked( $settings['allow_member_discussions'], true ); ?> />
						<?php esc_html_e( 'Allow members to create new forum topics', 'bp-page-like-groups' ); ?>
					</label>
				</div>
				<?php else : ?>
				<p class="description">
					<em><?php esc_html_e( 'Note: Forum is not enabled for this group. To use forum features, please enable the forum in the group settings.', 'bp-page-like-groups' ); ?></em>
				</p>
				<?php endif; ?>
			</div>

			
		</div>
		
	</fieldset>
	
	<style type="text/css">
		/* Match BuddyPress fieldset styling */
		fieldset.group-page-mode {
			border: 1px solid #e5e5e5;
			padding: 20px;
			margin: 20px 0;
			background: #fafafa;
		}
		
		fieldset.group-page-mode legend {
			font-weight: 600;
			font-size: 16px;
			padding: 0 10px;
			margin-left: -10px;
		}
		
		.group-setting-info {
			margin-bottom: 15px;
			color: #666;
		}
		
		.bp-page-mode-perfect-for {
			margin-bottom: 20px;
			font-style: italic;
		}
		
		.page-mode-options {
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #e5e5e5;
		}
		
		.group-setting-section {
			margin-bottom: 30px;
		}
		
		.group-setting-section:last-child {
			margin-bottom: 0;
		}
		
		.group-setting-section h4 {
			font-size: 14px;
			font-weight: 600;
			margin-bottom: 15px;
			color: #23282d;
		}
		
		.group-setting-section .radio,
		.group-setting-section .checkbox {
			margin-bottom: 10px;
		}
		
		.group-setting-section .radio:last-child,
		.group-setting-section .checkbox:last-child {
			margin-bottom: 0;
		}
		
		.group-setting-section label {
			font-weight: normal;
		}
		
		.group-setting-section .description {
			margin: 5px 0 0 24px;
			color: #666;
			font-size: 13px;
			font-style: italic;
		}
		
		/* Ensure consistent styling with BuddyPress */
		.group-page-mode input[type="radio"],
		.group-page-mode input[type="checkbox"] {
			margin-right: 5px;
			vertical-align: middle;
		}
		
		/* Match hover states */
		.group-page-mode label:hover {
			color: #0073aa;
		}
	</style>
	
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		// Page Mode toggle handler
		$('#page-mode-enabled').off('change.pagemode').on('change.pagemode', function() {
			if ($(this).is(':checked')) {
				$('#page-mode-options').slideDown(300);
				// Set default if none selected
				if (!$('input[name="posting-restriction"]:checked').length) {
					$('#posting-restriction-mods').prop('checked', true);
				}
			} else {
				$('#page-mode-options').slideUp(300);
			}
		});
		
		// Add hover effect to labels
		$('.group-page-mode label').hover(
			function() { $(this).css('cursor', 'pointer'); },
			function() { $(this).css('cursor', 'default'); }
		);
	});
	</script>
	
	<?php
}

/**
 * Additional styles for better integration
 */
add_action( 'bp_head', 'bp_plg_add_inline_styles' );
function bp_plg_add_inline_styles() {
	if ( ! bp_is_group_admin_screen( 'group-settings' ) && ! bp_is_group_creation_step( 'group-settings' ) ) {
		return;
	}
	?>
	<style type="text/css">
		/* Ensure Page Mode settings match other fieldsets */
		.standard-form fieldset.group-page-mode {
			margin-top: 20px;
		}
		
		/* Match the style of other settings sections */
		.group-create-body fieldset.group-page-mode,
		#group-settings-form fieldset.group-page-mode {
			border: 1px solid #e5e5e5;
			clear: both;
		}
		
		/* Responsive adjustments */
		@media screen and (max-width: 768px) {
			.page-mode-options {
				margin-left: 0;
			}
			
			.group-setting-section .description {
				margin-left: 0;
			}
		}
	</style>
	<?php
}