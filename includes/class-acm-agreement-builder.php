<?php
/**
 * Agreement Builder Class
 * Allows users to track selections throughout the course to build their agreement
 * File: includes/class-acm-agreement-builder.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACM_Agreement_Builder {
    
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
        // Register AJAX handlers
        add_action('wp_ajax_acm_save_agreement_choice', array($this, 'save_choice'));
        add_action('wp_ajax_acm_get_agreement_choices', array($this, 'get_choices'));
        add_action('wp_ajax_acm_export_agreement', array($this, 'export_agreement'));
        add_action('wp_ajax_acm_clear_agreement', array($this, 'clear_agreement'));
        
        // Add shortcodes
        add_shortcode('acm_agreement_builder', array($this, 'agreement_builder_shortcode'));
        add_shortcode('acm_agreement_option', array($this, 'agreement_option_shortcode'));
    }
    
    /**
     * Get user's agreement choices
     */
    public function get_user_choices($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_agreement_choices';
        
        $choices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY section_order ASC, choice_order ASC",
            $user_id
        ), ARRAY_A);
        
        // Organize by section
        $organized = array();
        foreach ($choices as $choice) {
            $section = $choice['section_id'];
            if (!isset($organized[$section])) {
                $organized[$section] = array(
                    'title' => $choice['section_title'],
                    'choices' => array()
                );
            }
            $organized[$section]['choices'][] = $choice;
        }
        
        return $organized;
    }
    
    /**
     * Save user's agreement choice
     */
    public function save_choice() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'advanced-course-manager')));
        }
        
        $section_id = isset($_POST['section_id']) ? sanitize_text_field($_POST['section_id']) : '';
        $section_title = isset($_POST['section_title']) ? sanitize_text_field($_POST['section_title']) : '';
        $choice_id = isset($_POST['choice_id']) ? sanitize_text_field($_POST['choice_id']) : '';
        $choice_text = isset($_POST['choice_text']) ? sanitize_textarea_field($_POST['choice_text']) : '';
        $choice_value = isset($_POST['choice_value']) ? sanitize_textarea_field($_POST['choice_value']) : '';
        $is_checked = isset($_POST['is_checked']) ? (bool)$_POST['is_checked'] : false;
        $section_order = isset($_POST['section_order']) ? intval($_POST['section_order']) : 0;
        $choice_order = isset($_POST['choice_order']) ? intval($_POST['choice_order']) : 0;
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_agreement_choices';
        
        if ($is_checked) {
            // Add or update choice
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND section_id = %s AND choice_id = %s",
                $user_id, $section_id, $choice_id
            ));
            
            if ($existing) {
                $wpdb->update(
                    $table,
                    array(
                        'choice_text' => $choice_text,
                        'choice_value' => $choice_value,
                        'section_title' => $section_title,
                        'section_order' => $section_order,
                        'choice_order' => $choice_order,
                        'updated_date' => current_time('mysql')
                    ),
                    array('id' => $existing->id),
                    array('%s', '%s', '%s', '%d', '%d', '%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $table,
                    array(
                        'user_id' => $user_id,
                        'section_id' => $section_id,
                        'section_title' => $section_title,
                        'section_order' => $section_order,
                        'choice_id' => $choice_id,
                        'choice_text' => $choice_text,
                        'choice_value' => $choice_value,
                        'choice_order' => $choice_order,
                        'created_date' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s')
                );
            }
        } else {
            // Remove choice
            $wpdb->delete(
                $table,
                array(
                    'user_id' => $user_id,
                    'section_id' => $section_id,
                    'choice_id' => $choice_id
                ),
                array('%d', '%s', '%s')
            );
        }
        
        // Get updated count
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        wp_send_json_success(array(
            'message' => __('Choice saved', 'advanced-course-manager'),
            'total_choices' => $count
        ));
    }
    
    /**
     * Get user's choices via AJAX
     */
    public function get_choices() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $choices = $this->get_user_choices($user_id);
        
        wp_send_json_success(array('choices' => $choices));
    }
    
    /**
     * Export agreement as PDF or Word
     */
    public function export_agreement() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'pdf';
        
        $choices = $this->get_user_choices($user_id);
        
        if (empty($choices)) {
            wp_send_json_error(array('message' => __('No choices to export', 'advanced-course-manager')));
        }
        
        // Generate agreement document
        $content = $this->generate_agreement_content($choices, $user_id);
        
        // For now, return HTML content
        // In production, you'd use a PDF library like TCPDF or Dompdf
        wp_send_json_success(array(
            'content' => $content,
            'download_url' => $this->create_download_file($content, $format, $user_id)
        ));
    }
    
    /**
     * Clear all agreement choices
     */
    public function clear_agreement() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_agreement_choices';
        
        $wpdb->delete($table, array('user_id' => $user_id), array('%d'));
        
        wp_send_json_success(array('message' => __('Agreement cleared', 'advanced-course-manager')));
    }
    
    /**
     * Generate agreement content from choices
     */
    private function generate_agreement_content($choices, $user_id) {
        $user = get_userdata($user_id);
        $province = acm_get_user_province($user_id);
        $province_name = ACM_Province_Manager::get_instance()->get_provinces()[$province];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Relationship Agreement - <?php echo esc_html($province_name); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
                h1 { color: #333; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                .section { margin-bottom: 30px; }
                .choice { margin-left: 20px; margin-bottom: 10px; }
                .meta { color: #666; font-size: 14px; margin-bottom: 20px; }
                .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <h1>Relationship Agreement</h1>
            
            <div class="meta">
                <p><strong>Province:</strong> <?php echo esc_html($province_name); ?></p>
                <p><strong>Created by:</strong> <?php echo esc_html($user->display_name); ?></p>
                <p><strong>Date:</strong> <?php echo date_i18n(get_option('date_format')); ?></p>
            </div>
            
            <?php foreach ($choices as $section_id => $section): ?>
                <div class="section">
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <?php foreach ($section['choices'] as $choice): ?>
                        <div class="choice">
                            <p><strong>• <?php echo esc_html($choice['choice_text']); ?></strong></p>
                            <?php if (!empty($choice['choice_value']) && $choice['choice_value'] !== $choice['choice_text']): ?>
                                <p style="margin-left: 20px; color: #555;"><?php echo nl2br(esc_html($choice['choice_value'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="footer">
                <p>This agreement was created using the Relationship Agreements course.</p>
                <p><em>Note: This document is for informational purposes. Please consult with a lawyer for legal advice.</em></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Create downloadable file
     */
    private function create_download_file($content, $format, $user_id) {
        $upload_dir = wp_upload_dir();
        $acm_dir = $upload_dir['basedir'] . '/acm-agreements/';
        
        if (!file_exists($acm_dir)) {
            wp_mkdir_p($acm_dir);
        }
        
        $filename = 'agreement-' . $user_id . '-' . time() . '.' . ($format === 'pdf' ? 'pdf' : 'html');
        $filepath = $acm_dir . $filename;
        
        file_put_contents($filepath, $content);
        
        return $upload_dir['baseurl'] . '/acm-agreements/' . $filename;
    }
    
    /**
     * Agreement builder shortcode - displays user's current agreement
     */
    public function agreement_builder_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your agreement builder.', 'advanced-course-manager') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $choices = $this->get_user_choices($user_id);
        $quiz_answers = function_exists('acm_get_quiz_answers') ? acm_get_quiz_answers($user_id) : array();
        $quiz_answers = apply_filters('acm_agreement_builder_quiz_answers', $quiz_answers, $user_id);
        
        ob_start();
        ?>
        <div class="acm-agreement-builder">
            <div class="agreement-header">
                <h2><?php _e('Your Relationship Agreement', 'advanced-course-manager'); ?></h2>
                <div class="agreement-actions">
                    <button id="acm-export-agreement" class="acm-btn acm-btn-primary">
                        <?php _e('Export Agreement', 'advanced-course-manager'); ?>
                    </button>
                    <button id="acm-clear-agreement" class="acm-btn acm-btn-secondary">
                        <?php _e('Clear All', 'advanced-course-manager'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($choices)): ?>
                <div class="agreement-empty">
                    <p><?php _e('You haven\'t made any selections yet. As you go through the course, tick the options you want to include in your agreement.', 'advanced-course-manager'); ?></p>
                </div>
            <?php else: ?>
                <div class="agreement-content">
                    <?php foreach ($choices as $section_id => $section): ?>
                        <div class="agreement-section">
                            <h3><?php echo esc_html($section['title']); ?></h3>
                            <ul class="agreement-choices">
                                <?php foreach ($section['choices'] as $choice): ?>
                                    <li>
                                        <strong><?php echo esc_html($choice['choice_text']); ?></strong>
                                        <?php if (!empty($choice['choice_value']) && $choice['choice_value'] !== $choice['choice_text']): ?>
                                            <div class="choice-details">
                                                <?php echo nl2br(esc_html($choice['choice_value'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        window.acmAgreementQuizAnswers = <?php echo wp_json_encode($quiz_answers); ?>;

        jQuery(document).ready(function($) {
            $('#acm-export-agreement').on('click', function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acm_export_agreement',
                        nonce: '<?php echo wp_create_nonce('acm_nonce'); ?>',
                        format: 'html'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.open(response.data.download_url, '_blank');
                        }
                    }
                });
            });
            
            $('#acm-clear-agreement').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to clear all your choices?', 'advanced-course-manager'); ?>')) {
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acm_clear_agreement',
                        nonce: '<?php echo wp_create_nonce('acm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Agreement option shortcode - add to course content
     * Usage: [acm_agreement_option section="financial" section_title="Financial Matters" option="joint_account"]Option text[/acm_agreement_option]
     */
    public function agreement_option_shortcode($atts, $content = '') {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'section' => 'general',
            'section_title' => 'General',
            'option' => '',
            'section_order' => 0,
            'order' => 0
        ), $atts);
        
        $user_id = get_current_user_id();
        $choice_id = $atts['section'] . '_' . $atts['option'];
        
        // Check if already selected
        global $wpdb;
        $table = $wpdb->prefix . 'acm_agreement_choices';
        $is_selected = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND choice_id = %s",
            $user_id, $choice_id
        ));
        
        $content = do_shortcode($content);
        
        ob_start();
        ?>
        <div class="acm-agreement-option" data-section="<?php echo esc_attr($atts['section']); ?>">
            <label class="agreement-checkbox">
                <input type="checkbox" 
                       class="acm-agreement-choice"
                       data-section-id="<?php echo esc_attr($atts['section']); ?>"
                       data-section-title="<?php echo esc_attr($atts['section_title']); ?>"
                       data-section-order="<?php echo esc_attr($atts['section_order']); ?>"
                       data-choice-id="<?php echo esc_attr($choice_id); ?>"
                       data-choice-text="<?php echo esc_attr(wp_strip_all_tags($content)); ?>"
                       data-choice-order="<?php echo esc_attr($atts['order']); ?>"
                       <?php checked($is_selected); ?>>
                <span class="agreement-option-text"><?php echo $content; ?></span>
                <span class="agreement-checkmark">✓</span>
            </label>
        </div>
        <?php
        return ob_get_clean();
    }
}