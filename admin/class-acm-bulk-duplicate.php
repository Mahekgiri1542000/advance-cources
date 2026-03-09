<?php
if (!defined('ABSPATH')) {
    exit;
}

class ACM_Bulk_Duplicate {

    private $duplicate_post_types = ['acm_lesson', 'acm_course'];
    private $assign_course_post_types = ['acm_lesson', 'acm_chapter'];
    private $assign_chapter_post_types = ['acm_lesson'];
    private $is_internal_parent_update = false;

    public function __construct() {

        foreach ($this->duplicate_post_types as $post_type) {
            add_filter("bulk_actions-edit-{$post_type}", [$this, 'register_bulk_action']);
            add_filter("handle_bulk_actions-edit-{$post_type}", [$this, 'handle_bulk_action'], 10, 3);
        }

        foreach ($this->assign_course_post_types as $post_type) {
            add_filter("bulk_actions-edit-{$post_type}", [$this, 'register_bulk_action']);
            add_filter("handle_bulk_actions-edit-{$post_type}", [$this, 'handle_bulk_action'], 10, 3);
        }

        foreach ($this->assign_chapter_post_types as $post_type) {
            add_filter("bulk_actions-edit-{$post_type}", [$this, 'register_bulk_action']);
            add_filter("handle_bulk_actions-edit-{$post_type}", [$this, 'handle_bulk_action'], 10, 3);
        }

        add_action('admin_notices', [$this, 'admin_notice']);
        add_action('restrict_manage_posts', [$this, 'render_bulk_course_selector'], 10, 2);
        add_action('bulk_edit_custom_box', [$this, 'render_bulk_edit_fields'], 10, 2);
        add_action('save_post_acm_chapter', [$this, 'handle_chapter_bulk_edit_save'], 20, 3);
        add_action('save_post_acm_lesson', [$this, 'handle_lesson_bulk_edit_save'], 20, 3);
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

        if (in_array($screen->post_type, $this->duplicate_post_types, true)) {
            $actions['acm_duplicate'] = sprintf(
                __('Duplicate Selected %s', 'advanced-course-manager'),
                $label
            );
        }

        if (in_array($screen->post_type, $this->assign_course_post_types, true)) {
            $actions['acm_assign_course'] = sprintf(
                __('Assign Selected %s to Course', 'advanced-course-manager'),
                $label
            );
        }

        if (in_array($screen->post_type, $this->assign_chapter_post_types, true)) {
            $actions['acm_assign_chapter'] = __('Assign Selected Lessons to Chapter', 'advanced-course-manager');
        }

        return $actions;
    }

    public function render_bulk_course_selector($post_type, $which = '') {
        if (!in_array($post_type, $this->assign_course_post_types, true) && !in_array($post_type, $this->assign_chapter_post_types, true)) {
            return;
        }

        if ($which !== 'top') {
            return;
        }

        if (in_array($post_type, $this->assign_course_post_types, true)) {
            $courses = get_posts(array(
                'post_type'      => 'acm_course',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));

            if (!empty($courses)) {
                echo '<select name="acm_bulk_target_course" style="margin-left:6px;">';
                echo '<option value="">' . esc_html__('Select Course for Bulk Assign', 'advanced-course-manager') . '</option>';

                foreach ($courses as $course) {
                    printf(
                        '<option value="%d">%s</option>',
                        (int) $course->ID,
                        esc_html($course->post_title)
                    );
                }

                echo '</select>';
            }
        }

        if (in_array($post_type, $this->assign_chapter_post_types, true)) {
            $chapters = get_posts(array(
                'post_type'      => 'acm_chapter',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));

            if (!empty($chapters)) {
                echo '<select name="acm_bulk_target_chapter" style="margin-left:6px;">';
                echo '<option value="">' . esc_html__('Select Chapter for Bulk Assign', 'advanced-course-manager') . '</option>';

                foreach ($chapters as $chapter) {
                    $chapter_course_id = $this->get_chapter_course_id($chapter->ID);
                    $course_label = $chapter_course_id > 0 ? get_the_title($chapter_course_id) : __('No Course', 'advanced-course-manager');
                    $label = $course_label . ' -> ' . $chapter->post_title;

                    printf(
                        '<option value="%d">%s</option>',
                        (int) $chapter->ID,
                        esc_html($label)
                    );
                }

                echo '</select>';
            }
        }
    }

