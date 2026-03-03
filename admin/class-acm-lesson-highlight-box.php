<?php
/**
 * Lesson "Highlight Box" Repeater Meta
 * File: admin/class-acm-lesson-highlight-box.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Lesson_Highlight_Box {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_acm_lesson', [$this, 'save_meta']);
    }

    public function register_meta_box() {
        add_meta_box(
            'acm_highlight_boxes',
            __('Highlight Boxes', 'advanced-course-manager'),
            [$this, 'render_meta_box'],
            'acm_lesson',
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('acm_highlight_boxes_nonce', 'acm_highlight_boxes_nonce');

        $boxes = get_post_meta($post->ID, '_acm_highlight_boxes', true);
        if (!is_array($boxes)) {
            $boxes = [];
        }
        ?>

        <div id="acm-highlight-boxes-wrapper">
            <?php foreach ($boxes as $box_index => $box): ?>
                <?php $this->render_box($box_index, $box); ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="acm-add-highlight-box">
            + Add Highlight Box
        </button>

        <style>
            .acm-highlight-box {
                border: 2px solid #ddd;
                padding: 15px;
                margin-bottom: 15px;
                background: #fafafa;
            }
            .acm-highlight-parent {
                border: 1px solid #ccc;
                padding: 10px;
                margin-bottom: 10px;
                background: #fff;
            }
            .acm-highlight-child {
                display: flex;
                gap: 8px;
                margin-bottom: 5px;
            }
            .acm-highlight-child input {
                flex: 1;
            }
            .remove {
                color: #b32d2e;
            }
            input.acm_highlight_boxes_btm_spc {
                margin-bottom: 10px;
            }
        </style>

        <script>
        (function($){
            let boxIndex = <?php echo count($boxes); ?>;

            $('#acm-add-highlight-box').on('click', function(){
                $('#acm-highlight-boxes-wrapper').append(getBoxTemplate(boxIndex));
                boxIndex++;
            });

            $(document).on('click', '.add-parent', function(){
                let box = $(this).closest('.acm-highlight-box');
                let boxIndex = box.data('index');
                let parentIndex = box.find('.acm-highlight-parent').length;

                box.find('.parents').append(getParentTemplate(boxIndex, parentIndex));
            });

            $(document).on('click', '.add-child', function(){
                let parent = $(this).closest('.acm-highlight-parent');
                let boxIndex = parent.data('box');
                let parentIndex = parent.data('parent');

                parent.find('.children').append(`
                    <div class="acm-highlight-child">
                        <input type="text" name="acm_highlight_boxes[${boxIndex}][items][${parentIndex}][children][]" placeholder="Sub point" class="acm_highlight_boxes_btm_spc">
                        <button type="button" class="button remove">×</button>
                    </div>
                `);
            });

            $(document).on('click', '.remove', function(){
                $(this).closest('div').remove();
            });

            function getBoxTemplate(index){
                return `
                <div class="acm-highlight-box" data-index="${index}">
                    <div class="acm-shortcode-wrap" style="margin-bottom:10px;">
                        <label><strong>Shortcode:</strong></label>
                        <input type="text" readonly
                            class="acm-shortcode-field"
                            value='[acm_highlight_box index="${index}"]'
                            style="width:260px;">
                        <button type="button" class="button acm-copy-shortcode">
                            Copy
                        </button>
                    </div>

                    <input type="text" name="acm_highlight_boxes[${index}][heading]" placeholder="Highlight Box Heading" style="width:100%;font-weight:bold;margin-bottom:10px;">
                    <div class="parents"></div>
                    <button type="button" class="button add-parent">+ Add Point</button>
                </div>`;
            }

            function getParentTemplate(box, parent){
                return `
                <div class="acm-highlight-parent" data-box="${box}" data-parent="${parent}">
                    <input type="text" name="acm_highlight_boxes[${box}][items][${parent}][title]" placeholder="Main point" style="width:100%;" class="acm_highlight_boxes_btm_spc">
                    <div class="children"></div>
                    <button type="button" class="button add-child">+ Sub</button>
                    <button type="button" class="button remove">Remove</button>
                </div>`;
            }

            $(document).on('click', '.acm-copy-shortcode', function () {
                let input = $(this).siblings('.acm-shortcode-field')[0];
                input.select();
                document.execCommand('copy');

                $(this).text('Copied!');
                setTimeout(() => $(this).text('Copy'), 1500);
            });

        })(jQuery);
        </script>

        <?php
    }

    private function render_box($index, $box) {
        ?>
        <div class="acm-highlight-box" data-index="<?php echo $index; ?>">

            <!-- Shortcode Display -->
            <div class="acm-shortcode-wrap" style="margin-bottom:10px;">
                <label style="font-weight:600;">Shortcode:</label>
                <input type="text"
                       readonly
                       class="acm-shortcode-field"
                       value='[acm_highlight_box index="<?php echo esc_attr($index); ?>"]'
                       style="width:260px;">
                <button type="button" class="button acm-copy-shortcode">
                    Copy
                </button>
            </div>

            <input type="text"
                   name="acm_highlight_boxes[<?php echo $index; ?>][heading]"
                   value="<?php echo esc_attr($box['heading'] ?? ''); ?>"
                   placeholder="Highlight Box Heading"
                   style="width:100%;font-weight:bold;margin-bottom:10px;">

            <div class="parents">
                <?php foreach ($box['items'] ?? [] as $p => $item): ?>
                    <div class="acm-highlight-parent" data-box="<?php echo $index; ?>" data-parent="<?php echo $p; ?>">
                        <input type="text"
                               name="acm_highlight_boxes[<?php echo $index; ?>][items][<?php echo $p; ?>][title]"
                               value="<?php echo esc_attr($item['title']); ?>"
                               placeholder="Main point" style="width:100%;" class="acm_highlight_boxes_btm_spc">
                        <div class="children">
                            <?php foreach ($item['children'] ?? [] as $child): ?>
                                <div class="acm-highlight-child">
                                    <input type="text"
                                           name="acm_highlight_boxes[<?php echo $index; ?>][items][<?php echo $p; ?>][children][]"
                                           value="<?php echo esc_attr($child); ?>"
                                           placeholder="Sub point" class="acm_highlight_boxes_btm_spc">
                                    <button type="button" class="button remove">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button add-child">+ Sub</button>
                        <button type="button" class="button remove">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button add-parent">+ Add Point</button>
        </div>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['acm_highlight_boxes_nonce']) || !wp_verify_nonce($_POST['acm_highlight_boxes_nonce'], 'acm_highlight_boxes_nonce')) {
            return;
        }

        $clean = [];
        foreach ($_POST['acm_highlight_boxes'] ?? [] as $box) {
            if (empty($box['heading'])) continue;
            $items = [];
            foreach ($box['items'] ?? [] as $item) {
                if (empty($item['title'])) continue;

                $items[] = [
                    'title' => sanitize_text_field($item['title']),
                    'children' => array_map(
                        'sanitize_text_field',
                        array_filter($item['children'] ?? [])
                    )
                ];
            }
            $clean[] = [
                'heading' => sanitize_text_field($box['heading']),
                'items'   => $items
            ];
        }
        update_post_meta($post_id, '_acm_highlight_boxes', $clean);
    }
}

new ACM_Lesson_Highlight_Box();