<?php
/**
 * Customization Quiz Class
 * Allows users to customize which course sections they see based on their situation
 * File: includes/class-acm-customization-quiz.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Customization_Quiz {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_acm_save_customization', array($this, 'save_customization'));
        add_action('wp_ajax_acm_get_customization', array($this, 'get_customization'));
        add_action('wp_ajax_acm_reset_customization', array($this, 'reset_customization'));
        
        // Filter lessons based on customization
        add_filter('acm_course_lessons', array($this, 'filter_lessons'), 10, 2);
    }
    
    /**
     * Get user's customization settings
     */
    public function get_user_customization($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $customization = get_user_meta($user_id, 'acm_course_customization', true);
        
        if (!is_array($customization)) {
            return array(
                'completed' => false,
                'show_all' => true,
                'sections' => array()
            );
        }
        
        return $customization;
    }
    
    /**
     * Save customization settings
     */
    public function save_customization() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'advanced-course-manager')));
        }
        
        $show_all = isset($_POST['show_all']) ? (bool)$_POST['show_all'] : false;
        $sections = isset($_POST['sections']) ? (array)$_POST['sections'] : array();
        
        $customization = array(
            'completed' => true,
            'show_all' => $show_all,
            'sections' => array_map('sanitize_text_field', $sections),
            'updated_date' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'acm_course_customization', $customization);
        
        wp_send_json_success(array(
            'message' => __('Customization saved', 'advanced-course-manager'),
            'customization' => $customization
        ));
    }
    
    /**
     * Get customization via AJAX
     */
    public function get_customization() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $customization = $this->get_user_customization($user_id);
        
        wp_send_json_success(array('customization' => $customization));
    }
    
    /**
     * Reset customization
     */
    public function reset_customization() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'acm_course_customization');
        
        wp_send_json_success(array('message' => __('Customization reset', 'advanced-course-manager')));
    }
    
    /**
     * Customization quiz shortcode
     */
    public function quiz_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to customize your course.', 'advanced-course-manager') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $customization = $this->get_user_customization($user_id);
        
        ob_start();
        ?>
        <div class="acm-customization-quiz">
            <div class="quiz-header">
                <h2><?php _e('Customize Your Course', 'advanced-course-manager'); ?></h2>
                <p><?php _e('Answer a few quick questions to see only the sections relevant to your situation. You can always view the full course if you prefer.', 'advanced-course-manager'); ?></p>
            </div>
            
            <form id="acm-customization-form" class="customization-form">
                <?php wp_nonce_field('acm_nonce', 'acm_customization_nonce'); ?>
                
                <div class="quiz-option preference-option">
                    <h3><?php _e('Course Display Preference', 'advanced-course-manager'); ?></h3>
                    <label class="radio-option">
                        <input type="radio" name="show_all" value="1" <?php checked($customization['show_all'], true); ?>>
                        <span class="option-text">
                            <strong><?php _e('Show me everything', 'advanced-course-manager'); ?></strong>
                            <small><?php _e('I want to see all sections of the course', 'advanced-course-manager'); ?></small>
                        </span>
                    </label>
                    
                    <label class="radio-option">
                        <input type="radio" name="show_all" value="0" <?php checked($customization['show_all'], false); ?>>
                        <span class="option-text">
                            <strong><?php _e('Customize my experience', 'advanced-course-manager'); ?></strong>
                            <small><?php _e('Show me only sections relevant to my situation', 'advanced-course-manager'); ?></small>
                        </span>
                    </label>
                </div>
                
                <div id="quiz-questions" style="display: <?php echo $customization['show_all'] ? 'none' : 'block'; ?>;">
                    
                    <!-- Children Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Children', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Do you or your partner have children (including from previous relationships)?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="child_support"
                                   <?php checked(in_array('child_support', $customization['sections'])); ?>>
                            <span><?php _e('Yes, we have children', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Property Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Property & Assets', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Do either of you own property or significant assets?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="property_division"
                                   <?php checked(in_array('property_division', $customization['sections'])); ?>>
                            <span><?php _e('Yes, we own property or assets', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Debt Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Debts', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Do either of you have significant debts?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="debt_management"
                                   <?php checked(in_array('debt_management', $customization['sections'])); ?>>
                            <span><?php _e('Yes, we have debts to consider', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Spousal Support Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Income & Support', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Is there a significant income difference between you and your partner?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="spousal_support"
                                   <?php checked(in_array('spousal_support', $customization['sections'])); ?>>
                            <span><?php _e('Yes, there is a significant income difference', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Business Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Business Ownership', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Do either of you own a business?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="business_matters"
                                   <?php checked(in_array('business_matters', $customization['sections'])); ?>>
                            <span><?php _e('Yes, we own a business', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Inheritance Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Inheritance & Family Gifts', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Have either of you received or expect to receive an inheritance or significant family gifts?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="inheritance"
                                   <?php checked(in_array('inheritance', $customization['sections'])); ?>>
                            <span><?php _e('Yes, inheritance or gifts are involved', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                    <!-- Pets Question -->
                    <div class="quiz-question">
                        <h3><?php _e('Pets', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Do you have pets together?', 'advanced-course-manager'); ?></p>
                        
                        <label class="checkbox-option">
                            <input type="checkbox" 
                                   name="sections[]" 
                                   value="pet_custody"
                                   <?php checked(in_array('pet_custody', $customization['sections'])); ?>>
                            <span><?php _e('Yes, we have pets', 'advanced-course-manager'); ?></span>
                        </label>
                    </div>
                    
                </div>
                
                <div class="quiz-actions">
                    <button type="submit" class="acm-btn acm-btn-primary">
                        <?php _e('Save & Continue to Course', 'advanced-course-manager'); ?>
                    </button>
                    
                    <?php if ($customization['completed']): ?>
                        <button type="button" id="acm-reset-customization" class="acm-btn acm-btn-secondary">
                            <?php _e('Reset Preferences', 'advanced-course-manager'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="quiz-info">
                <p><small><?php _e('Note: You can change these preferences at any time from your course dashboard.', 'advanced-course-manager'); ?></small></p>
            </div>
        </div>
        
        <style>
            .acm-customization-quiz {
                max-width: 800px;
                margin: 40px auto;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .quiz-header h2 {
                margin-top: 0;
                color: #333;
            }
            .quiz-question {
                margin: 30px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 6px;
            }
            .quiz-question h3 {
                margin-top: 0;
                color: #2563eb;
                font-size: 18px;
            }
            .radio-option,
            .checkbox-option {
                display: block;
                padding: 15px;
                margin: 10px 0;
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .radio-option:hover,
            .checkbox-option:hover {
                border-color: #2563eb;
                background: #f0f7ff;
            }
            .radio-option input[type="radio"],
            .checkbox-option input[type="checkbox"] {
                margin-right: 12px;
            }
            .option-text {
                display: inline-block;
            }
            .option-text strong {
                display: block;
                margin-bottom: 5px;
            }
            .option-text small {
                color: #666;
            }
            .preference-option {
                margin-bottom: 30px;
            }
            .quiz-actions {
                margin-top: 30px;
                display: flex;
                gap: 10px;
            }
            .quiz-info {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                color: #666;
                text-align: center;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle quiz questions based on preference
            $('input[name="show_all"]').on('change', function() {
                var showAll = $(this).val() === '1';
                $('#quiz-questions').toggle(!showAll);
            });
            
            // Save customization
            $('#acm-customization-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var showAll = $form.find('input[name="show_all"]:checked').val() === '1';
                var sections = [];
                
                if (!showAll) {
                    $form.find('input[name="sections[]"]:checked').each(function() {
                        sections.push($(this).val());
                    });
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acm_save_customization',
                        nonce: $form.find('input[name="acm_customization_nonce"]').val(),
                        show_all: showAll,
                        sections: sections
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect to course
                            var courseUrl = '<?php echo get_option('acm_dashboard'); ?>';
                            if (courseUrl) {
                                window.location.href = courseUrl;
                            } else {
                                location.reload();
                            }
                        }
                    }
                });
            });
            
            // Reset customization
            $('#acm-reset-customization').on('click', function() {
                if (!confirm('<?php _e('Reset all customization preferences?', 'advanced-course-manager'); ?>')) {
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acm_reset_customization',
                        nonce: '<?php echo wp_create_nonce('acm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Filter course lessons based on customization
     */
    public function filter_lessons($lessons, $course_id) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return $lessons;
        }
        
        $customization = $this->get_user_customization($user_id);
        
        // If showing all, return all lessons
        if ($customization['show_all'] || !$customization['completed']) {
            return $lessons;
        }
        
        // Filter lessons based on sections
        $filtered_lessons = array();
        
        foreach ($lessons as $lesson) {
            $lesson_section = get_post_meta($lesson->ID, '_acm_lesson_section', true);
            
            // Always include lessons without a section tag
            if (!$lesson_section) {
                $filtered_lessons[] = $lesson;
                continue;
            }
            
            // Include if section is in user's customization
            if (in_array($lesson_section, $customization['sections'])) {
                $filtered_lessons[] = $lesson;
            }
        }
        
        return $filtered_lessons;
    }
}