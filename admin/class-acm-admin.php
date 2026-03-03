<?php
/**
 * Admin Class - FIXED VERSION
 * File: admin/class-acm-admin.php
 */

if (!defined('ABSPATH')) exit;

class ACM_Admin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Admin AJAX handlers for certificates & progress
        add_action('wp_ajax_acm_admin_view_certificate', array($this, 'ajax_view_certificate'));
        add_action('wp_ajax_acm_admin_send_certificate', array($this, 'ajax_send_certificate'));
        add_action('wp_ajax_acm_admin_get_student_progress', array($this, 'ajax_get_student_progress'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=acm_course',
            __('Course Reports', 'advanced-course-manager'),
            __('Reports', 'advanced-course-manager'),
            'manage_options',
            'acm-reports',
            array($this, 'render_reports_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=acm_course',
            __('Certificates', 'advanced-course-manager'),
            __('Certificates', 'advanced-course-manager'),
            'manage_options',
            'acm-certificates',
            array($this, 'render_certificates_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=acm_course',
            __('Settings', 'advanced-course-manager'),
            __('Settings', 'advanced-course-manager'),
            'manage_options',
            'acm-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'acm_course') !== false || strpos($hook, 'acm-') !== false) {
            wp_enqueue_style('acm-admin-styles', ACM_PLUGIN_URL . 'admin/css/admin-style.css', array(), ACM_VERSION);
            wp_enqueue_script('acm-admin-scripts', ACM_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery'), ACM_VERSION, true);
            
            // DataTables for certificates page
            if (isset($_GET['page']) && $_GET['page'] === 'acm-certificates') {
                wp_enqueue_style(
                    'datatables-css',
                    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
                    array(),
                    '1.13.7'
                );
                
                wp_enqueue_script(
                    'datatables-js',
                    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                    array('jquery'),
                    '1.13.7',
                    true
                );
            }
            
            wp_localize_script('acm-admin-scripts', 'acmAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('acm_admin_nonce')
            ));
        }
    }
    
    public function render_reports_page() {
        global $wpdb;
        
        // Get filter parameters from URL
        $filtered_course_id = isset($_GET['acm_course_filter']) ? intval($_GET['acm_course_filter']) : 0;
        $filtered_chapter_id = isset($_GET['acm_chapter_filter']) ? intval($_GET['acm_chapter_filter']) : 0;
        $filtered_lesson_id = isset($_GET['acm_lesson_filter']) ? intval($_GET['acm_lesson_filter']) : 0;
        
        // If filtering by lesson, show lesson-specific statistics
        if ($filtered_lesson_id) {
            $this->render_lesson_statistics($filtered_lesson_id, $filtered_course_id, $filtered_chapter_id);
            return;
        }
        
        // If filtering by chapter, show chapter-specific statistics
        if ($filtered_chapter_id) {
            $this->render_chapter_statistics($filtered_chapter_id, $filtered_course_id);
            return;
        }
        
        // If filtering by course, show course-specific statistics
        if ($filtered_course_id) {
            $this->render_course_statistics($filtered_course_id);
            return;
        }
        
        // Default: Show all statistics
        $this->render_all_statistics();
    }
    
    /**
     * Render statistics for a specific lesson
     */
    private function render_lesson_statistics($lesson_id, $course_id, $chapter_id) {
        global $wpdb;
        
        $lesson = get_post($lesson_id);
        if (!$lesson) {
            echo '<div class="wrap"><p>Lesson not found.</p></div>';
            return;
        }
        
        $chapter = $chapter_id ? get_post($chapter_id) : null;
        $course = $course_id ? get_post($course_id) : null;
        
        // Get lesson statistics
        $table = $wpdb->prefix . 'acm_progress';
        $total_views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $table WHERE lesson_id = %d",
            $lesson_id
        ));
        
        $completed = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $table WHERE lesson_id = %d AND status = 'completed'",
            $lesson_id
        ));
        
        $in_progress = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $table WHERE lesson_id = %d AND status = 'in_progress'",
            $lesson_id
        ));
        
        $not_started = $total_views > 0 ? $total_views - $completed - $in_progress : 0;
        $completion_rate = $total_views > 0 ? round(($completed / $total_views) * 100) : 0;
        
        // Get student details
        $students = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.user_id,
                p.status,
                p.completion_date,
                p.time_spent,
                u.display_name,
                u.user_email
            FROM $table p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.lesson_id = %d
            ORDER BY p.completion_date DESC
        ", $lesson_id));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lesson Statistics', 'advanced-course-manager'); ?></h1>
            
            <!-- Breadcrumb -->
            <div style="margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=acm_course&page=acm-reports')); ?>">
                    <?php _e('All Reports', 'advanced-course-manager'); ?>
                </a>
                <?php if ($course): ?>
                    → <a href="<?php echo esc_url(admin_url('edit.php?post_type=acm_course&page=acm-reports&acm_course_filter=' . $course_id)); ?>">
                        <?php echo esc_html($course->post_title); ?>
                    </a>
                <?php endif; ?>
                <?php if ($chapter): ?>
                    → <a href="<?php echo esc_url(admin_url('edit.php?post_type=acm_chapter&page=acm-reports&acm_course_filter=' . $course_id . '&acm_chapter_filter=' . $chapter_id)); ?>">
                        <?php echo esc_html($chapter->post_title); ?>
                    </a>
                <?php endif; ?>
                → <strong><?php echo esc_html($lesson->post_title); ?></strong>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=acm_course&page=acm-reports')); ?>" style="float: right;">
                    [<?php _e('Clear Filters', 'advanced-course-manager'); ?>]
                </a>
            </div>
            
            <!-- Statistics Cards -->
            <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom: 30px;">
                <div style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #FF9800;">
                    <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Total Students', 'advanced-course-manager'); ?></div>
                    <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_views); ?></div>
                </div>
                <div style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #4CAF50;">
                    <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Completed', 'advanced-course-manager'); ?></div>
                    <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($completed); ?></div>
                </div>
                <div style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #2196F3;">
                    <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('In Progress', 'advanced-course-manager'); ?></div>
                    <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($in_progress); ?></div>
                </div>
                <div style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #9C27B0;">
                    <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Completion Rate', 'advanced-course-manager'); ?></div>
                    <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($completion_rate); ?>%</div>
                </div>
            </div>
            
            <!-- Student Details Table -->
            <h2><?php _e('Student Progress', 'advanced-course-manager'); ?></h2>
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <?php if ($students): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Student', 'advanced-course-manager'); ?></th>
                                <th><?php _e('Email', 'advanced-course-manager'); ?></th>
                                <th><?php _e('Status', 'advanced-course-manager'); ?></th>
                                <th><?php _e('Time Spent', 'advanced-course-manager'); ?></th>
                                <th><?php _e('Completed Date', 'advanced-course-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo esc_html($student->display_name); ?></td>
                                <td><?php echo esc_html($student->user_email); ?></td>
                                <td>
                                    <?php 
                                    $status_color = '#999';
                                    $status_text = __('Not Started', 'advanced-course-manager');
                                    
                                    if ($student->status === 'completed') {
                                        $status_color = '#4CAF50';
                                        $status_text = __('Completed', 'advanced-course-manager');
                                    } elseif ($student->status === 'in_progress') {
                                        $status_color = '#2196F3';
                                        $status_text = __('In Progress', 'advanced-course-manager');
                                    }
                                    ?>
                                    <span style="color:<?php echo esc_attr($status_color); ?>; font-weight:600;">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(isset($student->time_spent) ? $student->time_spent . ' min' : '—'); ?></td>
                                <td><?php echo $student->completion_date ? esc_html(date('M d, Y H:i', strtotime($student->completion_date))) : '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No student data available for this lesson.', 'advanced-course-manager'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render statistics for a specific chapter
     */
    private function render_chapter_statistics($chapter_id, $course_id) {
        $chapter = get_post($chapter_id);
        if (!$chapter) {
            echo '<div class="wrap"><p>Chapter not found.</p></div>';
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($chapter->post_title) . ' ' . __('Statistics', 'advanced-course-manager') . '</h1>';
        echo '<p>' . __('Chapter statistics display coming soon.', 'advanced-course-manager') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render statistics for a specific course
     */
    private function render_course_statistics($course_id) {
        $course = get_post($course_id);
        if (!$course) {
            echo '<div class="wrap"><p>Course not found.</p></div>';
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($course->post_title) . ' ' . __('Statistics', 'advanced-course-manager') . '</h1>';
        echo '<p>' . __('Course statistics display coming soon.', 'advanced-course-manager') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render all statistics (default view)
     */
    private function render_all_statistics() {
        global $wpdb;
        
        // Get statistics - FIXED QUERIES
        $total_courses  = wp_count_posts('acm_course')->publish;
        $total_chapters = wp_count_posts('acm_chapter')->publish;
        $total_lessons  = wp_count_posts('acm_lesson')->publish;
        
        // Get total unique students from progress table
        $total_students = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}acm_progress
        ");
        
        // Get total completions from certificates table
        $total_completions = (int) $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}acm_certificates
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Course Reports', 'advanced-course-manager'); ?></h1>
            
            <div class="acm-report-dashboard" style="margin-top: 20px;">
                <div class="acm-report-cards" style="display:flex; flex-wrap:wrap; gap:20px;">
                    <div class="acm-report-card" style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #db9563;">
                        <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Total Courses', 'advanced-course-manager'); ?></div>
                        <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_courses); ?></div>
                        <div style="font-size:12px; color:#777; margin-top:6px;">📚 <?php _e('Published course content', 'advanced-course-manager'); ?></div>
                    </div>
                    <div class="acm-report-card" style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #4CAF50;">
                        <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Total Chapters', 'advanced-course-manager'); ?></div>
                        <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_chapters); ?></div>
                        <div style="font-size:12px; color:#777; margin-top:6px;">📖 <?php _e('Chapter modules', 'advanced-course-manager'); ?></div>
                    </div>
                    <div class="acm-report-card" style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #2196F3;">
                        <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Total Lessons', 'advanced-course-manager'); ?></div>
                        <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_lessons); ?></div>
                        <div style="font-size:12px; color:#777; margin-top:6px;">🧩 <?php _e('Learning units available', 'advanced-course-manager'); ?></div>
                    </div>
                    <div class="acm-report-card" style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #FF9800;">
                        <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Active Students', 'advanced-course-manager'); ?></div>
                        <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_students); ?></div>
                        <div style="font-size:12px; color:#777; margin-top:6px;">👥 <?php _e('Students with recorded progress', 'advanced-course-manager'); ?></div>
                    </div>
                    <div class="acm-report-card" style="flex:1; min-width:200px; background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #9C27B0;">
                        <div style="font-size:13px; text-transform:uppercase; color:#999; margin-bottom:8px;"><?php _e('Course Completions', 'advanced-course-manager'); ?></div>
                        <div style="font-size:30px; font-weight:700; color:#333;"><?php echo esc_html($total_completions); ?></div>
                        <div style="font-size:12px; color:#777; margin-top:6px;">🎓 <?php _e('Certificates issued', 'advanced-course-manager'); ?></div>
                    </div>
                </div>
            </div>
            
            <h2 style="margin-top:40px;"><?php _e('Popular Courses', 'advanced-course-manager'); ?></h2>
            <?php
            global $wpdb;
            // FIXED: Get popular courses with proper student count
            $popular_courses = $wpdb->get_results("
                SELECT 
                    p.course_id,
                    COUNT(DISTINCT p.user_id) as student_count
                FROM {$wpdb->prefix}acm_progress p
                INNER JOIN {$wpdb->posts} posts ON p.course_id = posts.ID
                WHERE posts.post_type = 'acm_course' 
                  AND posts.post_status = 'publish'
                GROUP BY p.course_id
                ORDER BY student_count DESC
                LIMIT 10
            ");
            
            if ($popular_courses):
            ?>
            <div style="margin-top:10px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Course', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Chapters', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Lessons', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Students', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Completions', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Completion Rate', 'advanced-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_courses as $course): 
                            // Get chapter count for this course
                            $chapters = get_posts(array(
                                'post_type'      => 'acm_chapter',
                                'posts_per_page' => -1,
                                'post_status'    => 'publish',
                                'fields'         => 'ids',
                                'meta_query'     => array(
                                    array(
                                        'key'   => '_acm_chapter_course',
                                        'value' => $course->course_id,
                                        'compare' => '='
                                    )
                                )
                            ));
                            $chapter_count = count($chapters);
                            
                            // Get lesson count for this course (lessons within chapters of this course)
                            $lesson_count      = 0;
                            if (!empty($chapters)) {
                                $lesson_ids = get_posts(array(
                                    'post_type'      => 'acm_lesson',
                                    'posts_per_page' => -1,
                                    'post_status'    => 'publish',
                                    'fields'         => 'ids',
                                    'meta_query'     => array(
                                        array(
                                            'key'     => '_acm_lesson_chapter',
                                            'value'   => $chapters,
                                            'compare' => 'IN'
                                        )
                                    )
                                ));
                                $lesson_count = count($lesson_ids);
                            }
                            
                            // Get completions
                            $completions = (int) $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}acm_certificates WHERE course_id = %d",
                                $course->course_id
                            ));
                            
                            $rate = $course->student_count > 0 ? round(($completions / $course->student_count) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($course->course_id); ?>">
                                    <strong><?php echo esc_html(get_the_title($course->course_id)); ?></strong>
                                </a>
                            </td>
                            <td><?php echo esc_html($chapter_count); ?></td>
                            <td><?php echo esc_html($lesson_count); ?></td>
                            <td><?php echo esc_html($course->student_count); ?></td>
                            <td><?php echo esc_html($completions); ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; height:6px; background:#f1f1f1; border-radius:3px; overflow:hidden;">
                                        <div style="width: <?php echo esc_attr($rate); ?>%; height:100%; background:#4CAF50;"></div>
                                    </div>
                                    <span style="font-size:12px; font-weight:600;"><?php echo esc_html($rate); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?php _e('No course data available yet.', 'advanced-course-manager'); ?></p>
            <?php endif; ?>
            
            <!-- Additional Statistics Section -->
            <h2 style="margin-top:40px;"><?php _e('Recent Activity', 'advanced-course-manager'); ?></h2>
            <?php
            // Get recent completions
            $recent_completions = $wpdb->get_results("
                SELECT 
                    c.id,
                    c.user_id,
                    c.course_id,
                    c.issued_date,
                    u.display_name,
                    p.post_title as course_title
                FROM {$wpdb->prefix}acm_certificates c
                LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                LEFT JOIN {$wpdb->posts} p ON c.course_id = p.ID
                ORDER BY c.issued_date DESC
                LIMIT 10
            ");
            
            if ($recent_completions):
            ?>
            <div style="margin-top:10px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <h3 style="margin-top:0;"><?php _e('Latest Certificates Issued', 'advanced-course-manager'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Student', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Course', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Completion Date', 'advanced-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_completions as $completion): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_user_link($completion->user_id); ?>">
                                    <?php echo esc_html($completion->display_name); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($completion->course_id); ?>">
                                    <?php echo esc_html($completion->course_title); ?>
                                </a>
                            </td>
                            <td><?php echo date('M d, Y g:i A', strtotime($completion->issued_date)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?php _e('No recent completions.', 'advanced-course-manager'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function render_certificates_page() {
        global $wpdb;
        
        // Get all certificates with user and course data
        $certificates = $wpdb->get_results("
            SELECT 
                c.id,
                c.user_id,
                c.course_id,
                c.certificate_code,
                c.issued_date,
                c.email_sent,
                c.email_sent_date,
                u.display_name as user_name,
                u.user_email,
                p.post_title as course_title
            FROM {$wpdb->prefix}acm_certificates c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON c.course_id = p.ID
            ORDER BY c.issued_date DESC
        ");

        ?>
        <div class="wrap">
            <div class="loading-overlay">
                <div class="loading"></div>
            </div>
            <h1 class="wp-heading-inline"><?php _e('Course Certificates', 'advanced-course-manager'); ?></h1>

            <div class="acm-certificates-table-wrapper" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius:6px;">
                <table id="acm-certificates-table" class="wp-list-table widefat fixed striped table-view-list" style="width: 100%;">
                    <thead>
                        <tr>
                            <th><?php _e('Certificate Code', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Student Name', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Email', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Course', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Issued Date', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Email Sent', 'advanced-course-manager'); ?></th>
                            <th><?php _e('Actions', 'advanced-course-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($cert->certificate_code); ?></strong>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_user_link($cert->user_id); ?>">
                                    <?php echo esc_html($cert->user_name); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($cert->user_email); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($cert->course_id); ?>">
                                    <?php echo esc_html($cert->course_title); ?>
                                </a>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($cert->issued_date)); ?></td>
                            <td>
                                <?php if ($cert->email_sent): ?>
                                    <span style="color: #4CAF50; font-weight: 600;">
                                        ✓ <?php _e('Sent', 'advanced-course-manager'); ?>
                                    </span>
                                    <br>
                                    <small style="color: #999;">
                                        <?php echo date('M d, Y', strtotime($cert->email_sent_date)); ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color: #999;">
                                        <?php _e('Not Sent', 'advanced-course-manager'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small acm-view-cert" 
                                        data-cert-id="<?php echo esc_attr($cert->id); ?>"
                                        style="margin-right: 5px;margin-bottom: 5px;">
                                    👁️ <?php _e('View', 'advanced-course-manager'); ?>
                                </button>
                                
                                <?php if (!$cert->email_sent): ?>
                                <button class="button button-small acm-send-cert-email" 
                                        data-cert-id="<?php echo esc_attr($cert->id); ?>"
                                        data-email="<?php echo esc_attr($cert->user_email); ?>"
                                        style="margin-right: 5px;">
                                    📧 <?php _e('Send', 'advanced-course-manager'); ?>
                                </button>
                                <?php endif; ?>
                                
                                <button class="button button-small acm-view-student-progress" 
                                        data-user-id="<?php echo esc_attr($cert->user_id); ?>"
                                        data-course-id="<?php echo esc_attr($cert->course_id); ?>">
                                    📊 <?php _e('Progress', 'advanced-course-manager'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Progress Modal -->
        <div id="acm-progress-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
            <div style="position: relative; max-width: 900px; margin: 50px auto; background: #fff; border-radius: 10px; padding: 30px; max-height: 80vh; overflow-y: auto; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <button id="acm-close-modal" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                <h2 style="margin-top: 0;"><?php _e('Student Progress', 'advanced-course-manager'); ?></h2>
                <div id="acm-progress-content"></div>
            </div>
        </div>
        
        <style>
            .acm-stat-boxes {
                margin-bottom: 30px;
            }
            
            #acm-certificates-table {
                border-collapse: collapse;
            }
            
            #acm-certificates-table th {
                background: #f9f9f9;
                font-weight: 600;
                padding: 12px;
                text-align: left;
            }
            
            #acm-certificates-table td {
                padding: 12px;
                border-bottom: 1px solid #e5e5e5;
            }
            
            #acm-certificates-table tbody tr:hover {
                background: #f9f9f9;
            }
            
            .dataTables_wrapper .dataTables_length select {
                padding: 5px;
                margin: 0 5px;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                padding: 5px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 5px 10px;
                margin: 0 2px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
                cursor: pointer;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background: #f9f9f9;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: #db9563;
                color: #fff;
                border-color: #db9563;
            }

            /* Progress modal content styles */
            .acm-progress-summary {
                display:flex;
                flex-wrap:wrap;
                gap:15px;
                margin-bottom:20px;
            }
            .acm-progress-summary-card {
                flex:1;
                min-width:200px;
                background:#f9f9f9;
                border-radius:6px;
                padding:12px 15px;
            }
            .acm-progress-summary-card-title {
                font-size:12px;
                text-transform:uppercase;
                color:#888;
                margin-bottom:5px;
            }
            .acm-progress-summary-card-value {
                font-size:16px;
                font-weight:600;
            }
            .acm-progress-lessons-table {
                margin-top:15px;
            }
            .acm-progress-lessons-table table {
                width:100%;
                border-collapse:collapse;
            }
            .acm-progress-lessons-table th,
            .acm-progress-lessons-table td {
                padding:8px 10px;
                border-bottom:1px solid #eee;
                font-size:13px;
                text-align: left;
            }
            .acm-progress-status-pill {
                display:inline-block;
                padding:3px 8px;
                border-radius:999px;
                font-size:11px;
                font-weight:600;
            }
            .acm-progress-status-completed {
                background:#e8f5e9;
                color:#2e7d32;
            }
            .acm-progress-status-in_progress {
                background:#e3f2fd;
                color:#1565c0;
            }
            .acm-progress-status-not_started {
                background:#f5f5f5;
                color:#777;
            }
            div#acm-certificates-table_filter {
                margin-bottom: 25px;
            }
            .dataTables_wrapper .dataTables_length select {
                width: 60px;
            }
            table#acm-certificates-table {
                margin-bottom: 20px;
            }
            .loading-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.3);
                z-index: 9999;
                justify-content: center;
                align-items: center;
            }
            .loading {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                border: 5px solid #ddd;
                border-top-color: #db9563;
                animation: loading 1s linear infinite;
            }
            @keyframes loading {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize DataTable
            var table = $('#acm-certificates-table').DataTable({
                pageLength: 25,
                order: [[4, 'desc']], // Sort by issued date
                columnDefs: [
                    { orderable: false, targets: 6 } // Disable sorting on Actions column
                ],
                language: {
                    search: "<?php _e('Search:', 'advanced-course-manager'); ?>",
                    lengthMenu: "<?php _e('Show _MENU_ entries', 'advanced-course-manager'); ?>",
                    info: "<?php _e('Showing _START_ to _END_ of _TOTAL_ certificates', 'advanced-course-manager'); ?>",
                    infoEmpty: "<?php _e('No certificates found', 'advanced-course-manager'); ?>",
                    infoFiltered: "<?php _e('(filtered from _MAX_ total)', 'advanced-course-manager'); ?>",
                    paginate: {
                        first: "<?php _e('First', 'advanced-course-manager'); ?>",
                        last: "<?php _e('Last', 'advanced-course-manager'); ?>",
                        next: "<?php _e('Next', 'advanced-course-manager'); ?>",
                        previous: "<?php _e('Previous', 'advanced-course-manager'); ?>"
                    }
                }
            });
            
            // View Certificate
            $(document).on('click', '.acm-view-cert', function() {
                var certId = $(this).data('cert-id');
                var button = $(this);
                
                button.prop('disabled', true).text('<?php _e("Loading...", "advanced-course-manager"); ?>');
                $('.loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'acm_admin_view_certificate',
                        certificate_id: certId,
                        nonce: acmAdmin.nonce
                    },
                    success: function(response) {
                        $('.loading-overlay').css('display', 'none');
                        button.prop('disabled', false).html('👁️ <?php _e("View", "advanced-course-manager"); ?>');
                        
                        if (response.success && response.data.url) {
                            window.open(response.data.url, '_blank');
                        } else {
                            alert((response.data && response.data.message) || '<?php _e("Failed to load certificate", "advanced-course-manager"); ?>');
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).html('👁️ <?php _e("View", "advanced-course-manager"); ?>');
                        alert('<?php _e("An error occurred", "advanced-course-manager"); ?>');
                    }
                });
            });
            
            // Send Certificate Email
            $(document).on('click', '.acm-send-cert-email', function() {
                var certId = $(this).data('cert-id');
                var email  = $(this).data('email');
                var button = $(this);
                
                if (!confirm('<?php _e("Send certificate to", "advanced-course-manager"); ?> ' + email + '?')) {
                    return;
                }
                
                button.prop('disabled', true).text('<?php _e("Sending...", "advanced-course-manager"); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'acm_admin_send_certificate',
                        certificate_id: certId,
                        nonce: acmAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('td').prev().html('<span style="color: #4CAF50; font-weight: 600;">✓ <?php _e("Sent", "advanced-course-manager"); ?></span><br><small style="color: #999;"><?php echo date("M d, Y"); ?></small>');
                            button.remove();
                            alert(response.data.message);
                        } else {
                            button.prop('disabled', false).html('📧 <?php _e("Send", "advanced-course-manager"); ?>');
                            alert((response.data && response.data.message) || '<?php _e("Failed to send email", "advanced-course-manager"); ?>');
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).html('📧 <?php _e("Send", "advanced-course-manager"); ?>');
                        alert('<?php _e("An error occurred", "advanced-course-manager"); ?>');
                    }
                });
            });
            
            // View Student Progress
            $(document).on('click', '.acm-view-student-progress', function() {
                var userId   = $(this).data('user-id');
                var courseId = $(this).data('course-id');
                $('.loading-overlay').css('display', 'flex');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'acm_admin_get_student_progress',
                        user_id: userId,
                        course_id: courseId,
                        nonce: acmAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.loading-overlay').css('display', 'none');
                            $('#acm-progress-content').html(response.data.html);
                            $('#acm-progress-modal').fadeIn();
                        } else {
                            alert((response.data && response.data.message) || '<?php _e("Failed to load progress", "advanced-course-manager"); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e("An error occurred", "advanced-course-manager"); ?>');
                    }
                });
            });
            
            // Close Modal
            $('#acm-close-modal').on('click', function() {
                $('#acm-progress-modal').fadeOut();
            });
            
            $('#acm-progress-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut();
                }
            });
        });
        </script>
        <?php
    }
    
    public function render_settings_page() {
        if (isset($_POST['acm_save_settings'])) {
            check_admin_referer('acm_settings');
            
            $settings = array(
                'acm_enable_partnerships',
                'acm_enable_certificates',
                'acm_enable_discussions',
                'acm_enable_notes',
                'acm_enable_notifications',
                'acm_enable_drip_content'
            );
            
            foreach ($settings as $setting) {
                $value = isset($_POST[$setting]) ? 'yes' : 'no';
                update_option($setting, $value);
            }

            // Save text settings
            if (isset($_POST['acm_property_disclosure_worksheet_link'])) {
                update_option(
                    'acm_property_disclosure_worksheet_link',
                    sanitize_text_field($_POST['acm_property_disclosure_worksheet_link'])
                );
            }

            if (isset($_POST['acm_agreement_builder_worksheet_link'])) {
                update_option(
                    'acm_agreement_builder_worksheet_link',
                    sanitize_text_field($_POST['acm_agreement_builder_worksheet_link'])
                );
            }
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully', 'advanced-course-manager') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Course Settings', 'advanced-course-manager'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('acm_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Property Disclosure Worksheet Link', 'advanced-course-manager'); ?></th>
                        <td>
                            <input type="text" name="acm_property_disclosure_worksheet_link" value="<?php echo esc_attr(get_option('acm_property_disclosure_worksheet_link', '')); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Agreement Builder Worksheet Link', 'advanced-course-manager'); ?></th>
                        <td>
                            <input type="text" name="acm_agreement_builder_worksheet_link" value="<?php echo esc_attr(get_option('acm_agreement_builder_worksheet_link', '')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'advanced-course-manager'), 'primary', 'acm_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    /* ============================================================
     *  AJAX HANDLERS
     * ============================================================
     */

    /**
     * Admin: View certificate (open PDF)
     */
    public function ajax_view_certificate() {
        check_ajax_referer('acm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'advanced-course-manager')));
        }

        $certificate_id = isset($_POST['certificate_id']) ? intval($_POST['certificate_id']) : 0;
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate ID', 'advanced-course-manager')));
        }

        if (!class_exists('ACM_Certificate')) {
            wp_send_json_error(array('message' => __('Certificate handler not available', 'advanced-course-manager')));
        }

        $cert_instance = ACM_Certificate::get_instance();
        $url = $cert_instance->generate_pdf($certificate_id);

        if (!$url) {
            wp_send_json_error(array('message' => __('Unable to generate certificate PDF', 'advanced-course-manager')));
        }

        wp_send_json_success(array('url' => $url));
    }

    /**
     * Admin: Send certificate email
     */
    public function ajax_send_certificate() {
        check_ajax_referer('acm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'advanced-course-manager')));
        }

        $certificate_id = isset($_POST['certificate_id']) ? intval($_POST['certificate_id']) : 0;
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate ID', 'advanced-course-manager')));
        }

        if (!class_exists('ACM_Certificate')) {
            wp_send_json_error(array('message' => __('Certificate handler not available', 'advanced-course-manager')));
        }

        $cert_instance = ACM_Certificate::get_instance();
        $sent = $cert_instance->send_certificate_email($certificate_id);

        if ($sent) {
            wp_send_json_success(array('message' => __('Certificate email sent successfully.', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send certificate email.', 'advanced-course-manager')));
        }
    }

    /**
     * Admin: Get student progress (for modal)
     */
    public function ajax_get_student_progress() {
        check_ajax_referer('acm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'advanced-course-manager')));
        }

        $user_id   = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

        if (!$user_id || !$course_id) {
            wp_send_json_error(array('message' => __('Invalid user or course', 'advanced-course-manager')));
        }

        $user   = get_userdata($user_id);
        $course = get_post($course_id);

        if (!$user || !$course) {
            wp_send_json_error(array('message' => __('User or course not found', 'advanced-course-manager')));
        }

        // Get progress instance
        if (!class_exists('ACM_Progress')) {
            wp_send_json_error(array('message' => __('Progress handler not available', 'advanced-course-manager')));
        }

        $progress_instance = ACM_Progress::get_instance();
        
        // Get all chapters for this course
        $chapters = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'   => '_acm_chapter_course',
                    'value' => $course_id,
                    'compare' => '='
                )
            )
        ));

        // Calculate overall statistics
        $total_lessons     = 0;
        $completed_lessons = 0;
        
        // Group lessons by chapter
        $chapters_with_lessons = array();
        
        foreach ($chapters as $chapter) {
            // Get lessons for this chapter
            $lessons = get_posts(array(
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'   => '_acm_lesson_chapter',
                        'value' => $chapter->ID,
                        'compare' => '='
                    )
                )
            ));
            
            if (!empty($lessons)) {
                $chapter_lessons = array();
                
                foreach ($lessons as $lesson) {
                    $total_lessons++;
                    $lp = $progress_instance->get_lesson_progress($user_id, $lesson->ID);
                    $status = $lp ? $lp->status : 'not_started';
                    
                    if ($status === 'completed') {
                        $completed_lessons++;
                    }
                    
                    $chapter_lessons[] = array(
                        'lesson' => $lesson,
                        'status' => $status
                    );
                }
                
                $chapters_with_lessons[] = array(
                    'chapter' => $chapter,
                    'lessons' => $chapter_lessons
                );
            }
        }
        
        $percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

        ob_start();
        ?>
        <div class="acm-progress-summary">
            <div class="acm-progress-summary-card">
                <div class="acm-progress-summary-card-title"><?php _e('Student', 'advanced-course-manager'); ?></div>
                <div class="acm-progress-summary-card-value">
                    <?php echo esc_html($user->display_name); ?><br>
                    <span style="font-size:11px; color:#777;"><?php echo esc_html($user->user_email); ?></span>
                </div>
            </div>
            <div class="acm-progress-summary-card">
                <div class="acm-progress-summary-card-title"><?php _e('Course', 'advanced-course-manager'); ?></div>
                <div class="acm-progress-summary-card-value">
                    <?php echo esc_html($course->post_title); ?>
                </div>
            </div>
            <div class="acm-progress-summary-card">
                <div class="acm-progress-summary-card-title"><?php _e('Completion', 'advanced-course-manager'); ?></div>
                <div class="acm-progress-summary-card-value">
                    <?php echo esc_html($percentage); ?>%
                    <div style="margin-top:6px; height:6px; background:#f1f1f1; border-radius:3px; overflow:hidden;">
                        <div style="width: <?php echo esc_attr($percentage); ?>%; height:100%; background:#4CAF50;"></div>
                    </div>
                    <div style="font-size:11px; color:#777; margin-top:3px;">
                        <?php printf(__('%1$d of %2$d lessons completed', 'advanced-course-manager'), $completed_lessons, $total_lessons); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="acm-progress-lessons-table">
            <h3 style="margin-top:20px; margin-bottom:15px;"><?php _e('Progress by Chapter', 'advanced-course-manager'); ?></h3>
            
            <?php if (!empty($chapters_with_lessons)): ?>
                <?php foreach ($chapters_with_lessons as $chapter_data): 
                    $chapter = $chapter_data['chapter'];
                    $lessons = $chapter_data['lessons'];
                    
                    // Calculate chapter completion
                    $chapter_total = count($lessons);
                    $chapter_completed = 0;
                    foreach ($lessons as $lesson_data) {
                        if ($lesson_data['status'] === 'completed') {
                            $chapter_completed++;
                        }
                    }
                    $chapter_percentage = $chapter_total > 0 ? round(($chapter_completed / $chapter_total) * 100) : 0;
                ?>
                
                <div class="acm-chapter-section" style="margin-bottom:25px; background:#f9f9f9; border-radius:8px; padding:15px; border-left:4px solid #db9563;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h4 style="margin:0; font-size:16px; color:#333;">
                            📖 <?php echo esc_html($chapter->post_title); ?>
                        </h4>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:12px; color:#666;">
                                <?php printf(__('%1$d/%2$d lessons', 'advanced-course-manager'), $chapter_completed, $chapter_total); ?>
                            </span>
                            <span style="font-size:12px; font-weight:600; color:<?php echo $chapter_percentage == 100 ? '#4CAF50' : '#666'; ?>;">
                                <?php echo esc_html($chapter_percentage); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div style="height:4px; background:#e0e0e0; border-radius:2px; overflow:hidden; margin-bottom:15px;">
                        <div style="width: <?php echo esc_attr($chapter_percentage); ?>%; height:100%; background:#4CAF50; transition:width 0.3s;"></div>
                    </div>
                    
                    <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:4px; overflow:hidden;">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th style="padding:10px; text-align:left; font-size:12px; font-weight:600; color:#666; text-transform:uppercase;">
                                    <?php _e('Lesson', 'advanced-course-manager'); ?>
                                </th>
                                <th style="padding:10px; text-align:left; font-size:12px; font-weight:600; color:#666; text-transform:uppercase; width:150px;">
                                    <?php _e('Status', 'advanced-course-manager'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson_data): 
                                $lesson = $lesson_data['lesson'];
                                $status = $lesson_data['status'];
                                
                                $status_class = 'acm-progress-status-not_started';
                                $status_label = __('Not Started', 'advanced-course-manager');
                                $status_icon = '⚪';
                                
                                if ($status === 'completed') {
                                    $status_class = 'acm-progress-status-completed';
                                    $status_label = __('Completed', 'advanced-course-manager');
                                    $status_icon = '✅';
                                } elseif ($status === 'in_progress') {
                                    $status_class = 'acm-progress-status-in_progress';
                                    $status_label = __('In Progress', 'advanced-course-manager');
                                    $status_icon = '🔄';
                                }
                            ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px 12px; font-size:13px;">
                                    <?php echo esc_html($lesson->post_title); ?>
                                </td>
                                <td style="padding:10px 12px;">
                                    <span class="acm-progress-status-pill <?php echo esc_attr($status_class); ?>">
                                        <?php echo $status_icon; ?> <?php echo esc_html($status_label); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999; font-style:italic;"><?php _e('No chapters or lessons found for this course.', 'advanced-course-manager'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }
}

// Initialize
ACM_Admin::get_instance();