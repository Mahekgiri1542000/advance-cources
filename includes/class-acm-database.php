<?php
/**
 * Database Management Class
 * File: includes/class-acm-database.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Progress table
        $table_progress = $wpdb->prefix . 'acm_progress';
        $sql_progress = "CREATE TABLE $table_progress (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'not_started',
            completion_date datetime DEFAULT NULL,
            time_spent int(11) DEFAULT 0,
            last_accessed datetime DEFAULT NULL,
            video_progress int(11) DEFAULT 0,
            quiz_score decimal(5,2) DEFAULT NULL,
            attempts int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY lesson_id (lesson_id),
            UNIQUE KEY user_lesson (user_id, lesson_id)
        ) $charset_collate;";
        
        // Partnerships table
        $table_partnerships = $wpdb->prefix . 'acm_partnerships';
        $sql_partnerships = "CREATE TABLE $table_partnerships (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id_1 bigint(20) NOT NULL,
            user_id_2 bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            created_date datetime NOT NULL,
            status varchar(20) DEFAULT 'active',
            invite_token varchar(100) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id_1 (user_id_1),
            KEY user_id_2 (user_id_2),
            KEY course_id (course_id),
            UNIQUE KEY partnership (user_id_1, user_id_2, course_id)
        ) $charset_collate;";
        
        // Notes table
        $table_notes = $wpdb->prefix . 'acm_notes';
        $sql_notes = "CREATE TABLE $table_notes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            note_content longtext NOT NULL,
            is_shared tinyint(1) DEFAULT 0,
            created_date datetime NOT NULL,
            modified_date datetime DEFAULT NULL,
            video_timestamp int(11) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";
        
        // Discussions table
        $table_discussions = $wpdb->prefix . 'acm_discussions';
        $sql_discussions = "CREATE TABLE $table_discussions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lesson_id bigint(20) NOT NULL,
            partnership_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            parent_id bigint(20) DEFAULT NULL,
            created_date datetime NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY lesson_id (lesson_id),
            KEY partnership_id (partnership_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        
        // Certificates table
        $table_certificates = $wpdb->prefix . 'acm_certificates';
        $sql_certificates = "CREATE TABLE IF NOT EXISTS $table_certificates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            certificate_code varchar(50) NOT NULL,
            issued_date datetime NOT NULL,
            completion_time int(11) DEFAULT 0,
            file_path varchar(255) DEFAULT NULL,
            email_sent tinyint(1) DEFAULT 0,
            email_sent_date datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_certificate (user_id, course_id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY certificate_code (certificate_code)
        ) $charset_collate;";
        
        // Bookmarks table
        $table_bookmarks = $wpdb->prefix . 'acm_bookmarks';
        $sql_bookmarks = "CREATE TABLE $table_bookmarks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            bookmark_title varchar(255) DEFAULT NULL,
            video_timestamp int(11) DEFAULT NULL,
            note text DEFAULT NULL,
            created_date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";
        
        // Notifications table
        $table_notifications = $wpdb->prefix . 'acm_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(255) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        // Activity log table
        $table_activity = $wpdb->prefix . 'acm_activity';
        $sql_activity = "CREATE TABLE $table_activity (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            lesson_id bigint(20) DEFAULT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data text DEFAULT NULL,
            created_date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY activity_type (activity_type)
        ) $charset_collate;";
        
        // Agreement choices table
        $table_agreement = $wpdb->prefix . 'acm_agreement_choices';
        $sql_agreement = "CREATE TABLE $table_agreement (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            section_id varchar(100) NOT NULL,
            section_title varchar(255) NOT NULL,
            section_order int(11) DEFAULT 0,
            choice_id varchar(100) NOT NULL,
            choice_text text NOT NULL,
            choice_value text DEFAULT NULL,
            choice_order int(11) DEFAULT 0,
            created_date datetime NOT NULL,
            updated_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY section_id (section_id),
            UNIQUE KEY user_choice (user_id, choice_id)
        ) $charset_collate;";
        
        // Execute table creation
        dbDelta($sql_progress);
        dbDelta($sql_partnerships);
        dbDelta($sql_notes);
        dbDelta($sql_discussions);
        dbDelta($sql_certificates);
        dbDelta($sql_bookmarks);
        dbDelta($sql_notifications);
        dbDelta($sql_activity);
        dbDelta($sql_agreement);
        
        // Update database version
        update_option('acm_db_version', ACM_VERSION);
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'acm_progress',
            $wpdb->prefix . 'acm_partnerships',
            $wpdb->prefix . 'acm_notes',
            $wpdb->prefix . 'acm_discussions',
            $wpdb->prefix . 'acm_certificates',
            $wpdb->prefix . 'acm_bookmarks',
            $wpdb->prefix . 'acm_notifications',
            $wpdb->prefix . 'acm_activity'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('acm_db_version');
    }
}