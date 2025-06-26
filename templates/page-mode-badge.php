<?php
/**
 * Page mode badge template
 *
 * @package BuddyPress_Page_Like_Groups
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>

<span class="bp-page-mode-badge" title="<?php esc_attr_e( 'This is a Page-style group where only admins/mods can post', 'bp-page-like-groups' ); ?>">
	<span class="dashicons dashicons-megaphone"></span>
	<?php esc_html_e( 'Page', 'bp-page-like-groups' ); ?>
</span>