# Standard Operating Procedure (SOP) - Course Duplication Checklist

## Short SOP Checklist

Use this quick checklist when you need to duplicate a course without going through the full document.

### Duplicate the Course
1. Go to **WordPress Admin > Courses**.
2. Select the course you want to copy.
3. In **Bulk Actions**, choose **Duplicate Selected Courses**.
4. Click **Apply**.
5. Open the new duplicated course draft.

### Update the New Course
1. Rename the course.
2. Set the correct **Province** in **Course Settings**.
3. Check the **MemberPress memberships** assigned to the course.
4. Confirm the duplicated chapters are attached to the new course.
5. Confirm the duplicated lessons are attached to the correct chapters.

### Verify Before Publishing
1. Open a few lessons and confirm video settings, filter keys, and content blocks copied correctly.
2. Update any province-specific wording or legal references.
3. Preview the course on the front end.
4. Confirm the course appears in the correct province view.
5. Publish when ready.

---

## How Course Visibility Is Assigned

### Plain-English Summary

Course visibility in this plugin is controlled by **two separate things**:

1. **The course's province**
2. **The user's province and/or membership access**

For example, if you only want Ontario users to see the Ontario course:

- The **Ontario course** must have its **Province** set to `Ontario`
- The **user** must have their province set to `Ontario`
- If MemberPress is being used, the user must also have access to the correct Ontario membership/product if one is required

### Where the Province Is Set

#### On the Course
- Edit the course
- In **Course Settings**, set **Province** to `Ontario`, `Alberta`, or `British Columbia`
- This saves the course meta: `_acm_course_province`

#### On the User
- Go to **WordPress Admin > Users > Edit User**
- Scroll to **Course Preferences**
- Set **Province** for that user
- This saves the user meta: `acm_user_province`

### How a User Gets Assigned a Province

Users can be assigned a province in three ways:

1. **Manually by admin**
  - Edit the user profile and select the province
2. **By the frontend province selector**
  - The shortcode `[acm_province_selector]` lets a user choose their province
3. **By session/default setting**
  - If nothing is selected, the plugin falls back to the default province, which is currently Ontario

### Important Clarification

The plugin stores the user's province correctly, but the course archive currently filters mostly by the **selected province in the page URL/dropdown**, not strictly by the saved user province alone.

That means:

- A course must still be tagged with the correct province
- A user can be assigned Ontario in their profile
- But the archive page still needs to be showing/filtering Ontario courses for that user to see only Ontario items

### Best Practice for Ontario-Only Access

If you want Ontario users to view only the Ontario course:

1. Set the Ontario course province to **Ontario**
2. Set Ontario users' profile province to **Ontario**
3. If using MemberPress, assign the Ontario membership/product to those users
4. Do not assign Ontario users to Alberta or BC memberships/courses
5. Test using an Ontario user account on the course archive and the course page itself

### Quick Admin Checklist for User Access

- User profile province set to **Ontario**
- Course province set to **Ontario**
- Course status set to **Published**
- Correct MemberPress membership assigned, if required
- User can see the course in the intended archive/filter view

---

## 📋 Quick Reference: How to Duplicate a Course

### Access the Bulk Duplicate Feature
1. Go to **WordPress Admin > Courses** (ACM Course post type list)
2. Look at the course you want to duplicate
3. Check the checkbox next to the course title
4. In the **Bulk Actions** dropdown at the top, select **"Duplicate Selected Courses"**
5. Click **Apply**

---

## ✅ Step-by-Step Duplication Process

### Pre-Duplication (Planning)

**STEP 1: Plan Your Course Structure**
- [ ] Identify which course to duplicate (source course)
- [ ] Determine the new course name/title
- [ ] Decide if it's for a different province (e.g., Ontario → Alberta)
- [ ] Plan new course settings (difficulty, duration, membership requirements)

**STEP 2: Verify Source Course is Complete**
- [ ] All chapters are created
- [ ] All lessons are assigned to chapters
- [ ] All lesson content is published (videos, callout boxes, highlight boxes)
- [ ] All lesson meta is properly configured
  - Video URLs set
  - Filter keys assigned (if personalization is enabled)
  - Callout boxes, highlight boxes, need-to-know points added

---

### Duplication (Execution)

**STEP 3: Select and Duplicate**
- [ ] Go to **Courses list** in WordPress Admin
- [ ] Select ONE OR MULTIPLE courses to duplicate by checking checkboxes
- [ ] From **Bulk Actions** dropdown, select **"Duplicate Selected Courses"**
- [ ] Click **Apply**

**What Gets Duplicated Automatically:**
- ✅ Course title, content, excerpt, featured image
- ✅ Course meta (province, difficulty, duration)
- ✅ MemberPress membership requirements
- ✅ ALL associated chapters (hierarchical structure maintained)
- ✅ ALL lessons within those chapters
- ✅ ALL lesson meta:
  - Video URLs
  - Callout boxes
  - Highlight boxes
  - Need-to-know points
  - Filter keys (personalization settings)
- ✅ All post settings (visibility, author, etc.)

**Status After Duplication:**
- New course: Draft status (not published)
- Course ID: New unique ID assigned
- Duplicate suffix: Added to title (e.g., "My Course - Copy")

---

### Post-Duplication (Customization)

**STEP 4: Edit the Duplicated Course**

- [ ] Go to **Courses list**
- [ ] Find the newly created course (usually titled "Course Name - Copy")
- [ ] Click **Edit**

**STEP 5: Update Course Settings**

In the **Course Settings Metabox**:
- [ ] Update course title (remove " - Copy" if needed)
- [ ] Update featured image if different province/theme
- [ ] Update course excerpt/description
- [ ] Verify course content body matches intended scope

