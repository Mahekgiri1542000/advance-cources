<?php
/**
 * Taxonomies Registration
 * File: includes/class-acm-taxonomies.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Taxonomies {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    public function register_taxonomies() {
        // Course Category
        $category_labels = array(
            'name' => __('Course Categories', 'advanced-course-manager'),
            'singular_name' => __('Course Category', 'advanced-course-manager'),
            'search_items' => __('Search Categories', 'advanced-course-manager'),
            'all_items' => __('All Categories', 'advanced-course-manager'),
            'parent_item' => __('Parent Category', 'advanced-course-manager'),
            'parent_item_colon' => __('Parent Category:', 'advanced-course-manager'),
            'edit_item' => __('Edit Category', 'advanced-course-manager'),
            'update_item' => __('Update Category', 'advanced-course-manager'),
            'add_new_item' => __('Add New Category', 'advanced-course-manager'),
            'new_item_name' => __('New Category Name', 'advanced-course-manager'),
            'menu_name' => __('Categories', 'advanced-course-manager')
        );
        
        register_taxonomy('acm_course_category', array('acm_course'), array(
            'hierarchical' => true,
            'labels' => $category_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'course-category'),
            'show_in_rest' => true
        ));
        
        // Course Module (for grouping lessons)
        $module_labels = array(
            'name' => __('Modules', 'advanced-course-manager'),
            'singular_name' => __('Module', 'advanced-course-manager'),
            'search_items' => __('Search Modules', 'advanced-course-manager'),
            'all_items' => __('All Modules', 'advanced-course-manager'),
            'parent_item' => __('Parent Module', 'advanced-course-manager'),
            'parent_item_colon' => __('Parent Module:', 'advanced-course-manager'),
            'edit_item' => __('Edit Module', 'advanced-course-manager'),
            'update_item' => __('Update Module', 'advanced-course-manager'),
            'add_new_item' => __('Add New Module', 'advanced-course-manager'),
            'new_item_name' => __('New Module Name', 'advanced-course-manager'),
            'menu_name' => __('Modules', 'advanced-course-manager')
        );
        
        register_taxonomy('acm_course_module', array('acm_lesson'), array(
            'hierarchical' => true,
            'labels' => $module_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'module'),
            'show_in_rest' => true
        ));
        
        // Course Tags
        $tag_labels = array(
            'name' => __('Course Tags', 'advanced-course-manager'),
            'singular_name' => __('Course Tag', 'advanced-course-manager'),
            'search_items' => __('Search Tags', 'advanced-course-manager'),
            'all_items' => __('All Tags', 'advanced-course-manager'),
            'edit_item' => __('Edit Tag', 'advanced-course-manager'),
            'update_item' => __('Update Tag', 'advanced-course-manager'),
            'add_new_item' => __('Add New Tag', 'advanced-course-manager'),
            'new_item_name' => __('New Tag Name', 'advanced-course-manager'),
            'menu_name' => __('Tags', 'advanced-course-manager')
        );
        
        register_taxonomy('acm_course_tag', array('acm_course'), array(
            'hierarchical' => false,
            'labels' => $tag_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'course-tag'),
            'show_in_rest' => true
        ));
        
        // Difficulty Level
        $difficulty_labels = array(
            'name' => __('Difficulty Levels', 'advanced-course-manager'),
            'singular_name' => __('Difficulty Level', 'advanced-course-manager'),
            'menu_name' => __('Difficulty', 'advanced-course-manager')
        );
        
        register_taxonomy('acm_difficulty', array('acm_course'), array(
            'hierarchical' => true,
            'labels' => $difficulty_labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'difficulty'),
            'show_in_rest' => true
        ));
    }
}