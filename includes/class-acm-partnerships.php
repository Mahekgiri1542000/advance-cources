<?php
/**
 * Partnerships Management Class
 * File: includes/class-acm-partnerships.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Partnerships {
    
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
        // Add any initialization hooks here
    }
    
    /**
     * Create a partnership between two users for a course
     */
    public function create_partnership($user_id_1, $user_id_2, $course_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_partnerships';
        
        // Check if partnership already exists
        $existing = $this->get_partnership($user_id_1, $user_id_2, $course_id);
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('Partnership already exists', 'advanced-course-manager')
            );
        }
        
        // Ensure user_id_1 is always smaller for consistency
        if ($user_id_1 > $user_id_2) {
            $temp = $user_id_1;
            $user_id_1 = $user_id_2;
            $user_id_2 = $temp;
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id_1' => $user_id_1,
                'user_id_2' => $user_id_2,
                'course_id' => $course_id,
                'created_date' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Send notifications to both users
            $user1 = get_userdata($user_id_1);
            $user2 = get_userdata($user_id_2);
            $course_title = get_the_title($course_id);
            
            ACM_Notifications::get_instance()->send_notification(
                $user_id_1,
                'partnership_created',
                __('New Course Partner', 'advanced-course-manager'),
                sprintf(__('You are now partnered with %s for "%s"', 'advanced-course-manager'), 
                    $user2->display_name, $course_title),
                get_permalink($course_id)
            );
            
            ACM_Notifications::get_instance()->send_notification(
                $user_id_2,
                'partnership_created',
                __('New Course Partner', 'advanced-course-manager'),
                sprintf(__('You are now partnered with %s for "%s"', 'advanced-course-manager'), 
                    $user1->display_name, $course_title),
                get_permalink($course_id)
            );
            
            return array(
                'success' => true,
                'partnership_id' => $wpdb->insert_id,
                'message' => __('Partnership created successfully', 'advanced-course-manager')
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to create partnership', 'advanced-course-manager')
        );
    }
    
    /**
     * Get partnership between two users
     */
    public function get_partnership($user_id_1, $user_id_2, $course_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_partnerships';
        
        // Ensure consistency in user order
        if ($user_id_1 > $user_id_2) {
            $temp = $user_id_1;
            $user_id_1 = $user_id_2;
            $user_id_2 = $temp;
        }
        
        $partnership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id_1 = %d AND user_id_2 = %d AND course_id = %d",
            $user_id_1,
            $user_id_2,
            $course_id
        ));
        
        return $partnership;
    }
    
    /**
     * Get partner for a user in a specific course
     */
    public function get_partner($user_id, $course_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_partnerships';
        
        $partnership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE (user_id_1 = %d OR user_id_2 = %d) 
            AND course_id = %d 
            AND status = 'active'",
            $user_id,
            $user_id,
            $course_id
        ));
        
        if ($partnership) {
            // Return the other user's ID
            return $partnership->user_id_1 == $user_id ? $partnership->user_id_2 : $partnership->user_id_1;
        }
        
        return null;
    }
    
    /**
     * Get all partnerships for a user
     */
    public function get_user_partnerships($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_partnerships';
        
        $partnerships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE (user_id_1 = %d OR user_id_2 = %d) 
            AND status = 'active'
            ORDER BY created_date DESC",
            $user_id,
            $user_id
        ));
        
        return $partnerships;
    }
    
    /**
     * Generate invite token for partnership
     */
    public function generate_invite_token($user_id, $course_id) {
        $token = wp_generate_password(32, false);
        
        set_transient('acm_invite_' . $token, array(
            'user_id' => $user_id,
            'course_id' => $course_id
        ), DAY_IN_SECONDS * 7); // Valid for 7 days
        
        return $token;
    }
    
    /**
     * Accept partnership invite
     */
    public function accept_invite($token, $user_id) {
        $invite_data = get_transient('acm_invite_' . $token);
        
        if (!$invite_data) {
            return array(
                'success' => false,
                'message' => __('Invalid or expired invite', 'advanced-course-manager')
            );
        }
        
        $result = $this->create_partnership(
            $invite_data['user_id'],
            $user_id,
            $invite_data['course_id']
        );
        
        if ($result['success']) {
            delete_transient('acm_invite_' . $token);
        }
        
        return $result;
    }
    
    /**
     * Remove partnership
     */
    public function remove_partnership($user_id, $course_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_partnerships';
        
        $result = $wpdb->update(
            $table,
            array('status' => 'inactive'),
            array(
                'course_id' => $course_id
            ),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get partner's progress
     */
    public function get_partner_progress($user_id, $course_id) {
        $partner_id = $this->get_partner($user_id, $course_id);
        
        if (!$partner_id) {
            return null;
        }
        
        return ACM_Progress::get_instance()->get_course_progress($partner_id, $course_id);
    }
}