# BuddyPress Page-like Groups - Developer Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Core Components](#core-components)
3. [Hooks & Filters](#hooks--filters)
4. [Functions Reference](#functions-reference)
5. [Database Schema](#database-schema)
6. [Extending the Plugin](#extending-the-plugin)
7. [Theme Compatibility](#theme-compatibility)
8. [Best Practices](#best-practices)

## Architecture Overview

The plugin follows WordPress coding standards and BuddyPress plugin architecture patterns.

### File Structure
```
buddypress-page-like-groups/
├── admin/
│   ├── admin-functions.php         # Admin menu and pages
│   └── group-edit-integration.php  # Group edit screen integration
├── assets/
│   ├── css/
│   │   └── style.css              # Frontend and admin styles
│   └── js/
│       └── script.js              # Frontend JavaScript
├── includes/
│   ├── class-bp-page-like-groups.php  # Main plugin class
│   ├── functions.php                   # Helper functions
│   ├── hooks.php                       # Additional hooks
│   ├── settings-integration.php        # Settings form integration
│   └── feature-implementations.php     # Core feature implementations
├── templates/
│   ├── admin/
│   │   └── group-settings-fields.php   # Settings fields template
│   ├── member-restriction-message.php  # Member restriction notice
│   └── page-mode-badge.php            # Page mode indicator
├── languages/                          # Translation files
└── buddypress-page-like-groups.php    # Main plugin file
```

### Design Patterns

1. **Singleton Pattern**: Main plugin class uses singleton pattern
2. **Hook-based Architecture**: Leverages WordPress/BuddyPress action and filter hooks
3. **Template System**: Overridable templates for customization
4. **Modular Structure**: Features separated into logical files

## Core Components

### Main Plugin Class

```php
class BP_Page_Like_Groups {
    // Singleton instance
    private static $instance = null;
    
    // Meta keys constants
    const META_KEY_ENABLED = '_group_page_mode_enabled';
    const META_KEY_RESTRICTION = '_group_posting_restriction';
    const META_KEY_SETTINGS = '_group_page_mode_settings';
    
    // Get instance method
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Meta Keys

The plugin uses three main meta keys for storing group settings:

- `_group_page_mode_enabled`: Boolean flag for Page Mode status
- `_group_posting_restriction`: Who can post ('admins' or 'mods')
- `_group_page_mode_settings`: Array of additional settings

## Hooks & Filters

### Actions

#### Frontend Actions
```php
// Add settings fields to group settings
add_action( 'bp_after_group_settings_creation_step', 'callback' );
add_action( 'bp_after_group_settings_admin', 'callback' );

// Save settings
add_action( 'groups_create_group_step_save_group-settings', 'callback' );
add_action( 'groups_group_settings_edited', 'callback' );

// Display elements
add_action( 'bp_before_group_activity_post_form', 'callback' );
add_action( 'bp_group_header_meta', 'callback' );
```

#### Admin Actions
```php
// Admin menu
add_action( 'admin_menu', 'bp_plg_add_admin_menu', 99 );

// Group edit screen
add_action( 'bp_groups_admin_edit_group_settings', 'callback' );
add_action( 'bp_group_admin_edit_after', 'callback' );

// Scripts and styles
add_action( 'admin_enqueue_scripts', 'callback' );
```

### Filters

#### Permission Filters
```php
// Activity posting permission
add_filter( 'bp_activity_can_post', 'callback' );
add_filter( 'bp_activity_user_can_post', 'callback', 10, 2 );

// Forum permissions
add_filter( 'bbp_current_user_can_publish_topics', 'callback' );
add_filter( 'bbp_current_user_can_access_create_topic_form', 'callback' );
```

#### Display Filters
```php
// Activity action string
add_filter( 'bp_get_activity_action', 'callback', 10, 2 );

// Admin columns
add_filter( 'bp_groups_list_table_get_columns', 'callback' );
add_filter( 'bp_groups_admin_get_group_custom_column', 'callback', 10, 3 );
```

## Functions Reference

### Core Functions

#### `bp_plg_is_page_mode_enabled( $group_id )`
Check if Page Mode is enabled for a group.

**Parameters:**
- `$group_id` (int) - The group ID

**Returns:**
- (bool) - True if enabled, false otherwise

**Example:**
```php
if ( bp_plg_is_page_mode_enabled( $group_id ) ) {
    // Page mode specific code
}
```

#### `bp_plg_get_page_mode_settings( $group_id )`
Get all Page Mode settings for a group.

**Parameters:**
- `$group_id` (int) - The group ID

**Returns:**
- (array) - Settings array with defaults merged

**Example:**
```php
$settings = bp_plg_get_page_mode_settings( $group_id );
if ( $settings['allow_member_discussions'] ) {
    // Allow forum discussions
}
```

#### `bp_plg_user_can_post( $user_id, $group_id )`
Check if a user can post in a Page Mode group.

**Parameters:**
- `$user_id` (int) - The user ID
- `$group_id` (int) - The group ID

**Returns:**
- (bool) - True if user can post, false otherwise

**Example:**
```php
if ( bp_plg_user_can_post( get_current_user_id(), $group_id ) ) {
    // Show post form
}
```

#### `bp_plg_group_has_forum_enabled( $group_id = 0 )`
Check if group has forums enabled (bbPress integration).

**Parameters:**
- `$group_id` (int) - The group ID (optional, defaults to current)

**Returns:**
- (bool) - True if forum enabled, false otherwise

### Template Functions

#### `bp_plg_get_group_forum_url( $group_id = 0 )`
Get the forum URL for a group.

**Parameters:**
- `$group_id` (int) - The group ID (optional)

**Returns:**
- (string|false) - Forum URL or false if not available

## Database Schema

The plugin uses WordPress/BuddyPress meta tables:

### Group Meta Storage

```sql
-- Example data in wp_bp_groups_groupmeta table
meta_id | group_id | meta_key                    | meta_value
--------|----------|-----------------------------|-----------
1       | 5        | _group_page_mode_enabled    | 1
2       | 5        | _group_posting_restriction  | mods
3       | 5        | _group_page_mode_settings   | a:1:{s:24:"allow_member_discussions";b:1;}
```

## Extending the Plugin

### Adding Custom Settings

```php
// Add custom setting field
add_action( 'bp_plg_settings_fields', function( $group_id ) {
    $custom_value = groups_get_groupmeta( $group_id, 'my_custom_setting', true );
    ?>
    <label>
        <input type="checkbox" name="settings[my_custom_setting]" value="1" 
               <?php checked( $custom_value, true ); ?> />
        <?php _e( 'My Custom Setting', 'textdomain' ); ?>
    </label>
    <?php
});

// Save custom setting
add_filter( 'bp_plg_save_settings', function( $settings, $group_id ) {
    if ( isset( $_POST['settings']['my_custom_setting'] ) ) {
        $settings['my_custom_setting'] = 1;
    }
    return $settings;
}, 10, 2 );
```

### Modifying Permissions

```php
// Custom permission check
add_filter( 'bp_plg_user_can_post', function( $can_post, $user_id, $group_id ) {
    // Add custom logic
    if ( my_custom_condition( $user_id, $group_id ) ) {
        return true;
    }
    return $can_post;
}, 10, 3 );
```

### Adding Custom Restrictions

```php
// Add new restriction type
add_filter( 'bp_plg_posting_restrictions', function( $restrictions ) {
    $restrictions['custom'] = __( 'Custom Role Only', 'textdomain' );
    return $restrictions;
});

// Handle custom restriction
add_filter( 'bp_plg_check_posting_permission', function( $can_post, $restriction, $user_id, $group_id ) {
    if ( 'custom' === $restriction ) {
        // Custom logic here
        return my_custom_role_check( $user_id, $group_id );
    }
    return $can_post;
}, 10, 4 );
```

## Theme Compatibility

### Template Overrides

Themes can override plugin templates by creating these files:

```
your-theme/
└── buddypress/
    └── page-like-groups/
        ├── member-restriction-message.php
        └── page-mode-badge.php
```

### CSS Classes

Key CSS classes for styling:

```css
/* Main containers */
.bp-page-mode-active          /* Body class when viewing page mode group */
.bp-page-like-group          /* Alternative body class */
.bp-page-mode-badge          /* Page mode indicator badge */

/* Settings form */
.bp-plg-page-mode-settings   /* Settings fieldset */
.bp-plg-page-mode-section    /* Settings section */
.bp-plg-description          /* Setting descriptions */

/* Member interface */
.bp-page-mode-member-actions /* Member actions container */
.member-discussion-prompt    /* Forum prompt for members */
```

### JavaScript Events

```javascript
// Page mode enabled/disabled
jQuery(document).on('bpplg:pagemode:enabled', function(e, groupId) {
    // Handle page mode enabled
});

jQuery(document).on('bpplg:pagemode:disabled', function(e, groupId) {
    // Handle page mode disabled
});
```

## Best Practices

### Security

1. **Nonce Verification**: Always verify nonces in form submissions
2. **Capability Checks**: Use proper capability checks
3. **Data Sanitization**: Sanitize all input data
4. **SQL Injection Prevention**: Use prepared statements

```php
// Good practice example
if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'group-settings' ) ) {
    return;
}

if ( ! groups_is_user_admin( $user_id, $group_id ) ) {
    wp_die( __( 'Access denied', 'bp-page-like-groups' ) );
}

$restriction = sanitize_text_field( $_POST['posting-restriction'] );
```

### Performance

1. **Cache Group Meta**: Use caching for repeated queries
2. **Minimize Queries**: Batch operations when possible
3. **Lazy Loading**: Load features only when needed

```php
// Cache example
function bp_plg_get_cached_setting( $group_id, $key ) {
    $cache_key = "bpplg_setting_{$group_id}_{$key}";
    $value = wp_cache_get( $cache_key );
    
    if ( false === $value ) {
        $value = groups_get_groupmeta( $group_id, $key, true );
        wp_cache_set( $cache_key, $value, '', 3600 );
    }
    
    return $value;
}
```

### Internationalization

Always use proper i18n functions:

```php
// Text domain: 'bp-page-like-groups'
__( 'Text to translate', 'bp-page-like-groups' );
_e( 'Echo translated text', 'bp-page-like-groups' );
_n( 'Singular', 'Plural', $count, 'bp-page-like-groups' );
```

### Error Handling

```php
try {
    // Risky operation
    $result = perform_operation();
} catch ( Exception $e ) {
    // Log error
    error_log( 'BP Page Like Groups Error: ' . $e->getMessage() );
    
    // User-friendly message
    bp_core_add_message( 
        __( 'An error occurred. Please try again.', 'bp-page-like-groups' ), 
        'error' 
    );
}
```

## Debugging

### Debug Constants

```php
// Enable debug logging
define( 'BP_PLG_DEBUG', true );

// In your code
if ( defined( 'BP_PLG_DEBUG' ) && BP_PLG_DEBUG ) {
    error_log( 'BP PLG Debug: ' . print_r( $data, true ) );
}
```

### Common Issues

1. **Settings not saving**: Check nonces and permissions
2. **Restrictions not working**: Verify meta key values
3. **Templates not loading**: Check file paths and names
4. **Conflicts**: Look for JavaScript/CSS namespace issues

## Contributing

When contributing to the plugin:

1. Follow WordPress Coding Standards
2. Add proper documentation
3. Include unit tests for new features
4. Update version numbers appropriately
5. Test with multiple BuddyPress versions

## Conclusion

This guide provides the foundation for extending and customizing BuddyPress Page-like Groups. The plugin's modular architecture makes it easy to add features while maintaining compatibility with BuddyPress core functionality.