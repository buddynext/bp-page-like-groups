# BuddyPress Page-like Groups - User Guide

## Table of Contents
1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Enabling Page Mode](#enabling-page-mode)
4. [Managing Page Settings](#managing-page-settings)
5. [Member Experience](#member-experience)
6. [Admin Features](#admin-features)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

## Overview

BuddyPress Page-like Groups transforms your BuddyPress groups into Facebook-style Pages where only administrators and moderators can create posts, while members can engage through comments and discussions. This creates a more controlled content environment perfect for:

- 📢 Company announcements
- 📰 News and updates
- 🏢 Brand communities
- 📋 Official organization pages
- 🎓 Educational institutions
- 🎯 Focused topic discussions

## Getting Started

### Requirements
- WordPress 5.0 or higher
- BuddyPress 5.0 or higher
- PHP 7.0 or higher

### Installation

1. **Upload the Plugin**
   - Download the plugin zip file
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin" and select the zip file
   - Click "Install Now"

2. **Activate the Plugin**
   - After installation, click "Activate"
   - The plugin is now ready to use

3. **Verify Installation**
   - Go to any BuddyPress group's settings
   - Look for the "Page Mode Settings" section

## Enabling Page Mode

### For New Groups

1. Navigate to **Groups → Create a Group**
2. Fill in the basic group details
3. In the **Settings** step, find "Page Mode Settings"
4. Check **"Enable Page Mode"**
5. Configure your preferred options
6. Complete group creation

### For Existing Groups

#### Frontend Method:
1. Go to your group's main page
2. Click **Admin → Settings**
3. Scroll to "Page Mode Settings"
4. Check **"Enable Page Mode"**
5. Configure options
6. Click **"Save Settings"**

#### Admin Dashboard Method:
1. Go to **WordPress Admin → Groups**
2. Click **"Edit"** next to your group
3. Find the "Page Mode Settings" section
4. Enable and configure as needed
5. Save changes

## Managing Page Settings

### Posting Restrictions

Choose who can publish posts in your Page-mode group:

**Option 1: Administrators and Moderators**
- Both group admins and moderators can create posts
- Best for: Larger organizations with multiple content creators
- Use when: You have trusted moderators helping manage content

**Option 2: Administrators Only**
- Only group administrators can create posts
- Best for: Strict content control
- Use when: You want maximum control over published content

### Forum Discussions (if bbPress is enabled)

**Allow members to create new forum topics**
- ✅ Checked: Members can start discussions in the forum tab
- ❌ Unchecked: Only admins/mods can start new forum topics

This setting allows you to:
- Keep main activity feed controlled while allowing open discussions
- Create a space for member-initiated conversations
- Maintain engagement without cluttering announcements

## Member Experience

### What Members Can Do

When Page Mode is active, regular members can:

1. **View all posts** from administrators/moderators
2. **Comment on posts** to engage with content
3. **React to posts** (if your theme supports it)
4. **Share posts** (if enabled in BuddyPress)
5. **Reply to existing forum topics** (if forums enabled)
6. **View group content** based on privacy settings

### What Members Cannot Do

- ❌ Create new posts in the activity feed
- ❌ Start forum topics (unless allowed in settings)
- ❌ Delete or edit admin/moderator posts
- ❌ Change group settings

### Member Interface

Members will see:
- A clear message explaining the Page-style restrictions
- The group's posts in a clean, announcement-style format
- A "📢 Page" badge indicating this is a Page-mode group
- Options to engage through comments

## Admin Features

### Quick Actions

**From the Groups List:**
1. Go to **WordPress Admin → Groups**
2. Hover over any group to see quick actions
3. Click **"Enable Page Mode"** or **"Disable Page Mode"**

### Bulk Actions

**To update multiple groups:**
1. Select multiple groups using checkboxes
2. Choose from Bulk Actions dropdown:
   - Enable Page Mode
   - Disable Page Mode
3. Click **"Apply"**

### Page Mode Dashboard

Access from **Groups → Page Mode** in WordPress admin:

- **Overview**: See all groups with Page Mode enabled
- **Statistics**: 
  - Total Page Groups
  - Admin-Only posting groups
  - Admin & Mod posting groups
- **Quick Management**: Edit settings directly

### Visual Indicators

- **Page Mode Column**: Shows status in groups list
- **Page Badge**: Visual indicator on group pages
- **Admin Label**: Shows "Page Mode Active" when editing

## Best Practices

### Content Strategy

1. **Post Regularly**: Keep your page active with consistent updates
2. **Clear Communication**: Use clear, professional language
3. **Engage with Comments**: Respond to member comments
4. **Use Forums Wisely**: Direct discussions to forums when appropriate

### Moderation Tips

1. **Set Clear Guidelines**: Establish posting rules for moderators
2. **Consistent Voice**: Maintain a consistent tone across posts
3. **Timely Responses**: Address member questions promptly
4. **Monitor Engagement**: Track which posts get most interaction

### Group Structure

1. **Appoint Trusted Moderators**: If allowing moderator posting
2. **Regular Reviews**: Periodically review your Page Mode settings
3. **Member Feedback**: Listen to member needs and adjust accordingly

## Troubleshooting

### Common Issues

**Page Mode not showing in settings:**
- Ensure BuddyPress is active and updated
- Check plugin compatibility
- Clear browser cache

**Members can still post:**
- Verify Page Mode is enabled
- Check posting restrictions are saved
- Clear site cache

**Settings not saving:**
- Check user permissions
- Ensure proper form submission
- Look for JavaScript errors

**Forum restrictions not working:**
- Verify bbPress is installed and active
- Check forum is enabled for the group
- Confirm settings are properly saved

### Getting Help

1. **Check Documentation**: Review this guide thoroughly
2. **Plugin Support**: Visit the plugin support forum
3. **Debug Mode**: Enable WordPress debug mode for detailed errors
4. **Contact Support**: Reach out to Wbcom Designs support

## Tips for Success

### For Announcement Groups
- Post important updates as standalone posts
- Use comments for clarifications
- Direct detailed discussions to forums

### For Brand Communities
- Share product updates and news
- Encourage member engagement through comments
- Use forums for product feedback

### For Educational Groups
- Post lessons and materials as main posts
- Use forums for Q&A sessions
- Encourage peer discussion in comments

### For Organization Pages
- Share official announcements
- Keep members informed of changes
- Use consistent posting schedule

## Conclusion

BuddyPress Page-like Groups provides a powerful way to create controlled, professional communication channels within your community. By following this guide, you can effectively manage your Page-mode groups and create engaging experiences for your members.

Remember: The key to a successful Page-mode group is balancing control with engagement. Use the main feed for important content while encouraging member participation through comments and forum discussions.