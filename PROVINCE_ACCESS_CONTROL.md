# Province-Based Course Access Control - Complete Guide

## 🎯 Overview

The Advanced Course Manager uses a **province-based content filtering system** to ensure users only see courses relevant to their legal jurisdiction. Users from Ontario see Ontario courses, Alberta users see Alberta courses, etc.

---

## 🔍 How Province Assignment Works

### Three-Layer Access Control System

```
┌─────────────────────────────────────────────────────────────┐
│                    USER VIEWS ARCHIVE PAGE                  │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│   LAYER 1: USER'S PROVINCE (Where do they come from?)       │
│   ├─ User Profile: acm_user_province (user meta)            │
│   ├─ Session: $_SESSION['acm_selected_province']            │
│   └─ Default: Ontario (site default)                        │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│   LAYER 2: COURSE PROVINCE (What province is this course?)  │
│   ├─ Post Meta: _acm_course_province                        │
│   ├─ Values: ontario, alberta, british_columbia             │
│   └─ Set In: Course admin > Course Settings metabox         │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│   LAYER 3: FILTERING (Do they match?)                       │
│   ├─ If match: Course appears in list                       │
│   ├─ If no match: Course hidden from archive                │
│   └─ If accessing directly: MemberPress check applies       │
└─────────────────────────────────────────────────────────────┘
```

---

## 📱 How Users Get Assigned to a Province

### Method 1: User Profile (For Registered Users)

**Where:** WordPress > Users > Edit User Profile

**Screen Location:** Under "Course Preferences" section

**How to Set:**
1. Go to **WordPress Admin > Users**
2. Click on a user to edit
3. Scroll to **"Course Preferences"** section
4. Select province from dropdown:
   - Ontario
   - Alberta
   - British Columbia
5. Click **Update Profile**

**Stored As:** User Meta - `acm_user_province` = `'ontario'`, `'alberta'`, or `'british_columbia'`

**Code Reference:**
```php
// Saved in: includes/class-acm-province-manager.php
add
_action('show_user_profile', 'add_province_field');
add_action('edit_user_profile', 'add_province_field');
```

---

### Method 2: Province Selector Shortcode (For All Users)

**Shortcode:** `[acm_province_selector]`

**Purpose:** Allows users to select their province on the frontend

**How It Works:**
1. Place `[acm_province_selector]` on any page
2. Displays a dropdown with province options
3. User selects their province
4. Selection is saved to:
   - User meta (if logged in)
   - Session (if not logged in)

**Example Implementation:**
```
Add this to a page using the block editor or shortcode:
[acm_province_selector]
```

**Stored As:**
- Logged-in users: User Meta - `acm_user_province`
- Guest users: Session - `$_SESSION['acm_selected_province']`

**Code Reference:**
```php
// Implemented in: includes/class-acm-province-manager.php
public function province_selector_shortcode()
```

---

### Method 3: Session/URL Parameter (Automatic Selection)

**Parameter:** `?province=ontario` (or `?province=alberta`, `?province=british_columbia`)

**Where:** Added to course archive URL

**Example:**
```
yoursite.com/course/?province=ontario
```

**How It Works:**
1. User clicks link with province parameter
2. System reads province from URL
3. Filters courses to that province
4. Saves to session/user meta

**Stored As:** Passed via `$_GET['province']` and sessions

---

## 🗄️ Where Province Information is Stored

### User Province Storage

| Storage Type | Key | Location | Format |
|------|--------|----------|--------|
| User Meta | `acm_user_province` | User Profile | `'ontario'`, `'alberta'`, `'british_columbia'` |
| Session | `$_SESSION['acm_selected_province']` | Server Session | `'ontario'`, `'alberta'`, `'british_columbia'` |
| Default | Site Option | Settings | `'ontario'` (hardcoded as default) |

**Query Users by Province:**
```sql
-- Find all Ontario users
SELECT user_id FROM wp_usermeta 
WHERE meta_key = 'acm_user_province' 
AND meta_value = 'ontario';
```

---

### Course Province Storage

| Data | Storage | Meta Key | Format |
|------|---------|----------|--------|
| Course Province | Post Meta | `_acm_course_province` | `'ontario'`, `'alberta'`, `'british_columbia'` |
| Course Name | Post Title | N/A | Text (e.g., "Relationship Agreements - Ontario") |

**Set When:** Creating/editing course in admin

**Where to Set:** Course Edit > Course Settings Metabox > Province field

