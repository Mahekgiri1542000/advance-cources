<?php
/**
 * MemberPress Integration Class
 * File: includes/class-acm-memberpress.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_MemberPress {
    
    private static $instance = null;
    private $memberpress_active = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if MemberPress is active
        $this->memberpress_active = class_exists('MeprUser');
        
        if ($this->memberpress_active) {
            add_filter('the_content', array($this, 'restrict_content'), 10);
            add_action('acm_before_lesson_content', array($this, 'check_access'));
            add_filter('acm_can_access_course', array($this, 'memberpress_access_check'), 10, 2);
        }
    }
    
    /**
     * Check if user has access to a course
     */
    public function has_access($user_id, $course_id) {
        if (!$this->memberpress_active) {
            // If MemberPress is not active, allow access by default
            return true;
        }
        
        // Get the MemberPress rules for this course
        $rules = $this->get_course_rules($course_id);
        
        if (empty($rules)) {
            // No rules set, allow access
            return true;
        }
        
        $user = new MeprUser($user_id);
        
        // Check if user has an active subscription to any of the required memberships
        foreach ($rules as $rule) {
            if ($user->is_already_subscribed_to($rule['membership_id'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has access to a lesson
     */
    public function has_lesson_access($user_id, $lesson_id) {
        // Get the course this lesson belongs to
        $course_id = get_post_meta($lesson_id, '_acm_lesson_course', true);
        
        if (!$course_id) {
            return true;
        }
        
        return $this->has_access($user_id, $course_id);
    }
    
    /**
     * Get MemberPress rules for a course
     */
    private function get_course_rules($course_id) {
        if (!$this->memberpress_active) {
            return array();
        }
        
        // Get memberships associated with this course
        $memberships = get_post_meta($course_id, '_acm_memberpress_memberships', true);
        
        if (empty($memberships)) {
            return array();
        }
        
        $rules = array();
        foreach ($memberships as $membership_id) {
            $rules[] = array(
                'membership_id' => $membership_id,
                'course_id' => $course_id
            );
        }
        
        return $rules;
    }
    
    /**
     * Restrict course content
     */
    public function restrict_content($content) {
        if (!is_singular(array('acm_course', 'acm_lesson'))) {
            return $content;
        }
        
        global $post;
        $user_id = get_current_user_id();
        
        // Check access
        if (get_post_type() === 'acm_course') {
            $has_access = $this->has_access($user_id, $post->ID);
        } else {
            $has_access = $this->has_lesson_access($user_id, $post->ID);
        }
        
        if (!$has_access) {
            return $this->get_restriction_message($post->ID);
        }
        
        return $content;
    }
    
    /**
     * Get restriction message
     */
    private function get_restriction_message($post_id) {
        $memberships = get_post_meta($post_id, '_acm_memberpress_memberships', true);
        
        ob_start();
        ?>
        <div class="acm-restriction-message">
            <div class="acm-lock-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3><?php _e('Access Restricted', 'advanced-course-manager'); ?></h3>
            <p><?php _e('You need an active membership to access this content.', 'advanced-course-manager'); ?></p>
            
            <?php if (!empty($memberships) && $this->memberpress_active): ?>
                <div class="acm-required-memberships">
                    <h4><?php _e('Required Membership:', 'advanced-course-manager'); ?></h4>
                    <ul>
                        <?php foreach ($memberships as $membership_id): 
                            $membership = get_post($membership_id);
                            if ($membership):
                        ?>
                            <li>
                                <a href="<?php echo get_permalink($membership_id); ?>">
                                    <?php echo esc_html($membership->post_title); ?>
                                </a>
                            </li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="acm-action-buttons">
                <?php if (!is_user_logged_in()): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="acm-btn acm-btn-primary">
                        <?php _e('Log In', 'advanced-course-manager'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo home_url('/pricing'); ?>" class="acm-btn acm-btn-primary">
                        <?php _e('View Membership Plans', 'advanced-course-manager'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check access before displaying lesson content
     */
    public function check_access() {
        if (!is_singular('acm_lesson')) {
            return;
        }
        
        global $post;
        $user_id = get_current_user_id();
        
        if (!$this->has_lesson_access($user_id, $post->ID)) {
            wp_redirect(get_permalink($post->ID));
            exit;
        }
    }
    
    /**
     * Filter for checking course access
     */
    public function memberpress_access_check($has_access, $course_id) {
        $user_id = get_current_user_id();
        return $this->has_access($user_id, $course_id);
    }
    
    /**
     * Get user's accessible courses
     */
    public function get_user_courses($user_id) {
        if (!$this->memberpress_active) {
            // Return all courses if MemberPress is not active
            return get_posts(array(
                'post_type' => 'acm_course',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
        }
        
        $user = new MeprUser($user_id);
        $active_memberships = $user->active_product_subscriptions('ids');
        
        if (empty($active_memberships)) {
            return array();
        }
        
        // Get all courses
        $all_courses = get_posts(array(
            'post_type' => 'acm_course',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $accessible_courses = array();
        
        foreach ($all_courses as $course) {
            $course_memberships = get_post_meta($course->ID, '_acm_memberpress_memberships', true);
            
            if (empty($course_memberships)) {
                // No restrictions
                $accessible_courses[] = $course;
                continue;
            }
            
            // Check if user has any of the required memberships
            foreach ($course_memberships as $required_membership) {
                if (in_array($required_membership, $active_memberships)) {
                    $accessible_courses[] = $course;
                    break;
                }
            }
        }
        
        return $accessible_courses;
    }
    
    /**
     * Check if MemberPress is active
     */
    public function is_memberpress_active() {
        return $this->memberpress_active;
    }
}