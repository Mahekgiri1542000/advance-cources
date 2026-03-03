<?php
/**
 * Admin Row Actions - Statistics Links
 * File: admin/class-acm-admin-row-actions.php
 * 
 * Adds "Statistics" row action links to Course, Chapter, and Lesson admin listing pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Admin_Row_Actions {
    
    private function __construct() {
        // Add row actions for Courses
        add_filter('post_row_actions', array($this, 'add_row_actions'), 10, 2);
    }
    
    public static function init() {
        new self();
    }
    
    /**
     * Add Statistics row action to all post types
     */
    public function add_row_actions($actions, $post) {
        
        // Only process ACM post types
        if (!in_array($post->post_type, array('acm_course', 'acm_chapter', 'acm_lesson'))) {
            return $actions;
        }
        
        $stats_url = '';
        $label = '';
        
        // Build appropriate URL based on post type
        if ($post->post_type === 'acm_course') {
            $stats_url = admin_url('edit.php?post_type=acm_course&page=acm-reports&acm_course_filter=' . intval($post->ID));
            $label = __('View Statistics for this course', 'advanced-course-manager');
        } 
        elseif ($post->post_type === 'acm_chapter') {
            $course_id = get_post_meta($post->ID, '_acm_chapter_course', true);
            if (!$course_id) {
                return $actions;
            }
            $stats_url = admin_url('edit.php?post_type=acm_chapter&page=acm-reports&acm_course_filter=' . intval($course_id) . '&acm_chapter_filter=' . intval($post->ID));
            $label = __('View Statistics for this chapter', 'advanced-course-manager');
        } 
        elseif ($post->post_type === 'acm_lesson') {
            $chapter_id = get_post_meta($post->ID, '_acm_lesson_chapter', true);
            if (!$chapter_id) {
                return $actions;
            }
            $course_id = get_post_meta($chapter_id, '_acm_chapter_course', true);
            if (!$course_id) {
                return $actions;
            }
            $stats_url = admin_url('edit.php?post_type=acm_lesson&page=acm-reports&acm_course_filter=' . intval($course_id) . '&acm_chapter_filter=' . intval($chapter_id) . '&acm_lesson_filter=' . intval($post->ID));
            $label = __('View Statistics for this lesson', 'advanced-course-manager');
        }
        
        // Add the Statistics action if URL was built
        if ($stats_url && $label) {
            $actions['acm-statistics'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url($stats_url),
                esc_attr($label),
                __('Statistics', 'advanced-course-manager')
            );
        }
        
        return $actions;
    }
}

// Initialize the row actions class
ACM_Admin_Row_Actions::init();
