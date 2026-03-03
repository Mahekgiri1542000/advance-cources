<?php
/**
 * Lesson Callout Boxes (WYSIWYG + Infinite + Shortcode)
 * File: admin/class-acm-lesson-callout-box.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Lesson_Callout_Box {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_acm_lesson', [$this, 'save_meta']);

        // IMPORTANT: load editor scripts for dynamic editors
        add_action('admin_enqueue_scripts', [$this, 'enqueue_editor_assets']);
    }

    public function enqueue_editor_assets($hook) {

        // Only post edit screens
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'acm_lesson') {
            return;
        }

        // Loads TinyMCE + Quicktags + wp.editor
        wp_enqueue_editor();
    }

    public function register_meta_box() {
        add_meta_box(
            'acm_callout_boxes',
            __('Callout Boxes (Editor)', 'advanced-course-manager'),
            [$this, 'render_meta_box'],
            'acm_lesson',
            'normal',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('acm_callout_boxes_nonce', 'acm_callout_boxes_nonce');

        $boxes = get_post_meta($post->ID, '_acm_callout_boxes', true);
        if (!is_array($boxes)) $boxes = [];
        ?>

        <div id="acm-callout-boxes-wrapper">
            <?php foreach ($boxes as $index => $content): ?>
                <?php $this->render_single_box($index, $content); ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="acm-add-callout-box">
            + Add Callout Box
        </button>

        <style>
            .acm-callout-admin-box {
                border: 2px solid #ddd;
                background: #fff;
                padding: 14px;
                margin-bottom: 16px;
                border-radius: 10px;
            }
            .acm-callout-admin-top {
                display: flex;
                gap: 10px;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
                flex-wrap: wrap;
            }
            .acm-callout-shortcode-wrap {
                display: flex;
                gap: 8px;
                align-items: center;
                flex-wrap: wrap;
            }
            .acm-callout-shortcode-field {
                width: 320px;
            }
            .acm-callout-remove-box {
                color: #b32d2e;
                border-color: #b32d2e;
            }
        </style>

        <script>
        (function($){

            function initCalloutEditor(editorId) {

                // Avoid double init
                if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                    return;
                }

                if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            height: 180,
                            wpautop: true,
                            toolbar1: 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
                            toolbar2: '',
                        },
                        quicktags: true,
                        mediaButtons: false
                    });
                }
            }

            // Copy shortcode
            $(document).on('click', '.acm-callout-copy-shortcode', function(){
                let input = $(this).siblings('.acm-callout-shortcode-field')[0];
                input.select();
                input.setSelectionRange(0, 99999);
                document.execCommand('copy');

                $(this).text('Copied!');
                setTimeout(() => $(this).text('Copy'), 1200);
            });

            // Add new callout box
            $('#acm-add-callout-box').on('click', function(){
                let wrapper = $('#acm-callout-boxes-wrapper');
                let index = wrapper.find('.acm-callout-admin-box').length;
                let editorId = 'acm_callout_editor_' + index;

                let html = `
                <div class="acm-callout-admin-box" data-index="${index}">
                    <div class="acm-callout-admin-top">
                        <div class="acm-callout-shortcode-wrap">
                            <strong>Shortcode:</strong>
                            <input type="text" readonly class="acm-callout-shortcode-field"
                                value='[acm_callout_box index="${index}"]'>
                            <button type="button" class="button acm-callout-copy-shortcode">Copy</button>
                        </div>

                        <button type="button" class="button acm-callout-remove-box">Remove Box</button>
                    </div>

                    <textarea id="${editorId}" class="acm-callout-editor-raw"
                        name="acm_callout_boxes[${index}]"></textarea>

                    <p style="margin-top:10px;color:#666;">
                        Use: <code>[acm_callout_box index="${index}"]</code> inside lesson content.
                    </p>
                </div>`;

                wrapper.append(html);

                // Init editor after small delay
                setTimeout(function(){
                    initCalloutEditor(editorId);
                }, 200);
            });

            // Remove callout box safely (remove editor instance)
            $(document).on('click', '.acm-callout-remove-box', function(){
                if (!confirm('Remove this callout box?')) return;

                let box = $(this).closest('.acm-callout-admin-box');
                let textarea = box.find('textarea');
                let editorId = textarea.attr('id');

                // Remove editor instance
                if (typeof wp !== 'undefined' && wp.editor && wp.editor.remove) {
                    wp.editor.remove(editorId);
                }

                box.remove();

                refreshIndexes();
            });

            // Re-index boxes after delete
            function refreshIndexes(){
                $('#acm-callout-boxes-wrapper .acm-callout-admin-box').each(function(newIndex){
                    let box = $(this);

                    // Remove existing editor before changing IDs/names
                    let textarea = box.find('textarea');
                    let oldId = textarea.attr('id');

                    if (typeof wp !== 'undefined' && wp.editor && wp.editor.remove && oldId) {
                        wp.editor.remove(oldId);
                    }

                    // Set new index
                    box.attr('data-index', newIndex);

                    // Update textarea id + name
                    let newEditorId = 'acm_callout_editor_' + newIndex;
                    textarea.attr('id', newEditorId);
                    textarea.attr('name', `acm_callout_boxes[${newIndex}]`);

                    // Update shortcode field
                    box.find('.acm-callout-shortcode-field')
                        .val(`[acm_callout_box index="${newIndex}"]`);

                    // Update tip text
                    box.find('code').text(`[acm_callout_box index="${newIndex}"]`);

                    // Re-init editor after reindex
                    setTimeout(function(){
                        initCalloutEditor(newEditorId);
                    }, 200);
                });
            }

        })(jQuery);
        </script>

        <?php
    }

    private function render_single_box($index, $content) {
        $content = is_string($content) ? $content : '';
        $editor_id = 'acm_callout_editor_' . $index;
        ?>
        <div class="acm-callout-admin-box" data-index="<?php echo esc_attr($index); ?>">

            <div class="acm-callout-admin-top">
                <div class="acm-callout-shortcode-wrap">
                    <strong>Shortcode:</strong>
                    <input type="text" readonly class="acm-callout-shortcode-field"
                        value='[acm_callout_box index="<?php echo esc_attr($index); ?>"]'>
                    <button type="button" class="button acm-callout-copy-shortcode">Copy</button>
                </div>

                <button type="button" class="button acm-callout-remove-box">Remove Box</button>
            </div>

            <?php
            wp_editor(
                $content,
                $editor_id,
                [
                    'textarea_name' => "acm_callout_boxes[$index]",
                    'textarea_rows' => 8,
                    'media_buttons' => false,
                    'teeny'         => false,
                    'quicktags'     => true,
                ]
            );
            ?>

            <p style="margin-top:10px;color:#666;">
                Use: <code>[acm_callout_box index="<?php echo esc_attr($index); ?>"]</code> inside lesson content.
            </p>
        </div>
        <?php
    }

    public function save_meta($post_id) {

        if (
            !isset($_POST['acm_callout_boxes_nonce']) ||
            !wp_verify_nonce($_POST['acm_callout_boxes_nonce'], 'acm_callout_boxes_nonce')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        $clean = [];

        if (!empty($_POST['acm_callout_boxes']) && is_array($_POST['acm_callout_boxes'])) {
            foreach ($_POST['acm_callout_boxes'] as $content) {
                $content = wp_kses_post($content);

                // Skip empty editor content
                if (trim(wp_strip_all_tags($content)) === '') {
                    continue;
                }

                $clean[] = $content;
            }
        }

        update_post_meta($post_id, '_acm_callout_boxes', $clean);
    }
}

new ACM_Lesson_Callout_Box();