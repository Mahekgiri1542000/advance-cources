<?php
/**
 * Settings Class
 * File: admin/class-acm-settings.php
 */
if (!defined('ABSPATH')) {
    exit;
}

class ACM_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Settings are handled in ACM_Admin class
    }
}