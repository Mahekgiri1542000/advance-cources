<?php
/**
 * Single Course Template - Modern UI/UX
 * File: templates/single-course.php
 */

get_header();

$user_id = get_current_user_id();
$course_id = get_the_ID();
$is_logged_in = is_user_logged_in();

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
            <p><?php _e('You need an active membership to access this course.', 'advanced-course-manager'); ?></p>
            <?php if (!$is_logged_in): ?>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="acm-btn acm-btn-primary">
                    <?php _e('Log In', 'advanced-course-manager'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo home_url('/account/?action=subscriptions'); ?>" class="acm-btn acm-btn-primary">
                    <?php _e('View Membership Plans', 'advanced-course-manager'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

// Get course data
$course = get_post($course_id);
$thumbnail = get_the_post_thumbnail_url($course_id, 'large');
$province = get_post_meta($course_id, '_acm_course_province', true);
$duration = get_post_meta($course_id, '_acm_course_duration', true);
$difficulty = get_post_meta($course_id, '_acm_course_difficulty', true);
$chapter_number = get_post_meta($course_id, '_acm_chapter_number', true);

// Get lessons
$chapter_id = get_the_ID();
$course_id  = get_post_meta($chapter_id, '_acm_chapter_course', true);

$lessons = get_posts(array(
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
// Get progress
$progress = $is_logged_in ? acm_get_course_progress($user_id, $course_id) : array(
    'total_lessons' => count($lessons),
    'completed_lessons' => 0,
    'percentage' => 0,
    'total_time' => 0
);

// Get partner
$partner_id = $is_logged_in ? acm_get_user_partner($user_id, $course_id) : null;

// Get customization settings
$customization = $is_logged_in && class_exists('ACM_Customization_Quiz') 
    ? ACM_Customization_Quiz::get_instance()->get_user_customization($user_id) 
    : array('completed' => false, 'show_all' => true);

$related_only_mode = $is_logged_in
    && isset($_GET['acm_related_only'])
    && $_GET['acm_related_only'] === '1'
    && function_exists('acm_is_quiz_completed')
    && acm_is_quiz_completed($user_id);

if ($related_only_mode && function_exists('acm_is_lesson_related_for_user')) {
    $lessons = array_values(array_filter($lessons, function($lesson) use ($user_id) {
        return acm_is_lesson_related_for_user($lesson->ID, $user_id);
    }));
}

// Get agreement choices count
$agreement_count = 0;
if ($is_logged_in && class_exists('ACM_Agreement_Builder')) {
    global $wpdb;
    $table = $wpdb->prefix . 'acm_agreement_choices';
    $agreement_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d",
        $user_id
    ));
}

// Get province name
$province_names = array(
    'ontario' => __('Ontario', 'advanced-course-manager'),
    'alberta' => __('Alberta', 'advanced-course-manager'),
    'british_columbia' => __('British Columbia', 'advanced-course-manager')
);
$province_name = $province && isset($province_names[$province]) ? $province_names[$province] : '';


$chapter_total_lessons     = count($lessons);
$chapter_completed_lessons = 0;
$chapter_total_time        = 0;
if ($is_logged_in && $chapter_total_lessons > 0) {
    foreach ($lessons as $lesson) {
        $lesson_progress = ACM_Progress::get_instance()
            ->get_lesson_progress($user_id, $lesson->ID);

        if ($lesson_progress && $lesson_progress->status === 'completed') {
            $chapter_completed_lessons++;
        }

        if ($lesson_progress && !empty($lesson_progress->time_spent)) {
            $chapter_total_time += (int) $lesson_progress->time_spent;
        }
    }
}
$chapter_progress_pct = $chapter_total_lessons > 0 ? round(($chapter_completed_lessons / $chapter_total_lessons) * 100) : 0;


$certificate = null;
$is_chapter_complete = false;

if ($is_logged_in && $chapter_total_lessons > 0) {
    $is_chapter_complete = ($chapter_completed_lessons === $chapter_total_lessons);

    if ($is_chapter_complete) {
        $acm_certificate = ACM_Certificate::get_instance();

        // IMPORTANT: pass chapter_id instead of course_id
        $certificate = $acm_certificate->get_certificate($user_id, $chapter_id);
    }
}

?>
<link rel="stylesheet" id="advance-course-lesson-css" href="<?php echo ACM_PLUGIN_URL; ?>public/css/advance-course-lesson.css?ver=<?php echo time(); ?>" media="all">
<link rel="stylesheet" id="advance-course-lesson-css" href="<?php echo ACM_PLUGIN_URL; ?>public/css/single-course.css?ver=<?php echo time(); ?>" media="all">
<style>
    p a[href*="textonly=1"] {
        display: none !important;
    }
</style>
<div class="acm-course-page acm-filter-root">
    <div class="loading-overlay">
        <div class="loadingSpinnner"></div>
    </div>
    <!-- Course Header -->
    <div class="acm-course-header" <?php if ($thumbnail): ?>style="background-image: url('<?php echo esc_url($thumbnail); ?>');" <?php else: ?> style="background: #db9563; "<?php endif; ?>>
        <div class="course-header-overlay">
            <div class="course-header-content">
                <nav class="course-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo home_url(); ?>/account/dashboard"><?php _e('Dashboard', 'advanced-course-manager'); ?></a>
                    <span class="separator">/</span>
                    <a href="<?php echo get_permalink($course_id); ?>"><?php echo esc_html(get_the_title($course_id)); ?></a>
                    <span class="separator">/</span>
                    <span class="current"><?php the_title(); ?></span>
                </nav>
                
                <h1 class="course-title"><?php the_title(); ?></h1>
                
                <?php if ($province_name): ?>
                    <div class="course-province">
                        <span class="province-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" style="width: 20px;">
                                <ellipse fill="#ffffff" cx="18" cy="34.5" rx="4" ry="1.5"/>
                                <path fill="#ffffff" d="M14.339 10.725S16.894 34.998 18.001 35c1.106.001 3.66-24.275 3.66-24.275h-7.322z"/>
                                <circle fill="#ffffff" cx="18" cy="8" r="8"/>
                            </svg>
                            <?php echo esc_html($province_name); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="course-meta">
                    <?php if ($duration): ?>
                        <div class="meta-item" style="align-items: inherit;">
                            <span class="meta-icon" aria-label="Duration"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" style="width: 20px;"><circle fill="#fff" cx="18" cy="18" r="18"></circle><circle fill="#fff" cx="18" cy="18" r="14"></circle><path fill="#db9563" d="M19 18c0 .553-.447 1-1 1-.552 0-1-.447-1-1V7c0-.552.448-1 1-1 .553 0 1 .448 1 1v11z"></path><path fill="#db9563" d="M23.25 9.237c.479.276.643.888.367 1.366l-4.5 7.795c-.276.478-.889.642-1.367.365-.478-.276-.642-.888-.365-1.365l4.5-7.795c.276-.478.887-.642 1.365-.366z"></path></svg> </span>
                            <span class="meta-text"><?php echo esc_html($duration); ?> <?php _e('hours', 'advanced-course-manager'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="meta-item" style="align-items: inherit;">
                        <span class="meta-icon" aria-label="Lesson count" style="width: 20px;"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#fff" d="M15 31c0 2.209-.791 4-3 4H5c-4 0-4-14 0-14h7c2.209 0 3 1.791 3 4v6z"></path><path fill="#fff" d="M34 33h-1V23h1c.553 0 1-.447 1-1s-.447-1-1-1H10c-4 0-4 14 0 14h24c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M34.172 33H11c-2 0-2-10 0-10h23.172c1.104 0 1.104 10 0 10z"></path><path fill="#fff" d="M11.5 25h23.35c-.135-1.175-.36-2-.678-2H11c-1.651 0-1.938 6.808-.863 9.188C9.745 29.229 10.199 25 11.5 25z"></path><path fill="#fff" d="M12 8c0 2.209-1.791 4-4 4H4C0 12 0 1 4 1h4c2.209 0 4 1.791 4 4v3z"></path><path fill="#fff" d="M31 10h-1V3h1c.553 0 1-.447 1-1s-.447-1-1-1H7C3 1 3 12 7 12h24c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M31.172 10H8c-2 0-2-7 0-7h23.172c1.104 0 1.104 7 0 7z"></path><path fill="#fff" d="M8 5h23.925c-.114-1.125-.364-2-.753-2H8C6.807 3 6.331 5.489 6.562 7.5 6.718 6.142 7.193 5 8 5z"></path><path fill="#fff" d="M20 17c0 2.209-1.791 4-4 4H6c-4 0-4-9 0-9h10c2.209 0 4 1.791 4 4v1z"></path><path fill="#fff" d="M35 19h-1v-5h1c.553 0 1-.447 1-1s-.447-1-1-1H15c-4 0-4 9 0 9h20c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M35.172 19H16c-2 0-2-5 0-5h19.172c1.104 0 1.104 5 0 5z"></path><path fill="#fff" d="M16 16h19.984c-.065-1.062-.334-2-.812-2H16c-1.274 0-1.733 2.027-1.383 3.5.198-.839.657-1.5 1.383-1.5z"></path></svg> </span>
                        <span class="meta-text"><?php _e('Chapter', 'advanced-course-manager'); ?> <?php echo $chapter_number; ?></span>
                    </div>

                    <div class="meta-item" style="align-items: inherit;">
                        <span class="meta-icon" aria-label="Lesson count" style="width: 20px;"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#fff" d="M15 31c0 2.209-.791 4-3 4H5c-4 0-4-14 0-14h7c2.209 0 3 1.791 3 4v6z"></path><path fill="#fff" d="M34 33h-1V23h1c.553 0 1-.447 1-1s-.447-1-1-1H10c-4 0-4 14 0 14h24c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M34.172 33H11c-2 0-2-10 0-10h23.172c1.104 0 1.104 10 0 10z"></path><path fill="#fff" d="M11.5 25h23.35c-.135-1.175-.36-2-.678-2H11c-1.651 0-1.938 6.808-.863 9.188C9.745 29.229 10.199 25 11.5 25z"></path><path fill="#fff" d="M12 8c0 2.209-1.791 4-4 4H4C0 12 0 1 4 1h4c2.209 0 4 1.791 4 4v3z"></path><path fill="#fff" d="M31 10h-1V3h1c.553 0 1-.447 1-1s-.447-1-1-1H7C3 1 3 12 7 12h24c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M31.172 10H8c-2 0-2-7 0-7h23.172c1.104 0 1.104 7 0 7z"></path><path fill="#fff" d="M8 5h23.925c-.114-1.125-.364-2-.753-2H8C6.807 3 6.331 5.489 6.562 7.5 6.718 6.142 7.193 5 8 5z"></path><path fill="#fff" d="M20 17c0 2.209-1.791 4-4 4H6c-4 0-4-9 0-9h10c2.209 0 4 1.791 4 4v1z"></path><path fill="#fff" d="M35 19h-1v-5h1c.553 0 1-.447 1-1s-.447-1-1-1H15c-4 0-4 9 0 9h20c.553 0 1-.447 1-1s-.447-1-1-1z"></path><path fill="#fff" d="M35.172 19H16c-2 0-2-5 0-5h19.172c1.104 0 1.104 5 0 5z"></path><path fill="#fff" d="M16 16h19.984c-.065-1.062-.334-2-.812-2H16c-1.274 0-1.733 2.027-1.383 3.5.198-.839.657-1.5 1.383-1.5z"></path></svg> </span>
                        <span class="meta-text"><?php echo count($lessons); ?> <?php _e('Lessons', 'advanced-course-manager'); ?></span>
                    </div>
                </div>
                
                <?php if ($is_logged_in && $chapter_total_lessons > 0): ?>
                    <div class="course-progress-header" role="progressbar" aria-valuenow="<?php echo esc_html($chapter_progress_pct); ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-info">
                            <span class="progress-percentage"><?php echo esc_html($chapter_progress_pct); ?>% <?php _e('Complete', 'advanced-course-manager'); ?></span>
                            <span class="progress-lessons"><?php echo esc_html($chapter_completed_lessons); ?> / <?php echo esc_html($chapter_total_lessons); ?> <?php _e('Lessons', 'advanced-course-manager'); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_html($chapter_progress_pct); ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="acm-course-container">
        <!-- Main Column -->
        <main class="acm-course-main" role="main">
            
            <!-- Action Buttons -->
            <?php if ($is_logged_in): ?>
                <?php if ($agreement_count > 0): ?>
                    <div class="course-actions">
                        <a href="<?php echo get_permalink(get_option('acm_agreement_builder')); ?>" class="acm-btn acm-btn-secondary" aria-label="View my agreement">
                            <span class="btn-icon">📋</span>
                            <span class="btn-text"><?php _e('My Agreement', 'advanced-course-manager'); ?></span>
                            <span class="btn-badge"><?php echo $agreement_count; ?></span>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($customization['completed']): ?>
                    <div class="course-actions">
                        <a href="<?php echo get_permalink(get_option('acm_customization_quiz')); ?>" class="acm-btn acm-btn-secondary" aria-label="Customize course">
                            <span class="btn-icon">⚙️</span>
                            <span class="btn-text"><?php _e('Customize Course', 'advanced-course-manager'); ?></span>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($partner_id): 
                    $partner = get_userdata($partner_id);
                ?>
                    <div class="course-actions">
                        <span class="partner-indicator" aria-label="Learning with partner">
                            <span class="partner-icon">👥</span>
                            <span class="partner-text"><?php printf(__('Learning with %s', 'advanced-course-manager'), esc_html($partner->display_name)); ?></span>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($is_logged_in && $customization['completed'] && !$customization['show_all']): ?>
            <div class="course-customization-notice" role="alert">
                <div class="notice-icon" aria-hidden="true">ℹ️</div>
                <div class="notice-content">
                    <strong><?php _e('Customized View', 'advanced-course-manager'); ?></strong>
                    <p><?php _e('This course has been customized based on your preferences. Some sections are hidden.', 'advanced-course-manager'); ?></p>
                    <a href="<?php echo get_permalink(get_option('acm_customization_quiz')); ?>" aria-label="View all course sections">
                        <?php _e('View all sections', 'advanced-course-manager'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
                <?php if (function_exists('acm_get_personalization_prompt_html')): ?>
                    <?php echo acm_get_personalization_prompt_html($user_id); ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (has_excerpt()): ?>
                <section class="course-description" aria-labelledby="course-description-title">
                    <h2>Summary</h2>
                    <?php echo get_the_excerpt(); ?>
                </section>
            <?php endif; ?>
            
            <?php
            $chapter_content = apply_filters('the_content', get_the_content());
            if (trim(wp_strip_all_tags($chapter_content)) !== ''):
            ?>
                <section class="course-description" aria-labelledby="course-description-title">
                    <?php echo $chapter_content; ?>
                </section>
            <?php endif; ?>
            
            <style>
                a.acm-btn.acm-btn-primary.acm-btn-block {
                    color: #fff !important;
                }
                a.acm-btn.acm-btn-small.acm-btn-primary {
                    color: #fff !important;
                }
            </style>

            <section class="course-curriculum" aria-labelledby="course-curriculum-title">
                <header>
                    <h2 id="course-curriculum-title"><?php _e('Lessons', 'advanced-course-manager'); ?></h2>
                    <?php if ($is_logged_in): ?>
                        <p class="curriculum-progress">
                            <?php echo esc_html($chapter_completed_lessons); ?> of <?php echo esc_html($chapter_total_lessons); ?> <?php _e('lessons completed', 'advanced-course-manager'); ?>
                        </p>
                    <?php endif; ?>
                </header>
                
                <?php if (!empty($lessons)): ?>
                    <div class="lessons-list" role="list">
                        <?php 
                        $lesson_number = 1;
                        $current_module = '';
                        $module_count = 0;
                        
                        foreach ($lessons as $lesson):
                            $lesson_progress = $is_logged_in ? ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson->ID) : null;
                            $is_completed = $lesson_progress && $lesson_progress->status === 'completed';
                            $is_started = $lesson_progress && $lesson_progress->status === 'in_progress';
                            $lesson_duration = get_post_meta($lesson->ID, '_acm_lesson_duration', true);
                            $lesson_hidden_class = function_exists('acm_get_hidden_class') ? acm_get_hidden_class($lesson->ID, $user_id) : '';
                            $lesson_link = get_permalink($lesson->ID);
                            if ($related_only_mode) {
                                $lesson_link = add_query_arg('acm_related_only', '1', $lesson_link);
                            }
                            
                            $modules = wp_get_post_terms($lesson->ID, 'acm_course_module');
                            $lesson_module = !empty($modules) ? $modules[0]->name : '';
                            
                            if ($lesson_module && $lesson_module !== $current_module):
                                if ($current_module !== '') {
                                    echo '</div>'; // Close previous module
                                }
                                $current_module = $lesson_module;
                                $module_count++;
                        ?>
                            <div class="lesson-module" role="listitem">
                                <h3 class="module-title">
                                    <span class="module-number"><?php echo $module_count; ?>.</span>
                                    <?php echo esc_html($lesson_module); ?>
                                </h3>
                        <?php endif; ?>
                        
                        <article class="lesson-item <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_started ? 'started' : ''; ?><?php echo esc_attr($lesson_hidden_class); ?>" role="listitem" data-acm-item-id="<?php echo esc_attr($lesson->ID); ?>" data-acm-filter-key="<?php echo esc_attr(function_exists('acm_get_filter_key') ? acm_get_filter_key($lesson->ID) : ''); ?>">
                            <div class="lesson-number" aria-label="Lesson <?php echo $lesson_number; ?>">
                                <?php echo $lesson_number; ?>
                            </div>
                            
                            <div class="lesson-content">
                                <header>
                                    <h4 class="lesson-title">
                                        <a href="<?php echo esc_url($lesson_link); ?>" aria-label="Go to lesson <?php echo esc_attr($lesson->post_title); ?>">
                                            <?php echo esc_html($lesson->post_title); ?>
                                        </a>
                                    </h4>
                                </header>
                                
                                <?php if ($lesson->post_excerpt): ?>
                                    <div class="lesson-excerpt">
                                        <?php echo esc_html($lesson->post_excerpt); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <footer class="lesson-meta-info">
                                
                                    
                                    <?php if ($lesson_duration): ?>
                                        <span class="lesson-duration">
                                            <span class="duration-icon" aria-hidden="true">🕐</span>
                                            <span class="duration-text"><?php echo esc_html($lesson_duration); ?> min</span>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_completed): ?>
                                        <span class="lesson-status completed">
                                            <span class="status-icon" aria-hidden="true">✓</span>
                                            <span class="status-text"><?php _e('Completed', 'advanced-course-manager'); ?></span>
                                        </span>
                                    <?php elseif ($is_started): ?>
                                        <span class="lesson-status started">
                                            <span class="status-icon" aria-hidden="true">▶️</span>
                                            <span class="status-text"><?php _e('In progress', 'advanced-course-manager'); ?></span>
                                        </span>
                                    <?php elseif ($is_logged_in): ?>
                                        <span class="lesson-status not-started">
                                            <span class="status-text"><?php _e('Not started', 'advanced-course-manager'); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </footer>
                            </div>
                            
                            <div class="lesson-action">
                                <?php if ($is_completed): ?>
                                    <a href="<?php echo esc_url($lesson_link); ?>" class="acm-btn acm-btn-small acm-btn-secondary" aria-label="Review lesson <?php echo esc_attr($lesson->post_title); ?>">
                                        <?php _e('Review', 'advanced-course-manager'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($lesson_link); ?>" class="acm-btn acm-btn-small acm-btn-primary" aria-label="Start lesson <?php echo esc_attr($lesson->post_title); ?>">
                                        <?php echo $is_started ? __('Continue', 'advanced-course-manager') : __('Start', 'advanced-course-manager'); ?>
                                        <span class="btn-arrow" aria-hidden="true">→</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                        
                        <?php 
                            $lesson_number++;
                        endforeach; 
                        if ($current_module !== '') {
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <p class="no-lessons"><?php _e('No lessons available yet.', 'advanced-course-manager'); ?></p>
                <?php endif; ?>
            </section>
            
        </main>
        
        <aside class="acm-course-sidebar" role="complementary" aria-label="Course sidebar">
            
            <div class="sidebar-card course-start-card">
                <?php if ($is_logged_in): ?>
                    <?php if ($progress['percentage'] > 0 && $progress['percentage'] < 100): ?>
                        <h3><?php _e('Continue Learning', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Pick up where you left off', 'advanced-course-manager'); ?></p>
                        <?php
                        $next_lesson = null;
                        foreach ($lessons as $lesson) {
                            $lesson_progress = ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson->ID);
                            if (!$lesson_progress || $lesson_progress->status !== 'completed') {
                                $next_lesson = $lesson;
                                break;
                            }
                        }
                        $next_lesson_link = $next_lesson ? get_permalink($next_lesson->ID) : '';
                        if ($related_only_mode && $next_lesson_link) {
                            $next_lesson_link = add_query_arg('acm_related_only', '1', $next_lesson_link);
                        }
                        ?>
                        <?php if ($next_lesson): ?>
                            <a href="<?php echo esc_url($next_lesson_link); ?>" class="acm-btn acm-btn-primary acm-btn-block" aria-label="Continue with next lesson">
                                <span class="btn-text"><?php _e('Continue', 'advanced-course-manager'); ?></span>
                                <span class="btn-arrow" aria-hidden="true">→</span>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($progress['percentage'] >= 100): ?>
                        <h3><?php _e('Chapter Completed!', 'advanced-course-manager'); ?></h3>
                        <p><?php _e('Congratulations on completing this Chapter!', 'advanced-course-manager'); ?></p>
                        <?php if ($agreement_count > 0): ?>
                            <a href="<?php echo get_permalink(get_option('acm_agreement_builder')); ?>" class="acm-btn acm-btn-primary acm-btn-block" aria-label="View your agreement">
                                <?php _e('View Your Agreement', 'advanced-course-manager'); ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <h3><?php _e('Start Learning', 'advanced-course-manager'); ?></h3>
                        <p><?php echo count($lessons); ?> <?php _e('lessons to complete', 'advanced-course-manager'); ?></p>
                        <?php if (!empty($lessons)): ?>
                            <?php
                                $first_lesson_link = get_permalink($lessons[0]->ID);
                                if ($related_only_mode) {
                                    $first_lesson_link = add_query_arg('acm_related_only', '1', $first_lesson_link);
                                }
                            ?>
                            <a href="<?php echo esc_url($first_lesson_link); ?>" class="acm-btn acm-btn-primary acm-btn-block" aria-label="Start the course">
                                <span class="btn-text"><?php _e('Start Chapter', 'advanced-course-manager'); ?></span>
                                <span class="btn-arrow" aria-hidden="true">→</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <h3><?php _e('Enroll Now', 'advanced-course-manager'); ?></h3>
                    <p><?php _e('Log in to start this chapter and track your progress', 'advanced-course-manager'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="acm-btn acm-btn-primary acm-btn-block" aria-label="Log in to access the course">
                        <?php _e('Log In', 'advanced-course-manager'); ?>
                    </a>
                <?php endif; ?>
            </div>

            
            
            <?php if ($is_logged_in && $chapter_total_lessons > 0): ?>
            <div class="sidebar-card progress-stats" aria-label="Your progress statistics">
                <h3><?php _e('Your Progress', 'advanced-course-manager'); ?></h3>
                
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Completion', 'advanced-course-manager'); ?></div>
                    <div class="stat-value"><?php echo esc_html($chapter_progress_pct); ?>%</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Lessons Completed', 'advanced-course-manager'); ?></div>
                    <div class="stat-value"><?php echo esc_html($chapter_completed_lessons); ?> / <?php echo esc_html($chapter_total_lessons); ?></div>
                </div>

                <?php if ($progress['total_time'] > 0): ?>
                <div class="stat-item">
                    <div class="stat-label"><?php _e('Time Spent', 'advanced-course-manager'); ?></div>
                    <div class="stat-value"><?php echo esc_html(round($chapter_total_time / 60)); ?> min</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Certificate of Completion -->
            <?php /*
            <?php if ($is_logged_in && $is_chapter_complete): ?>
                <div class="sidebar-card certificate-card">
                    <div class="certificate-icon">🎓</div>
                    <h3><?php _e('Certificate of Completion', 'advanced-course-manager'); ?></h3>
                    
                    <?php if ($certificate): ?>
                        <p class="certificate-message">
                            <?php _e('Congratulations! You have completed this Chapter.', 'advanced-course-manager'); ?>
                        </p>
                        
                        <div class="certificate-info">
                            <div class="cert-code">
                                <span class="label"><?php _e('Certificate Code:', 'advanced-course-manager'); ?></span>
                                <span class="value"><?php echo esc_html($certificate->certificate_code); ?></span>
                            </div>
                            <div class="cert-date">
                                <span class="label"><?php _e('Issued:', 'advanced-course-manager'); ?></span>
                                <span class="value"><?php echo date('M d, Y', strtotime($certificate->issued_date)); ?></span>
                            </div>
                        </div>
                        
                        <div class="certificate-actions">
                            <button id="acm-view-certificate" 
                                    class="acm-btn acm-btn-secondary acm-btn-block" 
                                    data-certificate-id="<?php echo esc_attr($certificate->id); ?>"
                                    aria-label="View certificate">
                                <span class="btn-icon">👁️</span>
                                <span class="btn-text"><?php _e('View Certificate', 'advanced-course-manager'); ?></span>
                            </button>
                            
                            <button id="acm-download-certificate" 
                                    class="acm-btn acm-btn-secondary acm-btn-block" 
                                    data-certificate-id="<?php echo esc_attr($certificate->id); ?>"
                                    aria-label="Download certificate PDF">
                                <span class="btn-icon">📥</span>
                                <span class="btn-text"><?php _e('Download PDF', 'advanced-course-manager'); ?></span>
                            </button>
                            
                            <?php if (!$certificate->email_sent): ?>
                            <button id="acm-email-certificate" 
                                    class="acm-btn acm-btn-secondary acm-btn-block" 
                                    data-certificate-id="<?php echo esc_attr($certificate->id); ?>"
                                    aria-label="Email certificate">
                                <span class="btn-icon">📧</span>
                                <span class="btn-text"><?php _e('Email Certificate', 'advanced-course-manager'); ?></span>
                            </button>
                            <?php else: ?>
                            <div class="email-sent-notice">
                                <span class="notice-icon">✓</span>
                                <span class="notice-text"><?php _e('Certificate emailed', 'advanced-course-manager'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p><?php _e('Generating your certificate...', 'advanced-course-manager'); ?></p>
                        <button id="acm-generate-certificate" 
                                class="acm-btn acm-btn-primary acm-btn-block" 
                                data-course-id="<?php echo esc_attr($chapter_id); ?>"
                                aria-label="Generate certificate">
                            <?php _e('Generate Certificate', 'advanced-course-manager'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Certificate Modal -->
                <div id="acm-certificate-modal" class="acm-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="certificate-modal-title">
                    <div class="acm-modal-overlay"></div>
                    <div class="acm-modal-content certificate-modal-content">
                        <button class="acm-modal-close" aria-label="Close modal">&times;</button>
                        <h2 id="certificate-modal-title"><?php _e('Your Certificate', 'advanced-course-manager'); ?></h2>
                        <div class="certificate-preview" id="certificate-preview-container">
                            <!-- Certificate preview will be loaded here -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            */ ?>

            <div class="sidebar-card quick-links">
                <?php $acm_property_disclosure_worksheet_link = get_option('acm_property_disclosure_worksheet_link'); 
                      $acm_agreement_builder_worksheet_link = get_option('acm_agreement_builder_worksheet_link'); 
                ?>
                <h3><?php _e('Worksheets', 'advanced-course-manager'); ?></h3>
                <ul class="quick-links-list">
                    <li>
                        <?php if(!empty($acm_property_disclosure_worksheet_link)) { ?>
                            <a href="<?php echo $acm_property_disclosure_worksheet_link; ?>" target="_blank">
                                <span class="link-text"><?php _e('Property Disclosure Worksheet', 'advanced-course-manager'); ?></span>
                            </a>
                        <?php } else { ?>
                            <a href="javascript:void(0)">
                                <span class="link-text"><?php _e('Property Disclosure Worksheet', 'advanced-course-manager'); ?></span>
                            </a>
                        <?php } ?>
                    </li>
                    <li>
                        <?php if(!empty($acm_agreement_builder_worksheet_link)) { ?>
                            <a href="<?php echo $acm_agreement_builder_worksheet_link; ?>" target="_blank">
                                <span class="link-text"><?php _e('Agreement Builder Worksheet', 'advanced-course-manager'); ?></span>
                            </a>
                        <?php } else { ?>
                            <a href="javascript:void(0)">
                                <span class="link-text"><?php _e('Agreement Builder Worksheet', 'advanced-course-manager'); ?></span>
                            </a>
                        <?php } ?>
                    </li>
                    <?php if ($partner_id): ?>
                    <li>
                        <a href="<?php echo get_permalink(get_option('acm_partnerships')); ?>">
                            <span class="link-icon">👥</span>
                            <span class="link-text"><?php _e('Partner Portal', 'advanced-course-manager'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
        </aside>
    </div>
</div>
<?php 
    function acm_get_completed_courses_count_by_province($user_id, $province) {
        if (!$user_id || !$province) {
            return 0;
        }
        
        // Get all courses for the specified province
        $args = array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_acm_course_province',
                    'value'   => $province,
                    'compare' => '='
                )
            )
        );
        
        $courses_query = new WP_Query($args);
        $completed_count = 0;
        
        if ($courses_query->have_posts()) {
            $acm_certificate = ACM_Certificate::get_instance();
            
            while ($courses_query->have_posts()) {
                $courses_query->the_post();
                $course_id = get_the_ID();
                
                // Check if the course is completed for this user
                if ($acm_certificate->is_course_completed($user_id, $course_id)) {
                    $completed_count++;
                }
            }
            
            wp_reset_postdata();
        }
        
        return $completed_count;
    }

    function acm_get_completed_courses_count_by_province_alt($user_id, $province) {
        if (!$user_id || !$province) {
            return 0;
        }
        
        // Get all courses for the specified province
        $args = array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_acm_course_province',
                    'value'   => $province,
                    'compare' => '='
                )
            )
        );
        
        $courses_query = new WP_Query($args);
        $completed_count = 0;
        
        if ($courses_query->have_posts()) {
            while ($courses_query->have_posts()) {
                $courses_query->the_post();
                $course_id = get_the_ID();
                
                // Get all lessons for this course
                $lessons = ACM_Progress::get_instance()->get_course_lessons($course_id);
                
                if (empty($lessons)) {
                    continue; // Skip courses with no lessons
                }
                
                $all_completed = true;
                
                // Check if all lessons are completed
                foreach ($lessons as $lesson) {
                    $lesson_progress = ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson->ID);
                    
                    if (!$lesson_progress || $lesson_progress->status !== 'completed') {
                        $all_completed = false;
                        break;
                    }
                }
                
                if ($all_completed) {
                    $completed_count++;
                }
            }
            
            wp_reset_postdata();
        }
        
        return $completed_count;
    }

    function acm_get_province_completion_stats($user_id, $province) {
        if (!$user_id || !$province) {
            return array(
                'total_courses' => 0,
                'completed_courses' => 0,
                'in_progress_courses' => 0,
                'not_started_courses' => 0,
                'completion_percentage' => 0
            );
        }
        
        // Get all courses for the specified province
        $args = array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_acm_course_province',
                    'value'   => $province,
                    'compare' => '='
                )
            )
        );
        
        $courses_query = new WP_Query($args);
        
        $stats = array(
            'total_courses' => 0,
            'completed_courses' => 0,
            'in_progress_courses' => 0,
            'not_started_courses' => 0,
            'completion_percentage' => 0
        );
        
        if ($courses_query->have_posts()) {
            $stats['total_courses'] = $courses_query->post_count;
            $acm_certificate = ACM_Certificate::get_instance();
            
            while ($courses_query->have_posts()) {
                $courses_query->the_post();
                $course_id = get_the_ID();
                
                // Check if course is completed
                if ($acm_certificate->is_course_completed($user_id, $course_id)) {
                    $stats['completed_courses']++;
                } else {
                    // Check if course is in progress
                    $progress = acm_get_course_progress($user_id, $course_id);
                    
                    if ($progress && $progress['percentage'] > 0) {
                        $stats['in_progress_courses']++;
                    } else {
                        $stats['not_started_courses']++;
                    }
                }
            }
            
            wp_reset_postdata();
            
            // Calculate completion percentage
            if ($stats['total_courses'] > 0) {
                $stats['completion_percentage'] = round(
                    ($stats['completed_courses'] / $stats['total_courses']) * 100
                );
            }
        }
        
        return $stats;
    }
