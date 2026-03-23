<?php
/**
 * Province Manager Class
 * Handles province-based course selection for Relationship Agreements
 * File: includes/class-acm-province-manager.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Province_Manager {
    
    private static $instance = null;
    
    // Available provinces
    private $provinces = array(
        'ontario' => 'Ontario',
        'alberta' => 'Alberta',
        'british_columbia' => 'British Columbia'
    );
    
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
        // Add province selection to user profile
        add_action('show_user_profile', array($this, 'add_province_field'));
        add_action('edit_user_profile', array($this, 'add_province_field'));
        add_action('personal_options_update', array($this, 'save_province_field'));
        add_action('edit_user_profile_update', array($this, 'save_province_field'));
        
        // Add province selector shortcode
        add_shortcode('acm_province_selector', array($this, 'province_selector_shortcode'));
        
        // Filter course access based on province
        add_filter('acm_user_course_redirect', array($this, 'redirect_to_province_course'), 10, 2);
    }
    
    /**
     * Get available provinces
     */
    public function get_provinces() {
        return $this->provinces;
    }
    
    /**
     * Get user's selected province
     */
    public function get_user_province($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Prefer province inferred from active MemberPress memberships.
        $membership_province = $this->get_membership_based_province($user_id);
        if ($membership_province) {
            $stored_province = get_user_meta($user_id, 'acm_user_province', true);
            if ($stored_province !== $membership_province) {
                update_user_meta($user_id, 'acm_user_province', $membership_province);
            }
            return $membership_province;
        }
        
        // Check if user has province set
        $province = get_user_meta($user_id, 'acm_user_province', true);
        
        // Check session if not in user meta
        if (!$province && isset($_SESSION['acm_selected_province'])) {
            $province = sanitize_text_field($_SESSION['acm_selected_province']);
        }
        
        // Default to Ontario if not set
        if (!$province || !array_key_exists($province, $this->provinces)) {
            $province = get_option('acm_default_province', 'ontario');
        }
        
        return $province;
    }

    /**
     * Infer province from user's active MemberPress memberships.
     */
    public function get_membership_based_province($user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0 || !class_exists('MeprUser')) {
            return null;
        }

        $member = new MeprUser($user_id);
        if (!method_exists($member, 'active_product_subscriptions')) {
            return null;
        }

        $active_memberships = array_map('intval', (array) $member->active_product_subscriptions('ids'));
        if (empty($active_memberships)) {
            return null;
        }

        $course_ids = get_posts(array(
            'post_type' => 'acm_course',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ));

        if (empty($course_ids)) {
            return null;
        }

        $matched_provinces = array();

        foreach ($course_ids as $course_id) {
            $province = sanitize_key(get_post_meta($course_id, '_acm_course_province', true));
            if (!$province || !array_key_exists($province, $this->provinces)) {
                continue;
            }

            $course_memberships = get_post_meta($course_id, '_acm_memberpress_memberships', true);
            if (empty($course_memberships) || !is_array($course_memberships)) {
                continue;
            }

            $course_memberships = array_map('intval', $course_memberships);
            if (!empty(array_intersect($active_memberships, $course_memberships))) {
                $matched_provinces[$province] = true;
            }
        }

        if (empty($matched_provinces)) {
            return null;
        }

        foreach (array_keys($this->provinces) as $province_key) {
            if (isset($matched_provinces[$province_key])) {
                return $province_key;
            }
        }

        return null;
    }
    
    /**
     * Set user's province
     */
    public function set_user_province($user_id, $province) {
        if (!array_key_exists($province, $this->provinces)) {
            return false;
        }
        
        update_user_meta($user_id, 'acm_user_province', $province);
        
        // Also set in session for non-logged-in users
        if (!session_id()) {
            session_start();
        }
        $_SESSION['acm_selected_province'] = $province;
        
        return true;
    }
    
    /**
     * Get course ID for a specific province
     */
    public function get_course_for_province($province = null) {
        if (!$province) {
            $province = $this->get_user_province();
        }
        
        // Get all courses
        $args = array(
            'post_type' => 'acm_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_acm_course_province',
                    'value' => $province,
                    'compare' => '='
                )
            )
        );
        
        $courses = get_posts($args);
        
        if (!empty($courses)) {
            return $courses[0]->ID;
        }
        
        // Fallback: find by title containing province name
        $province_name = $this->provinces[$province];
        $args = array(
            'post_type' => 'acm_course',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            's' => 'Relationship Agreements - ' . $province_name
        );
        
        $courses = get_posts($args);
        
        return !empty($courses) ? $courses[0]->ID : null;
    }
    
    /**
     * Add province field to user profile
     */
    public function add_province_field($user) {
        $current_province = get_user_meta($user->ID, 'acm_user_province', true);
        if (!$current_province) {
            $current_province = 'ontario';
        }
        ?>
        <h3><?php _e('Course Preferences', 'advanced-course-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="acm_user_province"><?php _e('Province', 'advanced-course-manager'); ?></label>
                </th>
                <td>
                    <select name="acm_user_province" id="acm_user_province">
                        <?php foreach ($this->provinces as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_province, $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select your province to view the appropriate Relationship Agreements course.', 'advanced-course-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save province field
     */
    public function save_province_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['acm_user_province'])) {
            $province = sanitize_text_field($_POST['acm_user_province']);
            if (array_key_exists($province, $this->provinces)) {
                update_user_meta($user_id, 'acm_user_province', $province);
            }
        }
    }
    
    /**
     * Province selector shortcode
     */
    public function province_selector_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => 'course'
        ), $atts);
        
        $user_id = get_current_user_id();
        $current_province = $this->get_user_province($user_id);
        
        ob_start();
        ?>
        <div class="acm-province-selector">
            <h3><?php _e('Select Your Province', 'advanced-course-manager'); ?></h3>
            <p><?php _e('Please select your province to view the appropriate Relationship Agreements course:', 'advanced-course-manager'); ?></p>
            
            <form method="post" id="acm-province-selector-form" class="acm-province-form">
                <?php wp_nonce_field('acm_province_selection', 'acm_province_nonce'); ?>
                
                <div class="province-options">
                    <?php foreach ($this->provinces as $key => $name): ?>
                        <label class="province-option">
                            <input type="radio" 
                                   name="acm_province" 
                                   value="<?php echo esc_attr($key); ?>" 
                                   <?php checked($current_province, $key); ?>>
                            <span class="province-name"><?php echo esc_html($name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="acm_redirect" value="<?php echo esc_attr($atts['redirect']); ?>">
                <button type="submit" class="acm-btn acm-btn-primary">
                    <?php _e('Continue to Course', 'advanced-course-manager'); ?>
                </button>
            </form>
        </div>
        
        <style>
            .acm-province-selector {
                max-width: 600px;
                margin: 40px auto;
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .acm-province-selector h3 {
                margin-top: 0;
                color: #333;
            }
            .province-options {
                margin: 30px 0;
            }
            .province-option {
                display: block;
                padding: 15px 20px;
                margin-bottom: 10px;
                background: #f5f5f5;
                border: 2px solid transparent;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .province-option:hover {
                background: #e8f4f8;
                border-color: #2563eb;
            }
            .province-option input[type="radio"] {
                margin-right: 12px;
            }
            .province-option input[type="radio"]:checked + .province-name {
                font-weight: 600;
                color: #2563eb;
            }
            .province-name {
                font-size: 16px;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#acm-province-selector-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var province = $form.find('input[name="acm_province"]:checked').val();
                var redirect = $form.find('input[name="acm_redirect"]').val();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acm_set_province',
                        nonce: $form.find('input[name="acm_province_nonce"]').val(),
                        province: province
                    },
                    success: function(response) {
                        if (response.success) {
                            if (redirect === 'course' && response.data.course_url) {
                                window.location.href = response.data.course_url;
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(response.data.message || 'Error setting province');
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
     * Redirect to appropriate province course
     */
    public function redirect_to_province_course($redirect_url, $user_id) {
        $province = $this->get_user_province($user_id);
        $course_id = $this->get_course_for_province($province);
        
        if ($course_id) {
            return get_permalink($course_id);
        }
        
        return $redirect_url;
    }
}