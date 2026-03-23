<?php
/**
 * Archive Course Template - Enhanced with Province Filter
 * File: templates/archive-course.php
 */

get_header();

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

$selected_province = isset($_GET['province']) ? sanitize_text_field($_GET['province']) : '';
$related_only_mode = $is_logged_in
    && isset($_GET['acm_related_only'])
    && $_GET['acm_related_only'] === '1'
    && function_exists('acm_is_quiz_completed')
    && acm_is_quiz_completed($user_id);
if ($is_logged_in) {
    $selected_province = acm_get_user_province($user_id);
}

$all_provinces = array(
    '' => __('All Provinces', 'advanced-course-manager'),
    'ontario' => __('Ontario', 'advanced-course-manager'),
    'alberta' => __('Alberta', 'advanced-course-manager'),
    'british_columbia' => __('British Columbia', 'advanced-course-manager')
);

$related_course_ids = array();
if ($related_only_mode) {
    $all_course_ids = get_posts(array(
        'post_type'      => 'acm_course',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids'
    ));

    foreach ($all_course_ids as $course_id) {
        if (function_exists('acm_is_course_related_for_user') && acm_is_course_related_for_user($course_id, $user_id)) {
            $related_course_ids[] = $course_id;
        }
    }
}

$provinces = $all_provinces;
if ($related_only_mode) {
    $available_provinces = array();
    foreach ($related_course_ids as $related_course_id) {
        $course_province = get_post_meta($related_course_id, '_acm_course_province', true);
        if (!empty($course_province)) {
            $available_provinces[$course_province] = true;
        }
    }

    $provinces = array('' => $all_provinces['']);
    foreach ($all_provinces as $province_key => $province_label) {
        if ($province_key === '') {
            continue;
        }
        if (isset($available_provinces[$province_key])) {
            $provinces[$province_key] = $province_label;
        }
    }

    if ($selected_province && !isset($provinces[$selected_province])) {
        $selected_province = '';
    }
}
?>
<style>
    .empty-state .btn-primary {
        background-color: #db9563;
        color: white !important;
        width: 250px;
        margin: 0 auto;
    }
</style>

