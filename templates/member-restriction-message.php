<?php
/**
 * Member restriction message template
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$group_id = bp_get_current_group_id();
$settings = bp_plg_get_page_mode_settings( $group_id );
$forum_url = bp_plg_get_group_forum_url( $group_id );
$restriction = groups_get_groupmeta( $group_id, BP_Page_Like_Groups::META_KEY_RESTRICTION, true ) ?: 'mods';

// Determine who can post
$can_post_text = ( 'admins' === $restriction ) 
	? __( 'Only administrators', 'bp-page-like-groups' )
	: __( 'Only administrators and moderators', 'bp-page-like-groups' );
?>

<div class="bp-page-mode-member-actions">
	<div class="bp-feedback info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php 
			printf( 
				__( 'This is a Page-style group. %s can create posts, but you can engage by commenting and reacting to posts.', 'bp-page-like-groups' ),
				$can_post_text
			); 
			?>
		</p>
	</div>

	<?php 
	// Show forum link only if:
	// 1. Member discussions are allowed in settings
	// 2. Forum is actually enabled for this group
	// 3. User is logged in
	if ( ! empty( $settings['allow_member_discussions'] ) && $forum_url && is_user_logged_in() ) : ?>
		<div class="member-discussion-prompt">
			<p><?php esc_html_e( 'Want to start a discussion?', 'bp-page-like-groups' ); ?></p>
			<a href="<?php echo esc_url( $forum_url ); ?>" class="button">
				<?php esc_html_e( 'Go to Forum', 'bp-page-like-groups' ); ?>
			</a>
		</div>
	<?php elseif ( ! is_user_logged_in() ) : ?>
		<div class="member-discussion-prompt">
			<p><?php esc_html_e( 'Please log in to participate in this group.', 'bp-page-like-groups' ); ?></p>
			<a href="<?php echo esc_url( wp_login_url( bp_get_group_permalink( groups_get_current_group() ) ) ); ?>" class="button">
				<?php esc_html_e( 'Log In', 'bp-page-like-groups' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>