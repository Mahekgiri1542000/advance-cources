<?php
/**
 * Alternative Certificate System - Using HTML2PDF or Browser Print
 * File: includes/class-acm-certificate-simple.php
 * 
 * This version creates an HTML certificate that can be printed as PDF
 * No external libraries required!
 */

if (!defined('ABSPATH')) exit;

class ACM_Certificate_Simple {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_acm_view_certificate', array($this, 'view_certificate_ajax'));
        add_action('wp_ajax_acm_generate_certificate_simple', array($this, 'generate_certificate_ajax'));
        add_action('wp_ajax_acm_email_certificate_simple', array($this, 'email_certificate_ajax'));
        
        // Add certificate template page
        add_action('template_redirect', array($this, 'certificate_template'));
    }
    
    /**
     * Check if course is completed
     */
    public function is_course_completed($user_id, $course_id) {
        $lessons = ACM_Progress::get_instance()->get_course_lessons($course_id);
        
        if (empty($lessons)) {
            return false;
        }
        
        foreach ($lessons as $lesson) {
            $progress = ACM_Progress::get_instance()->get_lesson_progress($user_id, $lesson->ID);
            if (!$progress || $progress->status !== 'completed') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get or create certificate
     */
    public function get_certificate($user_id, $course_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        if (!$certificate && $this->is_course_completed($user_id, $course_id)) {
            $certificate_code = $this->generate_certificate_code($user_id, $course_id);
            
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'certificate_code' => $certificate_code,
                'issued_date' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ));
            
            $certificate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $wpdb->insert_id
            ));
        }
        
        return $certificate;
    }
    
    /**
     * Generate certificate code
     */
    private function generate_certificate_code($user_id, $course_id) {
        return 'CERT-' . strtoupper(substr(md5($user_id . $course_id . time()), 0, 12));
    }
    
    /**
     * Get certificate URL
     */
    public function get_certificate_url($certificate_id, $download = false) {
        $url = add_query_arg(array(
            'acm_certificate' => $certificate_id,
            'action' => $download ? 'download' : 'view'
        ), home_url());
        
        return $url;
    }
    
    /**
     * Certificate template handler
     */
    public function certificate_template() {
        if (!isset($_GET['acm_certificate'])) {
            return;
        }
        
        $certificate_id = intval($_GET['acm_certificate']);
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'view';
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $certificate_id
        ));
        
        if (!$certificate) {
            wp_die(__('Certificate not found', 'advanced-course-manager'));
        }
        
        // Get data
        $user = get_userdata($certificate->user_id);
        $course = get_post($certificate->course_id);
        
        // Output certificate HTML
        $this->render_certificate($user, $course, $certificate, $action === 'download');
        exit;
    }
    
    /**
     * Render certificate HTML
     */
    private function render_certificate($user, $course, $certificate, $download = false) {
        $site_name = get_bloginfo('name');
        $user_name = $user->display_name;
        $course_title = $course->post_title;
        $issue_date = date('F d, Y', strtotime($certificate->issued_date));
        $cert_code = $certificate->certificate_code;
        
        // Get logo if available
        $logo_url = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Certificate of Completion - <?php echo esc_html($user_name); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Georgia', serif;
                    background: #f5f5f5;
                    padding: 20px;
                }
                
                .certificate-container {
                    width: 297mm;
                    height: 210mm;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                    position: relative;
                }
                
                .certificate-border {
                    border: 15px solid #db9563;
                    border-image: linear-gradient(45deg, #db9563, #f5b377) 1;
                    padding: 40px;
                    height: 100%;
                    position: relative;
                    background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
                }
                
                .certificate-inner-border {
                    border: 3px solid #db9563;
                    padding: 30px;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                
                .certificate-header {
                    text-align: center;
                }
                
                .certificate-logo {
                    width: 100px;
                    height: auto;
                    margin-bottom: 20px;
                }
                
                .site-name {
                    font-size: 28px;
                    font-weight: bold;
                    color: #db9563;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .certificate-title {
                    font-size: 56px;
                    font-weight: bold;
                    color: #db9563;
                    margin-bottom: 20px;
                    text-transform: uppercase;
                    letter-spacing: 4px;
                    font-family: 'Times New Roman', serif;
                }
                
                .certificate-subtitle {
                    font-size: 22px;
                    color: #666;
                    margin-bottom: 30px;
                    font-style: italic;
                }
                
                .certificate-body {
                    text-align: center;
                    flex-grow: 1;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
                
                .recipient-name {
                    font-size: 48px;
                    font-weight: bold;
                    color: #db9563;
                    margin: 30px 0;
                    border-bottom: 3px solid #db9563;
                    display: inline-block;
                    padding-bottom: 10px;
                    font-family: 'Brush Script MT', cursive;
                }
                
                .certificate-text {
                    font-size: 20px;
                    color: #333;
                    line-height: 1.8;
                    margin: 15px 0;
                }
                
                .course-name {
                    font-size: 32px;
                    font-weight: bold;
                    color: #333;
                    margin: 25px 0;
                    font-family: 'Georgia', serif;
                }
                
                .certificate-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    padding-top: 30px;
                    border-top: 2px solid #ddd;
                }
                
                .signature-section {
                    text-align: center;
                    flex: 1;
                }
                
                .signature-line {
                    width: 200px;
                    border-bottom: 2px solid #333;
                    margin: 0 auto 10px;
                    height: 50px;
                }
                
                .signature-label {
                    font-size: 16px;
                    color: #666;
                }
                
                .certificate-info {
                    text-align: center;
                    font-size: 14px;
                    color: #999;
                    margin-top: 20px;
                }
                
                .decorative-element {
                    position: absolute;
                    width: 100px;
                    height: 100px;
                    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="%23db9563" stroke-width="2"/><path d="M50 10 L60 40 L90 50 L60 60 L50 90 L40 60 L10 50 L40 40 Z" fill="%23db9563" opacity="0.2"/></svg>') no-repeat center;
                    opacity: 0.1;
                }
                
                .decorative-top-left {
                    top: 20px;
                    left: 20px;
                }
                
                .decorative-top-right {
                    top: 20px;
                    right: 20px;
                    transform: rotate(90deg);
                }
                
                .decorative-bottom-left {
                    bottom: 20px;
                    left: 20px;
                    transform: rotate(-90deg);
                }
                
                .decorative-bottom-right {
                    bottom: 20px;
                    right: 20px;
                    transform: rotate(180deg);
                }
                
                @media print {
                    body {
                        background: white;
                        padding: 0;
                    }
                    
                    .certificate-container {
                        box-shadow: none;
                        width: 100%;
                        height: 100vh;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
                
                .print-button {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 30px;
                    background: #db9563;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000;
                }
                
                .print-button:hover {
                    background: #c78555;
                }
            </style>
            <?php if ($download): ?>
            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
            <?php endif; ?>
        </head>
        <body>
            <?php if (!$download): ?>
            <button onclick="window.print()" class="print-button no-print">🖨️ Print / Save as PDF</button>
            <?php endif; ?>
            
            <div class="certificate-container">
                <div class="certificate-border">
                    <div class="decorative-element decorative-top-left"></div>
                    <div class="decorative-element decorative-top-right"></div>
                    <div class="decorative-element decorative-bottom-left"></div>
                    <div class="decorative-element decorative-bottom-right"></div>
                    
                    <div class="certificate-inner-border">
                        <div class="certificate-header">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="certificate-logo">
                            <?php endif; ?>
                            <div class="site-name"><?php echo esc_html($site_name); ?></div>
                            <div class="certificate-title">Certificate of Completion</div>
                            <div class="certificate-subtitle">This is to certify that</div>
                        </div>
                        
                        <div class="certificate-body">
                            <div class="recipient-name"><?php echo esc_html($user_name); ?></div>
                            
                            <div class="certificate-text">
                                has successfully completed the course
                            </div>
                            
                            <div class="course-name"><?php echo esc_html($course_title); ?></div>
                            
                            <div class="certificate-text">
                                demonstrating dedication, commitment,<br>
                                and mastery of the course material
                            </div>
                        </div>
                        
                        <div class="certificate-footer">
                            <div class="signature-section">
                                <div class="signature-line"></div>
                                <div class="signature-label">Date: <?php echo $issue_date; ?></div>
                            </div>
                            
                            <div class="signature-section">
                                <div class="signature-line"></div>
                                <div class="signature-label">Authorized Signature</div>
                            </div>
                        </div>
                        
                        <div class="certificate-info">
                            Certificate Code: <?php echo $cert_code; ?> | 
                            Verify at: <?php echo home_url('/verify-certificate/'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * AJAX: View certificate
     */
    public function view_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $certificate_id = intval($_POST['certificate_id']);
        
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate', 'advanced-course-manager')));
        }
        
        $url = $this->get_certificate_url($certificate_id, false);
        
        wp_send_json_success(array(
            'url' => $url,
            'message' => __('Certificate ready', 'advanced-course-manager')
        ));
    }
    
    /**
     * AJAX: Generate certificate
     */
    public function generate_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $course_id = intval($_POST['course_id']);
        
        if (!$this->is_course_completed($user_id, $course_id)) {
            wp_send_json_error(array('message' => __('Please complete all lessons first', 'advanced-course-manager')));
        }
        
        $certificate = $this->get_certificate($user_id, $course_id);
        
        if ($certificate) {
            wp_send_json_success(array(
                'message' => __('Certificate generated successfully', 'advanced-course-manager'),
                'certificate_id' => $certificate->id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to generate certificate', 'advanced-course-manager')));
        }
    }
    
    /**
     * AJAX: Email certificate
     */
    public function email_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $certificate_id = intval($_POST['certificate_id']);
        
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate', 'advanced-course-manager')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $certificate_id
        ));
        
        if (!$certificate) {
            wp_send_json_error(array('message' => __('Certificate not found', 'advanced-course-manager')));
        }
        
        $user = get_userdata($certificate->user_id);
        $course = get_post($certificate->course_id);
        $cert_url = $this->get_certificate_url($certificate_id, true);
        
        $to = $user->user_email;
        $subject = sprintf(__('Your Certificate of Completion - %s', 'advanced-course-manager'), $course->post_title);
        
        $message = sprintf(
            __('Congratulations %s!

You have successfully completed the course "%s".

View and download your certificate here:
%s

Certificate Code: %s

Best regards,
%s Team', 'advanced-course-manager'),
            $user->display_name,
            $course->post_title,
            $cert_url,
            $certificate->certificate_code,
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            $wpdb->update(
                $table,
                array('email_sent' => 1, 'email_sent_date' => current_time('mysql')),
                array('id' => $certificate_id)
            );
            
            wp_send_json_success(array('message' => __('Certificate sent to your email', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send email', 'advanced-course-manager')));
        }
    }
}

// Initialize
ACM_Certificate_Simple::get_instance();