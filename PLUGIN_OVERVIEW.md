# Advanced Course Manager - Complete Plugin Overview

## 📋 Plugin Information

| Property | Value |
|----------|-------|
| **Name** | Advanced Course Manager |
| **Version** | 2.0.0 |
| **Author** | Getjointly |
| **Author URI** | https://getjointly.ca |
| **Plugin URL** | https://getjointly.ca |
| **Minimum WordPress** | 5.8 |
| **Minimum PHP** | 7.4 |
| **Text Domain** | advanced-course-manager |
| **Purpose** | Comprehensive course management system with partner collaboration, progress tracking, and seamless MemberPress integration |

---

## 🎯 Core Features Overview

### 1. **Custom Post Types & Taxonomies**
- **Post Types**: Courses, Chapters, Lessons
- **Taxonomies**: Course Categories, Modules, Tags, Difficulty Levels
- All types have REST API support enabled

### 2. **Student Progress Tracking**
- Real-time lesson completion tracking
- Time spent analytics per lesson
- Video progress tracking with bookmarks and notes
- Course-level completion percentage calculations

### 3. **Partnership System**
- Pair students taking the same course
- Shared notes and discussion threads
- Invitation system with tokens

### 4. **Notes & Discussions**
- Timestamped notes tied to video lessons
- Shared notes between partners
- Threaded discussion system for partnerships
- Read status tracking

### 5. **Certificate System**
- Two implementation options: PDF (with TCPDF) or HTML/Print-friendly
- Auto-generated upon course completion
- Unique certificate codes
- Email delivery functionality

### 6. **Personalization & Content Filtering**
- Quiz-based course personalization questionnaire
- Dynamic content visibility based on user answers
- Agreement builder for personalized documents
- Home buying situation filtering

### 7. **Provincial Management**
- Province selector (Ontario, Alberta, British Columbia)
- Province-specific course filtering
- Multi-province course support

### 8. **MemberPress Integration**
- Check user subscription status for course access
- Lesson-level access restrictions
- Membership requirement configuration

### 9. **Admin Management Dashboard**
- Reports page with student statistics
- Certificate management interface
- Bulk actions (duplicate, assign content)
- Custom admin columns and filters

### 10. **AJAX & REST API**
- Extensive AJAX handlers for real-time updates
- REST API framework in place for future expansion

---

## 📁 File Structure & Architecture

### Root Level Files
```
advanced-course-manager.php       - Main plugin file with class loader
course-filter.php                 - Content filtering for personalization
quiz-handler.php                  - Personalization quiz manager
quiz-template.php                 - Quiz template & display logic
```

### /admin/ Directory
Complete WordPress admin interface implementation:
- `class-acm-admin.php` - Main admin dashboard, reports, settings
- `class-acm-metaboxes.php` - Post metabox registration
- `class-acm-settings.php` - Plugin settings page
- `class-acm-lesson-need-to-know.php` - Key points metabox builder
- `class-acm-lesson-highlight-box.php` - Highlight boxes metabox builder
- `class-acm-lesson-callout-box.php` - Callout boxes metabox with WYSIWYG editor
- `class-acm-course-lessons-admin-column.php` - Custom admin columns
- `class-acm-admin-columns-filters.php` - Admin filters and sorting
- `class-acm-lesson-course-filter.php` - Lesson filter by course
- `class-acm-admin-row-actions.php` - Quick stats links
- `class-acm-bulk-duplicate.php` - Bulk duplicate/assign functionality

### /includes/ Directory
Core functionality and database management:

#### Database & Core
- `class-acm-database.php` - Custom table creation and schema

#### Content Management
- `class-acm-post-types.php` - Custom post type registration
- `class-acm-taxonomies.php` - Custom taxonomy registration

#### User Features
- `class-acm-progress.php` - Student progress tracking and calculations
- `class-acm-partnerships.php` - Partnership system and management
- `class-acm-notes.php` - User notes with timestamps
- `class-acm-discussions.php` - Partnership discussion threads
- `class-acm-bookmarks.php` - Video bookmarks with timestamps

#### Content & Learning
- `class-acm-customization-quiz.php` - Course-level content customization
- `class-acm-agreement-builder.php` - Personalized agreement generation
- `class-acm-certificate.php` - PDF certificate generation (TCPDF)
- `class-acm-certificate-simple.php` - Simple HTML certificate

