<?php
/**
 * Admin Course Lessons Column
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Course_Lessons_Admin_Column {

    public function __construct() {
        add_filter('manage_acm_course_posts_columns', [$this, 'add_lessons_column']);
        add_action('manage_acm_course_posts_custom_column', [$this, 'render_lessons_column'], 10, 2);
        add_action('pre_get_posts', [$this, 'filter_lessons_by_course']);
    }

    /**
     * Add Lessons column
     */
    public function add_lessons_column($columns) {
        $columns['acm_course_lessons'] = __('Lessons', 'advanced-course-manager');
        return $columns;
    }

    /**
     * Render Lessons column content
     */
    public function render_lessons_column($column, $course_id) {

        if ($column !== 'acm_course_lessons') {
            return;
        }

        $lessons = get_posts([
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'   => '_acm_lesson_course',
                    'value' => $course_id,
                ]
            ],
        ]);

        if (empty($lessons)) {
            echo '<span style="color:#999;">No lessons</span>';
            return;
        }

        // Link to filtered lesson list
        $lesson_list_url = admin_url(
            'edit.php?post_type=acm_lesson&acm_lesson_course=' . $course_id
        );

        echo '<strong><a href="' . esc_url($lesson_list_url) . '">' . sprintf(__('%d Lessons', 'advanced-course-manager'), count($lessons)) . '</a></strong>';
    }

    /**
     * Filter lesson list by course
     */
    public function filter_lessons_by_course($query) {

        if (
            !is_admin() ||
            !$query->is_main_query() ||
            $query->get('post_type') !== 'acm_lesson'
        ) {
            return;
        }

        if (!empty($_GET['acm_lesson_course'])) {
            $query->set('meta_query', [
                [
                    'key'   => '_acm_lesson_course',
                    'value' => intval($_GET['acm_lesson_course']),
                ]
            ]);
        }
    }
}
