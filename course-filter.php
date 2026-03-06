<?php
/**
 * Personalized Course Filtering
 * File: course-filter.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Course_Filter {

    private static $instance = null;

    private $home_visibility_map = array(
        'buying_together' => 'home_buying_together',
        'already_own_together' => 'home_already_own',
        'one_of_us_owns' => 'home_one_owns',
        'we_rent' => 'home_renting',
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_acm_get_quiz_answers_for_builder', array($this, 'get_quiz_answers_for_builder'));
        add_action('wp_ajax_acm_clear_personalization_filter', array($this, 'clear_personalization_filter'));
        add_action('template_redirect', array($this, 'handle_clear_filter_request'));
    }

    private function get_quiz_meta_keys() {
        return array(
            'acm_quiz_has_kids',
            'acm_quiz_has_business',
            'acm_quiz_has_pets',
            'acm_quiz_home_situation',
            'acm_quiz_has_second_home',
            'acm_quiz_completed',
            'acm_course_customization',
        );
    }

    private function clear_quiz_filter_for_user($user_id) {
        foreach ($this->get_quiz_meta_keys() as $meta_key) {
            delete_user_meta($user_id, $meta_key);
        }
    }

    private function build_clean_redirect_url($url = '') {
        $target_url = $url ? $url : wp_get_referer();

        if (!$target_url) {
            $target_url = home_url('/');
        }

        return remove_query_arg(array('acm_related_only', 'acm_clear_quiz_filter', '_acm_nonce'), $target_url);
    }

    public function get_user_quiz_answers($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return array(
            'acm_quiz_has_kids' => get_user_meta($user_id, 'acm_quiz_has_kids', true),
            'acm_quiz_has_business' => get_user_meta($user_id, 'acm_quiz_has_business', true),
            'acm_quiz_has_pets' => get_user_meta($user_id, 'acm_quiz_has_pets', true),
            'acm_quiz_home_situation' => get_user_meta($user_id, 'acm_quiz_home_situation', true),
            'acm_quiz_has_second_home' => get_user_meta($user_id, 'acm_quiz_has_second_home', true),
            'acm_quiz_completed' => get_user_meta($user_id, 'acm_quiz_completed', true),
        );
    }

    public function is_quiz_completed($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return get_user_meta($user_id, 'acm_quiz_completed', true) === 'yes';
    }

    public function get_filter_key($post_id) {
        $key = get_post_meta($post_id, '_acm_filter_key', true);

        if ($key === '') {
            $key = get_post_meta($post_id, 'acm_filter_key', true);
        }

        return sanitize_key($key);
    }

    public function is_hidden_for_user($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return false;
        }

        $filter_key = $this->get_filter_key($post_id);
        if ($filter_key === '') {
            return false;
        }

        $answers = $this->get_user_quiz_answers($user_id);

        if (in_array($filter_key, array('family_law', 'spousal_support', 'general_property', 'finalizing', 'special_rules'), true)) {
            return false;
        }

        if ($filter_key === 'child_support') {
            return $answers['acm_quiz_has_kids'] === 'no';
        }

        if ($filter_key === 'business') {
            return $answers['acm_quiz_has_business'] === 'no';
        }

        if ($filter_key === 'pets') {
            return $answers['acm_quiz_has_pets'] === 'no';
        }

        if ($filter_key === 'second_home') {
            return $answers['acm_quiz_has_second_home'] === 'no';
        }

        if (in_array($filter_key, array_values($this->home_visibility_map), true)) {
            $selected_home_key = isset($this->home_visibility_map[$answers['acm_quiz_home_situation']])
                ? $this->home_visibility_map[$answers['acm_quiz_home_situation']]
                : '';

            if ($selected_home_key === '') {
                return false;
            }

            return $filter_key !== $selected_home_key;
        }

        return false;
    }

    public function get_hidden_class($post_id, $user_id = null) {
        return $this->is_hidden_for_user($post_id, $user_id) ? ' acm-lesson--hidden' : '';
    }
    
    public function is_lesson_related_for_user($lesson_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return true;
        }
        
        $filter_key = $this->get_filter_key($lesson_id);
        if ($filter_key === '') {
            return true;
        }
        
        return !$this->is_hidden_for_user($lesson_id, $user_id);
    }

    public function is_chapter_related_for_user($chapter_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return true;
        }

        $chapter_filter_key = $this->get_filter_key($chapter_id);
        if ($chapter_filter_key !== '') {
            return !$this->is_hidden_for_user($chapter_id, $user_id);
        }

        $chapter_lessons = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_acm_lesson_chapter',
                    'value' => $chapter_id,
                    'compare' => '='
                )
            )
        ));

        foreach ($chapter_lessons as $lesson_id) {
            if ($this->is_lesson_related_for_user($lesson_id, $user_id)) {
                return true;
            }
        }

        return false;
    }

    public function is_course_related_for_user($course_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return true;
        }

        $course_filter_key = $this->get_filter_key($course_id);
        if ($course_filter_key !== '') {
            return !$this->is_hidden_for_user($course_id, $user_id);
        }

        $chapter_ids = get_posts(array(
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

        if (empty($chapter_ids)) {
            return false;
        }

        foreach ($chapter_ids as $chapter_id) {
            if ($this->is_chapter_related_for_user($chapter_id, $user_id)) {
                return true;
            }
        }

        return false;
    }

    public function get_personalization_prompt_html($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return '';
        }

        if ($this->is_quiz_completed($user_id)) {
            return '';
        }

        $quiz_page_id = get_option('acm_customization_quiz');
        $quiz_link = $quiz_page_id ? get_permalink($quiz_page_id) : '#';

        return '<div class="acm-personalize-prompt">'
            . '<h3 class="acm-personalize-prompt__title">' . esc_html__('Personalize your Course', 'advanced-course-manager') . '</h3>'
            . '<p class="acm-personalize-prompt__text">' . esc_html__('Tell us more about you so that we can personalize your course to your circumstances', 'advanced-course-manager') . '</p>'
            . '<a class="acm-btn acm-btn-secondary acm-personalize-prompt__button" href="' . esc_url($quiz_link) . '">' . esc_html__('Get started ->', 'advanced-course-manager') . '</a>'
            . '</div>';
    }

    public function get_clear_quiz_filter_box_html($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return '';
        }

        return '<div class="acm-personalize-prompt acm-personalize-prompt--clear-filter">'
            . '<h3 class="acm-personalize-prompt__title">' . esc_html__('Quiz Filter Applied', 'advanced-course-manager') . '</h3>'
            . '<p class="acm-personalize-prompt__text">' . esc_html__('Your course view is personalized by your quiz answers. Clear the filter to view all sections.', 'advanced-course-manager') . '</p>'
            . '<button type="button" class="acm-btn acm-btn-secondary acm-personalize-prompt__button acm-clear-quiz-filter-btn" data-nonce="' . esc_attr(wp_create_nonce('acm_nonce')) . '" data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '">' . esc_html__('Clear Quiz Filter', 'advanced-course-manager') . '</button>'
            . '</div>';
    }

    public function get_view_toggle_html($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_quiz_completed($user_id)) {
            return '';
        }

        return '<button type="button" class="acm-btn acm-btn-secondary" data-acm-toggle-full-course="off"><span class="acm-toggle-label">' . esc_html__('View Full Course', 'advanced-course-manager') . '</span></button>';
    }

    public function get_quiz_answers_for_builder() {
        check_ajax_referer('acm_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in to continue.', 'advanced-course-manager')));
        }

        wp_send_json_success(array(
            'answers' => $this->get_user_quiz_answers($user_id)
        ));
    }

    public function clear_personalization_filter() {
        check_ajax_referer('acm_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in to continue.', 'advanced-course-manager')));
        }

        $this->clear_quiz_filter_for_user($user_id);

        $current_url = '';
        if (isset($_POST['current_url'])) {
            $current_url = esc_url_raw(wp_unslash($_POST['current_url']));
        }

        $redirect_url = $this->build_clean_redirect_url($current_url);

        wp_send_json_success(array(
            'message' => __('Quiz filter cleared.', 'advanced-course-manager'),
            'redirect_url' => esc_url_raw($redirect_url)
        ));
    }

    public function handle_clear_filter_request() {
        if (!isset($_GET['acm_clear_quiz_filter']) || $_GET['acm_clear_quiz_filter'] !== '1') {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        $nonce = isset($_GET['_acm_nonce']) ? sanitize_text_field(wp_unslash($_GET['_acm_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'acm_nonce')) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $this->clear_quiz_filter_for_user($user_id);

        $redirect_url = $this->build_clean_redirect_url();
        wp_safe_redirect($redirect_url);
        exit;
    }
}

function acm_course_filter() {
    return ACM_Course_Filter::get_instance();
}

function acm_is_quiz_completed($user_id = null) {
    return acm_course_filter()->is_quiz_completed($user_id);
}

function acm_get_quiz_answers($user_id = null) {
    return acm_course_filter()->get_user_quiz_answers($user_id);
}

function acm_get_filter_key($post_id) {
    return acm_course_filter()->get_filter_key($post_id);
}

function acm_is_lesson_hidden_for_user($post_id, $user_id = null) {
    return acm_course_filter()->is_hidden_for_user($post_id, $user_id);
}
 
function acm_is_lesson_related_for_user($lesson_id, $user_id = null) {
    return acm_course_filter()->is_lesson_related_for_user($lesson_id, $user_id);
}

function acm_get_hidden_class($post_id, $user_id = null) {
    return acm_course_filter()->get_hidden_class($post_id, $user_id);
}

function acm_get_personalization_prompt_html($user_id = null) {
    return acm_course_filter()->get_personalization_prompt_html($user_id);
}

function acm_get_view_toggle_html($user_id = null) {
    return acm_course_filter()->get_view_toggle_html($user_id);
}

function acm_get_clear_quiz_filter_box_html($user_id = null) {
    return acm_course_filter()->get_clear_quiz_filter_box_html($user_id);
}

function acm_is_chapter_related_for_user($chapter_id, $user_id = null) {
    return acm_course_filter()->is_chapter_related_for_user($chapter_id, $user_id);
}

function acm_is_course_related_for_user($course_id, $user_id = null) {
    return acm_course_filter()->is_course_related_for_user($course_id, $user_id);
}