**Query Courses by Province:**
```sql
-- Find all Ontario courses
SELECT post_id FROM wp_postmeta 
WHERE meta_key = '_acm_course_province' 
AND meta_value = 'ontario' 
AND post_id IN (SELECT ID FROM wp_posts WHERE post_type='acm_course' AND post_status='publish');
```

---

## 🔐 Access Control Flow (Technical)

### Step 1: User Province Detection

When user visits course archive:

```php
// File: templates/archive-course.php
$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Get province from:
1. URL parameter (?province=ontario)
2. User meta (if logged in)
3. Session (if not logged in)
4. Default (ontario)

$user_province = acm_get_user_province($user_id);
// Returns: 'ontario', 'alberta', 'british_columbia', or default
```

**Code:**
```php
// In: includes/class-acm-province-manager.php
public function get_user_province($user_id = null) {
    $province = get_user_meta($user_id, 'acm_user_province', true);
    
    if (!$province && isset($_SESSION['acm_selected_province'])) {
        $province = sanitize_text_field($_SESSION['acm_selected_province']);
    }
    
    if (!$province) {
        $province = get_option('acm_default_province', 'ontario');
    }
    
    return $province;
}
```

---

### Step 2: Course Province Matching

When filtering courses on archive page:

```php
// File: templates/archive-course.php
$args = array(
    'post_type' => 'acm_course',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_acm_course_province',
            'value' => $selected_province,  // e.g., 'ontario'
            'compare' => '='
        )
    )
);

$courses = new WP_Query($args);
```

**SQL Generated:**
```sql
SELECT p.ID 
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'acm_course'
AND p.post_status = 'publish'
AND pm.meta_key = '_acm_course_province'
AND pm.meta_value = 'ontario';
```

---

### Step 3: Course Display

**If Province Matches:** ✅
- Course appears in archive list
- User can click and view course

**If Province Doesn't Match:** ❌
- Course does NOT appear in list
- Course is excluded from query results

**If User Accesses Course Directly:**
- Example: User goes to `/courses/ontario-course/` but selected Alberta
- MemberPress check runs (if enabled)
- Access may be restricted if user doesn't have membership

---

## 🏗️ Implementation Architecture

### Database Relationships

```
┌─────────────────────────────────────────────────────────────┐
│ WordPress User                                              │
│                                                             │
│ ID: 123                                                     │
│ user_login: john_smith                                      │
│                                                             │
│ ├─ User Meta: acm_user_province = 'ontario'                │
│ └─ [Stored in wp_usermeta table]                           │
│                                                             │
└─────────────────┬───────────────────────────────────────────┘
                  │ 
                  │ (User attends course)
                  │
                  ▼
┌─────────────────────────────────────────────────────────────┐
│ Post: acm_course (Ontario Course)                           │
│                                                             │
│ ID: 456                                                     │
│ post_title: Relationship Agreements - Ontario              │
│ post_type: acm_course                                       │
│                                                             │
│ ├─ Post Meta: _acm_course_province = 'ontario'            │
│ ├─ [Stored in wp_postmeta table]                           │
│ └─ Related Chapters (via _acm_chapter_course meta)         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Province Selection Workflow

### Workflow Diagram

```
┌─ New User (not logged in)
│
├─ Visits course archive
├─> No province in user meta
├─> Checks session: empty
├─> Default to: ontario
└─> Sees Ontario courses

┌─ How to override:
│
├─ Method A: Click province selector
│  ├─> Selects "Alberta"
│  ├─> Saved to session
│  └─> Now sees Alberta courses
│
├─ Method B: Visit URL with parameter
│  ├─> yoursite.com/courses/?province=british_columbia
│  ├─> System reads "british_columbia" from URL
│  └─> Filters to BC courses
│
└─ Method C: Create account & select in profile
   ├─> User registers
   ├─> Update profile: selects "Alberta"
   ├─> Saved to user meta
   └─> Always sees Alberta courses (persistent)
