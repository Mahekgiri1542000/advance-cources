<?php
/**
 * Admin Columns & Filters - Combined
 * Consolidates all admin column and filter classes
 * File: admin/class-acm-admin-columns-filters.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chapter Lessons Admin Column
 * Displays number of lessons in each chapter
 */
class ACM_Chapter_Lessons_Admin_Column {

    public function __construct() {
        add_filter('manage_acm_chapter_posts_columns', [$this, 'add_columns']);
        add_action('manage_acm_chapter_posts_custom_column', [$this, 'render_columns'], 10, 2);
    }

    /**
     * Add columns to chapter listing
     */
    public function add_columns($columns) {
        // Insert after title, before date
        $new_columns = array();
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['acm_chapter_course'] = __('Course', 'advanced-course-manager');
                $new_columns['acm_chapter_lessons'] = __('Lessons', 'advanced-course-manager');
            }
        }
        return $new_columns;
    }

    /**
     * Render column content
     */
    public function render_columns($column, $chapter_id) {
        if ($column === 'acm_chapter_course') {
            $course_id = get_post_meta($chapter_id, '_acm_chapter_course', true);
            if ($course_id) {
                echo '<a href="' . esc_url(get_edit_post_link($course_id)) . '">';
                echo esc_html(get_the_title($course_id));
                echo '</a>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            return;
        }

        if ($column === 'acm_chapter_lessons') {
            $lessons = get_posts([
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'meta_query'     => [
                    [
                        'key'   => '_acm_lesson_chapter',
                        'value' => $chapter_id,
                    ]
                ],
            ]);

            if (empty($lessons)) {
                echo '<span style="color:#999;">No lessons</span>';
                return;
            }

            $lesson_list_url = admin_url(
                'edit.php?post_type=acm_lesson&acm_lesson_chapter=' . $chapter_id
            );

            echo '<strong><a href="' . esc_url($lesson_list_url) . '">'
                . sprintf(__('%d Lessons', 'advanced-course-manager'), count($lessons))
                . '</a></strong>';
        }
    }
}

/**
 * Lesson Stats Admin Column
 * Displays lesson progress and statistics
 */
class ACM_Lesson_Stats_Admin_Column {

    public function __construct() {
        add_filter('manage_acm_lesson_posts_columns', [$this, 'add_columns']);
        add_action('manage_acm_lesson_posts_custom_column', [$this, 'render_columns'], 10, 2);
    }

    /**
     * Add columns to lesson listing
     */
    public function add_columns($columns) {
        // Insert before date
        $new_columns = array();
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['acm_lesson_chapter'] = __('Chapter', 'advanced-course-manager');
                $new_columns['acm_lesson_students'] = __('Students', 'advanced-course-manager');
                $new_columns['acm_lesson_progress'] = __('Completion', 'advanced-course-manager');
            }
        }
        return $new_columns;
    }

    /**
     * Render column content
     */
    public function render_columns($column, $lesson_id) {
        global $wpdb;

        if ($column === 'acm_lesson_chapter') {
            $chapter_id = get_post_meta($lesson_id, '_acm_lesson_chapter', true);
            if ($chapter_id) {
                echo '<a href="' . esc_url(get_edit_post_link($chapter_id)) . '">';
                echo esc_html(get_the_title($chapter_id));
                echo '</a>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            return;
        }

        if ($column === 'acm_lesson_students') {
            $table = $wpdb->prefix . 'acm_progress';
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM $table WHERE lesson_id = %d",
                $lesson_id
            ));
            
            if ($count > 0) {
                // Link to reports page filtered by lesson
                $lesson_progress_url = admin_url(
                    'admin.php?page=acm-reports&filter_lesson=' . $lesson_id
                );
                echo '<a href="' . esc_url($lesson_progress_url) . '">';
                echo '<strong style="color:#0073aa;">' . esc_html($count) . '</strong>';
                echo '</a>';
            } else {
                echo '<span style="color:#999;">0</span>';
            }
            return;
        }

        if ($column === 'acm_lesson_progress') {
            $table = $wpdb->prefix . 'acm_progress';
            
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE lesson_id = %d",
                $lesson_id
            ));

            if ($total === 0) {
                echo '<span style="color:#999;">—</span>';
                return;
            }

            $completed = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE lesson_id = %d AND status = 'completed'",
                $lesson_id
            ));

            $rate = round(($completed / $total) * 100);

            echo '<div style="display:flex; align-items:center; gap:8px; width:150px;">';
            echo '<div style="flex:1; height:4px; background:#f1f1f1; border-radius:2px; overflow:hidden;">';
            echo '<div style="width: ' . esc_attr($rate) . '%; height:100%; background:#4CAF50;"></div>';
            echo '</div>';
            echo '<span style="font-size:11px; font-weight:600; white-space:nowrap;">';
            echo esc_html($rate . '%');
            echo '</span>';
            echo '</div>';
            return;
        }
    }
}

