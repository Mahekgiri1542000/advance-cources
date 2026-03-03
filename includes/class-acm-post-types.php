<?php
/**
 * Custom Post Types Registration
 * File: includes/class-acm-post-types.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Post_Types {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_filter('template_include', array($this, 'load_templates'));
    }
    
    public function register_post_types() {
        // Register Course Post Type
        $course_labels = array(
            'name' => __('Courses', 'advanced-course-manager'),
            'singular_name' => __('Course', 'advanced-course-manager'),
            'menu_name' => __('Courses', 'advanced-course-manager'),
            'add_new' => __('Add New Course', 'advanced-course-manager'),
            'add_new_item' => __('Add New Course', 'advanced-course-manager'),
            'edit_item' => __('Edit Course', 'advanced-course-manager'),
            'new_item' => __('New Course', 'advanced-course-manager'),
            'view_item' => __('View Course', 'advanced-course-manager'),
            'search_items' => __('Search Courses', 'advanced-course-manager'),
            'not_found' => __('No courses found', 'advanced-course-manager'),
            'not_found_in_trash' => __('No courses found in trash', 'advanced-course-manager')
        );
        
        $course_args = array(
            'labels' => $course_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'course'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'show_in_rest' => true
        );
        
        register_post_type('acm_course', $course_args);

        // Register Chapter Post Type
        $chapter_labels = array(
            'name' => __('Chapters', 'advanced-course-manager'),
            'singular_name' => __('Chapter', 'advanced-course-manager'),
            'menu_name' => __('Chapters', 'advanced-course-manager'),
            'add_new' => __('Add New Chapter', 'advanced-course-manager'),
            'add_new_item' => __('Add New Chapter', 'advanced-course-manager'),
            'edit_item' => __('Edit Chapter', 'advanced-course-manager'),
            'new_item' => __('New Chapter', 'advanced-course-manager'),
            'view_item' => __('View Chapter', 'advanced-course-manager'),
            'search_items' => __('Search Chapters', 'advanced-course-manager'),
            'not_found' => __('No chapters found', 'advanced-course-manager'),
            'not_found_in_trash' => __('No chapters found in trash', 'advanced-course-manager')
        );

        $chapter_args = array(
            'labels' => $chapter_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=acm_course',
            'query_var' => true,
            'rewrite' => array('slug' => 'chapter'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'page-attributes'),
            'show_in_rest' => true
        );

        register_post_type('acm_chapter', $chapter_args);
        
        // Register Lesson Post Type
        $lesson_labels = array(
            'name' => __('Lessons', 'advanced-course-manager'),
            'singular_name' => __('Lesson', 'advanced-course-manager'),
            'menu_name' => __('Lessons', 'advanced-course-manager'),
            'add_new' => __('Add New Lesson', 'advanced-course-manager'),
            'add_new_item' => __('Add New Lesson', 'advanced-course-manager'),
            'edit_item' => __('Edit Lesson', 'advanced-course-manager'),
            'new_item' => __('New Lesson', 'advanced-course-manager'),
            'view_item' => __('View Lesson', 'advanced-course-manager'),
            'search_items' => __('Search Lessons', 'advanced-course-manager'),
            'not_found' => __('No lessons found', 'advanced-course-manager'),
            'not_found_in_trash' => __('No lessons found in trash', 'advanced-course-manager')
        );
        
        $lesson_args = array(
            'labels' => $lesson_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=acm_course',
            'query_var' => true,
            'rewrite' => array('slug' => 'lesson'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'show_in_rest' => true
        );
        
        register_post_type('acm_lesson', $lesson_args);
        
        // Register Quiz Post Type
        // $quiz_labels = array(
        //     'name' => __('Quizzes', 'advanced-course-manager'),
        //     'singular_name' => __('Quiz', 'advanced-course-manager'),
        //     'menu_name' => __('Quizzes', 'advanced-course-manager'),
        //     'add_new' => __('Add New Quiz', 'advanced-course-manager'),
        //     'add_new_item' => __('Add New Quiz', 'advanced-course-manager'),
        //     'edit_item' => __('Edit Quiz', 'advanced-course-manager')
        // );
        
        // $quiz_args = array(
        //     'labels' => $quiz_labels,
        //     'public' => true,
        //     'publicly_queryable' => true,
        //     'show_ui' => true,
        //     'show_in_menu' => 'edit.php?post_type=acm_course',
        //     'query_var' => true,
        //     'rewrite' => array('slug' => 'quiz'),
        //     'capability_type' => 'post',
        //     'has_archive' => false,
        //     'hierarchical' => false,
        //     'supports' => array('title', 'editor'),
        //     'show_in_rest' => true
        // );
        
        // register_post_type('acm_quiz', $quiz_args);
    }
    
    public function load_templates($template) {
        if (is_singular('acm_course')) {
            $custom_template = ACM_PLUGIN_DIR . 'templates/single-course.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        if (is_singular('acm_chapter')) {
            $custom_template = ACM_PLUGIN_DIR . 'templates/single-chapter.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        if (is_singular('acm_lesson')) {
            $custom_template = ACM_PLUGIN_DIR . 'templates/single-lesson.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('acm_course')) {
            $custom_template = ACM_PLUGIN_DIR . 'templates/archive-course.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
}