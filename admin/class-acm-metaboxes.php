<?php
/**
 * Metaboxes Class
 * File: admin/class-acm-metaboxes.php
 */

if (!defined('ABSPATH')) exit;
class ACM_Metaboxes {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_course_meta'), 10, 2);
        add_action('save_post', array($this, 'save_chapter_meta'), 10, 2);
        add_action('save_post', array($this, 'save_lesson_meta'), 10, 2);
    }
    
    public function add_meta_boxes() {
        // Course metaboxes
        add_meta_box(
            'acm_course_settings',
            __('Course Settings', 'advanced-course-manager'),
            array($this, 'render_course_settings'),
            'acm_course',
            'normal',
            'high'
        );
        
        add_meta_box(
            'acm_course_memberpress',
            __('MemberPress Settings', 'advanced-course-manager'),
            array($this, 'render_memberpress_settings'),
            'acm_course',
            'side',
            'default'
        );
        
        // Lesson metaboxes
        add_meta_box(
            'acm_lesson_settings',
            __('Lesson Settings', 'advanced-course-manager'),
            array($this, 'render_lesson_settings'),
            'acm_lesson',
            'normal',
            'high'
        );

        // Chapter metaboxes
        add_meta_box(
            'acm_chapter_settings',
            __('Chapter Settings', 'advanced-course-manager'),
            array($this, 'render_chapter_settings'),
            'acm_chapter',
            'normal',
            'high'
        );
    }

    public function render_chapter_settings($post) {
        wp_nonce_field('acm_chapter_meta', 'acm_chapter_meta_nonce');

        $course_id = get_post_meta($post->ID, '_acm_chapter_course', true);
        $chapter_number = get_post_meta($post->ID, '_acm_chapter_number', true);
        $filter_key = get_post_meta($post->ID, '_acm_filter_key', true);

        $courses = get_posts(array(
            'post_type' => 'acm_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="acm_chapter_course"><?php _e('Course', 'advanced-course-manager'); ?></label></th>
                <td>
                    <select id="acm_chapter_course" name="acm_chapter_course" class="regular-text">
                        <option value=""><?php _e('Select Course', 'advanced-course-manager'); ?></option>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?php echo esc_attr($course->ID); ?>" <?php selected($course_id, $course->ID); ?>>
                                <?php echo esc_html($course->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="acm_chapter_number"><?php _e('Chapter Number', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="number" id="acm_chapter_number" name="acm_chapter_number" value="<?php echo esc_attr($chapter_number); ?>" class="regular-text" min="1">
                </td>
            </tr>
            <tr>
                <th><label for="acm_filter_key"><?php _e('Filter Key', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="text" id="acm_filter_key" name="acm_filter_key" value="<?php echo esc_attr($filter_key); ?>" class="regular-text">
                    <p class="description"><?php _e('Use consistent keys across provinces (e.g. child_support).', 'advanced-course-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_chapter_meta($post_id, $post) {
        if ($post->post_type !== 'acm_chapter') {
            return;
        }

        if (!isset($_POST['acm_chapter_meta_nonce']) || !wp_verify_nonce($_POST['acm_chapter_meta_nonce'], 'acm_chapter_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['acm_chapter_course'])) {
            $chapter_course_id = intval($_POST['acm_chapter_course']);

            update_post_meta($post_id, '_acm_chapter_course', $chapter_course_id);
            update_post_meta($post_id, 'acm_chapter_course', $chapter_course_id);
            update_post_meta($post_id, 'course_id', $chapter_course_id);

            wp_update_post(array(
                'ID'          => $post_id,
                'post_parent' => $chapter_course_id,
            ));
        }

        if (isset($_POST['acm_chapter_number'])) {
            update_post_meta($post_id, '_acm_chapter_number', intval($_POST['acm_chapter_number']));
        }

        if (isset($_POST['acm_filter_key'])) {
            update_post_meta($post_id, '_acm_filter_key', sanitize_key($_POST['acm_filter_key']));
        }
    }
    
    public function render_course_settings($post) {
        wp_nonce_field('acm_course_meta', 'acm_course_meta_nonce');
        
        $duration = get_post_meta($post->ID, '_acm_course_duration', true);
        $difficulty = get_post_meta($post->ID, '_acm_course_difficulty', true);
        $certificate = get_post_meta($post->ID, '_acm_enable_certificate', true);
        $province = get_post_meta($post->ID, '_acm_course_province', true);
        
        $provinces = array(
            'ontario' => 'Ontario',
            'alberta' => 'Alberta',
            'british_columbia' => 'British Columbia'
        );
        
        ?>
        <table class="form-table">
        
            <tr>
                <th><label for="acm_course_province"><?php _e('Province', 'advanced-course-manager'); ?></label></th>
                <td>
                    <select id="acm_course_province" name="acm_course_province">
                        <option value=""><?php _e('All Provinces', 'advanced-course-manager'); ?></option>
                        <?php foreach ($provinces as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($province, $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Assign this course to a specific province for Relationship Agreements', 'advanced-course-manager'); ?></p>
                </td>
            </tr>
            
            <?php /*
                <tr>
                    <th><label for="acm_course_duration"><?php _e('Duration (hours)', 'advanced-course-manager'); ?></label></th>
                    <td>
                        <input type="number" id="acm_course_duration" name="acm_course_duration" 
                            value="<?php echo esc_attr($duration); ?>" step="0.5" class="regular-text">
                    </td>
                </tr>
            */ ?>
            
            <?php /*
                <tr>
                    <th><label for="acm_course_difficulty"><?php _e('Difficulty', 'advanced-course-manager'); ?></label></th>
                    <td>
                        <select id="acm_course_difficulty" name="acm_course_difficulty">
                            <option value="beginner" <?php selected($difficulty, 'beginner'); ?>><?php _e('Beginner', 'advanced-course-manager'); ?></option>
                            <option value="intermediate" <?php selected($difficulty, 'intermediate'); ?>><?php _e('Intermediate', 'advanced-course-manager'); ?></option>
                            <option value="advanced" <?php selected($difficulty, 'advanced'); ?>><?php _e('Advanced', 'advanced-course-manager'); ?></option>
                        </select>
                    </td>
                </tr>
    
                <tr>
                    <th><label for="acm_enable_certificate"><?php _e('Enable Certificate', 'advanced-course-manager'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="acm_enable_certificate" name="acm_enable_certificate" 
                                value="yes" <?php checked($certificate, 'yes'); ?>>
                            <?php _e('Generate certificate upon completion', 'advanced-course-manager'); ?>
                        </label>
                    </td>
                </tr>
            */ ?>
        </table>
        <?php
    }
    
    public function render_memberpress_settings($post) {
        if (!class_exists('MeprUser')) {
            echo '<p>' . __('MemberPress is not active', 'advanced-course-manager') . '</p>';
            return;
        }
        
        wp_nonce_field('acm_memberpress_meta', 'acm_memberpress_meta_nonce');
        
        $memberships = get_post_meta($post->ID, '_acm_memberpress_memberships', true);
        if (!is_array($memberships)) {
            $memberships = array();
        }
        
        $all_memberships = get_posts(array(
            'post_type' => 'memberpressproduct',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        ?>
        <div class="acm-memberpress-settings">
            <p><strong><?php _e('Required Memberships:', 'advanced-course-manager'); ?></strong></p>
            <?php foreach ($all_memberships as $membership): ?>
                <label style="display: block; margin-bottom: 8px;">
                    <input type="checkbox" name="acm_memberpress_memberships[]" 
                        value="<?php echo esc_attr($membership->ID); ?>" 
                        <?php checked(in_array($membership->ID, $memberships)); ?>>
                    <?php echo esc_html($membership->post_title); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    public function render_lesson_settings($post) {
        wp_nonce_field('acm_lesson_meta', 'acm_lesson_meta_nonce');
        
        $chapter_id = get_post_meta($post->ID, '_acm_lesson_chapter', true);
        $video_url = get_post_meta($post->ID, '_acm_lesson_video_url', true);
        $video_type = get_post_meta($post->ID, '_acm_lesson_video_type', true);
        $duration = get_post_meta($post->ID, '_acm_lesson_duration', true);
        $section = get_post_meta($post->ID, '_acm_lesson_section', true);
        $filter_key = get_post_meta($post->ID, '_acm_filter_key', true);
        $lesson_order = (int) $post->menu_order;
        
        $chapters = get_posts(array(
            'post_type' => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $sections = array(
            'family_law' => 'Family Law',
            'spousal_support' => 'Spousal Support',
            'child_support' => 'Child Support',
            'property' => 'Property',
            'special_rules' => 'Special Rules',
            'finalizing' => 'Finalizing'
        );
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="acm_lesson_chapter"><?php _e('Chapter', 'advanced-course-manager'); ?></label></th>
                <td>
                    <select id="acm_lesson_chapter" name="acm_lesson_chapter" class="regular-text">
                        <option value=""><?php _e('Select Chapter', 'advanced-course-manager'); ?></option>
                        <?php foreach ($chapters as $chapter): ?>
                            <?php 
                            $course_id = get_post_meta($chapter->ID, '_acm_chapter_course', true);
                            $course_title = $course_id ? get_the_title($course_id) : __('No Course', 'advanced-course-manager');
                            ?>
                            <option value="<?php echo esc_attr($chapter->ID); ?>" 
                                <?php selected($chapter_id, $chapter->ID); ?>>
                                <?php echo esc_html($course_title . ' → ' . $chapter->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select the chapter this lesson belongs to', 'advanced-course-manager'); ?></p>
                </td>
            </tr>

            <?php /*
                <tr>
                    <th><label for="acm_lesson_section"><?php _e('Section (for customization)', 'advanced-course-manager'); ?></label></th>
                    <td>
                        <select id="acm_lesson_section" name="acm_lesson_section" class="regular-text">
                            <?php foreach ($sections as $key => $name): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($section, $key); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('If set, this lesson will only show to users who selected this topic in the customization quiz.', 'advanced-course-manager'); ?>
                        </p>
                    </td>
                </tr>
            */ ?>
            
            <tr>
                <th><label for="acm_lesson_video_type"><?php _e('Video Type', 'advanced-course-manager'); ?></label></th>
                <td>
                    <select id="acm_lesson_video_type" name="acm_lesson_video_type">
                        <option value="youtube" <?php selected($video_type, 'youtube'); ?>>YouTube</option>
                        <option value="vimeo" <?php selected($video_type, 'vimeo'); ?>>Vimeo</option>
                        <option value="self" <?php selected($video_type, 'self'); ?>><?php _e('Self-hosted', 'advanced-course-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="acm_lesson_video_url"><?php _e('Video URL/ID', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="text" id="acm_lesson_video_url" name="acm_lesson_video_url" 
                        value="<?php echo esc_attr($video_url); ?>" class="regular-text">
                    <p class="description">
                        <?php _e('For YouTube/Vimeo: Enter video ID. For self-hosted: Enter full URL', 'advanced-course-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label for="acm_lesson_duration"><?php _e('Duration (minutes)', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="number" id="acm_lesson_duration" name="acm_lesson_duration" 
                        value="<?php echo esc_attr($duration); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="acm_lesson_order"><?php _e('Lesson Order', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="number" id="acm_lesson_order" name="acm_lesson_order"
                        value="<?php echo $lesson_order > 0 ? esc_attr($lesson_order) : ''; ?>" class="small-text" min="1" step="1">
                    <p class="description"><?php _e('Lower numbers appear first inside a chapter. Leave empty to auto-place at the end.', 'advanced-course-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="acm_filter_key_lesson"><?php _e('Filter Key', 'advanced-course-manager'); ?></label></th>
                <td>
                    <input type="text" id="acm_filter_key_lesson" name="acm_filter_key" value="<?php echo esc_attr($filter_key); ?>" class="regular-text">
                    <p class="description"><?php _e('Use keys like business, pets, second_home, home_buying_together, home_already_own, home_one_owns, home_renting.', 'advanced-course-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_course_meta($post_id, $post) {
        if (!isset($_POST['acm_course_meta_nonce']) || !wp_verify_nonce($_POST['acm_course_meta_nonce'], 'acm_course_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if ($post->post_type !== 'acm_course') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save course meta
        if (isset($_POST['acm_course_province'])) {
            update_post_meta($post_id, '_acm_course_province', sanitize_text_field($_POST['acm_course_province']));
        }
        
        if (isset($_POST['acm_course_duration'])) {
            update_post_meta($post_id, '_acm_course_duration', sanitize_text_field($_POST['acm_course_duration']));
        }
        
        if (isset($_POST['acm_course_difficulty'])) {
            update_post_meta($post_id, '_acm_course_difficulty', sanitize_text_field($_POST['acm_course_difficulty']));
        }
        
        $certificate = isset($_POST['acm_enable_certificate']) ? 'yes' : 'no';
        update_post_meta($post_id, '_acm_enable_certificate', $certificate);
        
        // Save MemberPress memberships
        $memberships = array();
        if (isset($_POST['acm_memberpress_memberships'])) {
            $memberships = array_map('intval', (array) $_POST['acm_memberpress_memberships']);
            update_post_meta($post_id, '_acm_memberpress_memberships', $memberships);
        } else {
            delete_post_meta($post_id, '_acm_memberpress_memberships');
        }

        // Keep chapter and lesson access rules aligned with the course-level setting.
        $this->sync_memberpress_permissions_to_course_tree($post_id, $memberships);
    }

    /**
     * Sync MemberPress memberships from a course to all child chapters/lessons.
     */
    private function sync_memberpress_permissions_to_course_tree($course_id, $memberships) {
        $meta_chapter_ids = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_acm_chapter_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'acm_chapter_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'course_id',
                    'value' => $course_id,
                ),
            ),
        ));

        $parent_chapter_ids = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'post_parent'    => (int) $course_id,
        ));

        $chapter_ids = array_values(array_unique(array_map('intval', array_merge($meta_chapter_ids, $parent_chapter_ids))));

        foreach ($chapter_ids as $chapter_id) {
            if (!empty($memberships)) {
                update_post_meta($chapter_id, '_acm_memberpress_memberships', $memberships);
            } else {
                delete_post_meta($chapter_id, '_acm_memberpress_memberships');
            }
        }

        $lesson_ids = array();

        if (!empty($chapter_ids)) {
            $chapter_lesson_ids = get_posts(array(
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'meta_query'     => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_acm_lesson_chapter',
                        'value'   => $chapter_ids,
                        'compare' => 'IN',
                    ),
                    array(
                        'key'     => 'acm_lesson_chapter',
                        'value'   => $chapter_ids,
                        'compare' => 'IN',
                    ),
                ),
            ));

            $parent_lesson_ids = get_posts(array(
                'post_type'      => 'acm_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'post_parent__in'=> $chapter_ids,
            ));

            $lesson_ids = array_merge($lesson_ids, $chapter_lesson_ids, $parent_lesson_ids);
        }

        $direct_lesson_ids = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_acm_lesson_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'acm_lesson_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'course_id',
                    'value' => $course_id,
                ),
            ),
        ));

        $direct_parent_lesson_ids = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'post_parent'    => (int) $course_id,
        ));

        $lesson_ids = array_values(array_unique(array_map('intval', array_merge($lesson_ids, $direct_lesson_ids, $direct_parent_lesson_ids))));

        foreach ($lesson_ids as $lesson_id) {
            if (!empty($memberships)) {
                update_post_meta($lesson_id, '_acm_memberpress_memberships', $memberships);
            } else {
                delete_post_meta($lesson_id, '_acm_memberpress_memberships');
            }
        }
    }
    
    public function save_lesson_meta($post_id, $post) {
        if (!isset($_POST['acm_lesson_meta_nonce']) || !wp_verify_nonce($_POST['acm_lesson_meta_nonce'], 'acm_lesson_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if ($post->post_type !== 'acm_lesson') {
            return;
        }
        
        // Save lesson meta
        if (isset($_POST['acm_lesson_section'])) {
            update_post_meta($post_id, '_acm_lesson_section', sanitize_text_field($_POST['acm_lesson_section']));
        }
        
        if (isset($_POST['acm_lesson_chapter'])) {
            $lesson_chapter_id = intval($_POST['acm_lesson_chapter']);
            $lesson_course_id = 0;

            if ($lesson_chapter_id > 0) {
                $lesson_course_id = (int) get_post_meta($lesson_chapter_id, '_acm_chapter_course', true);
                if (!$lesson_course_id) {
                    $lesson_course_id = (int) get_post_meta($lesson_chapter_id, 'acm_chapter_course', true);
                }
                if (!$lesson_course_id) {
                    $lesson_course_id = (int) get_post_meta($lesson_chapter_id, 'course_id', true);
                }
                if (!$lesson_course_id) {
                    $chapter = get_post($lesson_chapter_id);
                    $lesson_course_id = $chapter ? (int) $chapter->post_parent : 0;
                }
            }

            update_post_meta($post_id, '_acm_lesson_chapter', $lesson_chapter_id);
            update_post_meta($post_id, 'acm_lesson_chapter', $lesson_chapter_id);

            if ($lesson_course_id > 0) {
                update_post_meta($post_id, '_acm_lesson_course', $lesson_course_id);
                update_post_meta($post_id, 'acm_lesson_course', $lesson_course_id);
                update_post_meta($post_id, 'course_id', $lesson_course_id);
            }

            wp_update_post(array(
                'ID'          => $post_id,
                'post_parent' => $lesson_chapter_id,
            ));
        }
        
        if (isset($_POST['acm_lesson_video_type'])) {
            update_post_meta($post_id, '_acm_lesson_video_type', sanitize_text_field($_POST['acm_lesson_video_type']));
        }
        
        if (isset($_POST['acm_lesson_video_url'])) {
            update_post_meta($post_id, '_acm_lesson_video_url', sanitize_text_field($_POST['acm_lesson_video_url']));
        }
        
        if (isset($_POST['acm_lesson_duration'])) {
            update_post_meta($post_id, '_acm_lesson_duration', intval($_POST['acm_lesson_duration']));
        }

        if (isset($_POST['acm_filter_key'])) {
            update_post_meta($post_id, '_acm_filter_key', sanitize_key($_POST['acm_filter_key']));
        }

        $target_order = null;
        $current_order = (int) get_post_field('menu_order', $post_id);

        if (isset($_POST['acm_lesson_order']) && $_POST['acm_lesson_order'] !== '') {
            $target_order = max(1, intval($_POST['acm_lesson_order']));
        } elseif ($current_order === 0) {
            $chapter_for_order = isset($_POST['acm_lesson_chapter'])
                ? intval($_POST['acm_lesson_chapter'])
                : intval(get_post_meta($post_id, '_acm_lesson_chapter', true));

            if ($chapter_for_order > 0) {
                $chapter_lesson_ids = get_posts(array(
                    'post_type'      => 'acm_lesson',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'     => '_acm_lesson_chapter',
                            'value'   => $chapter_for_order,
                            'compare' => '='
                        )
                    ),
                    'exclude'        => array($post_id),
                ));

                $max_order = 0;
                if (!empty($chapter_lesson_ids)) {
                    foreach ($chapter_lesson_ids as $chapter_lesson_id) {
                        $existing_order = (int) get_post_field('menu_order', $chapter_lesson_id);
                        if ($existing_order > $max_order) {
                            $max_order = $existing_order;
                        }
                    }
                }

                $target_order = $max_order + 1;
            }
        }

        if ($target_order !== null && $target_order !== $current_order) {
            remove_action('save_post', array($this, 'save_lesson_meta'), 10);
            wp_update_post(array(
                'ID' => $post_id,
                'menu_order' => $target_order,
            ));
            add_action('save_post', array($this, 'save_lesson_meta'), 10, 2);
        }
    }
}