/**
 * Lesson Chapter Filter
 * Filters lessons by course and chapter
 */
class ACM_Lesson_Chapter_Filter {

    public function __construct() {
        add_action('restrict_manage_posts', [$this, 'add_filters']);
        add_action('pre_get_posts', [$this, 'apply_filters']);
    }

    /**
     * Add Chapter and Course dropdown filters on Lessons admin page
     */
    public function add_filters($post_type) {
        if ($post_type !== 'acm_lesson') {
            return;
        }

        // Course filter
        $selected_course = isset($_GET['acm_lesson_course']) ? intval($_GET['acm_lesson_course']) : '';

        $courses = get_posts([
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (!empty($courses)) {
            echo '<select name="acm_lesson_course">';
            echo '<option value="">' . esc_html__('All Courses', 'advanced-course-manager') . '</option>';

            foreach ($courses as $course) {
                printf(
                    '<option value="%d"%s>%s</option>',
                    $course->ID,
                    selected($selected_course, $course->ID, false),
                    esc_html($course->post_title)
                );
            }

            echo '</select>';
        }

        // Chapter filter
        $selected_chapter = isset($_GET['acm_lesson_chapter']) ? intval($_GET['acm_lesson_chapter']) : '';

        $chapters = get_posts([
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (!empty($chapters)) {
            echo '<select name="acm_lesson_chapter">';
            echo '<option value="">' . esc_html__('All Chapters', 'advanced-course-manager') . '</option>';

            foreach ($chapters as $chapter) {
                printf(
                    '<option value="%d"%s>%s</option>',
                    $chapter->ID,
                    selected($selected_chapter, $chapter->ID, false),
                    esc_html($chapter->post_title)
                );
            }

            echo '</select>';
        }
    }

    /**
     * Apply Course and Chapter filters to Lessons query
     */
    public function apply_filters($query) {
        if (
            !is_admin() ||
            !$query->is_main_query() ||
            $query->get('post_type') !== 'acm_lesson'
        ) {
            return;
        }

        $meta_queries = [];

        if (!empty($_GET['acm_lesson_course'])) {
            $meta_queries[] = [
                'key'   => '_acm_lesson_course',
                'value' => intval($_GET['acm_lesson_course']),
            ];
        }

        if (!empty($_GET['acm_lesson_chapter'])) {
            $meta_queries[] = [
                'key'   => '_acm_lesson_chapter',
                'value' => intval($_GET['acm_lesson_chapter']),
            ];
        }

        if (!empty($meta_queries)) {
            if (count($meta_queries) > 1) {
                $meta_queries['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_queries);
        }
    }
}

/**
 * Chapter Course Filter
 * Filters chapters by course
 */
class ACM_Chapter_Course_Filter {

    public function __construct() {
        add_action('restrict_manage_posts', [$this, 'add_course_dropdown']);
        add_action('pre_get_posts', [$this, 'filter_chapters_by_course']);
    }

    /**
     * Add Course dropdown filter on Chapters admin page
     */
    public function add_course_dropdown($post_type) {
        if ($post_type !== 'acm_chapter') {
            return;
        }

        $selected_course = isset($_GET['acm_course']) ? intval($_GET['acm_course']) : '';

        $courses = get_posts([
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($courses)) {
            return;
        }

        echo '<select name="acm_course">';
        echo '<option value="">' . esc_html__('All Courses', 'advanced-course-manager') . '</option>';

        foreach ($courses as $course) {
            printf(
                '<option value="%d"%s>%s</option>',
                $course->ID,
                selected($selected_course, $course->ID, false),
                esc_html($course->post_title)
            );
        }

        echo '</select>';
    }

    /**
     * Apply Course filter to Chapters query
     */
    public function filter_chapters_by_course($query) {
        if (
            !is_admin() ||
            !$query->is_main_query() ||
            $query->get('post_type') !== 'acm_chapter'
        ) {
            return;
        }

        if (!empty($_GET['acm_course'])) {
            $query->set('meta_query', [
                [
                    'key'   => '_acm_chapter_course',
                    'value' => intval($_GET['acm_course']),
                ]
            ]);
        }
    }
}

/**
 * INSTANTIATE ALL CLASSES
 */
new ACM_Chapter_Lessons_Admin_Column();
new ACM_Lesson_Stats_Admin_Column();
new ACM_Lesson_Chapter_Filter();
new ACM_Chapter_Course_Filter();
