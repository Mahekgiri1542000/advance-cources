<?php
/**
 * Certificate Generation Handler
 * File: includes/class-acm-certificate.php
 */

if (!defined('ABSPATH')) exit;

class ACM_Certificate {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_acm_generate_certificate', array($this, 'generate_certificate_ajax'));
        add_action('wp_ajax_acm_download_certificate', array($this, 'download_certificate_ajax'));
        add_action('wp_ajax_acm_email_certificate', array($this, 'email_certificate_ajax'));
    }
    
    /**
     * Check if user has completed all lessons in a course
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
     * Get or create certificate record
     */
    public function get_certificate($user_id, $course_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        // Check if certificate exists
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND course_id = %d",
            $user_id, $course_id
        ));
        
        // If not exists, create one
        if (!$certificate && $this->is_course_completed($user_id, $course_id)) {
            $certificate_code = $this->generate_certificate_code($user_id, $course_id);
            
            $wpdb->insert($table, array(
                'user_id'        => $user_id,
                'course_id'      => $course_id,
                'certificate_code' => $certificate_code,
                'issued_date'    => current_time('mysql'),
                'created_at'     => current_time('mysql')
            ));
            
            $certificate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $wpdb->insert_id
            ));
        }
        
        return $certificate;
    }
    
    /**
     * Generate unique certificate code
     */
    private function generate_certificate_code($user_id, $course_id) {
        return 'CERT-' . strtoupper(substr(md5($user_id . $course_id . time()), 0, 12));
    }
    
    /**
     * Generate certificate PDF
     * - A4 size
     * - Landscape
     * - Single page (no auto page breaks)
     */
    public function generate_pdf($certificate_id, $return_path = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $certificate_id
        ));
        
        if (!$certificate) {
            return false;
        }
        
        // Get user and course data
        $user   = get_userdata($certificate->user_id);
        $course = get_post($certificate->course_id);
        
        // Check if TCPDF library exists
        if (!class_exists('TCPDF')) {
            require_once(ABSPATH . 'wp-content/plugins/advanced-course-manager/includes/libraries/tcpdf/tcpdf.php');
        }
        
        // Create PDF - A4 landscape
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Advanced Course Manager');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle('Certificate of Completion');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins (tight enough to fit everything on one A4 page)
        $pdf->SetMargins(10, 10, 10);
        
        // IMPORTANT: single-page certificate, no automatic page breaks
        $pdf->SetAutoPageBreak(false, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Get certificate template HTML
        $html = $this->get_certificate_html($user, $course, $certificate);
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Define file path
        $upload_dir = wp_upload_dir();
        $cert_dir   = $upload_dir['basedir'] . '/certificates/';
        
        // Create directory if not exists
        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
        }
        
        $filename = 'certificate-' . $certificate->certificate_code . '.pdf';
        $filepath = $cert_dir . $filename;
        
        // Save PDF
        $pdf->Output($filepath, 'F');
        
        // Update database with file path
        $wpdb->update(
            $table,
            array('file_path' => $filename),
            array('id' => $certificate_id)
        );
        
        if ($return_path) {
            return $filepath;
        }
        
        return $upload_dir['baseurl'] . '/certificates/' . $filename;
    }
    
    /**
     * Get certificate HTML template
     * Carefully sized to fit A4 landscape in one page
     */
    private function get_certificate_html($user, $course, $certificate) {
        $site_name   = get_bloginfo('name');
        $user_name   = $user->display_name;
        $course_title = $course->post_title;
        $issue_date  = date('F d, Y', strtotime($certificate->issued_date));
        $cert_code   = $certificate->certificate_code;
        $verify_url  = home_url('/verify-certificate/');
        
        $html = '
        <style>
            body { font-family: "Helvetica", sans-serif; }

            .certificate-container {
                width: 100%;
                text-align: center;
                padding: 25px;
                border: 10px solid #db9563;
                background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
                box-sizing: border-box;
            }

            .certificate-border {
                border: 3px solid #db9563;
                padding: 20px 30px;
                box-sizing: border-box;
            }

            .certificate-header {
                margin-bottom: 15px;
            }

            .certificate-logo {
                font-size: 20px;
                font-weight: bold;
                color: #db9563;
                margin-bottom: 5px;
            }

            .certificate-title {
                font-size: 36px;
                font-weight: bold;
                color: #db9563;
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: 2px;
            }

            .certificate-subtitle {
                font-size: 16px;
                color: #666;
                margin-bottom: 15px;
            }

            .certificate-body {
                margin: 20px 0;
            }

            .certificate-text p{
                font-size: 14px;
                color: #333;
                line-height: 1.6;
                margin-bottom: 0px;
                margin-top: 0px;
            }

            .recipient-name {
                font-size: 26px;
                font-weight: bold;
                color: #db9563;
                margin: 12px 0;
                border-bottom: 2px solid #db9563;
                display: inline-block;
                padding-bottom: 4px;
                margin-top: 0px;
            }

            .course-name {
                font-size: 18px;
                font-weight: bold;
                color: #333;
                margin: 12px 0;
            }

            .certificate-footer {
                margin-top: 20px;
                padding-top: 12px;
                border-top: 1px solid #ddd;
            }

            .signature-section {
                display: table;
                width: 100%;
                margin-top: 15px;
            }

            .signature {
                display: table-cell;
                width: 50%;
                text-align: center;
                vertical-align: top;
            }

            .signature-line {
                width: 160px;
                border-bottom: 2px solid #333;
                margin: 0 auto 6px;
            }

            .signature-label {
                font-size: 11px;
                color: #666;
            }

            .certificate-code {
                font-size: 10px;
                color: #999;
                margin-top: 12px;
            }

            a.verify-link {
                color: #db9563;
                text-decoration: none;
            }

            a.verify-link:hover {
                text-decoration: underline;
            }
        </style>
        
        <div class="certificate-container">
            <div class="certificate-border">
                <div class="certificate-header">
                    <div class="certificate-logo">' . esc_html($site_name) . '</div>
                    <div class="certificate-title">Certificate of Completion</div>
                    <div class="certificate-subtitle">This is to certify that</div>
                </div>
                
                <div class="certificate-body">
                    <div class="recipient-name">' . esc_html($user_name) . '</div>
                    
                    <div class="certificate-text">
                        has successfully completed the course
                    </div>
                    
                    <div class="course-name">' . esc_html($course_title) . '</div>
                    
                    <div class="certificate-text">
                        <p>demonstrating dedication, commitment, and mastery of the course material.</p>
                        <p>Date: ' . $issue_date . '</p>
                        <p>Certificate Code: ' . $cert_code . '</p>
                    </div>
                </div>
            </div>
        </div>
        ';
        
        return $html;
    }
    
    /**
     * Send certificate via email
     */
    public function send_certificate_email($certificate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'acm_certificates';
        
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $certificate_id
        ));
        
        if (!$certificate) {
            return false;
        }
        
        $user   = get_userdata($certificate->user_id);
        $course = get_post($certificate->course_id);
        
        // Generate PDF if not exists
        $filepath = $this->generate_pdf($certificate_id, true);
        
        if (!$filepath || !file_exists($filepath)) {
            return false;
        }
        
        // Email content
        $to      = $user->user_email;
        $subject = sprintf(__('Your Certificate of Completion - %s', 'advanced-course-manager'), $course->post_title);
        
        $message = sprintf(
            __('Congratulations %s!

You have successfully completed the course "%s".

Please find your certificate of completion attached to this email.

Certificate Code: %s

You can also download your certificate anytime from your dashboard.

Best regards,
%s Team', 'advanced-course-manager'),
            $user->display_name,
            $course->post_title,
            $certificate->certificate_code,
            get_bloginfo('name')
        );
        
        $headers     = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array($filepath);
        
        // Send email
        $sent = wp_mail($to, $subject, nl2br($message), $headers, $attachments);
        
        if ($sent) {
            // Update email sent status
            $wpdb->update(
                $table,
                array(
                    'email_sent'      => 1,
                    'email_sent_date' => current_time('mysql')
                ),
                array('id' => $certificate_id)
            );
        }
        
        return $sent;
    }
    
    /**
     * AJAX: Generate Certificate
     */
    public function generate_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $user_id  = get_current_user_id();
        $course_id = intval($_POST['course_id']);
        
        if (!$user_id || !$course_id) {
            wp_send_json_error(array('message' => __('Invalid request', 'advanced-course-manager')));
        }
        
        // Check if course is completed
        if (!$this->is_course_completed($user_id, $course_id)) {
            wp_send_json_error(array('message' => __('Please complete all lessons first', 'advanced-course-manager')));
        }
        
        $certificate = $this->get_certificate($user_id, $course_id);
        
        if ($certificate) {
            wp_send_json_success(array(
                'message'          => __('Certificate generated successfully', 'advanced-course-manager'),
                'certificate_id'   => $certificate->id,
                'certificate_code' => $certificate->certificate_code
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to generate certificate', 'advanced-course-manager')));
        }
    }
    
    /**
     * AJAX: Download Certificate
     */
    public function download_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $certificate_id = intval($_POST['certificate_id']);
        
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate', 'advanced-course-manager')));
        }
        
        $pdf_url = $this->generate_pdf($certificate_id);
        
        if ($pdf_url) {
            wp_send_json_success(array(
                'download_url' => $pdf_url,
                'message'      => __('Certificate ready for download', 'advanced-course-manager')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to generate PDF', 'advanced-course-manager')));
        }
    }
    
    /**
     * AJAX: Email Certificate
     */
    public function email_certificate_ajax() {
        check_ajax_referer('acm_nonce', 'nonce');
        
        $certificate_id = intval($_POST['certificate_id']);
        
        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('Invalid certificate', 'advanced-course-manager')));
        }
        
        $sent = $this->send_certificate_email($certificate_id);
        
        if ($sent) {
            wp_send_json_success(array('message' => __('Certificate sent to your email', 'advanced-course-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send email', 'advanced-course-manager')));
        }
    }
}

// Initialize
ACM_Certificate::get_instance();
