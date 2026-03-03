<?php
/**
 * Plugin Name: Advanced Course Manager
 * Plugin URI: https://getjointly.ca
 * Description: A comprehensive course management system with partner collaboration, progress tracking, and seamless MemberPress integration.
 * Version: 1.0.0
 * Author: Getjointly
 * Author URI: https://getjointly.ca
 * Text Domain: advanced-course-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACM_VERSION', '2.0.0');
define('ACM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Advanced_Course_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'), 0);
        add_action('init', array($this, 'load_textdomain'));
    }
    
    private function load_dependencies() {
        // Core classes
        $core_files = array(
            'includes/class-acm-database.php',
            'includes/class-acm-post-types.php',
            'includes/class-acm-taxonomies.php',
            'includes/class-acm-progress.php',
            'includes/class-acm-province-manager.php',
            'includes/class-acm-agreement-builder.php',
            'includes/class-acm-customization-quiz.php',
            'includes/class-acm-partnerships.php',
            'includes/class-acm-notes.php',
            'includes/class-acm-discussions.php',
            'includes/class-acm-certificate.php',
            'includes/class-acm-notifications.php',
            'includes/class-acm-memberpress.php',
            'includes/class-acm-ajax.php',
            'includes/class-acm-rest-api.php',
            'includes/class-acm-certificate-simple.php',
            'quiz-handler.php',
            'course-filter.php',
            'quiz-template.php',
        );
        
        foreach ($core_files as $file) {
            $filepath = ACM_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
        
        // Admin classes
        if (is_admin()) {
            $admin_files = array(
                'admin/class-acm-admin.php',
                'admin/class-acm-metaboxes.php',
                'admin/class-acm-settings.php',
                'admin/class-acm-lesson-need-to-know.php',
                'admin/class-acm-lesson-highlight-box.php',
                'admin/class-acm-course-lessons-admin-column.php',
                'admin/class-acm-bulk-duplicate.php',
                'admin/class-acm-lesson-callout-box.php',
                'admin/class-acm-admin-row-actions.php'
            );
            
            foreach ($admin_files as $file) {
                $filepath = ACM_PLUGIN_DIR . $file;
                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }
        }
        
        // Public classes
        $public_files = array(
            'public/class-acm-public.php',
            'public/class-acm-shortcodes.php',
            'public/class-acm-templates.php'
        );
        
        foreach ($public_files as $file) {
            $filepath = ACM_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
    }
    
    public function init() {
        // Initialize all components
        if (class_exists('ACM_Post_Types')) {
            ACM_Post_Types::get_instance();
        }
        
        if (class_exists('ACM_Taxonomies')) {
            ACM_Taxonomies::get_instance();
        }
        
        if (class_exists('ACM_Progress')) {
            ACM_Progress::get_instance();
        }
        
        if (class_exists('ACM_Province_Manager')) {
            ACM_Province_Manager::get_instance();
        }
        
        if (class_exists('ACM_Agreement_Builder')) {
            ACM_Agreement_Builder::get_instance();
        }
        
        if (class_exists('ACM_Customization_Quiz')) {
            ACM_Customization_Quiz::get_instance();
        }

        if (class_exists('ACM_Quiz_Handler')) {
            ACM_Quiz_Handler::get_instance();
        }

        if (class_exists('ACM_Course_Filter')) {
            ACM_Course_Filter::get_instance();
        }

        if (class_exists('ACM_Quiz_Template')) {
            ACM_Quiz_Template::get_instance();
        }
        
        if (class_exists('ACM_Partnerships')) {
            ACM_Partnerships::get_instance();
        }
        
        if (class_exists('ACM_Notes')) {
            ACM_Notes::get_instance();
        }
        
        if (class_exists('ACM_Discussions')) {
            ACM_Discussions::get_instance();
        }
        
        if (class_exists('ACM_Certificates')) {
            ACM_Certificates::get_instance();
        }
        
        if (class_exists('ACM_Notifications')) {
            ACM_Notifications::get_instance();
        }
        
        if (class_exists('ACM_MemberPress')) {
            ACM_MemberPress::get_instance();
        }
        
        if (class_exists('ACM_AJAX')) {
            ACM_AJAX::get_instance();
        }
        
        if (class_exists('ACM_REST_API')) {
            ACM_REST_API::get_instance();
        }
        
        if (class_exists('ACM_Public')) {
            ACM_Public::get_instance();
        }
        
        if (class_exists('ACM_Shortcodes')) {
            ACM_Shortcodes::get_instance();
        }
        
        if (class_exists('ACM_Templates')) {
            ACM_Templates::get_instance();
        }
        
        if (is_admin()) {
            if (class_exists('ACM_Admin')) {
                ACM_Admin::get_instance();
            }
            
            if (class_exists('ACM_Metaboxes')) {
                ACM_Metaboxes::get_instance();
            }
            
            if (class_exists('ACM_Settings')) {
                ACM_Settings::get_instance();
            }

            if (class_exists('ACM_Lesson_Need_To_Know')) {
                new ACM_Lesson_Need_To_Know();
            }

            if (class_exists('ACM_Lesson_Highlight_Box')) {
                new ACM_Lesson_Highlight_Box();
            }

            if (class_exists('ACM_Lesson_Callout_Box')) {
                new ACM_Lesson_Callout_Box();
            }

            if (class_exists('ACM_Course_Lessons_Admin_Column')) {
                new ACM_Course_Lessons_Admin_Column();
            }

            if (class_exists('ACM_Lesson_Course_Filter')) {
                new ACM_Lesson_Course_Filter();
            }
        }
    }
    
    public function activate() {
        // Create database tables
        if (class_exists('ACM_Database')) {
            ACM_Database::create_tables();
        }
        
        // Register post types and taxonomies first
        if (class_exists('ACM_Post_Types')) {
            $post_types = ACM_Post_Types::get_instance();
            $post_types->register_post_types();
        }
        
        if (class_exists('ACM_Taxonomies')) {
            $taxonomies = ACM_Taxonomies::get_instance();
            $taxonomies->register_taxonomies();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default pages
        $this->create_default_pages();
        
        // Set default options
        $this->set_default_options();
        
        // Set activation flag
        update_option('acm_activated', '1');
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_default_pages() {
        $pages = array(
            'acm_dashboard' => array(
                'title' => __('Course Dashboard', 'advanced-course-manager'),
                'content' => '[acm_dashboard]'
            ),
            'acm_my_courses' => array(
                'title' => __('My Courses', 'advanced-course-manager'),
                'content' => '[acm_my_courses]'
            ),
            'acm_agreement_builder' => array(
                'title' => __('Agreement Builder', 'advanced-course-manager'),
                'content' => '[acm_agreement_builder]'
            )
        );
        
        foreach ($pages as $option_key => $page_data) {
            $page_id = get_option($option_key);
            
            if (!$page_id || !get_post($page_id)) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    update_option($option_key, $page_id);
                }
            }
        }
    }
    
    private function set_default_options() {
        $defaults = array(
            'acm_enable_partnerships' => 'yes',
            'acm_enable_certificates' => 'no',
            'acm_enable_discussions' => 'yes',
            'acm_enable_notes' => 'yes',
            'acm_enable_notifications' => 'yes',
            'acm_enable_agreement_builder' => 'yes',
            'acm_enable_customization_quiz' => 'yes',
            'acm_default_province' => 'ontario'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'advanced-course-manager',
            false,
            dirname(ACM_PLUGIN_BASENAME) . '/languages'
        );
    }
}

// Initialize the plugin
function acm_init() {
    return Advanced_Course_Manager::get_instance();
}

// Start the plugin immediately
acm_init();

// Helper functions
function acm_get_course_progress($user_id, $course_id) {
    if (!class_exists('ACM_Progress')) {
        return array();
    }
    return ACM_Progress::get_instance()->get_course_progress($user_id, $course_id);
}

function acm_mark_lesson_complete($user_id, $lesson_id) {
    if (!class_exists('ACM_Progress')) {
        return false;
    }
    return ACM_Progress::get_instance()->mark_lesson_complete($user_id, $lesson_id);
}

function acm_get_user_partner($user_id, $course_id) {
    if (!class_exists('ACM_Partnerships')) {
        return null;
    }
    return ACM_Partnerships::get_instance()->get_partner($user_id, $course_id);
}

function acm_has_course_access($user_id, $course_id) {
    if (!class_exists('ACM_MemberPress')) {
        return true;
    }
    return ACM_MemberPress::get_instance()->has_access($user_id, $course_id);
}

function acm_get_user_province($user_id) {
    if (!class_exists('ACM_Province_Manager')) {
        return 'ontario';
    }
    return ACM_Province_Manager::get_instance()->get_user_province($user_id);
}

function acm_get_course_for_province($province = null) {
    if (!class_exists('ACM_Province_Manager')) {
        return null;
    }
    return ACM_Province_Manager::get_instance()->get_course_for_province($province);
}

add_shortcode('acm_highlight_boxes', 'acm_highlight_boxes_shortcode');
add_shortcode('acm_highlight_box', 'acm_highlight_box_single_shortcode');

function acm_highlight_boxes_shortcode() {
    $boxes = get_post_meta(get_the_ID(), '_acm_highlight_boxes', true);
    if (empty($boxes)) return '';

    ob_start();
    foreach ($boxes as $box) {
        acm_render_highlight_box($box);
    }
    return ob_get_clean();
}

function acm_highlight_box_single_shortcode($atts) {
    $atts = shortcode_atts(['index' => 0], $atts);
    $boxes = get_post_meta(get_the_ID(), '_acm_highlight_boxes', true);

    if (!isset($boxes[$atts['index']])) return '';

    ob_start();
    acm_render_highlight_box($boxes[$atts['index']]);
    return ob_get_clean();
}

function acm_render_highlight_box($box) {
    ?>
    <div class="info-box">
        <div class="info-box-header">
            <?php echo esc_html($box['heading']); ?>
        </div>
        <div class="info-box-body">
            <ul>
                <?php foreach ($box['items'] as $item): ?>
                    <li>
                        <?php echo esc_html($item['title']); ?>
                        <?php if (!empty($item['children'])): ?>
                            <ul>
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><?php echo esc_html($child); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}


// Shortcodes
add_shortcode('acm_callout_boxes', 'shortcode_all_boxes');
add_shortcode('acm_callout_box', 'shortcode_single_box');

function shortcode_all_boxes() {
    $boxes = get_post_meta(get_the_ID(), '_acm_callout_boxes', true);
    if (empty($boxes) || !is_array($boxes)) return '';

    ob_start();
    foreach ($boxes as $content) {
        render_front_box($content);
    }
    return ob_get_clean();
}

function shortcode_single_box($atts) {
    $atts = shortcode_atts(['index' => 0], $atts);
    $index = intval($atts['index']);

    $boxes = get_post_meta(get_the_ID(), '_acm_callout_boxes', true);
    if (empty($boxes) || !isset($boxes[$index])) return '';

    return render_front_box($boxes[$index]);
}

function render_front_box($content) {
    $content = is_string($content) ? $content : '';

    ob_start();
    ?>
    <div class="acm-callout-front-box">
        <div class="acm-callout-front-content">
            <?php echo wpautop($content); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}