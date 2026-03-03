<?php
/**
 * Admin Columns & Filters - Combined
 * Consolidates all admin column and filter classes
 * File: admin/class-acm-course-lessons-admin-column.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('acm_get_chapter_ids_for_course')) {
    function acm_get_chapter_ids_for_course($course_id) {
        $course_id = (int) $course_id;
        if (!$course_id) {
            return array();
        }

        $meta_ids = get_posts(array(
            'post_type'        => 'acm_chapter',
            'posts_per_page'   => -1,
            'post_status'      => 'any',
            'fields'           => 'ids',
            'suppress_filters' => true,
            'meta_query'       => array(
                'relation' => 'OR',
                array(
                    'key'     => '_acm_chapter_course',
                    'value'   => $course_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC'
                ),
                array(
                    'key'     => 'acm_chapter_course',
                    'value'   => $course_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC'
                ),
                array(
                    'key'     => 'course_id',
                    'value'   => $course_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC'
                )
            )
        ));

        $parent_ids = get_posts(array(
            'post_type'        => 'acm_chapter',
            'posts_per_page'   => -1,
            'post_status'      => 'any',
            'fields'           => 'ids',
            'suppress_filters' => true,
            'post_parent'      => $course_id
        ));

        return array_values(array_unique(array_merge($meta_ids, $parent_ids)));
    }
}

/**
 * Chapter Lessons Admin Column
 * Displays number of lessons in each chapter
 */
class ACM_Chapter_Lessons_Admin_Column {

    public function __construct() {
        add_filter('manage_acm_chapter_posts_columns', array($this, 'add_columns'));
        add_action('manage_acm_chapter_posts_custom_column', array($this, 'render_columns'), 10, 2);
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
            if (!$course_id) {
                $course_id = get_post_meta($chapter_id, 'acm_chapter_course', true);
            }
            if (!$course_id) {
                $course_id = get_post_meta($chapter_id, 'course_id', true);
            }
            if (!$course_id) {
                $chapter = get_post($chapter_id);
                $course_id = $chapter ? (int) $chapter->post_parent : 0;
            }
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
            $lessons = get_posts(array(
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'meta_query'     => array(
                    array(
                        'key'   => '_acm_lesson_chapter',
                        'value' => $chapter_id,
                    )
                ),
            ));

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
        add_filter('manage_acm_lesson_posts_columns', array($this, 'add_columns'));
        add_action('manage_acm_lesson_posts_custom_column', array($this, 'render_columns'), 10, 2);
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
                // Get chapter and course info for proper URL
                $chapter_id = get_post_meta($lesson_id, '_acm_lesson_chapter', true);
                $course_id = $chapter_id ? get_post_meta($chapter_id, '_acm_chapter_course', true) : 0;
                
                // Link to reports page filtered by this lesson
                $lesson_progress_url = admin_url(
                    'edit.php?post_type=acm_course&page=acm-reports&acm_course_filter=' . intval($course_id) . '&acm_chapter_filter=' . intval($chapter_id) . '&acm_lesson_filter=' . intval($lesson_id)
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
        add_action('restrict_manage_posts', array($this, 'add_filters'));
        add_action('pre_get_posts', array($this, 'apply_filters'));
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

        $courses = get_posts(array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

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

        $chapters = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

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
            !$query->is_main_query()
        ) {
            return;
        }

        $post_type = $query->get('post_type');
        if (is_array($post_type)) {
            if (!in_array('acm_lesson', $post_type, true)) {
                return;
            }
        } elseif ($post_type !== 'acm_lesson') {
            return;
        }

        $meta_queries = array();

        if (!empty($_GET['acm_lesson_course'])) {
            $course_id = intval($_GET['acm_lesson_course']);
            $chapter_ids = acm_get_chapter_ids_for_course($course_id);

            if (empty($chapter_ids)) {
                $query->set('post__in', array(0));
                return;
            }

            $meta_queries[] = array(
                'key'   => '_acm_lesson_chapter',
                'value' => $chapter_ids,
                'compare' => 'IN'
            );
        }

        if (!empty($_GET['acm_lesson_chapter'])) {
            $meta_queries[] = array(
                'key'   => '_acm_lesson_chapter',
                'value' => intval($_GET['acm_lesson_chapter']),
            );
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
        add_action('restrict_manage_posts', array($this, 'add_course_dropdown'));
        add_action('pre_get_posts', array($this, 'filter_chapters_by_course'));
    }

    /**
     * Add Course dropdown filter on Chapters admin page
     */
    public function add_course_dropdown($post_type) {
        if ($post_type !== 'acm_chapter') {
            return;
        }

        $selected_course = 0;
        if (!empty($_GET['acm_course_id'])) {
            $selected_course = intval($_GET['acm_course_id']);
        } elseif (!empty($_GET['acm_course'])) {
            $selected_course = intval($_GET['acm_course']);
        }

        $courses = get_posts(array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        if (empty($courses)) {
            return;
        }

        echo '<select name="acm_course_id">';
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
            !$query->is_main_query()
        ) {
            return;
        }

        $post_type = $query->get('post_type');
        if (is_array($post_type)) {
            if (!in_array('acm_chapter', $post_type, true)) {
                return;
            }
        } elseif ($post_type !== 'acm_chapter') {
            return;
        }

        $course_id = 0;
        if (!empty($_GET['acm_course_id'])) {
            $course_id = intval($_GET['acm_course_id']);
        } elseif (!empty($_GET['acm_course'])) {
            $course_id = intval($_GET['acm_course']);
        } elseif (!empty($_GET['acm_course_filter'])) {
            $course_id = intval($_GET['acm_course_filter']);
        }

        if ($course_id) {
            $chapter_ids = acm_get_chapter_ids_for_course($course_id);

            if (empty($chapter_ids)) {
                // No chapters found
                $query->set('post__in', array(0));
            } else {
                // Set the found chapter IDs
                $query->set('post__in', $chapter_ids);
            }
        }
    }
}

/**
 * Course Admin Columns
 * Shows chapter and lesson counts for each course with links to filtered lists
 */
class ACM_Course_Admin_Column {

    public function __construct() {
        add_filter('manage_acm_course_posts_columns', array($this, 'add_columns'));
        add_action('manage_acm_course_posts_custom_column', array($this, 'render_columns'), 10, 2);
    }

    /**
     * Add columns to course listing
     */
    public function add_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['acm_course_chapters'] = __('Chapters', 'advanced-course-manager');
                $new_columns['acm_course_lessons']  = __('Lessons', 'advanced-course-manager');
            }
        }
        return $new_columns;
    }

    /**
     * Render column content for courses
     */
    public function render_columns($column, $course_id) {
        if ($column === 'acm_course_chapters') {
            $chapter_ids = acm_get_chapter_ids_for_course($course_id);
            $count = count($chapter_ids);

            if ($count === 0) {
                echo '<span style="color:#999;">0</span>';
                return;
            }

            $url = admin_url('edit.php?post_type=acm_chapter&acm_course_id=' . $course_id);
            echo '<a href="' . esc_url($url) . '"><strong>' . esc_html($count) . '</strong></a>';
            return;
        }

        if ($column === 'acm_course_lessons') {
            $chapter_ids = acm_get_chapter_ids_for_course($course_id);

            $lessons = array();
            if (!empty($chapter_ids)) {
                $lessons = get_posts(array(
                    'post_type'      => 'acm_lesson',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_query'     => array(
                        array(
                            'key'     => '_acm_lesson_chapter',
                            'value'   => $chapter_ids,
                            'compare' => 'IN'
                        )
                    ),
                    'fields' => 'ids',
                ));
            }

            $count = is_array($lessons) ? count($lessons) : 0;

            if ($count === 0) {
                echo '<span style="color:#999;">No lessons</span>';
                return;
            }

            $url = admin_url('edit.php?post_type=acm_lesson&acm_lesson_course=' . $course_id);
            echo '<a href="' . esc_url($url) . '"><strong>' . sprintf(__('%d Lessons', 'advanced-course-manager'), $count) . '</strong></a>';
            return;
        }
    }
}

