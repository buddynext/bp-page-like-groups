<?php
/**
 * Group settings fields template
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$group_id = bp_get_current_group_id();
$page_mode_enabled = bp_plg_is_page_mode_enabled( $group_id );
$restriction = groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';
$settings = bp_plg_get_page_mode_settings( $group_id );
$has_forum = bp_plg_group_has_forum_enabled( $group_id );
?>

<fieldset class="group-create-page-mode radio">
	<legend><?php esc_html_e( 'Page Mode Settings', 'bp-page-like-groups' ); ?></legend>
	
	<div class="page-mode-description">
		<p><?php esc_html_e( 'Page Mode transforms your group into a Facebook Page-style community where only administrators and moderators can create posts, while all members can engage through comments.', 'bp-page-like-groups' ); ?></p>
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
			
			<?php if ( $has_forum ) : ?>
			<label>
				<input type="checkbox" name="settings[allow_member_discussions]" value="1" <?php checked( $settings['allow_member_discussions'], true ); ?> />
				<?php esc_html_e( 'Allow members to create new forum topics', 'bp-page-like-groups' ); ?>
				<span class="description">
					<?php esc_html_e( 'When disabled, only administrators and moderators can start new forum discussions', 'bp-page-like-groups' ); ?>
				</span>
			</label>
			<?php else : ?>
			<p class="description">
				<em><?php esc_html_e( 'Note: Forum is not enabled for this group. To use forum features, please enable the forum in the group settings.', 'bp-page-like-groups' ); ?></em>
			</p>
			<?php endif; ?>
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