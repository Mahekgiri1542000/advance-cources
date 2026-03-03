<?php
/**
 * AJAX Handler Class
 * File: includes/class-acm-ajax.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_AJAX {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Progress actions
        add_action('wp_ajax_acm_mark_lesson_complete', array($this, 'mark_lesson_complete'));
        add_action('wp_ajax_acm_update_time_spent', array($this, 'update_time_spent'));
        add_action('wp_ajax_acm_update_video_progress', array($this, 'update_video_progress'));
        
        // Partnership actions
        add_action('wp_ajax_acm_create_partnership', array($this, 'create_partnership'));
        add_action('wp_ajax_acm_remove_partnership', array($this, 'remove_partnership'));
        add_action('wp_ajax_acm_generate_invite', array($this, 'generate_invite'));
        add_action('wp_ajax_acm_accept_invite', array($this, 'accept_invite'));
        
        // Notes actions
        add_action('wp_ajax_acm_save_note', array($this, 'save_note'));
        add_action('wp_ajax_acm_delete_note', array($this, 'delete_note'));
        add_action('wp_ajax_acm_get_notes', array($this, 'get_notes'));
        
        // Discussion actions
        add_action('wp_ajax_acm_post_message', array($this, 'post_message'));
        add_action('wp_ajax_acm_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_acm_mark_messages_read', array($this, 'mark_messages_read'));
        
        // Bookmark actions
        add_action('wp_ajax_acm_save_bookmark', array($this, 'save_bookmark'));
        add_action('wp_ajax_acm_delete_bookmark', array($this, 'delete_bookmark'));
        add_action('wp_ajax_acm_get_bookmarks', array($this, 'get_bookmarks'));
        
        // Notification actions
        add_action('wp_ajax_acm_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_acm_get_notifications', array($this, 'get_notifications'));
        
        // Province selection
        add_action('wp_ajax_acm_set_province', array($this, 'set_province'));
        add_action('wp_ajax_nopriv_acm_set_province', array($this, 'set_province'));
    }
    
    /**
     * Mark lesson as complete
     */
    public function mark_lesson_complete() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson ID', 'advanced-course-manager')));
        }
        
        $result = ACM_Progress::get_instance()->mark_lesson_complete($user_id, $lesson_id);
        if ($result) {

            // Get updated progress
            $course_id = get_post_meta($lesson_id, '_acm_lesson_course', true);
            // $progress = ACM_Progress::get_instance()->get_course_progress($user_id, $course_id);
            
            wp_send_json_success(array(
                'success'  => true,
                'message' => __('Lesson marked as complete', 'advanced-course-manager'),
                'progress' => $progress
            ));
        } else {
            // wp_send_json_error(array('message' => __('Failed to mark lesson as complete', 'advanced-course-manager')));
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Failed to mark lesson as complete', 'advanced-course-manager')
            ));
        }
    }
    
    /**
     * Update time spent on lesson
     */
    public function update_time_spent() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $seconds = isset($_POST['seconds']) ? intval($_POST['seconds']) : 0;
        
        if (!$lesson_id || !$seconds) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'advanced-course-manager')));
        }
        
        $result = ACM_Progress::get_instance()->update_time_spent($user_id, $lesson_id, $seconds);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Time updated', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update time', 'advanced-course-manager')));
        }
    }
    
    /**
     * Update video progress
     */
    public function update_video_progress() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $percentage = isset($_POST['percentage']) ? intval($_POST['percentage']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson ID', 'advanced-course-manager')));
        }
        
        $result = ACM_Progress::get_instance()->update_video_progress($user_id, $lesson_id, $percentage);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Progress updated', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update progress', 'advanced-course-manager')));
        }
    }
    
    /**
     * Create partnership
     */
    public function create_partnership() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $partner_email = isset($_POST['partner_email']) ? sanitize_email($_POST['partner_email']) : '';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        if (!$partner_email || !$course_id) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'advanced-course-manager')));
        }
        
        $partner = get_user_by('email', $partner_email);
        
        if (!$partner) {
            wp_send_json_error(array('message' => __('User not found', 'advanced-course-manager')));
        }
        
        $result = ACM_Partnerships::get_instance()->create_partnership($user_id, $partner->ID, $course_id);
        
        wp_send_json($result);
    }
    
    /**
     * Generate partnership invite
     */
    public function generate_invite() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        if (!$course_id) {
            wp_send_json_error(array('message' => __('Invalid course ID', 'advanced-course-manager')));
        }
        
        $token = ACM_Partnerships::get_instance()->generate_invite_token($user_id, $course_id);
        $invite_url = add_query_arg('acm_invite', $token, home_url('/course-invite/'));
        
        wp_send_json_success(array(
            'token' => $token,
            'invite_url' => $invite_url,
            'message' => __('Invite link generated', 'advanced-course-manager')
        ));
    }
    
    /**
     * Accept partnership invite
     */
    public function accept_invite() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        
        if (!$token) {
            wp_send_json_error(array('message' => __('Invalid token', 'advanced-course-manager')));
        }
        
        $result = ACM_Partnerships::get_instance()->accept_invite($token, $user_id);
        
        wp_send_json($result);
    }
    
    /**
     * Save note
     */
    public function save_note() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $is_shared = isset($_POST['is_shared']) ? (bool)$_POST['is_shared'] : false;
        $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : null;
        
        if (!$lesson_id || empty($content)) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'advanced-course-manager')));
        }
        
        $result = ACM_Notes::get_instance()->save_note($user_id, $lesson_id, $content, $is_shared, $timestamp);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Note saved', 'advanced-course-manager'),
                'note_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save note', 'advanced-course-manager')));
        }
    }
    
    /**
     * Get notes for a lesson
     */
    public function get_notes() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson ID', 'advanced-course-manager')));
        }
        
        $notes = ACM_Notes::get_instance()->get_lesson_notes($user_id, $lesson_id);
        
        wp_send_json_success(array('notes' => $notes));
    }
    
    /**
     * Delete note
     */
    public function delete_note() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
        
        if (!$note_id) {
            wp_send_json_error(array('message' => __('Invalid note ID', 'advanced-course-manager')));
        }
        
        $result = ACM_Notes::get_instance()->delete_note($note_id, $user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Note deleted', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete note', 'advanced-course-manager')));
        }
    }
    
    /**
     * Post discussion message
     */
    public function post_message() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        if (!$lesson_id || empty($message)) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'advanced-course-manager')));
        }
        
        $result = ACM_Discussions::get_instance()->post_message($user_id, $lesson_id, $message);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Message posted', 'advanced-course-manager'),
                'message_id' => $result
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to post message', 'advanced-course-manager')));
        }
    }
    
    /**
     * Get discussion messages
     */
    public function get_messages() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson ID', 'advanced-course-manager')));
        }
        
        $messages = ACM_Discussions::get_instance()->get_lesson_messages($user_id, $lesson_id);
        
        wp_send_json_success(array('messages' => $messages));
    }
    
    /**
     * Save bookmark
     */
    public function save_bookmark() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : null;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Invalid lesson ID', 'advanced-course-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_bookmarks';
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'lesson_id' => $lesson_id,
                'bookmark_title' => $title,
                'video_timestamp' => $timestamp,
                'note' => $note,
                'created_date' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Bookmark saved', 'advanced-course-manager'),
                'bookmark_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save bookmark', 'advanced-course-manager')));
        }
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(array('message' => __('Invalid notification ID', 'advanced-course-manager')));
        }
        
        $result = ACM_Notifications::get_instance()->mark_as_read($notification_id, $user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Notification marked as read', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to mark notification', 'advanced-course-manager')));
        }
    }
    
    /**
     * Set user province
     */
    public function set_province() {
        check_ajax_referer('acm_province_selection', 'nonce');
        
        $province = isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '';
        
        if (!$province) {
            wp_send_json_error(array('message' => __('Invalid province', 'advanced-course-manager')));
        }
        
        $user_id = get_current_user_id();
        
        // Set province
        if (class_exists('ACM_Province_Manager')) {
            $result = ACM_Province_Manager::get_instance()->set_user_province($user_id, $province);
            
            if ($result || !$user_id) {
                // Get course for this province
                $course_id = ACM_Province_Manager::get_instance()->get_course_for_province($province);
                $course_url = $course_id ? get_permalink($course_id) : home_url();
                
                wp_send_json_success(array(
                    'message' => __('Province set successfully', 'advanced-course-manager'),
                    'province' => $province,
                    'course_url' => $course_url
                ));
            }
        }
        
        wp_send_json_error(array('message' => __('Failed to set province', 'advanced-course-manager')));
    }
}