/**
 * Course Chapter Filter
 * Filters courses by whether they have chapters
 */
class ACM_Course_Chapter_Filter {

    public function __construct() {
        add_action('restrict_manage_posts', array($this, 'add_chapter_filter'));
        add_action('pre_get_posts', array($this, 'filter_courses_by_chapters'), 20);
    }

    /**
     * Add Chapters dropdown filter on Courses admin page
     */
    public function add_chapter_filter($post_type) {
        if ($post_type !== 'acm_course') {
            return;
        }

        $selected = '';
        if (!empty($_GET['acm_course_chapters'])) {
            $selected = sanitize_text_field(wp_unslash($_GET['acm_course_chapters']));
        }

        echo '<select name="acm_course_chapters">';
        echo '<option value="">' . esc_html__('All Chapters', 'advanced-course-manager') . '</option>';
        echo '<option value="has"' . selected($selected, 'has', false) . '>'
            . esc_html__('Has Chapters', 'advanced-course-manager') . '</option>';
        echo '<option value="none"' . selected($selected, 'none', false) . '>'
            . esc_html__('No Chapters', 'advanced-course-manager') . '</option>';
        echo '</select>';
    }

    /**
     * Apply Chapters filter to Courses query
     */
    public function filter_courses_by_chapters($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if (is_array($post_type)) {
            if (!in_array('acm_course', $post_type, true)) {
                return;
            }
        } elseif ($post_type !== 'acm_course') {
            return;
        }

        if (empty($_GET['acm_course_chapters'])) {
            return;
        }

        $filter = sanitize_text_field(wp_unslash($_GET['acm_course_chapters']));
        if (!in_array($filter, array('has', 'none'), true)) {
            return;
        }

        $course_ids = $this->get_course_ids_with_chapters();

        if ($filter === 'has') {
            $query->set('post__in', !empty($course_ids) ? $course_ids : array(0));
            return;
        }

        if (!empty($course_ids)) {
            $query->set('post__not_in', $course_ids);
        }
    }

    private function get_course_ids_with_chapters() {
        global $wpdb;

        $meta_keys = array('_acm_chapter_course', 'acm_chapter_course', 'course_id');
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $meta_sql = $wpdb->prepare(
            "SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.post_type = %s
               AND pm.meta_key IN ($placeholders)
               AND pm.meta_value <> ''",
            array_merge(array('acm_chapter'), $meta_keys)
        );

        $parent_sql = $wpdb->prepare(
            "SELECT DISTINCT post_parent
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_parent > 0",
            'acm_chapter'
        );

        $ids = $wpdb->get_col("($meta_sql) UNION ($parent_sql)");
        if (empty($ids)) {
            return array();
        }

        $course_ids = array();
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id) {
                $course_ids[$id] = true;
            }
        }

        return array_keys($course_ids);
    }
}

/**
 * INSTANTIATE ALL CLASSES
 */
new ACM_Chapter_Lessons_Admin_Column();
new ACM_Lesson_Stats_Admin_Column();
new ACM_Lesson_Chapter_Filter();
new ACM_Chapter_Course_Filter();
new ACM_Course_Admin_Column();
new ACM_Course_Chapter_Filter();