#### System & Integration
- `class-acm-memberpress.php` - MemberPress membership integration
- `class-acm-notifications.php` - User notification system
- `class-acm-province-manager.php` - Provincial content management
- `class-acm-ajax.php` - Centralized AJAX handlers
- `class-acm-rest-api.php` - REST API framework

#### Libraries
- `/libraries/tcpdf/` - TCPDF library for PDF generation

### /public/ Directory
Frontend implementation:
- `class-acm-public.php` - Asset enqueuing and public setup
- `class-acm-shortcodes.php` - Shortcode registration
- `class-acm-templates.php` - Template management

#### CSS
- `css/public-style.css` - Main frontend styles
- `css/course-filter.css` - Personalization filter UI
- `css/advance-course-lesson.css` - Lesson-specific styles
- `css/single-course.css` - Course page styles

#### JavaScript
- `js/public-scripts.js` - Main frontend functionality and AJAX calls
- `js/next-lesson.js` - Next/previous lesson navigation

### /templates/ Directory
Frontend templates:
- `archive-course.php` - Course archive/listing page
- `single-course.php` - Single course page with chapters and lessons
- `single-chapter.php` - Single chapter page
- `single-lesson.php` - Single lesson page with video, notes, discussions

---

## 🗄️ Database Schema

### Custom Tables

#### 1. wp_acm_progress
Tracks user progress through lessons
```
- user_id (primary key)
- course_id (primary key)
- lesson_id (primary key)
- status (not_started, in_progress, completed)
- completion_date
- time_spent (in seconds)
- last_accessed (timestamp)
- video_progress (percentage)
- quiz_score
- attempts (number of attempts)
```

#### 2. wp_acm_partnerships
User partnerships for shared learning
```
- user_id_1 (primary key)
- user_id_2 (primary key)
- course_id (primary key)
- created_date
- status (active, inactive)
- invite_token
- UNIQUE(user_id_1, user_id_2, course_id)
```

#### 3. wp_acm_notes
User notes with video timestamps
```
- user_id
- lesson_id
- note_content (long text)
- is_shared (boolean, for partnerships)
- created_date
- modified_date
- video_timestamp (seconds)
```

#### 4. wp_acm_discussions
Threaded discussions for partnerships
```
- lesson_id
- partnership_id
- user_id
- message (long text)
- parent_id (for threaded replies)
- created_date
- is_read (boolean)
```

#### 5. wp_acm_certificates
Issued certificates
```
- user_id (unique with course_id)
- course_id (unique with user_id)
- certificate_code (unique, format: CERT-xxxxx)
- issued_date
- completion_time
- file_path
- email_sent (boolean)
- email_sent_date
```

#### 6. wp_acm_bookmarks
Video bookmarks with notes
```
- user_id
- lesson_id
- timestamp (seconds)
- note (text)
- created_date
```

#### 7. wp_acm_notifications
System notifications
```
- user_id
- type (partnership_created, etc.)
- title
- message
- link
- is_read (boolean)
- created_date
- INDEXED(user_id, is_read)
```

#### 8. wp_acm_activity
Activity logging for analytics
```
- user_id
- action_type
- post_id
- metadata (serialized)
- created_date
```

#### 9. wp_acm_agreement_choices
Agreement builder selections
```
- user_id
- section_id
- section_title
- choice_id
- choice_text
- choice_value
- ordering
```

### Post Meta Keys (Post Type Meta)

#### Course Meta
- `_acm_course_province` - Associated province(s)
- `_acm_course_difficulty` - Difficulty level
- `_acm_course_duration` - Duration in hours
- `_acm_memberpress_memberships` - Required membership IDs

#### Chapter Meta
- `_acm_chapter_course` - Associated course ID
- `_acm_chapter_number` - Chapter order/number
- `_acm_filter_key` - Personalization filter key

#### Lesson Meta
- `_acm_lesson_course` - Associated course ID (cached)
- `_acm_lesson_chapter` - Associated chapter ID
- `_acm_lesson_video_url` - Video URL (YouTube, Vimeo, etc.)
- `_acm_lesson_video_type` - Video platform type
- `_acm_need_to_know` - Key points array
- `_acm_highlight_boxes` - Highlight boxes array
- `_acm_callout_boxes` - Callout boxes array
- `_acm_lesson_difficulty` - Difficulty level

### User Meta Keys

#### Personalization
- `acm_personalization_quiz` - Quiz answers (serialized array)
- `acm_quiz_has_kids` - Has kids (yes/no)
- `acm_quiz_has_business` - Has business (yes/no)
- `acm_quiz_has_pets` - Has pets (yes/no)
- `acm_quiz_home_situation` - Home buying situation
- `acm_quiz_has_second_home` - Has second home (yes/no)
- `acm_quiz_completed` - Quiz completion flag

