<?php
/**
 * Progress Tracking Class
 * File: includes/class-acm-progress.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Progress {
    
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
        // Hooks can be added here
    }
    
    /**
     * Get user's progress for a specific course
     */
    public function get_course_progress($user_id, $course_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_progress';
        $lessons = $this->get_course_lessons($course_id);
        
        if (empty($lessons)) {
            return array(
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'percentage' => 0,
                'total_time' => 0
            );
        }
        
        $lesson_ids = wp_list_pluck($lessons, 'ID');
        $placeholders = implode(',', array_fill(0, count($lesson_ids), '%d'));
        
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(time_spent) as total_time
            FROM $table 
            WHERE user_id = %d AND lesson_id IN ($placeholders)",
            array_merge(array($user_id), $lesson_ids)
        );
        
        $result = $wpdb->get_row($query);
        
        $total_lessons = count($lesson_ids);
        $completed = $result ? (int)$result->completed : 0;
        $percentage = $total_lessons > 0 ? round(($completed / $total_lessons) * 100, 2) : 0;
        
        return array(
            'total_lessons' => $total_lessons,
            'completed_lessons' => $completed,
            'percentage' => $percentage,
            'total_time' => $result ? (int)$result->total_time : 0,
            'lessons' => $lessons
        );
    }

    public function get_course_chapters($course_id) {
        return get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_acm_chapter_course',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'orderby'  => 'meta_value_num',
            'meta_key' => '_acm_chapter_number',
            'order'    => 'ASC'
        ));
    }
    
    /**
     * Get all lessons for a course
     */
    public function get_course_lessons($course_id) {
        $chapters = $this->get_course_chapters($course_id);
        $lessons  = array();

        foreach ($chapters as $chapter) {
            $chapter_lessons = get_posts(array(
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_acm_lesson_chapter',
                        'value' => $chapter->ID,
                        'compare' => '='
                    )
                ),
                'orderby' => 'menu_order',
                'order'   => 'ASC'
            ));

            $lessons = array_merge($lessons, $chapter_lessons);
        }

        return $lessons;
    }
    
    /**
     * Get lesson progress for a user
     */
    public function get_lesson_progress($user_id, $lesson_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_progress';
        
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));
        
        return $progress;
    }
    
    /**
     * Mark lesson as complete
     */
    public function mark_lesson_complete($user_id, $lesson_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_progress';
        $course_id = get_post_meta($lesson_id, '_acm_lesson_course', true);
        
        // Check if progress exists
        $existing = $this->get_lesson_progress($user_id, $lesson_id);
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table,
                array(
                    'status' => 'completed',
                    'completion_date' => current_time('mysql'),
                    'last_accessed' => current_time('mysql')
                ),
                array(
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id
                ),
                array('%s', '%s', '%s'),
                array('%d', '%d')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'lesson_id' => $lesson_id,
                    'status' => 'completed',
                    'completion_date' => current_time('mysql'),
                    'last_accessed' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s')
            );
        }
        
        // Log activity
        $this->log_activity($user_id, $course_id, $lesson_id, 'lesson_completed');
        
        // Check if course is complete
        // $this->check_course_completion($user_id, $course_id);
        
        // Notify partner
        // $this->notify_partner_progress($user_id, $course_id, $lesson_id);
        
        return $result !== false;
    }
    
    /**
     * Update time spent on lesson
     */
    public function update_time_spent($user_id, $lesson_id, $seconds) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_progress';
        $course_id = get_post_meta($lesson_id, '_acm_lesson_course', true);
        
        $existing = $this->get_lesson_progress($user_id, $lesson_id);
        
        if ($existing) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET time_spent = time_spent + %d, last_accessed = %s WHERE user_id = %d AND lesson_id = %d",
                $seconds,
                current_time('mysql'),
                $user_id,
                $lesson_id
            ));
        } else {
            $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'lesson_id' => $lesson_id,
                    'status' => 'in_progress',
                    'time_spent' => $seconds,
                    'last_accessed' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%d', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Update video progress
     */
    public function update_video_progress($user_id, $lesson_id, $percentage) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_progress';
        $course_id = get_post_meta($lesson_id, '_acm_lesson_course', true);
        
        $existing = $this->get_lesson_progress($user_id, $lesson_id);
        
        if ($existing) {
            $wpdb->update(
                $table,
                array(
                    'video_progress' => $percentage,
                    'last_accessed' => current_time('mysql')
                ),
                array(
                    'user_id' => $user_id,
                    'lesson_id' => $lesson_id
                ),
                array('%d', '%s'),
                array('%d', '%d')
            );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'lesson_id' => $lesson_id,
                    'video_progress' => $percentage,
                    'status' => 'in_progress',
                    'last_accessed' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%s', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Check if course is complete and award certificate
     */
    private function check_course_completion($user_id, $course_id) {
        $progress = $this->get_course_progress($user_id, $course_id);
        
        if ($progress['percentage'] >= 100) {
            // Course is complete
            do_action('acm_course_completed', $user_id, $course_id, $progress);
            
            // Generate certificate
            ACM_Certificates::get_instance()->generate_certificate($user_id, $course_id);
            
            // Send notification
            ACM_Notifications::get_instance()->send_notification(
                $user_id,
                'course_completed',
                __('Course Completed!', 'advanced-course-manager'),
                sprintf(__('Congratulations! You have completed "%s"', 'advanced-course-manager'), get_the_title($course_id)),
                get_permalink($course_id)
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Notify partner about progress
     */
    private function notify_partner_progress($user_id, $course_id, $lesson_id) {
        $partner_id = acm_get_user_partner($user_id, $course_id);
        
        if ($partner_id) {
            $user = get_userdata($user_id);
            $lesson_title = get_the_title($lesson_id);
            
            ACM_Notifications::get_instance()->send_notification(
                $partner_id,
                'partner_progress',
                __('Partner Progress Update', 'advanced-course-manager'),
                sprintf(
                    __('%s completed "%s"', 'advanced-course-manager'),
                    $user->display_name,
                    $lesson_title
                ),
                get_permalink($lesson_id)
            );
        }
    }
    
    /**
     * Log user activity
     */
    private function log_activity($user_id, $course_id, $lesson_id, $activity_type, $data = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_activity';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'lesson_id' => $lesson_id,
                'activity_type' => $activity_type,
                'activity_data' => $data ? json_encode($data) : null,
                'created_date' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get user's recent activity
     */
    public function get_user_activity($user_id, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'acm_activity';
        
        $activity = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_date DESC LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $activity;
    }
}