<div class="acm-courses-archive">
    
    <!-- Hero Header -->
    <div class="archive-hero">
        <div class="hero-background">
            <div class="hero-pattern"></div>
        </div>
        <div class="hero-content">
            <span class="hero-badge">📚 E-Learning Platform</span>
            <h1 class="hero-title"><?php _e('Relationship Agreement Cours', 'advanced-course-manager'); ?></h1>
            <p class="hero-subtitle">
                <?php _e('Province-specific guidance to help you create a comprehensive relationship agreement', 'advanced-course-manager'); ?>
            </p>
            
            <!-- Province Filter -->
            <div class="province-filter-card">
                <div class="filter-content">
                    <label for="province-select"><?php _e('Select Your Province:', 'advanced-course-manager'); ?></label>
                    <select id="province-select" class="province-dropdown">
                        <?php foreach ($provinces as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_province, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Bar -->
    <?php
        $total_courses = wp_count_posts('acm_course')->publish;
        $total_lessons = wp_count_posts('acm_lesson')->publish;
    ?>
    <!-- Courses Section -->
    <div class="courses-section">
        <div class="section-container">
            
            <!-- Loading State -->
            <div id="loading-state" class="loading-state" style="display: none;">
                <div class="loader"></div>
                <p><?php _e('Loading courses...', 'advanced-course-manager'); ?></p>
            </div>
            
            <!-- Courses Grid -->
            <div id="courses-grid-container">
                <?php

                $args = array(
                    'post_type'      => 'acm_course',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'ASC'
                );

                if (!empty($selected_province)) {
                    $args['meta_query'] = array(
                        'relation' => 'AND',
                        array(
                            'key'     => '_acm_course_province',
                            'value'   => $selected_province,
                            'compare' => '='
                        )
                    );
                }

                if ($related_only_mode) {
                    $args['post__in'] = !empty($related_course_ids) ? $related_course_ids : array(0);
                }

                $courses_query = new WP_Query($args);
                if ($courses_query->have_posts()):
                ?>
                
                <div class="courses-grid">
                    <?php while ($courses_query->have_posts()): $courses_query->the_post(); 
                        $course_id = get_the_ID();
                        $thumbnail = get_the_post_thumbnail_url($course_id, 'large');
                        $province = get_post_meta($course_id, '_acm_course_province', true);
                        $difficulty = get_post_meta($course_id, '_acm_course_difficulty', true);
                        $chapter_number = get_post_meta($course_id, '_acm_chapter_number', true);
                        
                        $progress = null;
                        $has_access = true;
                        
                        if ($is_logged_in) {
                            $progress = acm_get_course_progress($user_id, $course_id);
                            $has_access = acm_has_course_access($user_id, $course_id);
                        }
                        
                        $province_labels = array(
                            'ontario' => __('Ontario', 'advanced-course-manager'),
                            'alberta' => __('Alberta', 'advanced-course-manager'),
                            'british_columbia' => __('British Columbia', 'advanced-course-manager')
                        );
                        $province_name = $province && isset($province_labels[$province]) ? $province_labels[$province] : '';
                        $course_link = get_permalink();
                        if ($related_only_mode) {
                            $course_link = add_query_arg('acm_related_only', '1', $course_link);
                        }
                    ?>
                    
                    <div class="course-card-modern <?php echo !$has_access ? 'locked' : ''; ?>">
                        
                        <!-- Course Image -->
                        <div class="course-image">
                            <?php if ($thumbnail): ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>">
                            <?php else: ?>
                                <div class="image-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$has_access): ?>
                                <div class="lock-badge">🔒</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Course Info -->
                        <div class="course-info">
                            <h3 class="course-title">
                                <a href="<?php echo esc_url($course_link); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <?php if (has_excerpt()): ?>
                                <p class="course-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                            <?php endif; ?>
                            
                            <!-- Course Meta -->
                            <div class="course-meta-grid">

                                <?php 
                                    $chapters = get_posts(array(
                                        'post_type'      => 'acm_chapter',
                                        'posts_per_page' => -1,
                                        'post_status'    => 'publish',
                                        'fields'         => 'ids',
                                        'meta_query'     => array(
                                            array(
                                                'key'   => '_acm_chapter_course',
                                                'value' => $course_id,
                                                'compare' => '='
                                            )
                                        )
                                    ));
                                    $chapter_count = count($chapters);
                                ?>

                                <?php if ($chapter_count): ?>
                                    <div class="meta-badge">
                                        <span>Chapter <?php echo esc_html($chapter_count); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="meta-badge">
                                        <span>Chapter 0</span>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                    $lesson_count      = 0;
                                    $total_lesson_time = 0; // minutes

                                    if (!empty($chapters)) {

                                        // Get lesson IDs only (fast)
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
                                        foreach ($lesson_ids as $lesson_id) {
                                            $raw_duration = get_post_meta($lesson_id, '_acm_lesson_duration', true);
                                            if (empty($raw_duration)) {
                                                continue;
                                            }

                                            if (is_string($raw_duration)) {
                                                preg_match('/\d+/', $raw_duration, $m);
                                                $raw_duration = $m[0] ?? 0;
                                            }
                                            $duration = (int) $raw_duration;
                                            if ($duration > 0) {
                                                $total_lesson_time += $duration;
                                            }
                                        }
                                    }
                                    $total_hours = $total_lesson_time > 0 ? round($total_lesson_time / 60, 1) : 0;
                                ?>

                                <?php if ($lesson_count > 0){ ?>
                                    <div class="meta-badge">
                                        <span class="meta-icon">📚</span>
                                        <span><?php echo $lesson_count; ?> Lessons</span>
                                    </div>
                                <?php } else { ?>
                                    <div class="meta-badge">
                                        <span class="meta-icon">📚</span>
                                        <span>0 Lessons</span>
                                    </div>
                                <?php } ?>
                                
                                <?php if ($total_hours): ?>
                                    <div class="meta-badge">
                                        <span class="meta-icon">⏱️</span>
                                        <span><?php echo $total_hours; ?>h</span>
                                    </div>
                                <?php else: ?>
                                    <div class="meta-badge">
                                        <span class="meta-icon">⏱️</span>
                                        <span>0 h</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- CTA Button -->
                            <div class="course-action">
                                <?php if (!$has_access): ?>
                                    <a href="<?php echo esc_url($course_link); ?>" class="btn-modern btn-primary">
                                        <span><?php _e('View Details', 'advanced-course-manager'); ?></span>
                                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #555a5a;">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php elseif ($progress && $progress['percentage'] > 0): ?>
                                    <a href="<?php echo esc_url($course_link); ?>" class="btn-modern btn-primary">
                                        <span><?php _e('Continue Chapter', 'advanced-course-manager'); ?></span>
                                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #555a5a;">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($course_link); ?>" class="btn-modern btn-primary">
                                        <span><?php _e('Start Chapter', 'advanced-course-manager'); ?></span>
                                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #555a5a;">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
                <?php else: ?>
                
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    <h3><?php _e('No Courses Found', 'advanced-course-manager'); ?></h3>
                    <?php if ($related_only_mode): ?>
                        <p><?php _e('No related courses were found for your current quiz answers.', 'advanced-course-manager'); ?></p>
                    <?php else: ?>
                        <p><?php _e('No courses available for the selected province. Try selecting a different province.', 'advanced-course-manager'); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('acm_course')); ?>" class="btn-modern btn-primary">
                        <?php _e('View All Courses', 'advanced-course-manager'); ?>
                    </a>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .province-tag img {
        position: relative !important;
    }
    .acm-courses-archive {
        /*background: #f8fafc;*/
        min-height: 100vh;
    }
    .archive-hero {
        position: relative;
        padding: 80px 20px;
        overflow: hidden;
        background: linear-gradient(135deg, #ede7dd 0%, #ede7dd 100%);
    }
    .hero-background {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0.1;
    }
    .hero-pattern {
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: 
            repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);
    }
    .hero-content {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
        color: white;
    }
    .hero-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #555a5a;
    }
    .hero-title {
        font-size: 48px;
        font-weight: 800;
        margin: 0 0 20px 0;
        line-height: 1.2;
    }
    .hero-subtitle {
        font-size: 20px;
        opacity: 0.95;
        margin: 0 0 40px 0;
        line-height: 1.6;
        color: #555a5a;
    }
    .province-filter-card {
        display: inline-flex;
        align-items: center;
        gap: 20px;
        padding: 20px 30px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .filter-icon {
        font-size: 32px;
    }
    .filter-content {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .filter-content label {
        font-weight: 600;
        color: #1f2937;
        font-size: 16px;
    }
    .province-dropdown {
        padding: 12px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 16px;
        min-width: 200px;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .province-dropdown:hover {
        border-color: #db9563;
    }
    .province-dropdown:focus {
        outline: none;
        border-color: #db9563;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0;
        max-width: 1200px;
        margin: 0px auto 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 50px;
    }
    .stat-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 30px;
        border-right: 1px solid #f1f5f9;
    }
    .stat-item:last-child {
        border-right: none;
    }
    .stat-icon {
        font-size: 40px;
    }
    .stat-number {
        font-size: 32px;
        font-weight: 800;
        color: #1f2937;
        line-height: 1;
    }
    .stat-label {
        font-size: 14px;
        color: #64748b;
        margin-top: 5px;
    }
    .courses-section {
        padding: 60px 20px;
    }
    .section-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .loading-state {
        text-align: center;
        padding: 80px 20px;
    }
    .loader {
        width: 50px;
        height: 50px;
        border: 4px solid #e5e7eb;
        border-top-color: #db9563;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 30px;
    }
    .course-card-modern {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .course-card-modern:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    .course-card-modern.locked {
        opacity: 0.7;
    }
    .course-image {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        background: #DB9563;
        overflow: hidden;
    }
    .course-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .image-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .image-placeholder svg {
        width: 80px;
        height: 80px;
        color: white;
        opacity: 0.8;
    }
    .province-tag {
        position: absolute;
        top: 16px;
        right: 16px;
        padding: 8px 16px;
        background: white;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .lock-badge {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 48px;
    }
    .course-info {
        padding: 30px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .course-title {
        margin: 0 0 12px 0;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
    }
    .course-title a {
        color: #1f2937;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .course-title a:hover {
        color: #667eea;
    }
    .course-excerpt {
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    .course-meta-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    .meta-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 8px;
        background: #f1f5f9;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .meta-badge.difficulty-beginner { background: #dbeafe; color: #1e40af; }
    .meta-badge.difficulty-intermediate { background: #fef3c7; color: #92400e; }
    .meta-badge.difficulty-advanced { background: #fee2e2; color: #991b1b; }
    .meta-icon {
        font-size: 16px;
    }
    .course-progress-card {
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .progress-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }
    .progress-percent {
        font-size: 18px;
        font-weight: 700;
        color: #db9563;
    }
    .progress-bar-modern {
        height: 8px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-fill-modern {
        height: 100%;
        background: linear-gradient(90deg, #db9563 0%, #db9563 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    .course-action {
        margin-top: auto;
    }
    .btn-modern {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 14px 24px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-primary {
        background-color: #db9563;
        color: white !important;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    .btn-outline {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }
    .btn-outline:hover {
        background: #667eea;
        color: white;
    }
    .btn-icon {
        width: 20px;
        height: 20px;
    }
    .empty-state {
        text-align: center;
        padding: 20px 20px;
    }
    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        color: #cbd5e1;
    }
    .empty-icon svg {
        width: 100%;
        height: 100%;
    }
    .empty-state h3 {
        font-size: 24px;
        margin: 0 0 10px 0;
        color: #1f2937;
    }
    .empty-state p {
        color: #64748b;
        margin-bottom: 30px;
    }
    .features-section {
        padding: 80px 20px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }
    .section-title {
        text-align: center;
        font-size: 36px;
        font-weight: 800;
        margin: 0 0 50px 0;
        color: #1f2937;
    }
    .features-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }
    .feature-card-modern {
        padding: 40px;
        background: #f8fafc;
        border-radius: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .feature-card-modern:hover {
        background: white;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        transform: translateY(-5px);
    }
    .feature-icon-modern {
        font-size: 56px;
        margin-bottom: 20px;
    }
    .feature-card-modern h3 {
        font-size: 22px;
        margin: 0 0 12px 0;
        color: #1f2937;
    }
    .feature-card-modern p {
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    .feature-link {
        color: #667eea;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .feature-link:hover {
        color: #764ba2;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 32px;
        }
        .hero-subtitle {
            font-size: 16px;
        }
        .province-filter-card {
            flex-direction: column;
            gap: 15px;
        }
        .filter-content {
            flex-direction: column;
        }
        .stats-bar {
            grid-template-columns: 1fr;
            margin: -20px 20px 40px;
        }
        .stat-item {
            border-right: none;
            border-bottom: 1px solid #f1f5f9;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .courses-grid {
            grid-template-columns: 1fr;
        }
        .section-title {
            font-size: 28px;
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        var relatedOnlyMode = '<?php echo $related_only_mode ? '1' : '0'; ?>';

        $('#province-select').on('change', function() {
            var province = $(this).val();
            var currentUrl = window.location.href.split('?')[0];
            $('#loading-state').show();
            $('#courses-grid-container').fadeTo(300, 0.3);
            var targetUrl = currentUrl;

            if (province) {
                targetUrl = currentUrl + '?province=' + encodeURIComponent(province);
                if (relatedOnlyMode === '1') {
                    targetUrl += '&acm_related_only=1';
                }
            } else {
                if (relatedOnlyMode === '1') {
                    targetUrl = currentUrl + '?acm_related_only=1';
                }
            }

            window.location.href = targetUrl;
        });
    });
</script>

<?php get_footer(); ?>