# BuddyPress Page-like Groups - Updated Feature Testing Checklist

## Core Functionality Tests

### 1. **Enable Page Mode**

- [ ] Create a new group and enable Page Mode during creation
- [ ] Edit existing group to enable Page Mode
- [ ] Verify "Page" badge appears in group header when enabled
- [ ] Disable Page Mode and verify badge disappears
- [ ] Check that settings persist after save

### 2. **Posting Restrictions**

#### Option: Administrators and Moderators

- [ ] Group admin can create posts ✓
- [ ] Group moderator can create posts ✓
- [ ] Regular member cannot see post form ✓
- [ ] Regular member sees restriction message ✓
- [ ] Restriction message shows "administrators and moderators" text
- [ ] AJAX posting blocked for regular members ✓

#### Option: Administrators Only

- [ ] Group admin can create posts ✓
- [ ] Group moderator cannot see post form ✓
- [ ] Moderator sees restriction message ✓
- [ ] Regular member cannot see post form ✓
- [ ] Restriction message shows "administrators only" text
- [ ] AJAX posting blocked for non-admins ✓

### 3. **Page Mode Badge**

- [ ] Badge displays "📢 Page" in group header
- [ ] Badge shows on group directory page
- [ ] Badge shows on single group page
- [ ] Badge has proper styling (blue background, white text)
- [ ] Badge includes hover tooltip explaining Page Mode

### 4. **Forum Discussions** (when bbPress/forums enabled)

#### When "Allow members to create new forum topics" is ENABLED:

- [ ] Members can see forum tab
- [ ] Members can create new topics
- [ ] Members can reply to topics
- [ ] "New Topic" button is visible to members
- [ ] Standard forum permissions apply

#### When DISABLED:

- [ ] Only admins/mods can create topics
- [ ] Members see info message about restriction
- [ ] Members can still reply to existing topics
- [ ] "New Topic" button hidden for regular members
- [ ] Forum tab still visible to all

#### When forum not enabled for group:

- [ ] Settings show note about forum not being enabled
- [ ] No forum-related options cause errors
- [ ] "Go to Forum" button doesn't appear in restriction message

### 5. **Member Invites**

#### When "Members can invite others" is ENABLED:

- [ ] All members see "Invite" tab
- [ ] Members can send invitations
- [ ] Standard invite flow works
- [ ] Invite notifications sent properly

#### When DISABLED:

- [ ] Only admins/mods see "Invite" tab
- [ ] Regular members cannot see invite option
- [ ] Direct invite URL access blocked for members
- [ ] Admins/mods can still invite normally

### 6. **Content Moderation**

#### When "Moderator posts need admin approval" is ENABLED:

- [ ] Moderator posts go to pending status
- [ ] Admin sees pending posts notification
- [ ] Admin can approve/reject posts
- [ ] Moderators see "pending approval" message

#### When DISABLED:

- [ ] Moderator posts publish immediately
- [ ] No approval workflow needed

## Integration Tests

### With BuddyPress Core:

- [ ] Activity stream shows Page posts correctly
- [ ] Comments work normally on Page posts
- [ ] @ mentions function properly
- [ ] Activity privacy respected
- [ ] Group membership functions work
- [ ] Notifications work correctly

### With bbPress (if installed):

- [ ] Forum restrictions work correctly
- [ ] Page Mode doesn't break forum functionality
- [ ] Topic/reply permissions properly enforced
- [ ] Forum tab visibility correct

### Mobile Testing:

- [ ] Settings display correctly on mobile
- [ ] Page badge responsive
- [ ] Restriction messages readable
- [ ] All features accessible on touch devices
- [ ] Forms work on mobile browsers

## Admin Backend Tests

### Group Edit Screen:

- [ ] Page Mode settings appear in group edit
- [ ] Settings save correctly
- [ ] No JavaScript errors in console
- [ ] Settings section properly styled

### Groups List Table:

- [ ] Page Mode column shows status
- [ ] Quick toggle action works
- [ ] Bulk enable/disable Page Mode works
- [ ] Proper success/error messages

### Plugin Admin Page:

- [ ] Stats display correctly
- [ ] Groups with Page Mode listed
- [ ] Settings link in plugins list works
- [ ] No PHP warnings/errors

## Edge Cases & Error Handling

### Settings Changes:

- [ ] Switching between posting restrictions updates immediately
- [ ] Disabling/re-enabling Page Mode preserves settings
- [ ] Multiple admins can change settings without conflicts
- [ ] Settings work with different group privacy levels (public/private/hidden)

### User Role Changes:

- [ ] Member promoted to mod gains posting ability (if allowed)
- [ ] Mod demoted to member loses posting ability
- [ ] Admin can always post regardless of settings

### Content Creation:

- [ ] Copy/paste in activity form works
- [ ] Image/media uploads work (if enabled)
- [ ] Long posts handled correctly
- [ ] Special characters don't break posts

## Performance Tests

- [ ] Page load time acceptable with Page Mode enabled
- [ ] No significant database query increase
- [ ] Settings load quickly
- [ ] No memory leaks in JavaScript
- [ ] Works with 1000+ member groups

## Security Tests

- [ ] Nonces verified on all forms
- [ ] Direct POST attempts blocked for unauthorized users
- [ ] XSS prevention in restriction messages
- [ ] SQL injection not possible through settings
- [ ] File upload restrictions respected

## Compatibility Tests

### BuddyPress Versions:

- [ ] Works with BuddyPress 5.0+
- [ ] Compatible with latest BuddyPress
- [ ] Legacy/Nouveau template packs both work

### WordPress Versions:

- [ ] Works with WordPress 5.0+
- [ ] Compatible with latest WordPress
- [ ] Multisite compatible

### Theme Compatibility:

- [ ] BuddyX theme
- [ ] BuddyBoss theme
- [ ] Default WordPress themes
- [ ] Custom themes with standard BP templates

## User Experience Tests

### Member Experience:

- [ ] Clear messaging about posting restrictions
- [ ] Obvious way to participate (comments)
- [ ] Forum participation clear (if enabled)
- [ ] No confusing options or dead ends

### Admin Experience:

- [ ] Easy to find and enable Page Mode
- [ ] Settings are self-explanatory
- [ ] Clear indication of what each option does
- [ ] Bulk management tools work well

### First Time Setup:

- [ ] Plugin activation smooth
- [ ] Default settings reasonable
- [ ] Instructions/help text clear
- [ ] No required configuration to start

## Regression Tests

After any changes:

- [ ] Existing groups without Page Mode still work normally
- [ ] Regular BuddyPress features unaffected
- [ ] Other plugins still function
- [ ] No JavaScript conflicts
- [ ] No PHP errors in logs
