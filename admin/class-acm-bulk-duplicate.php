<?php
if (!defined('ABSPATH')) {
    exit;
}

class ACM_Bulk_Duplicate {

    private $post_types = ['acm_lesson', 'acm_course'];

    public function __construct() {

        foreach ($this->post_types as $post_type) {
            add_filter("bulk_actions-edit-{$post_type}", [$this, 'register_bulk_action']);
            add_filter("handle_bulk_actions-edit-{$post_type}", [$this, 'handle_bulk_action'], 10, 3);
        }

        add_action('admin_notices', [$this, 'admin_notice']);
    }

    /**
     * Register bulk action
     */
    public function register_bulk_action($actions) {

        $screen = get_current_screen();
        if (!$screen || empty($screen->post_type)) {
            return $actions;
        }

        $post_type_object = get_post_type_object($screen->post_type);
        $label = $post_type_object && !empty($post_type_object->labels->name)
            ? $post_type_object->labels->name
            : __('Items', 'advanced-course-manager');

        $actions['acm_duplicate'] = sprintf(
            __('Duplicate Selected %s', 'advanced-course-manager'),
            $label
        );
        return $actions;
    }

    /**
     * Handle bulk duplicate
     */
    public function handle_bulk_action($redirect_url, $action, $post_ids) {

        if ($action !== 'acm_duplicate') {
            return $redirect_url;
        }

        if (!current_user_can('edit_posts')) {
            return $redirect_url;
        }

        $count = 0;

        foreach ($post_ids as $post_id) {
            if ($this->duplicate_post($post_id)) {
                $count++;
            }
        }

        return add_query_arg(
            'acm_duplicated',
            $count,
            $redirect_url
        );
    }

    /**
     * Duplicate post (course or lesson)
     */
    private function duplicate_post($post_id) {

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $new_post_id = wp_insert_post([
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
        ]);

        if (is_wp_error($new_post_id)) {
            return false;
        }

        // Copy taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
            wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }

        // Copy post meta
        $meta = get_post_meta($post_id);
        foreach ($meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        return true;
    }

    /**
     * Admin success notice
     */
    public function admin_notice() {

        if (!empty($_GET['acm_duplicated'])) {
            $count = intval($_GET['acm_duplicated']);

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html(sprintf(
                __('%d item(s) duplicated successfully.', 'advanced-course-manager'),
                $count
            )) . '</p>';
            echo '</div>';
        }
    }
}

new ACM_Bulk_Duplicate();