?>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Generate Certificate
        $('#acm-generate-certificate').on('click', function() {
            var button = $(this);
            var courseId = button.data('course-id');
            
            button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_generate_certificate',
                    course_id: courseId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e("Failed to generate certificate", "advanced-course-manager"); ?>');
                        button.removeClass('loading').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('<?php _e("An error occurred. Please try again.", "advanced-course-manager"); ?>');
                    button.removeClass('loading').prop('disabled', false);
                }
            });
        });
        
        // View Certificate
        $('#acm-view-certificate').on('click', function() {
            var certificateId = $(this).data('certificate-id');
            var button = $(this);
            
            button.addClass('loading');
            $('.loading-overlay').css('display', 'flex');
            
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_download_certificate',
                    certificate_id: certificateId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    button.removeClass('loading');
                    $('.loading-overlay').css('display', 'none');

                    if (response.success && response.data && response.data.download_url) {
                        var modal = $('#acm-certificate-modal');
                        var preview = $('#certificate-preview-container');
                        
                        preview.html('<iframe src="' + response.data.download_url + '"></iframe>');
                        modal.fadeIn(300);
                    } else {
                        alert((response.data && response.data.message) || '<?php _e("Failed to load certificate", "advanced-course-manager"); ?>');
                    }
                },
                error: function() {
                    button.removeClass('loading');
                    alert('<?php _e("An error occurred. Please try again.", "advanced-course-manager"); ?>');
                }
            });
        });
        
        // Download Certificate
        $('#acm-download-certificate').on('click', function() {
            var certificateId = $(this).data('certificate-id');
            var button = $(this);
            
            button.addClass('loading');
            $('.loading-overlay').css('display', 'flex');
            
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_download_certificate',
                    certificate_id: certificateId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    button.removeClass('loading');
                    $('.loading-overlay').css('display', 'none');
                    
                    if (response.success && response.data && response.data.download_url) {
                        // Create temporary link and trigger download
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = 'certificate.pdf';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        // Show success message
                        showNotification('<?php _e("Certificate downloaded successfully!", "advanced-course-manager"); ?>', 'success');
                    } else {
                        alert((response.data && response.data.message) || '<?php _e("Failed to download certificate", "advanced-course-manager"); ?>');
                    }
                },
                error: function() {
                    button.removeClass('loading');
                    alert('<?php _e("An error occurred. Please try again.", "advanced-course-manager"); ?>');
                }
            });
        });
        
        // Email Certificate
        $('#acm-email-certificate').on('click', function() {
            var certificateId = $(this).data('certificate-id');
            var button = $(this);
            
            if (!confirm('<?php _e("Send certificate to your registered email?", "advanced-course-manager"); ?>')) {
                return;
            }
            
            button.addClass('loading').prop('disabled', true);
            $('.loading-overlay').css('display', 'flex');
            
            $.ajax({
                url: acmLessonData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acm_email_certificate',
                    certificate_id: certificateId,
                    nonce: acmLessonData.nonce
                },
                success: function(response) {
                    button.removeClass('loading').prop('disabled', false);
                    $('.loading-overlay').css('display', 'none');
                    
                    if (response.success) {
                        button.replaceWith(
                            '<div class="email-sent-notice">' +
                            '<span class="notice-icon">✓</span>' +
                            '<span class="notice-text"><?php _e("Certificate emailed", "advanced-course-manager"); ?></span>' +
                            '</div>'
                        );
                        showNotification(response.data.message, 'success');
                    } else {
                        alert((response.data && response.data.message) || '<?php _e("Failed to send email", "advanced-course-manager"); ?>');
                    }
                },
                error: function() {
                    button.removeClass('loading').prop('disabled', false);
                    alert('<?php _e("An error occurred. Please try again.", "advanced-course-manager"); ?>');
                }
            });
        });
        
        // Close Modal
        $('.acm-modal-close, .acm-modal-overlay').on('click', function() {
            $('#acm-certificate-modal').fadeOut(300);
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            var notification = $('<div class="acm-notification acm-notification-' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    });
})(jQuery);
</script>

<?php get_footer(); ?>
