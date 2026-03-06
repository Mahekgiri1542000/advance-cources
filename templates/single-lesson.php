<?php
/**
 * Single Lesson Template
 * File: templates/single-lesson.php
 */

get_header();

$user_id = get_current_user_id();
$lesson_id = get_the_ID();

$chapter_id = get_post_meta($lesson_id, '_acm_lesson_chapter', true);
$course_id  = get_post_meta($chapter_id, '_acm_chapter_course', true);
$chapter_number = get_post_meta($chapter_id, '_acm_chapter_number', true);


// Check access
if (!acm_has_course_access($user_id, $course_id)) {
    ?>
    <style>
        .acm-no-access {
            text-align: center;
            margin: 35px;
        }
    </style>
    <div class="acm-no-access-wrapper">
        <div class="acm-no-access">
            <div class="access-icon">🔒</div>
            <h2><?php _e('Access Restricted', 'advanced-course-manager'); ?></h2>
            <p><?php _e('You do not have access to this lesson.', 'advanced-course-manager'); ?></p>
            <a href="<?php echo home_url('/course'); ?>" class="acm-btn acm-btn-primary">
                <?php _e('Back to Course', 'advanced-course-manager'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

// Get lesson data
$progress = ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson_id);
$course = get_post($course_id);
$all_lessons = ACM_Progress::get_instance()->get_course_lessons($course_id);
$partner_id = acm_get_user_partner($user_id, $course_id);

// Get video URL
$video_url = get_post_meta($lesson_id, '_acm_lesson_video_url', true);
$video_type = get_post_meta($lesson_id, '_acm_lesson_video_type', true);

// Calculate lesson position
$current_index = array_search($lesson_id, wp_list_pluck($all_lessons, 'ID'));
$prev_lesson = $current_index > 0 ? $all_lessons[$current_index - 1] : null;
$next_lesson = $current_index < count($all_lessons) - 1 ? $all_lessons[$current_index + 1] : null;

// Get course progress
$course_progress = ACM_Progress::get_instance()->get_course_progress($user_id, $course_id);

$lesson_sequence = array();
if (!empty($all_lessons)) {
    foreach ($all_lessons as $sequence_lesson) {
        $lesson_sequence[] = array(
            'id' => $sequence_lesson->ID,
            'url' => get_permalink($sequence_lesson->ID),
            'isHidden' => function_exists('acm_is_lesson_hidden_for_user') ? acm_is_lesson_hidden_for_user($sequence_lesson->ID, $user_id) : false,
        );
    }
}
?>
<link rel="stylesheet" id="advance-course-lesson-css" href="<?php echo ACM_PLUGIN_URL; ?>public/css/advance-course-lesson.css?ver=<?php echo time(); ?>" media="all">
<style>
    .acm-lesson-item.completed.completed a {
        border-left-color: #ede7dd;
    }
    .acm-lesson-item.completed a {
        background: #ede7dd;
        color: #555a5a;
        border: 1px solid #ede7dd;
    }
    .acm-lesson-item.completed a:hover {
        border-color: #db9563;
        background: #ede7dd;
        color: #555a5a !important;
    }
    .acm-lesson-item.completed .lesson-title {
        display: block;
        font-size: 0.9rem;
    }
    /*.acm-lesson-item.completed .lesson-status {
        color: #fff;
    }*/
    /*.acm-lesson-item.completed .lesson-status:hover {
        color: #000;
    }*/
    .acm-lesson-item.completed .lesson-number {
        background: #db9563;
        color: #fff;
    }
    .acm-lesson-item.active .lesson-number{
        background: #db9563;
        color: #fff;
    }
    .acm-lesson-navigation .acm-lesson-item a {
        background: #fff;
        color: #666;
        border: 1px solid #ede7dd;
    }
    .acm-lesson-navigation .acm-lesson-item:hover a {
        background: #fff;
        border-color: #db9563;
        transform: none;
    }
    .acm-lesson-navigation .acm-lesson-item.active a {
        background: #d6c3b2;
        color: #555a5a;
        border-color: #d6c3b2;
    }
    .acm-lesson-navigation .acm-lesson-item.completed a {
        background: #ede7dd;
        color: #555a5a;
        border-color: #ede7dd;
    }
    .acm-lesson-navigation .acm-lesson-item.active:hover a,
    .acm-lesson-navigation .acm-lesson-item.completed:hover a {
        border-color: #db9563;
    }
    .acm-lesson-navigation .acm-lesson-item.active .lesson-number,
    .acm-lesson-navigation .acm-lesson-item.completed .lesson-number {
        background: #db9563;
        color: #fff;
    }
    .acm-lesson-navigation .acm-lesson-item .lesson-title,
    .acm-lesson-navigation .acm-lesson-item.completed .lesson-title {
        font-size: 0.9rem;
    }
    .acm-lesson-actions .acm-btn-primary:hover{
        background: #f1f3f9 !important;
        color: #db9563 !important;
        transform: none;
        box-shadow: none;
    }
    .acm-lesson-navigation-buttons .acm-btn-primary:hover{
        transform: none;
        box-shadow: none;
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
    .acm-quick-actions .acm-btn-secondary {
        background: #ede7dd;
        color: #555a5a !important;
        border: 1px solid #d6c3b2;
    }
    .acm-btn-secondary {
        background: #ede7dd;
        color: #555a5a !important;
        border: 2px solid #d6c3b2;
    }
    .acm-lesson-navigation-buttons .acm-btn-secondary {
        border: 1px solid #d6c3b2;
    }
    button#acm-mark-complete:hover {
        background: #ede7dd !important;
        color: #555a5a !important;
    }
    @media (max-width: 1200px) {        
        .acm-lesson-sidebar,
        .acm-lesson-right-sidebar {
            order: 1;
        }
        .acm-lesson-main {
            order: 2;
        }
    }
    .need-to-know-box {
        background-color: #f4eee4;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
    }

    .need-to-know-box h3 {
        margin: 0 0 12px;
        font-size: 26px;
        font-weight: 700;
        color: #4a4a4a;
        border-bottom: 2px solid #d8cfc1;
        padding-bottom: 8px;
    }

    .need-to-know-box ul {
        margin: 0;
        padding-left: 20px;
    }

    .need-to-know-box ul li {
        font-size: 16px;
        line-height: 1.6;
        color: #333;
        margin-bottom: 8px;
    }
    .acm-lesson-header-content .lesson-main-heading {
        display: flex;
        gap: 50px;
        align-items: center;
    }
    .lesson-main-heading {
        background-color: #f4eee4;
        /*border-radius: 12px;*/
        padding: 20px 24px;
        margin-bottom: 24px;
        font-family: Arial, sans-serif;
    }
    .lesson-main-heading .acm-lesson-title {
        margin: 0 0 0px;
        border-bottom: none;
        padding-top: 20px;
        font-size: 35px;
    }
    .acm-lesson-body h2 {
        font-size: 35px !important;
    }
    .lesson-main-heading .crs-heading span{
        background-color: #db9563;
        color: #fff;
        font-weight: 700;
        padding: 6px;
        padding-right: 15px;
        padding-left: 15px;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    main.acm-lesson-main {
        border-top-left-radius: 0px;
        border-top-right-radius: 0px;
    }
    .acm-lesson-main {
        padding: 0px;
    }
    .info-box {
        border: 1px solid #ede7dd;
        border-radius: 14px;
        overflow: hidden;
        /*font-family: Arial, sans-serif;*/
        background: #fff;
        margin-bottom: 25px;
    }
    .info-box-header {
        background-color: #e29a66;
        color: #ffffff;
        font-weight: 700;
        font-size: 20px;
        padding: 14px 20px;
    }
    .info-box-body {
        padding: 18px 22px;
    }
    .info-box-body ul {
        margin: 0;
        padding-left: 22px;
    }
    .info-box-body li {
        font-size: 16px;
        line-height: 1.6;
        color: #000;
        margin-bottom: 10px;
    }
    p a[href*="textonly=1"] {
        display: none !important;
    }
    .active.completed span.lesson-status {
        color: #9Fa293;
    }
</style>

<div class="acm-lesson-wrapper acm-filter-root">
    <div class="loading-overlay">
        <div class="loading"></div>
    </div>
    
    <!-- Mobile Menu Toggle -->
    <button class="acm-mobile-menu-toggle" id="acm-menu-toggle" aria-label="Toggle lesson navigation">📚</button>

    <!-- Lesson Header -->
    <div class="acm-lesson-header">
        <nav class="acm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo get_permalink('account/dashboard'); ?>">
                <?php _e('Dashboard', 'advanced-course-manager'); ?>
            </a>
            <?php if (!empty($course_id)) : ?>
                <span class="separator">/</span>
                <a href="<?php echo get_permalink($course_id); ?>">
                    <?php echo esc_html(get_the_title($course_id)); ?>
                </a>
            <?php endif; ?>
            <span class="separator">/</span>
            <a href="<?php echo get_permalink($chapter_id); ?>">
                <?php echo esc_html(get_the_title($chapter_id)); ?>
            </a>
            <span class="separator">/</span>
            <span class="current" aria-current="page"><?php the_title(); ?></span>
        </nav>

        <?php if ($partner_id): 
            $partner = get_userdata($partner_id);
        ?>
        <div class="acm-partner-badge" aria-label="Learning with partner">
            <span class="partner-icon" aria-hidden="true">👥</span>
            <span class="partner-text"><?php printf(__('Learning with %s', 'advanced-course-manager'), esc_html($partner->display_name)); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Lesson Container -->
    <div class="acm-lesson-container <?php echo $partner_id ? 'with-partner' : ''; ?>">
        
        <!-- Left Sidebar - Course Navigation -->
        <aside class="acm-lesson-sidebar" id="acm-lesson-sidebar" role="navigation" aria-label="Course navigation">
            <?php
                $chapter_lessons = get_posts(array(
                    'post_type'      => 'acm_lesson',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'meta_query'     => array(
                        array(
                            'key'   => '_acm_lesson_chapter',
                            'value' => $chapter_id,
                            'compare' => '='
                        )
                    ),
                    'orderby' => 'menu_order',
                    'order'   => 'ASC'
                ));

                $chapter_total_lessons     = count($chapter_lessons);
                $chapter_completed_lessons = 0;

                if ($chapter_total_lessons > 0) {
                    foreach ($chapter_lessons as $cl) {
                        $lp = ACM_Progress::get_instance()->get_lesson_progress($user_id, $cl->ID);
                        if ($lp && $lp->status === 'completed') {
                            $chapter_completed_lessons++;
                        }
                    }
                }
                $chapter_progress_pct = $chapter_total_lessons > 0 ? round(($chapter_completed_lessons / $chapter_total_lessons) * 100) : 0;
            ?>
            <div class="acm-course-info">
                <h3><?php echo esc_html(get_the_title($chapter_id)); ?></h3>
                <div class="acm-progress-overview">
                    <div class="acm-progress-bar" role="progressbar" aria-valuenow="<?php echo esc_attr($chapter_progress_pct); ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="acm-progress-fill" style="width: <?php echo esc_attr($chapter_progress_pct); ?>%"></div>
                    </div>
                    <span class="acm-progress-text">
                        <?php echo esc_html($chapter_progress_pct); ?>% <?php _e('Complete', 'advanced-course-manager'); ?>
                    </span>
                </div>
            </div>

            <nav class="acm-lesson-navigation">
                <h4><?php _e('Chapter Content', 'advanced-course-manager'); ?></h4>
                <ul class="acm-lesson-list" role="list">
                    <?php
                    $lesson_number = 1;
                    $current_module = '';
                    
                    foreach ($chapter_lessons as $lesson):
                        $lesson_progress = ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson->ID);
                        $is_current = ($lesson->ID == $lesson_id);
                        $is_completed = ($lesson_progress && $lesson_progress->status === 'completed');
                        $is_started = ($lesson_progress && $lesson_progress->status === 'in_progress');
                        $is_quiz_hidden = function_exists('acm_is_lesson_hidden_for_user') ? acm_is_lesson_hidden_for_user($lesson->ID, $user_id) : false;
                        $lesson_link = $is_quiz_hidden ? '#' : get_permalink($lesson->ID);
                        $lesson_hidden_class = function_exists('acm_get_hidden_class') ? acm_get_hidden_class($lesson->ID, $user_id) : '';
                        
                        // Get module
                        $modules = wp_get_post_terms($lesson->ID, 'acm_course_module');
                        $lesson_module = !empty($modules) ? $modules[0]->name : '';
                        
                        // Module header
                        if ($lesson_module && $lesson_module !== $current_module):
                            $current_module = $lesson_module;
                    ?>
                        <li class="acm-module-header">
                            <span class="module-title"><?php echo esc_html($lesson_module); ?></span>
                        </li>
                    <?php endif; ?>
                    
                    <li class="acm-lesson-item <?php echo $is_current ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_started ? 'started' : ''; ?><?php echo esc_attr($lesson_hidden_class); ?>" role="listitem" data-acm-item-id="<?php echo esc_attr($lesson->ID); ?>" data-acm-filter-key="<?php echo esc_attr(function_exists('acm_get_filter_key') ? acm_get_filter_key($lesson->ID) : ''); ?>">
                        <a href="<?php echo esc_url($lesson_link); ?>" aria-label="Go to lesson <?php echo esc_attr($lesson->post_title); ?>" <?php echo $is_quiz_hidden ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                            <span class="lesson-number"><?php echo $lesson_number; ?></span>
                            <span class="lesson-title">
                                <?php echo esc_html($lesson->post_title); ?>
                                <?php if ($is_current): ?>
                                    <span class="visually-hidden"> (<?php _e('Current lesson', 'advanced-course-manager'); ?>)</span>
                                <?php endif; ?>
                            </span>
                            <?php if ($is_completed): ?>
                                <span class="visually-hidden"><?php echo esc_html($lesson->post_title); ?></span>
                                <span class="lesson-status" aria-hidden="true">✓</span>
                            <?php elseif ($is_started): ?>
                                <span class="lesson-status started" aria-hidden="true">▶️</span>
                                <span class="visually-hidden"><?php _e('In progress', 'advanced-course-manager'); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php
                        $lesson_number++;
                    endforeach;
                    ?>
                </ul>
            </nav>
            
            <!-- Quick Actions -->
            <div class="acm-quick-actions">
                <a href="<?php echo get_permalink($chapter_id); ?>" class="acm-btn acm-btn-secondary acm-btn-block">
                    <span class="btn-text"><?php _e('Back to Chapter', 'advanced-course-manager'); ?></span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="acm-lesson-main" role="main">

            <!-- Lesson Header -->
            <header class="acm-lesson-header-content">
                <div class="lesson-main-heading">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="featured-image-cls">
                            <img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'full' ); ?>" width="100">
                        </div>
                    <?php endif; ?>
                    <div class="crs-heading">
                        <span>
                            <?php if (!empty($chapter_number)) : ?>
                                <?php echo esc_html(sprintf(__('Chapter %s:', 'advanced-course-manager'), $chapter_number)); ?>
                            <?php endif; ?>
                            <?php echo esc_html(get_the_title($chapter_id)); ?>
                        </span>
                        <div>
                            <h1 class="acm-lesson-title"><?php the_title(); ?></h1>
                        </div>
                    </div>
                </div>
            </header>

            <div class="acm-lesson-content">

                <!-- Need To Know -->
                <?php $need_to_know = get_post_meta(get_the_ID(), '_acm_need_to_know', true); ?>
                <?php if (!empty($need_to_know)) :?>
                    <div class="need-to-know-box">
                        <h3><?php _e('Need To Know', 'advanced-course-manager'); ?></h3>
                        <ul>
                            <?php foreach ($need_to_know as $point): ?>
                                <li><?php echo esc_html($point); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Video Section -->
                <?php if ($video_url): ?>
                    <section class="acm-video-section" aria-labelledby="video-section-title">
                        <div class="acm-video-container" id="acm-video-wrapper">
                            <?php if ($video_type === 'youtube'): ?>
                                <iframe id="acm-video-player" 
                                        src="<?php echo esc_attr($video_url); ?>?enablejsapi=1&rel=0&modestbranding=1" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen
                                        title="<?php echo esc_attr(get_the_title()); ?>">
                                </iframe>
                            <?php elseif ($video_type === 'vimeo'): ?>
                                <iframe id="acm-video-player" 
                                        src="https://player.vimeo.com/video/<?php echo esc_attr($video_url); ?>" 
                                        frameborder="0" 
                                        allow="autoplay; fullscreen; picture-in-picture" 
                                        allowfullscreen
                                        title="<?php echo esc_attr(get_the_title()); ?>">
                                </iframe>
                            <?php else: ?>
                                <video id="acm-video-player" controls preload="metadata">
                                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                    <?php _e('Your browser does not support the video tag.', 'advanced-course-manager'); ?>
                                </video>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Lesson Content -->
                <section class="acm-lesson-body" aria-labelledby="lesson-content-title">
                    <?php the_content(); ?>
                </section>

                <!-- Action Buttons -->
                <div class="acm-lesson-actions">
                    <?php if (!$progress || $progress->status !== 'completed'): ?>
                        <button id="acm-mark-complete" class="acm-btn acm-btn-primary" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" aria-label="Mark this lesson as complete">
                            <?php _e('Mark as Complete', 'advanced-course-manager'); ?>
                        </button>
                        
                        <?php if ($progress && $progress->status === 'in_progress'): ?>
                            <button id="acm-save-progress" class="acm-btn acm-btn-secondary" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                💾 <?php _e('Save Progress', 'advanced-course-manager'); ?>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="acm-btn acm-btn-success" disabled aria-label="This lesson is completed">
                            <span class="btn-icon" aria-hidden="true">✓</span>
                            <span class="btn-text"><?php _e('Completed', 'advanced-course-manager'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Navigation Buttons -->
                <nav class="acm-lesson-navigation-buttons" aria-label="Lesson navigation">
                    <?php if ($prev_lesson): ?>
                        <a href="<?php echo get_permalink($prev_lesson->ID); ?>" class="acm-btn acm-btn-secondary" aria-label="Go to previous lesson">
                            <span class="btn-icon" aria-hidden="true" style="color: #555a5a;">←</span>
                            <span class="btn-text"><?php _e('Previous', 'advanced-course-manager'); ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="<?php echo get_permalink($next_lesson->ID); ?>" class="acm-btn acm-btn-primary acm-next-lesson-btn" aria-label="Go to next lesson">
                            <span class="btn-text"><?php _e('Next', 'advanced-course-manager'); ?></span>
                            <span class="btn-icon" aria-hidden="true">→</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo get_permalink($course_id); ?>" class="acm-btn acm-btn-primary">
                            <span class="btn-text"><?php _e('Finish Course', 'advanced-course-manager'); ?></span>
                            <span class="btn-icon" aria-hidden="true">🎉</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </main>

        <!-- Right Sidebar - Progress & Partner Info -->
        <?php if ($partner_id): ?>
        <aside class="acm-lesson-right-sidebar" role="complementary" aria-label="Partner progress">
            <div class="acm-partner-progress">
                <h4>
                    <span class="partner-icon" aria-hidden="true">👥</span>
                    <?php _e('Partner Progress', 'advanced-course-manager'); ?>
                </h4>
                <?php
                $partner_progress = ACM_Partnerships::get_instance()->get_partner_progress($user_id, $course_id);
                ?>
                <div class="acm-partner-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($partner_progress['percentage']); ?>%</span>
                        <span class="stat-label"><?php _e('Complete', 'advanced-course-manager'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($partner_progress['completed_lessons']); ?>/<?php echo esc_html($partner_progress['total_lessons']); ?></span>
                        <span class="stat-label"><?php _e('Lessons', 'advanced-course-manager'); ?></span>
                    </div>
                </div>
                
                <!-- Partner's Recent Activity -->
                <div class="acm-partner-activity">
                    <h5><?php _e('Recent Activity', 'advanced-course-manager'); ?></h5>
                    <?php
                    $recent_activity = ACM_Partnerships::get_instance()->get_recent_partner_activity($user_id, $course_id);
                    if (!empty($recent_activity)):
                    ?>
                        <ul class="acm-activity-list">
                            <?php foreach ($recent_activity as $activity): ?>
                                <li>
                                    <span class="activity-icon">
                                        <?php echo $activity['type'] === 'completed' ? '✓' : '▶️'; ?>
                                    </span>
                                    <span class="activity-text"><?php echo esc_html($activity['text']); ?></span>
                                    <span class="activity-time"><?php echo human_time_diff(strtotime($activity['time'])); ?> <?php _e('ago', 'advanced-course-manager'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-activity"><?php _e('No recent activity', 'advanced-course-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</div>

<script>
var acmLessonData = {
    lessonId: <?php echo json_encode($lesson_id); ?>,
    courseId: <?php echo json_encode($course_id); ?>,
    partnerId: <?php echo json_encode($partner_id); ?>,
    nonce: '<?php echo wp_create_nonce('acm_nonce'); ?>',
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    userId: <?php echo json_encode($user_id); ?>,
    currentLessonIndex: <?php echo $current_index; ?>,
    totalLessons: <?php echo count($all_lessons); ?>,
    lessonSequence: <?php echo wp_json_encode($lesson_sequence); ?>,
    isCompleted: <?php echo ($progress && $progress->status === 'completed') ? 'true' : 'false'; ?>,
    isInProgress: <?php echo ($progress && $progress->status === 'in_progress') ? 'true' : 'false'; ?>
};
</script>

<script>
// Enhanced JavaScript for better UX
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Mobile menu toggle
        $('#acm-menu-toggle').on('click', function() {
            $('#acm-lesson-sidebar').toggleClass('active');
            $(this).toggleClass('active');
        });
        
        // Tab switching
        $('.acm-tab-button').on('click', function() {
            var tab = $(this).data('tab');
            
            // Update tab buttons
            $('.acm-tab-button').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            
            // Update tab content
            $('.acm-tab-content').removeClass('active');
            $('#' + tab + '-tab').addClass('active');
        });
        
        // Video player enhancements
        var videoPlayer = $('#acm-video-player');
        if (videoPlayer.length) {
            // Track video progress
            var progressInterval;
            
            if (videoPlayer[0].tagName === 'VIDEO') {
                videoPlayer.on('timeupdate', function() {
                    trackVideoProgress(this.currentTime);
                });
                
                videoPlayer.on('play', function() {
                    startProgressTracking();
                });
                
                videoPlayer.on('pause ended', function() {
                    stopProgressTracking();
                });
            }
            
            // YouTube API
            if (acmLessonData.videoType === 'youtube') {
                var tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                var firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
        }
        
        // Notes functionality
        $('#acm-save-note').on('click', function() {
            var note = $('#acm-note-input').val().trim();
            if (note) {
                saveNote(note);
            }
        });
        
        // Discussion functionality
        if (acmLessonData.partnerId) {
            $('#acm-send-message').on('click', function() {
                var message = $('#acm-message-input').val().trim();
                if (message) {
                    sendMessage(message);
                }
            });
        }
        
        // Auto-save draft notes
        var noteTimeout;
        $('#acm-note-input').on('input', function() {
            clearTimeout(noteTimeout);
            noteTimeout = setTimeout(function() {
                var note = $('#acm-note-input').val().trim();
                if (note) {
                    saveNote(note, true); // Save as draft
                }
            }, 1000);
        });
        
        // Load saved notes and messages
        loadNotes();
        if (acmLessonData.partnerId) {
            loadMessages();
        }
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + Enter to mark complete
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                $('#acm-mark-complete').click();
            }
            
            // Space to play/pause video
            if (e.key === ' ' && $(e.target).is('body')) {
                e.preventDefault();
                if (videoPlayer.length && videoPlayer[0].tagName === 'VIDEO') {
                    if (videoPlayer[0].paused) {
                        videoPlayer[0].play();
                    } else {
                        videoPlayer[0].pause();
                    }
                }
            }
            
            // Arrow keys for navigation
            if (e.key === 'ArrowLeft' && acmLessonData.prevLessonUrl) {
                window.location.href = acmLessonData.prevLessonUrl;
            } else if (e.key === 'ArrowRight' && acmLessonData.nextLessonUrl) {
                window.location.href = acmLessonData.nextLessonUrl;
            }
        });
        
        // Helper functions
        function updateCourseProgress() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_course_progress',
                    course_id: acmLessonData.courseId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var progress = response.data.percentage;
                        $('.acm-progress-fill').css('width', progress + '%');
                        $('.acm-progress-text').text(progress + '% Complete');
                    }
                }
            });
        }
        
        function trackVideoProgress(currentTime) {
            // Send progress to server periodically
            if (currentTime % 30 < 1) { // Every ~30 seconds
                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_update_video_progress',
                        lesson_id: acmLessonData.lessonId,
                        current_time: currentTime,
                        nonce: acmLessonData.nonce
                    }
                });
            }
        }
        
        function startProgressTracking() {
            if (!acmLessonData.isCompleted && !acmLessonData.isInProgress) {
                // Mark as in progress
                $.ajax({
                    url: acmLessonData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acm_start_lesson',
                        lesson_id: acmLessonData.lessonId,
                        nonce: acmLessonData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            acmLessonData.isInProgress = true;
                            // Update UI
                            $('.acm-lesson-item.active').addClass('started');
                            $('.acm-lesson-item.active .lesson-status').html('▶️');
                        }
                    }
                });
            }
        }
        
        function stopProgressTracking() {
            // Clear interval if exists
            if (progressInterval) {
                clearInterval(progressInterval);
            }
        }
        
        function saveNote(note, isDraft) {
            var shareWithPartner = $('#acm-share-note').is(':checked');
            
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_save_note',
                    lesson_id: acmLessonData.lessonId,
                    note: note,
                    share: shareWithPartner,
                    is_draft: isDraft || false,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (!isDraft) {
                            $('#acm-note-input').val('');
                            loadNotes();
                        }
                    }
                }
            });
        }
        
        function loadNotes() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_notes',
                    lesson_id: acmLessonData.lessonId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayNotes(response.data);
                    }
                }
            });
        }
        
        function displayNotes(notes) {
            var notesList = $('#acm-notes-list');
            notesList.empty();
            
            if (notes.length === 0) {
                notesList.html('<p class="no-notes"><?php _e("No notes yet. Add your first note above!", "advanced-course-manager"); ?></p>');
                return;
            }
            
            notes.forEach(function(note) {
                var noteElement = $('<div class="acm-note-item"></div>');
                noteElement.html(
                    '<div class="note-content">' + note.content + '</div>' +
                    '<div class="note-meta">' +
                    '<span class="note-time">' + note.time + '</span>' +
                    (note.shared ? '<span class="note-shared">👥 Shared</span>' : '') +
                    '</div>'
                );
                notesList.append(noteElement);
            });
        }
        
        function sendMessage(message) {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_send_message',
                    lesson_id: acmLessonData.lessonId,
                    partner_id: acmLessonData.partnerId,
                    message: message,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#acm-message-input').val('');
                        loadMessages();
                    }
                }
            });
        }
        
        function loadMessages() {
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_get_messages',
                    lesson_id: acmLessonData.lessonId,
                    partner_id: acmLessonData.partnerId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayMessages(response.data);
                    }
                }
            });
        }
        
        function displayMessages(messages) {
            var messagesList = $('#acm-messages-list');
            messagesList.empty();
            
            if (messages.length === 0) {
                messagesList.html('<p class="no-messages"><?php _e("No messages yet. Start the conversation!", "advanced-course-manager"); ?></p>');
                return;
            }
            
            messages.forEach(function(message) {
                var messageClass = message.is_sender ? 'message-sent' : 'message-received';
                var messageElement = $('<div class="acm-message-item ' + messageClass + '"></div>');
                messageElement.html(
                    '<div class="message-content">' + message.content + '</div>' +
                    '<div class="message-meta">' +
                    '<span class="message-sender">' + message.sender + '</span>' +
                    '<span class="message-time">' + message.time + '</span>' +
                    '</div>'
                );
                messagesList.append(messageElement);
            });
            
            // Scroll to bottom
            messagesList.scrollTop(messagesList[0].scrollHeight);
        }
        
        // Initialize lesson navigation URLs
        if (acmLessonData.currentLessonIndex > 0) {
            acmLessonData.prevLessonUrl = '<?php echo $prev_lesson ? get_permalink($prev_lesson->ID) : ""; ?>';
        }
        if (acmLessonData.currentLessonIndex < acmLessonData.totalLessons - 1) {
            acmLessonData.nextLessonUrl = '<?php echo $next_lesson ? get_permalink($next_lesson->ID) : ""; ?>';
        }
    });
})(jQuery);
</script>
<!-- <script>
    let overlay = document.getElementsByClassName('loading-overlay')[0]
    //overlay.addEventListener('click', e => overlay.classList.toggle('is-active'))
    document.getElementById('load-button').addEventListener('click', e => overlay.classList.toggle('is-active'))
</script> -->

<?php get_footer(); ?>