**In Course Metaboxes:**
- [ ] **Province Assignment**: Set `_acm_course_province` to correct province
  - Options: `ontario`, `alberta`, `british_columbia`
  - Settings: Course Settings Metabox > Province field
- [ ] **Difficulty Level**: Set if different from original
- [ ] **Duration**: Update if different
- [ ] **MemberPress Memberships**: Select required memberships
  - If different from original course

**STEP 6: Verify Chapter/Lesson Structure**

- [ ] Scroll down and review all duplicated chapters
- [ ] Click on each chapter to verify:
  - [ ] Chapter title correct
  - [ ] All lessons present
  - [ ] Course assignment correct
- [ ] Click on each lesson to verify:
  - [ ] Lesson title correct
  - [ ] Video URL present and correct
  - [ ] Callout boxes intact
  - [ ] Highlight boxes intact
  - [ ] Need-to-know points intact
  - [ ] Filter keys (personalization) correct

**STEP 7: Customize Lesson Content (If Needed)**

For each lesson, if updates are needed:
- [ ] Edit lesson
- [ ] Update video URL if different
- [ ] Update callout box content
- [ ] Update highlight boxes
- [ ] Update need-to-know points
- [ ] Update filter keys for content personalization
- [ ] Save lesson

**STEP 8: Update Province-Specific Content**

If duplicating for a different province:
- [ ] Go back to course edit page
- [ ] Update all province references in lesson content
  - Search for old province name
  - Replace with new province name
- [ ] Update filter keys to match new province visibility rules
- [ ] Update any province-specific legal references
- [ ] Update any province-specific callout boxes

**STEP 9: Preview Course Chapters**

- [ ] Save course as Draft
- [ ] Click **View** or **Preview** to see how it displays
- [ ] Verify all chapters are visible in hierarchy
- [ ] Click through lessons to verify links work
- [ ] Verify videos load correctly

---

### Publishing & Activation

**STEP 10: Set Course Visibility**

- [ ] Go back to course edit page
- [ ] Set status:
  - [ ] **Published**: For live courses
  - [ ] **Draft**: For courses still under review
- [ ] Ensure appropriate publishing date if scheduled
- [ ] Save course

**STEP 11: Verify Navigation & Access**

- [ ] Go to **Courses Archive** page (public site)
- [ ] Filter by province (if province selector is available)
- [ ] Verify new course appears
- [ ] Click on course
- [ ] Verify all chapters and lessons display correctly
- [ ] Test partnership features (if applicable)
- [ ] Test progress tracking (as test user, if possible)

---

## 🔄 Alternative Duplication Methods

### Method 2: Bulk Assign (For Existing Content)

If you already have chapters/lessons created:

**STEP 1:** Go to **Lessons** or **Chapters** list, select multiple items

**STEP 2:** From **Bulk Actions**, select **"Assign Selected [Items] to Course"**

**STEP 3:** Select target course from dropdown

**STEP 4:** Click **Apply**

**Use Case:** When you've created individual lessons and chapters in bulk, then want to assign them to an existing course all at once.

---

### Method 3: Inline Edit (Quick Updates)

**STEP 1:** Go to **Lessons** list

**STEP 2:** Click **Quick Edit** (appears under lesson title on hover)

**STEP 3:** Update course/chapter assignment

**STEP 4:** Click **Update**

**Use Case:** Quick reassignment of a single lesson to a different course.

---

## 📊 Pre-Duplication Checklist (Complete)

Use this before duplicating:

```
Source Course: _____________________ (Title)

Course ID: _____________________

CONTENT VERIFICATION:
☐ All chapters created and ordered
☐ All lessons created and assigned to chapters
☐ All lessons have video URLs (if required)
☐ All lessons have callout boxes (if required)
☐ All lessons have highlight boxes (if required)
☐ All lessons have need-to-know points (if required)
☐ All lessons have filter keys assigned (if using personalization)
☐ All course metadata populated (province, difficulty, duration)
☐ Course featured image is final/correct
☐ Course excerpt/description is final

NEW COURSE DETAILS:
New Title: _____________________
New Province: _____________________
New Difficulty: _____________________
New Duration: _____________________
Required Memberships: _____________________

PERSONALIZATION SETTINGS:
☐ Filter keys map to same content visibility rules
☐ Quiz answers filtering logic is appropriate
☐ All conditional content is properly mapped
```

---

## 📋 Post-Duplication Completion Checklist

After completing all steps above:

```
DUPLICATION COMPLETE CHECKLIST:
☐ Source course identified and verified
☐ Duplicate action executed
☐ New course title updated
☐ New course province set correctly
☐ All chapters and lessons present
☐ All lesson metadata verified
☐ Course preview tested
☐ Course navigation tested
☐ User viewing tested (filtered by province)
☐ Course published
☐ Course visible on archive/filtered pages
☐ All documentation updated (if applicable)
☐ Stakeholders informed of new course availability
```

---

## 🎯 Time Estimates

| Task | Time |
|------|------|
| Pre-duplication planning | 10-15 min |
| Execute duplication | 2-3 min |
| Post-duplication customization | 15-30 min |
| Content verification | 10-20 min |
| Preview & testing | 10-15 min |
| Publishing | 2-3 min |
| **TOTAL** | **45-85 minutes** |

---

## 📞 Support Tips

- **Bulk Duplicate** only works for posts with status of any (publish, draft, etc.)
- Duplicates maintain all **post relationships** (chapters to courses, lessons to chapters)
- Duplicates copy **ALL post meta** exactly as-is
- Province filtering is controlled by `_acm_course_province` meta + user's `acm_user_province` meta
- Users can change their province via user profile or `[acm_province_selector]` shortcode

---

*Last Updated: March 17, 2026*
*Version: 1.0*
