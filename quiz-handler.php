<?php
/**
 * Personalized Quiz AJAX Handler
 * File: quiz-handler.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Quiz_Handler {

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
        add_action('wp_ajax_acm_save_personalization_quiz', array($this, 'save_quiz_answers'));
        add_action('wp_ajax_acm_get_personalization_quiz', array($this, 'get_quiz_answers'));
    }

    private function get_valid_home_situations() {
        return array('buying_together', 'already_own_together', 'one_of_us_owns', 'we_rent');
    }

    private function sanitize_yes_no($value) {
        return $value === 'yes' ? 'yes' : 'no';
    }

    private function get_required_payload() {
        return array(
            'acm_quiz_has_kids',
            'acm_quiz_has_business',
            'acm_quiz_has_pets',
            'acm_quiz_home_situation',
            'acm_quiz_has_second_home'
        );
    }

    public function save_quiz_answers() {
        check_ajax_referer('acm_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Please log in to save your quiz.', 'advanced-course-manager')
            ));
        }

        foreach ($this->get_required_payload() as $field_key) {
            if (!isset($_POST[$field_key]) || $_POST[$field_key] === '') {
                wp_send_json_error(array(
                    'message' => __('Please answer all quiz questions before saving.', 'advanced-course-manager')
                ));
            }
        }

        $home_situation = sanitize_text_field(wp_unslash($_POST['acm_quiz_home_situation']));
        if (!in_array($home_situation, $this->get_valid_home_situations(), true)) {
            wp_send_json_error(array(
                'message' => __('Invalid home situation value.', 'advanced-course-manager')
            ));
        }

        $answers = array(
            'acm_quiz_has_kids' => $this->sanitize_yes_no(sanitize_text_field(wp_unslash($_POST['acm_quiz_has_kids']))),
            'acm_quiz_has_business' => $this->sanitize_yes_no(sanitize_text_field(wp_unslash($_POST['acm_quiz_has_business']))),
            'acm_quiz_has_pets' => $this->sanitize_yes_no(sanitize_text_field(wp_unslash($_POST['acm_quiz_has_pets']))),
            'acm_quiz_home_situation' => $home_situation,
            'acm_quiz_has_second_home' => $this->sanitize_yes_no(sanitize_text_field(wp_unslash($_POST['acm_quiz_has_second_home']))),
        );

        foreach ($answers as $meta_key => $meta_value) {
            update_user_meta($user_id, $meta_key, $meta_value);
        }

        update_user_meta($user_id, 'acm_quiz_completed', 'yes');

        wp_send_json_success(array(
            'message' => __('Your course has been personalized.', 'advanced-course-manager'),
            'answers' => $answers,
            'completed' => 'yes'
        ));
    }

    public function get_quiz_answers() {
        check_ajax_referer('acm_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Please log in to continue.', 'advanced-course-manager')
            ));
        }

        $response = array(
            'acm_quiz_has_kids' => get_user_meta($user_id, 'acm_quiz_has_kids', true),
            'acm_quiz_has_business' => get_user_meta($user_id, 'acm_quiz_has_business', true),
            'acm_quiz_has_pets' => get_user_meta($user_id, 'acm_quiz_has_pets', true),
            'acm_quiz_home_situation' => get_user_meta($user_id, 'acm_quiz_home_situation', true),
            'acm_quiz_has_second_home' => get_user_meta($user_id, 'acm_quiz_has_second_home', true),
            'acm_quiz_completed' => get_user_meta($user_id, 'acm_quiz_completed', true),
        );

        wp_send_json_success(array('answers' => $response));
    }
}
