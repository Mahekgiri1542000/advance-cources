<?php
/**
 * Lesson "Need To Know" Repeater Meta
 * File: admin/class-acm-lesson-need-to-know.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Lesson_Need_To_Know {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_acm_lesson', [$this, 'save_meta']);
    }

    /**
     * Register Meta Box
     */
    public function register_meta_box() {
        add_meta_box(
            'acm_need_to_know',
            __('Need To Know', 'advanced-course-manager'),
            [$this, 'render_meta_box'],
            'acm_lesson',
            'normal',
            'default'
        );
    }

    /**
     * Render Meta Box UI (Repeater)
     */
    public function render_meta_box($post) {
        wp_nonce_field('acm_need_to_know_nonce', 'acm_need_to_know_nonce');

        $items = get_post_meta($post->ID, '_acm_need_to_know', true);
        if (!is_array($items)) {
            $items = [];
        }
        ?>
        <div id="acm-need-to-know-wrapper">
            <?php foreach ($items as $value): ?>
                <div class="acm-ntk-row">
                    <input
                        type="text"
                        name="acm_need_to_know[]"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php esc_attr_e('Enter point...', 'advanced-course-manager'); ?>"
                    />
                    <button type="button" class="button acm-remove-ntk">×</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="acm-add-ntk">
            + <?php _e('Add Point', 'advanced-course-manager'); ?>
        </button>

        <style>
            .acm-ntk-row {
                display: flex;
                gap: 10px;
                margin-bottom: 8px;
            }
            .acm-ntk-row input {
                flex: 1;
            }
            .acm-remove-ntk {
                color: #b32d2e;
                font-weight: bold;
            }
        </style>

        <script>
        (function($){
            $('#acm-add-ntk').on('click', function(){
                $('#acm-need-to-know-wrapper').append(
                    '<div class="acm-ntk-row">' +
                        '<input type="text" name="acm_need_to_know[]" placeholder="<?php esc_attr_e('Enter point...', 'advanced-course-manager'); ?>" />' +
                        '<button type="button" class="button acm-remove-ntk">×</button>' +
                    '</div>'
                );
            });

            $(document).on('click', '.acm-remove-ntk', function(){
                $(this).closest('.acm-ntk-row').remove();
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save Meta Data
     */
    public function save_meta($post_id) {

        if (!isset($_POST['acm_need_to_know_nonce']) ||
            !wp_verify_nonce($_POST['acm_need_to_know_nonce'], 'acm_need_to_know_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $items = isset($_POST['acm_need_to_know'])
            ? array_filter(array_map('sanitize_text_field', $_POST['acm_need_to_know']))
            : [];

        update_post_meta($post_id, '_acm_need_to_know', $items);
    }
}