    public function render_bulk_edit_fields($column_name, $post_type) {
        static $rendered_for = array();

        if (!in_array($post_type, array('acm_chapter', 'acm_lesson'), true)) {
            return;
        }

        if (isset($rendered_for[$post_type])) {
            return;
        }

        $rendered_for[$post_type] = true;

        $courses = get_posts(array(
            'post_type'      => 'acm_course',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        echo '<fieldset class="inline-edit-col-right">';
        echo '<div class="inline-edit-col">';

        if (!empty($courses)) {
            echo '<label class="alignleft">';
            echo '<span class="title">' . esc_html__('Assign Course', 'advanced-course-manager') . '</span>';
            echo '<select name="acm_bulk_edit_target_course">';
            echo '<option value="">' . esc_html__('-- No Change --', 'advanced-course-manager') . '</option>';

            foreach ($courses as $course) {
                printf(
                    '<option value="%d">%s</option>',
                    (int) $course->ID,
                    esc_html($course->post_title)
                );
            }

            echo '</select>';
            echo '</label>';
        }

        if ($post_type === 'acm_lesson') {
            $chapters = get_posts(array(
                'post_type'      => 'acm_chapter',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ));

            if (!empty($chapters)) {
                echo '<label class="alignleft" style="margin-top:8px;">';
                echo '<span class="title">' . esc_html__('Assign Chapter', 'advanced-course-manager') . '</span>';
                echo '<select name="acm_bulk_edit_target_chapter">';
                echo '<option value="">' . esc_html__('-- No Change --', 'advanced-course-manager') . '</option>';

                foreach ($chapters as $chapter) {
                    $chapter_course_id = $this->get_chapter_course_id($chapter->ID);
                    $course_label = $chapter_course_id > 0 ? get_the_title($chapter_course_id) : __('No Course', 'advanced-course-manager');
                    $label = $course_label . ' -> ' . $chapter->post_title;

                    printf(
                        '<option value="%d">%s</option>',
                        (int) $chapter->ID,
                        esc_html($label)
                    );
                }

                echo '</select>';
                echo '</label>';
            }
        }

        echo '</div>';
        echo '</fieldset>';
    }

    public function handle_chapter_bulk_edit_save($post_id, $post, $update) {
        if ($this->is_internal_parent_update) {
            return;
        }

        if (!is_admin() || !current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_REQUEST['acm_bulk_edit_target_course']) || $_REQUEST['acm_bulk_edit_target_course'] === '') {
            return;
        }

        $course_id = (int) $_REQUEST['acm_bulk_edit_target_course'];
        if ($course_id <= 0 || get_post_type($course_id) !== 'acm_course') {
            return;
        }

        $this->set_chapter_course_relation($post_id, $course_id);
        $this->sync_post_memberpress_memberships($post_id, $course_id);
    }

    public function handle_lesson_bulk_edit_save($post_id, $post, $update) {
        if ($this->is_internal_parent_update) {
            return;
        }

        if (!is_admin() || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $target_chapter_id = (isset($_REQUEST['acm_bulk_edit_target_chapter']) && $_REQUEST['acm_bulk_edit_target_chapter'] !== '')
            ? (int) $_REQUEST['acm_bulk_edit_target_chapter']
            : 0;

        if ($target_chapter_id > 0 && get_post_type($target_chapter_id) === 'acm_chapter') {
            $target_course_id = $this->get_chapter_course_id($target_chapter_id);
            if ($target_course_id > 0) {
                $this->assign_lesson_to_chapter($post_id, $target_chapter_id, $target_course_id);
                return;
            }
        }

        if (!isset($_REQUEST['acm_bulk_edit_target_course']) || $_REQUEST['acm_bulk_edit_target_course'] === '') {
            return;
        }

        $course_id = (int) $_REQUEST['acm_bulk_edit_target_course'];
        if ($course_id <= 0 || get_post_type($course_id) !== 'acm_course') {
            return;
        }

        $this->set_lesson_course_relation($post_id, $course_id);
        $this->sync_post_memberpress_memberships($post_id, $course_id);
    }

    /**
     * Handle bulk duplicate
     */
    public function handle_bulk_action($redirect_url, $action, $post_ids) {

        if ($action === 'acm_assign_course') {
            if (!current_user_can('edit_posts')) {
                return $redirect_url;
            }

            $target_course_id = isset($_REQUEST['acm_bulk_target_course'])
                ? (int) $_REQUEST['acm_bulk_target_course']
                : 0;

            if ($target_course_id <= 0 || get_post_type($target_course_id) !== 'acm_course') {
                return add_query_arg('acm_assign_course_error', 'missing_course', $redirect_url);
            }

            $count = 0;
            foreach ($post_ids as $post_id) {
                if ($this->assign_post_to_course((int) $post_id, $target_course_id)) {
                    $count++;
                }
            }

            return add_query_arg(
                array(
                    'acm_assigned_course' => $count,
                    'acm_target_course'   => $target_course_id,
                ),
                $redirect_url
            );
        }

        if ($action === 'acm_assign_chapter') {
            if (!current_user_can('edit_posts')) {
                return $redirect_url;
            }

            $target_chapter_id = isset($_REQUEST['acm_bulk_target_chapter'])
                ? (int) $_REQUEST['acm_bulk_target_chapter']
                : 0;

            if ($target_chapter_id <= 0 || get_post_type($target_chapter_id) !== 'acm_chapter') {
                return add_query_arg('acm_assign_chapter_error', 'missing_chapter', $redirect_url);
            }

            $target_course_id = $this->get_chapter_course_id($target_chapter_id);
            if ($target_course_id <= 0) {
                return add_query_arg('acm_assign_chapter_error', 'missing_course', $redirect_url);
            }

            $count = 0;
            foreach ($post_ids as $post_id) {
                $post_id = (int) $post_id;
                if (get_post_type($post_id) !== 'acm_lesson') {
                    continue;
                }

                if ($this->assign_lesson_to_chapter($post_id, $target_chapter_id, $target_course_id)) {
                    $count++;
                }
            }

            return add_query_arg(
                array(
                    'acm_assigned_chapter' => $count,
                    'acm_target_chapter'   => $target_chapter_id,
                ),
                $redirect_url
            );
        }

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

    private function assign_post_to_course($post_id, $course_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        if ($post->post_type === 'acm_chapter') {
            $this->set_chapter_course_relation($post_id, $course_id);
            $this->sync_post_memberpress_memberships($post_id, $course_id);
            return true;
        }

        if ($post->post_type === 'acm_lesson') {
            $this->set_lesson_course_relation($post_id, $course_id);
            $this->sync_post_memberpress_memberships($post_id, $course_id);
            return true;
        }

        return false;
    }

    private function set_chapter_course_relation($chapter_id, $course_id) {
        delete_post_meta($chapter_id, '_acm_chapter_course');
        delete_post_meta($chapter_id, 'acm_chapter_course');
        delete_post_meta($chapter_id, 'course_id');

        update_post_meta($chapter_id, '_acm_chapter_course', $course_id);
        update_post_meta($chapter_id, 'acm_chapter_course', $course_id);
        update_post_meta($chapter_id, 'course_id', $course_id);

        $this->update_post_parent($chapter_id, $course_id);
    }

    private function set_lesson_course_relation($lesson_id, $course_id) {
        $chapter_id = $this->get_lesson_chapter_id($lesson_id);
        $chapter_course_id = $chapter_id > 0 ? $this->get_chapter_course_id($chapter_id) : 0;

        if ($chapter_id > 0 && $chapter_course_id !== $course_id) {
            delete_post_meta($lesson_id, '_acm_lesson_chapter');
            delete_post_meta($lesson_id, 'acm_lesson_chapter');
            $chapter_id = 0;
        }

        delete_post_meta($lesson_id, '_acm_lesson_course');
        delete_post_meta($lesson_id, 'acm_lesson_course');
        delete_post_meta($lesson_id, 'course_id');

        update_post_meta($lesson_id, '_acm_lesson_course', $course_id);
        update_post_meta($lesson_id, 'acm_lesson_course', $course_id);
        update_post_meta($lesson_id, 'course_id', $course_id);

        $this->update_post_parent($lesson_id, $chapter_id > 0 ? $chapter_id : $course_id);
    }

    private function assign_lesson_to_chapter($lesson_id, $chapter_id, $course_id) {
        delete_post_meta($lesson_id, '_acm_lesson_chapter');
        delete_post_meta($lesson_id, 'acm_lesson_chapter');
        delete_post_meta($lesson_id, '_acm_lesson_course');
        delete_post_meta($lesson_id, 'acm_lesson_course');
        delete_post_meta($lesson_id, 'course_id');

        update_post_meta($lesson_id, '_acm_lesson_chapter', $chapter_id);
        update_post_meta($lesson_id, 'acm_lesson_chapter', $chapter_id);
        update_post_meta($lesson_id, '_acm_lesson_course', $course_id);
        update_post_meta($lesson_id, 'acm_lesson_course', $course_id);
        update_post_meta($lesson_id, 'course_id', $course_id);

        $this->update_post_parent($lesson_id, $chapter_id);

        $this->sync_post_memberpress_memberships($lesson_id, $course_id);
        return true;
    }

    private function get_lesson_chapter_id($lesson_id) {
        $chapter_id = (int) get_post_meta($lesson_id, '_acm_lesson_chapter', true);
        if ($chapter_id <= 0) {
            $chapter_id = (int) get_post_meta($lesson_id, 'acm_lesson_chapter', true);
        }

        if ($chapter_id <= 0) {
            $parent_id = (int) wp_get_post_parent_id($lesson_id);
            if ($parent_id > 0 && get_post_type($parent_id) === 'acm_chapter') {
                $chapter_id = $parent_id;
            }
        }

        return $chapter_id;
    }

    private function get_chapter_course_id($chapter_id) {
        $course_id = (int) get_post_meta($chapter_id, '_acm_chapter_course', true);
        if ($course_id <= 0) {
            $course_id = (int) get_post_meta($chapter_id, 'acm_chapter_course', true);
        }
        if ($course_id <= 0) {
            $course_id = (int) get_post_meta($chapter_id, 'course_id', true);
        }
        if ($course_id <= 0) {
            $course_id = (int) wp_get_post_parent_id($chapter_id);
        }

        return $course_id;
    }

    private function sync_post_memberpress_memberships($post_id, $course_id) {
        $memberships = get_post_meta($course_id, '_acm_memberpress_memberships', true);

        if (is_array($memberships) && !empty($memberships)) {
            update_post_meta($post_id, '_acm_memberpress_memberships', array_map('intval', $memberships));
            return;
        }

        delete_post_meta($post_id, '_acm_memberpress_memberships');
    }

    private function update_post_parent($post_id, $parent_id) {
        $post_id = (int) $post_id;
        $parent_id = (int) $parent_id;

        if ($post_id <= 0) {
            return;
        }

        if ((int) wp_get_post_parent_id($post_id) === $parent_id) {
            return;
        }

        $this->is_internal_parent_update = true;
        wp_update_post(array(
            'ID'          => $post_id,
            'post_parent' => $parent_id,
        ));
        $this->is_internal_parent_update = false;
    }

    /**
     * Duplicate post (course or lesson)
     */
    private function duplicate_post($post_id) {

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        if ($post->post_type === 'acm_course') {
            return $this->duplicate_course_tree($post_id);
        }

        return $this->create_duplicate_post($post);
    }

    /**
     * Duplicate a full course tree (course -> chapters -> lessons).
     */
    private function duplicate_course_tree($course_id) {
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'acm_course') {
            return false;
        }

        $new_course_id = $this->create_duplicate_post($course);
        if (!$new_course_id) {
            return false;
        }

        $course_memberships = get_post_meta($new_course_id, '_acm_memberpress_memberships', true);
        if (!is_array($course_memberships)) {
            $course_memberships = array();
        }

        $chapter_id_map = array();
        $duplicated_old_lesson_ids = array();

        $chapter_ids = $this->get_course_chapter_ids($course_id);
        foreach ($chapter_ids as $old_chapter_id) {
            $chapter = get_post($old_chapter_id);
            if (!$chapter || $chapter->post_type !== 'acm_chapter') {
                continue;
            }

            $new_chapter_id = $this->create_duplicate_post($chapter);
            if (!$new_chapter_id) {
                continue;
            }

            $chapter_id_map[$old_chapter_id] = $new_chapter_id;
            delete_post_meta($new_chapter_id, '_acm_chapter_course');
            delete_post_meta($new_chapter_id, 'acm_chapter_course');
            delete_post_meta($new_chapter_id, 'course_id');
            update_post_meta($new_chapter_id, '_acm_chapter_course', $new_course_id);
            update_post_meta($new_chapter_id, 'acm_chapter_course', $new_course_id);
            update_post_meta($new_chapter_id, 'course_id', $new_course_id);
            wp_update_post(array(
                'ID'          => $new_chapter_id,
                'post_parent' => $new_course_id,
            ));

            if (!empty($course_memberships)) {
                update_post_meta($new_chapter_id, '_acm_memberpress_memberships', $course_memberships);
            } else {
                delete_post_meta($new_chapter_id, '_acm_memberpress_memberships');
            }

            $lesson_ids = $this->get_chapter_lesson_ids($old_chapter_id);
            foreach ($lesson_ids as $old_lesson_id) {
                $lesson = get_post($old_lesson_id);
                if (!$lesson || $lesson->post_type !== 'acm_lesson') {
                    continue;
                }

                $new_lesson_id = $this->create_duplicate_post($lesson);
                if (!$new_lesson_id) {
                    continue;
                }

                delete_post_meta($new_lesson_id, '_acm_lesson_chapter');
                delete_post_meta($new_lesson_id, 'acm_lesson_chapter');
                delete_post_meta($new_lesson_id, '_acm_lesson_course');
                delete_post_meta($new_lesson_id, 'acm_lesson_course');
                delete_post_meta($new_lesson_id, 'course_id');
                update_post_meta($new_lesson_id, '_acm_lesson_chapter', $new_chapter_id);
                update_post_meta($new_lesson_id, 'acm_lesson_chapter', $new_chapter_id);
                update_post_meta($new_lesson_id, '_acm_lesson_course', $new_course_id);
                update_post_meta($new_lesson_id, 'acm_lesson_course', $new_course_id);
                update_post_meta($new_lesson_id, 'course_id', $new_course_id);
                wp_update_post(array(
                    'ID'          => $new_lesson_id,
                    'post_parent' => $new_chapter_id,
                ));

                if (!empty($course_memberships)) {
                    update_post_meta($new_lesson_id, '_acm_memberpress_memberships', $course_memberships);
                } else {
                    delete_post_meta($new_lesson_id, '_acm_memberpress_memberships');
                }

                $duplicated_old_lesson_ids[$old_lesson_id] = true;
            }
        }

        // Duplicate lessons attached directly to a course (without a chapter relation).
        $direct_lesson_ids = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'   => '_acm_lesson_course',
                    'value' => $course_id,
                ),
            ),
        ));

        foreach ($direct_lesson_ids as $old_lesson_id) {
            if (isset($duplicated_old_lesson_ids[$old_lesson_id])) {
                continue;
            }

            $lesson = get_post($old_lesson_id);
            if (!$lesson || $lesson->post_type !== 'acm_lesson') {
                continue;
            }

            $new_lesson_id = $this->create_duplicate_post($lesson);
            if (!$new_lesson_id) {
                continue;
            }

            delete_post_meta($new_lesson_id, '_acm_lesson_chapter');
            delete_post_meta($new_lesson_id, 'acm_lesson_chapter');
            delete_post_meta($new_lesson_id, '_acm_lesson_course');
            delete_post_meta($new_lesson_id, 'acm_lesson_course');
            delete_post_meta($new_lesson_id, 'course_id');

            $old_lesson_chapter_id = (int) get_post_meta($old_lesson_id, '_acm_lesson_chapter', true);
            if ($old_lesson_chapter_id > 0 && isset($chapter_id_map[$old_lesson_chapter_id])) {
                update_post_meta($new_lesson_id, '_acm_lesson_chapter', $chapter_id_map[$old_lesson_chapter_id]);
                update_post_meta($new_lesson_id, 'acm_lesson_chapter', $chapter_id_map[$old_lesson_chapter_id]);
                wp_update_post(array(
                    'ID'          => $new_lesson_id,
                    'post_parent' => $chapter_id_map[$old_lesson_chapter_id],
                ));
            }

            update_post_meta($new_lesson_id, '_acm_lesson_course', $new_course_id);
            update_post_meta($new_lesson_id, 'acm_lesson_course', $new_course_id);
            update_post_meta($new_lesson_id, 'course_id', $new_course_id);

            if (!empty($course_memberships)) {
                update_post_meta($new_lesson_id, '_acm_memberpress_memberships', $course_memberships);
            } else {
                delete_post_meta($new_lesson_id, '_acm_memberpress_memberships');
            }
        }

        return true;
    }

    /**
     * Create a duplicate of a single post and copy its taxonomy/meta data.
     */
    private function create_duplicate_post($post) {
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

        // Copy taxonomies.
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'ids']);
            wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }

        // Copy post meta.
        $meta = get_post_meta($post->ID);
        foreach ($meta as $key => $values) {
            if (in_array($key, array('_edit_lock', '_edit_last'), true)) {
                continue;
            }

            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        return true;
    }

    private function get_course_chapter_ids($course_id) {
        $meta_linked_ids = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_acm_chapter_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'acm_chapter_course',
                    'value' => $course_id,
                ),
                array(
                    'key'   => 'course_id',
                    'value' => $course_id,
                ),
            ),
        ));

        $parent_linked_ids = get_posts(array(
            'post_type'      => 'acm_chapter',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_parent'    => (int) $course_id,
        ));

        return array_values(array_unique(array_map('intval', array_merge($meta_linked_ids, $parent_linked_ids))));
    }

    private function get_chapter_lesson_ids($chapter_id) {
        $meta_linked_ids = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_acm_lesson_chapter',
                    'value' => $chapter_id,
                ),
                array(
                    'key'   => 'acm_lesson_chapter',
                    'value' => $chapter_id,
                ),
            ),
        ));

        $parent_linked_ids = get_posts(array(
            'post_type'      => 'acm_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_parent'    => (int) $chapter_id,
        ));

        return array_values(array_unique(array_map('intval', array_merge($meta_linked_ids, $parent_linked_ids))));
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

        if (!empty($_GET['acm_assign_course_error']) && $_GET['acm_assign_course_error'] === 'missing_course') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html__('Please select a target course from the dropdown before applying bulk assign.', 'advanced-course-manager') . '</p>';
            echo '</div>';
        }

        if (!empty($_GET['acm_assign_chapter_error']) && $_GET['acm_assign_chapter_error'] === 'missing_chapter') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html__('Please select a target chapter from the dropdown before applying bulk assign.', 'advanced-course-manager') . '</p>';
            echo '</div>';
        }

        if (!empty($_GET['acm_assign_chapter_error']) && $_GET['acm_assign_chapter_error'] === 'missing_course') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html__('Selected chapter is not linked to a course. Link the chapter to a course first.', 'advanced-course-manager') . '</p>';
            echo '</div>';
        }

        if (!empty($_GET['acm_assigned_course'])) {
            $count = intval($_GET['acm_assigned_course']);
            $target_course_id = !empty($_GET['acm_target_course']) ? intval($_GET['acm_target_course']) : 0;
            $target_course_label = $target_course_id > 0 ? get_the_title($target_course_id) : __('selected course', 'advanced-course-manager');

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html(sprintf(
                __('%1$d item(s) reassigned to course: %2$s.', 'advanced-course-manager'),
                $count,
                $target_course_label
            )) . '</p>';
            echo '</div>';
        }

        if (!empty($_GET['acm_assigned_chapter'])) {
            $count = intval($_GET['acm_assigned_chapter']);
            $target_chapter_id = !empty($_GET['acm_target_chapter']) ? intval($_GET['acm_target_chapter']) : 0;
            $target_chapter_label = $target_chapter_id > 0 ? get_the_title($target_chapter_id) : __('selected chapter', 'advanced-course-manager');

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html(sprintf(
                __('%1$d lesson(s) reassigned to chapter: %2$s.', 'advanced-course-manager'),
                $count,
                $target_chapter_label
            )) . '</p>';
            echo '</div>';
        }
    }
}

new ACM_Bulk_Duplicate();
