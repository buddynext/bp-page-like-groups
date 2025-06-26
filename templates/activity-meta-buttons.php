<?php
/**
 * Activity meta buttons template
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Only show in group context
if ( ! bp_is_group() ) {
	return;
}

$group_id = bp_get_current_group_id();
if ( ! $group_id || ! bp_plg_is_page_mode_enabled( $group_id ) ) {
	return;
}

$settings = bp_plg_get_page_mode_settings( $group_id );
$activity_id = bp_get_activity_id();

// Debug output (remove in production)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	echo '<!-- Page Mode Debug: Activity ID: ' . $activity_id . ', Quick Comments Enabled: ' . ( ! empty( $settings['enable_quick_comments'] ) ? 'Yes' : 'No' ) . ' -->';
}

// Show engagement stats if enabled
if ( ! empty( $settings['show_engagement_stats'] ) && $activity_id ) :
	$views = (int) bp_activity_get_meta( $activity_id, 'page_post_views', true );
	$engagement = (int) bp_activity_get_meta( $activity_id, 'page_post_engagement', true );
	?>
	<span class="page-engagement-stats">
		<?php if ( $views > 0 ) : ?>
			<span class="views">
				<span class="dashicons dashicons-visibility"></span>
				<?php echo esc_html( sprintf( _n( '%d view', '%d views', $views, 'bp-page-like-groups' ), $views ) ); ?>
			</span>
		<?php endif; ?>
		
		<?php if ( $engagement > 0 ) : ?>
			<span class="engagement">
				<span class="dashicons dashicons-groups"></span>
				<?php echo esc_html( sprintf( _n( '%d engaged', '%d engaged', $engagement, 'bp-page-like-groups' ), $engagement ) ); ?>
			</span>
		<?php endif; ?>
	</span>
<?php endif; ?>

<?php
// Quick comment buttons - only show if enabled and user can comment
if ( ! empty( $settings['enable_quick_comments'] ) && $activity_id && bp_activity_can_comment() && is_user_logged_in() ) : ?>
	<div class="quick-comment-buttons" data-activity-id="<?php echo esc_attr( $activity_id ); ?>">
		<button type="button" class="quick-comment bp-secondary-action" 
		        data-comment="👍" 
		        title="<?php esc_attr_e( 'Like this post', 'bp-page-like-groups' ); ?>"
		        aria-label="<?php esc_attr_e( 'Like this post', 'bp-page-like-groups' ); ?>">
			<span class="emoji" aria-hidden="true">👍</span>
			<span class="text"><?php esc_html_e( 'Like', 'bp-page-like-groups' ); ?></span>
		</button>
		
		<button type="button" class="quick-comment bp-secondary-action" 
		        data-comment="❤️" 
		        title="<?php esc_attr_e( 'Love this post', 'bp-page-like-groups' ); ?>"
		        aria-label="<?php esc_attr_e( 'Love this post', 'bp-page-like-groups' ); ?>">
			<span class="emoji" aria-hidden="true">❤️</span>
			<span class="text"><?php esc_html_e( 'Love', 'bp-page-like-groups' ); ?></span>
		</button>
		
		<button type="button" class="quick-comment bp-secondary-action" 
		        data-comment="🙏" 
		        title="<?php esc_attr_e( 'Say thanks', 'bp-page-like-groups' ); ?>"
		        aria-label="<?php esc_attr_e( 'Say thanks', 'bp-page-like-groups' ); ?>">
			<span class="emoji" aria-hidden="true">🙏</span>
			<span class="text"><?php esc_html_e( 'Thanks', 'bp-page-like-groups' ); ?></span>
		</button>
	</div>
<?php endif; ?>