#### Settings
- `acm_course_customization` - Course customization state
- `acm_user_province` - Selected province
- `acm_completed_courses` - List of completed courses

---

## 🔧 AJAX Endpoints

All endpoints require valid nonce and are prefixed with `wp_ajax` and `wp_ajax_nopriv` hooks where applicable.

### Progress Management
- `acm_mark_lesson_complete` - Mark lesson as completed
- `acm_update_time_spent` - Track time spent on lesson
- `acm_update_video_progress` - Save video playback position

### Partnership System
- `acm_create_partnership` - Create partnership invitation
- `acm_remove_partnership` - End partnership
- `acm_generate_invite` - Generate invite token
- `acm_accept_invite` - Accept partnership invite

### Notes & Bookmarks
- `acm_save_note` - Create or update user note
- `acm_delete_note` - Delete user note
- `acm_get_notes` - Retrieve all notes for lesson
- `acm_save_bookmark` - Create video bookmark
- `acm_delete_bookmark` - Delete bookmark
- `acm_get_bookmarks` - Retrieve all bookmarks

### Discussions
- `acm_post_message` - Post discussion message
- `acm_get_messages` - Retrieve discussion thread
- `acm_mark_messages_read` - Mark messages as read

### Notifications
- `acm_mark_notification_read` - Mark notification as read
- `acm_get_notifications` - Retrieve unread notifications

### Certificates
- `acm_generate_certificate` - Generate PDF certificate
- `acm_download_certificate` - Download certificate file
- `acm_email_certificate` - Send certificate via email
- `acm_view_certificate` - View certificate (admin)

### Personalization
- `acm_save_personalization_quiz` - Save quiz answers (no-priv enabled)
- `acm_get_personalization_quiz` - Retrieve saved answers
- `acm_set_province` - Store selected province (no-priv enabled)

### Admin Only
- `acm_admin_view_certificate` - Admin certificate viewer
- `acm_admin_send_certificate` - Admin certificate sender
- `acm_admin_get_student_progress` - Fetch student stats

---

## 🏷️ Shortcodes

### Content Display
- `[acm_personalization_quiz]` - Display personalization questionnaire
- `[acm_customization_quiz]` - Alternative quiz shortcode
- `[acm_province_selector]` - Province selection dropdown
- `[acm_agreement_builder]` - Agreement builder interface
- `[acm_agreement_option]` - Individual agreement option
- `[acm_callout]` - Displayed callout box (shortcode output)

### Display Shortcodes (Framework in place)
- `[acm_course_list]` - List courses (template-based)
- `[acm_lesson_progress]` - Show student progress
- `[acm_partnership_info]` - Display partnership information

---

## 🎓 Admin Features

### Dashboard & Reports
- **Statistics Dashboard**: Course counts, student counts, completion rates
- **Hierarchical Filtering**: Filter reports by course → chapter → lesson
- **Student Analytics**: Time spent, attempts, quiz scores
- **Certificate Management**: Issue, email, download certificates

### Metabox Builders

#### Course Metabox
- General course settings
- MemberPress membership requirements
- Province associations
- Course difficulty and duration

#### Chapter Metabox
- Course assignment (required)
- Chapter numbering and ordering
- Personalization filter keys

#### Lesson Metabox
- Course and chapter assignment
- Video URL and type configuration
- Need-to-Know points builder (repeater)
- Highlight boxes builder (complex nested repeater)
- Callout boxes builder (WYSIWYG with infinite repeater)

### Admin Columns
- **Courses**: Chapter count with filter link
- **Chapters**: Course assignment, lesson count
- **Lessons**: Chapter, student count, completion %, progress bar

### Admin Filters
- Filter lessons by course dropdown
- Filter chapters by course
- Custom sorting by menu order, title, date

### Bulk Actions
- **Duplicate**: Clone courses/lessons with all meta
- **Assign to Course**: Bulk assign lessons/chapters
- **Assign to Chapter**: Bulk assign lessons
- **Statistics**: Quick link to reports

---

## 🔌 Integrations

### MemberPress Integration
- Access control per course
- Membership requirement configuration
- Automatic content restriction
- Graceful fallback if plugin not active

### REST API Framework
- Foundation in place for future API expansion
- Custom endpoints ready to be built