```

---

## 📊 Province Configuration

### Available Provinces

| Province Code | Display Name | Supported? |
|---------------|--------------|-----------|
| `ontario` | Ontario | ✅ Yes |
| `alberta` | Alberta | ✅ Yes |
| `british_columbia` | British Columbia | ✅ Yes |

**Defined In:**
```php
// File: includes/class-acm-province-manager.php
private $provinces = array(
    'ontario' => 'Ontario',
    'alberta' => 'Alberta',
    'british_columbia' => 'British Columbia'
);
```

### How to Add New Province

To add a new province (e.g., Manitoba):

1. **Edit Plugin Code** (advanced):
   - File: `includes/class-acm-province-manager.php`
   - Add to `$this->provinces` array:
     ```php
     'manitoba' => 'Manitoba'
     ```

2. **Create Province-Specific Courses**:
   - Create new course
   - Set `_acm_course_province` meta to `'manitoba'`

3. **Test**:
   - Set user province to Manitoba
   - Verify course appears

---

## 🧪 Testing Province Access

### Test Case 1: User from Ontario

1. Create test user
2. Edit user profile > Course Preferences > Select "Ontario"
3. Login as user
4. Visit `/courses/` archive
5. **Expected Result:** Only Ontario courses visible

---

### Test Case 2: User Switching Provinces

1. Logged-in user selects "Alberta" from province selector
2. Page reloads
3. **Expected Result:** Archive now shows Alberta courses

---

### Test Case 3: Direct Course Access

1. Ontario user visits `/courses/alberta-course/` (URL direct)
2. **Result:** 
   - Course page loads
   - Single course view renders
   - MemberPress membership check applies
   - Personalization filters apply

---

### Test Case 4: Guest User

1. Not logged in
2. Visit `/courses/?province=british_columbia`
3. **Expected:** BC courses appear
4. Refresh page
5. **Expected:** Courses persist (in session)

---

## 🛡️ Security Considerations

### Current Implementation

✅ **What's Protected:**
- Province filtering is enforced on archive pages
- Post meta accurately controls visibility
- User profiles are protected (only users/admins can edit)

### Potential Vulnerabilities

⚠️ **URL Parameter Tampering:**
- User could manually edit URL: `?province=ontario`
- Frontend filtering prevents this, but not foolproof
- Currently: No server-side validation that province matches user

**Recommendation:** Consider adding validation:
```php
// Suggest adding to archive-course.php
if (!current_user_can('read_private_posts')) {
    // Validate user's province matches URL parameter
    $user_province = acm_get_user_province($user_id);
    if ($selected_province !== $user_province) {
        $selected_province = $user_province;
    }
}
```

---

## 📋 Quick Reference: Province Meta Keys

### For Administrators

**To find all Ontario courses:**
```
WordPress Admin > Courses
Look at Course Settings metabox > Province field set to "Ontario"
```

**Via database:**
```sql
SELECT p.ID, p.post_title, pm.meta_value as province
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'acm_course'
AND p.post_status = 'publish'
AND pm.meta_key = '_acm_course_province'
ORDER BY pm.meta_value;
```

**To find all Alberta users:**
```sql
SELECT user_id, meta_value FROM wp_usermeta
WHERE meta_key = 'acm_user_province'
AND meta_value = 'alberta';
```

---

## 🔗 Related Features

### Personalization Quiz (Additional Filtering)

Note: Province filtering is **SEPARATE** from personalization quiz filtering.

**Personalization Quiz** filters content based on:
- Kids: yes/no
- Business: yes/no
- Pets: yes/no
- Home situation: buying_together, already_own, one_owns, renting
- Second home: yes/no

**Province** filters which course a user can access at all.

**Combined Flow:**
1. User selects province → sees appropriate province course
2. User takes personalization quiz → sees personalized lesson content within that course

---

## 📞 Common Questions & Answers

**Q: Can an Ontario user see Alberta courses?**
A: No. By default, Ontario users can only see Ontario courses via archive filter. They could visit Alberta course URL directly, but would need access (MemberPress membership).

**Q: How do I force a user to Ontario?**
A: Edit user profile > Course Preferences > Select "Ontario" > Update Profile.

**Q: What if I have no province set on a course?**
A: Course will NOT appear in filtered archives. Set a province in Course Settings metabox.

**Q: What's the default province?**
A: Ontario (hardcoded in plugin). Can be changed via `get_option('acm_default_province')`.

**Q: Can I remove the province system?**
A: Not easily without code modifications. It's built into templates and core filtering logic.

**Q: Do I need MemberPress for province filtering?**
A: No. Province filtering works independently. MemberPress adds membership access layer on top.

---

## 📝 Summary Table

| Aspect | Location | Storage | Scope |
|--------|----------|---------|-------|
| **User's Province** | User Profile Edit | `acm_user_province` user meta | Per user |
| **Course's Province** | Course Edit > Settings | `_acm_course_province` post meta | Per course |
| **Province Options** | Plugin code | `class-acm-province-manager.php` | ON/AB/BC |
| **Province Selector** | Frontend page | Session + User Meta | Dynamic |
| **Filtering Logic** | Archive template | `templates/archive-course.php` | Display |
| **Access Control** | Single course page | `acm_has_course_access()` | MemberPress + Meta |

---

*Last Updated: March 17, 2026*  
*Document Version: 1.0*
