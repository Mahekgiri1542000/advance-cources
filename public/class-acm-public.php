<?php
/**
 * Public Class
 * File: public/class-acm-public.php
 */

class ACM_Public {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function enqueue_styles() {
        // Check if we're on a course or lesson page
        if (is_singular(array('acm_course', 'acm_chapter', 'acm_lesson')) || is_page() || is_post_type_archive('acm_course')) {
            wp_enqueue_style(
                'acm-public-styles',
                ACM_PLUGIN_URL . 'public/css/public-style.css',
                array(),
                ACM_VERSION,
                'all'
            );

            wp_enqueue_style(
                'acm-course-filter-styles',
                ACM_PLUGIN_URL . 'public/css/course-filter.css',
                array('acm-public-styles'),
                ACM_VERSION,
                'all'
            );
        }
    }
    
    public function enqueue_scripts() {
        // Check if we're on a course or lesson page
        if (is_singular(array('acm_course', 'acm_chapter', 'acm_lesson')) || is_page() || is_post_type_archive('acm_course')) {
            wp_enqueue_script(
                'acm-public-scripts',
                ACM_PLUGIN_URL . 'public/js/public-scripts.js',
                array('jquery'),
                ACM_VERSION,
                true
            );

            wp_enqueue_script(
                'acm-next-lesson-script',
                ACM_PLUGIN_URL . 'public/js/next-lesson.js',
                array('jquery', 'acm-public-scripts'),
                ACM_VERSION,
                true
            );
            
            // Localize script with data
            $user_id = get_current_user_id();
            
            wp_localize_script('acm-public-scripts', 'acmLessonData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('acm_nonce'),
                'userId' => $user_id,
                'isLoggedIn' => is_user_logged_in()
            ));
        }
    }
}