### Video Platforms Support
- YouTube
- Vimeo
- Self-hosted video files
- Configurable per lesson

---

## 🚀 Key Functions & Hooks

### Public Functions (Frontend)
```php
// Progress Tracking
acm_get_course_progress($user_id, $course_id)
acm_get_lesson_progress($user_id, $lesson_id)
acm_mark_lesson_complete($user_id, $lesson_id)

// Partnerships
acm_get_user_partner($user_id, $course_id)
acm_create_partnership($user_id_1, $user_id_2, $course_id)

// Notes & Discussions
acm_get_user_notes($user_id, $lesson_id)
acm_save_note($user_id, $lesson_id, $content, $timestamp)

// Certificates
acm_generate_certificate($user_id, $course_id)
acm_has_course_certificate($user_id, $course_id)

// Personalization
acm_get_user_quiz_answers($user_id)
acm_is_content_hidden_for_user($post_id, $user_id)

// Province Management
acm_get_user_province($user_id)
acm_set_user_province($user_id, $province)
```

### Hooks & Filters
```php
// Access Control
apply_filters('acm_can_access_course', $has_access, $user_id, $course_id)
apply_filters('acm_can_access_lesson', $has_access, $user_id, $lesson_id)

// Content Display
apply_filters('acm_lesson_content', $content, $lesson_id, $user_id)
apply_filters('acm_course_chapters', $chapters, $course_id)

// Progress Tracking
do_action('acm_lesson_completed', $user_id, $lesson_id, $course_id)
do_action('acm_video_progress_updated', $user_id, $lesson_id, $progress)

// Templates
apply_filters('acm_template_include', $template_path)

// Notifications
do_action('acm_send_notification', $user_id, $type, $title, $message, $link)
```

---

## 🔐 Security Features

- Nonce verification on all AJAX endpoints
- User capability checks for admin functions
- Escaping/sanitization on all outputs
- SQL parameterization in database queries
- Role-based access control
- Admin-only sensitive functions

---

## 📊 Features Summary Table

| Feature | Implemented | Location | Status |
|---------|-------------|----------|--------|
| Course Management | ✅ | includes/, admin/ | Full |
| Progress Tracking | ✅ | class-acm-progress.php | Full |
| Partnerships | ✅ | class-acm-partnerships.php | Full |
| Notes System | ✅ | class-acm-notes.php | Partial |
| Discussions | ✅ | class-acm-discussions.php | Partial |
| Certificates (PDF) | ✅ | class-acm-certificate.php | Full |
| Certificates (HTML) | ✅ | class-acm-certificate-simple.php | Full |
| Personalization Quiz | ✅ | quiz-handler.php | Full |
| Content Filtering | ✅ | course-filter.php | Full |
| Agreement Builder | ✅ | class-acm-agreement-builder.php | Full |
| Province Manager | ✅ | class-acm-province-manager.php | Full |
| MemberPress Integration | ✅ | class-acm-memberpress.php | Full |
| Notifications | ✅ | class-acm-notifications.php | Partial |
| Admin Dashboard | ✅ | admin/class-acm-admin.php | Full |
| Admin Metaboxes | ✅ | admin/ | Full |
| Bulk Actions | ✅ | admin/class-acm-bulk-duplicate.php | Full |
| REST API | ⏳ | class-acm-rest-api.php | Framework |

---

## 🎯 Use Cases

1. **Real Estate Education Platform**
   - Province-specific courses (ON, AB, BC)
   - Personalized content based on home-buying situation
   - Partner collaboration for joint home purchases

2. **Professional Certification**
   - Track progress through multi-chapter courses
   - Issue PDF certificates upon completion
   - Membership-based access control

3. **Corporate Training**
   - Employee course enrollment and tracking
   - Time-spent analytics
   - Certificate generation and distribution

4. **Collaborative Learning**
   - Partner system for peer learning
   - Discussion threads and shared notes
   - Real-time progress indicators

---

## 📝 Version History

| Version | Changes |
|---------|---------|
| 2.0.0 | Current version - Full feature suite |
| 1.2.0 | Previous stable version |

---

## 🔄 Dependencies

- **WordPress**: 5.8+
- **PHP**: 7.4+
- **Optional**: MemberPress (for membership integration)
- **Libraries**: TCPDF (included for PDF generation)

---

## 📞 Support & Documentation

**Author**: Getjointly  
**Website**: https://getjointly.ca  
**Text Domain**: advanced-course-manager

---

*Last Updated: March 17, 2026*
*Document Version: 1.0*
