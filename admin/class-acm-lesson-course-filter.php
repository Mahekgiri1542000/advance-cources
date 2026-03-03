<?php
/**
 * Lesson Admin – Course Filter & Sorting
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Lesson_Course_Filter {

    public function __construct() {
        add_action('restrict_manage_posts', [$this, 'add_course_dropdown']);
        add_action('pre_get_posts', [$this, 'filter_lessons_by_course']);
    }

    /**
     * Add Course dropdown filter on Lessons admin page
     */
    public function add_course_dropdown($post_type) {

        if ($post_type !== 'acm_lesson') {
            return;
        }

        $selected_course = isset($_GET['acm_lesson_course']) ? intval($_GET['acm_lesson_course']) : '';

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

    /**
     * Apply Course filter to Lessons query
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
            $course_id = intval($_GET['acm_lesson_course']);
            $chapter_ids = get_posts([
                'post_type'      => 'acm_chapter',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_acm_chapter_course',
                        'value' => $course_id,
                    ]
                ]
            ]);

            if (empty($chapter_ids)) {
                $query->set('post__in', array(0));
                return;
            }

            $query->set('meta_query', [
                [
                    'key'     => '_acm_lesson_chapter',
                    'value'   => $chapter_ids,
                    'compare' => 'IN'
                ]
            ]);
        }
    }
}
