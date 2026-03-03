<?php
/**
 * Personalized Quiz Template / Shortcode
 * File: quiz-template.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Quiz_Template {

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
        add_shortcode('acm_personalization_quiz', array($this, 'render_quiz_shortcode'));
        add_shortcode('acm_customization_quiz', array($this, 'render_quiz_shortcode'));
    }

    private function get_user_answers($user_id) {
        return array(
            'acm_quiz_has_kids' => get_user_meta($user_id, 'acm_quiz_has_kids', true),
            'acm_quiz_has_business' => get_user_meta($user_id, 'acm_quiz_has_business', true),
            'acm_quiz_has_pets' => get_user_meta($user_id, 'acm_quiz_has_pets', true),
            'acm_quiz_home_situation' => get_user_meta($user_id, 'acm_quiz_home_situation', true),
            'acm_quiz_has_second_home' => get_user_meta($user_id, 'acm_quiz_has_second_home', true),
            'acm_quiz_completed' => get_user_meta($user_id, 'acm_quiz_completed', true),
        );
    }

    public function render_quiz_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to personalize your course.', 'advanced-course-manager') . '</p>';
        }

        $user_id = get_current_user_id();
        $answers = $this->get_user_answers($user_id);
        $is_completed = isset($answers['acm_quiz_completed']) && $answers['acm_quiz_completed'] === 'yes';
        $related_courses_url = get_post_type_archive_link('acm_course');
        if (!$related_courses_url) {
            $related_courses_url = home_url('/');
        }
        $related_courses_url = add_query_arg('acm_related_only', '1', $related_courses_url);

        ob_start();
        ?>
        <div class="acm-personalization-quiz-wrap acm-quiz-page-shell">
            <div class="acm-quiz-card">
                <div class="acm-quiz-header">
                    <h2><?php esc_html_e('Personalize Your Course', 'advanced-course-manager'); ?></h2>
                    <p><?php esc_html_e('Answer these quick questions and we will tailor what you see in your course.', 'advanced-course-manager'); ?></p>
                </div>

                <?php if (!$is_completed): ?>
                    <div class="acm-personalize-prompt acm-personalize-prompt--quiz-page">
                        <p><?php esc_html_e('Personalize your course — take the quiz', 'advanced-course-manager'); ?></p>
                    </div>
                <?php endif; ?>

                <div class="acm-quiz-success" style="display:none;"></div>

                <div class="acm-quiz-retake-state" style="display: <?php echo $is_completed ? 'block' : 'none'; ?>;">
                    <p><?php esc_html_e('Your quiz has already been completed.', 'advanced-course-manager'); ?></p>
                    <button type="button" class="acm-btn acm-btn-secondary" id="acm-retake-quiz-btn">
                        <?php esc_html_e('Retake Quiz', 'advanced-course-manager'); ?>
                    </button>
                </div>

                <form id="acm-personalization-quiz-form" style="display: <?php echo $is_completed ? 'none' : 'block'; ?>;">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('acm_nonce')); ?>" />

                    <div class="quiz-question">
                        <h3>1. <?php esc_html_e('Do you or your partner have kids?', 'advanced-course-manager'); ?></h3>
                        <div class="acm-quiz-options acm-quiz-options--inline">
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_kids" value="yes" <?php checked($answers['acm_quiz_has_kids'], 'yes'); ?> required> <span><?php esc_html_e('Yes', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_kids" value="no" <?php checked($answers['acm_quiz_has_kids'], 'no'); ?> required> <span><?php esc_html_e('No', 'advanced-course-manager'); ?></span></label>
                        </div>
                    </div>

                    <div class="quiz-question">
                        <h3>2. <?php esc_html_e('Do you or your partner own a business?', 'advanced-course-manager'); ?></h3>
                        <div class="acm-quiz-options acm-quiz-options--inline">
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_business" value="yes" <?php checked($answers['acm_quiz_has_business'], 'yes'); ?> required> <span><?php esc_html_e('Yes', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_business" value="no" <?php checked($answers['acm_quiz_has_business'], 'no'); ?> required> <span><?php esc_html_e('No', 'advanced-course-manager'); ?></span></label>
                        </div>
                    </div>

                    <div class="quiz-question">
                        <h3>3. <?php esc_html_e('Do you or your partner have pets?', 'advanced-course-manager'); ?></h3>
                        <div class="acm-quiz-options acm-quiz-options--inline">
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_pets" value="yes" <?php checked($answers['acm_quiz_has_pets'], 'yes'); ?> required> <span><?php esc_html_e('Yes', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_pets" value="no" <?php checked($answers['acm_quiz_has_pets'], 'no'); ?> required> <span><?php esc_html_e('No', 'advanced-course-manager'); ?></span></label>
                        </div>
                    </div>

                    <div class="quiz-question">
                        <h3>4. <?php esc_html_e('What best describes your home situation?', 'advanced-course-manager'); ?></h3>
                        <div class="acm-quiz-options acm-quiz-options--stacked">
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_home_situation" value="buying_together" <?php checked($answers['acm_quiz_home_situation'], 'buying_together'); ?> required> <span><?php esc_html_e('We are going to buy a family home together', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_home_situation" value="already_own_together" <?php checked($answers['acm_quiz_home_situation'], 'already_own_together'); ?> required> <span><?php esc_html_e('We already own a family home together', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_home_situation" value="one_of_us_owns" <?php checked($answers['acm_quiz_home_situation'], 'one_of_us_owns'); ?> required> <span><?php esc_html_e('One of us owns a home that we will use as our family home', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_home_situation" value="we_rent" <?php checked($answers['acm_quiz_home_situation'], 'we_rent'); ?> required> <span><?php esc_html_e('We rent and aren\'t buying right away', 'advanced-course-manager'); ?></span></label>
                        </div>
                    </div>

                    <div class="quiz-question">
                        <h3>5. <?php esc_html_e('Do either of you own a second home where you do not live?', 'advanced-course-manager'); ?></h3>
                        <div class="acm-quiz-options acm-quiz-options--inline">
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_second_home" value="yes" <?php checked($answers['acm_quiz_has_second_home'], 'yes'); ?> required> <span><?php esc_html_e('Yes', 'advanced-course-manager'); ?></span></label>
                            <label class="acm-quiz-option"><input type="radio" name="acm_quiz_has_second_home" value="no" <?php checked($answers['acm_quiz_has_second_home'], 'no'); ?> required> <span><?php esc_html_e('No', 'advanced-course-manager'); ?></span></label>
                        </div>
                    </div>

                    <div class="acm-quiz-actions">
                        <button type="submit" class="acm-btn acm-btn-primary" id="acm-personalization-submit">
                            <?php esc_html_e('Save & Apply', 'advanced-course-manager'); ?>
                        </button>
                    </div>
                    <div class="acm-quiz-error" style="display:none;"></div>
                </form>
            </div>
        </div>

        <script>
        (function($) {
            $(document).ready(function() {
                var $form = $('#acm-personalization-quiz-form');
                var $submit = $('#acm-personalization-submit');
                var $error = $('.acm-quiz-error');
                var $success = $('.acm-quiz-success');
                var $retakeState = $('.acm-quiz-retake-state');

                $('#acm-retake-quiz-btn').on('click', function() {
                    $retakeState.hide();
                    $form.show();
                });

                $form.on('submit', function(e) {
                    e.preventDefault();

                    $error.hide().text('');
                    $success.hide().text('');

                    if (!$form[0].checkValidity()) {
                        $form[0].reportValidity();
                        return;
                    }

                    var payload = {
                        action: 'acm_save_personalization_quiz',
                        nonce: $form.find('input[name="nonce"]').val(),
                        acm_quiz_has_kids: $form.find('input[name="acm_quiz_has_kids"]:checked').val(),
                        acm_quiz_has_business: $form.find('input[name="acm_quiz_has_business"]:checked').val(),
                        acm_quiz_has_pets: $form.find('input[name="acm_quiz_has_pets"]:checked').val(),
                        acm_quiz_home_situation: $form.find('input[name="acm_quiz_home_situation"]:checked').val(),
                        acm_quiz_has_second_home: $form.find('input[name="acm_quiz_has_second_home"]:checked').val()
                    };

                    $submit.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'advanced-course-manager')); ?>');

                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: payload,
                        success: function(response) {
                            if (!response || !response.success) {
                                var errorMessage = response && response.data && response.data.message
                                    ? response.data.message
                                    : '<?php echo esc_js(__('Something went wrong. Please try again.', 'advanced-course-manager')); ?>';

                                $error.text(errorMessage).show();
                                return;
                            }

                            $success.text(response.data.message).show();
                            $form.hide();
                            $retakeState.show();
                            window.dispatchEvent(new CustomEvent('acmQuizSaved'));
                            window.location.href = '<?php echo esc_url($related_courses_url); ?>';
                        },
                        error: function() {
                            $error.text('<?php echo esc_js(__('Could not save your quiz right now. Please try again.', 'advanced-course-manager')); ?>').show();
                        },
                        complete: function() {
                            $submit.prop('disabled', false).text('<?php echo esc_js(__('Save & Apply', 'advanced-course-manager')); ?>');
                        }
                    });
                });
            });
        })(jQuery);
        </script>
        <?php

        return ob_get_clean();
    }
}
