<?php
/**
 * Plugin Name: WP Categories Page
 * Description: Verwaltung und Anzeige von Inhaltskategorien mit Shortcodes, WYSIWYG-Editoren und Styling-Optionen.
 * Version: 1.5.5
 * Author: Heiko Dotzauer
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPCP_PLUGIN_VERSION')) {
    define('WPCP_PLUGIN_VERSION', '1.5.5');
}

if (!class_exists('WP_Categories_Page')) {
    final class WP_Categories_Page {
        private static $instance = null;

        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct() {
            add_action('init', array($this, 'register_post_types'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
            add_action('save_post_wpcp_category', array($this, 'save_category_meta'));
            add_action('save_post_wpcp_detail', array($this, 'save_detail_meta'));
            add_action('admin_menu', array($this, 'register_admin_menu'));
            add_action('admin_menu', array($this, 'register_settings_page'));
            add_action('admin_init', array($this, 'register_settings'));
            add_shortcode('category_overview', array($this, 'shortcode_category_overview'));
            add_shortcode('category_detail', array($this, 'shortcode_category_detail'));
        }

        public function enqueue_admin_assets($hook_suffix) {
            if (in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
                wp_enqueue_media();
            }
        }

        public function register_post_types() {
            register_post_type(
                'wpcp_category',
                array(
                    'labels' => array(
                        'name' => __('Kategorien', 'wp-categories-page'),
                        'singular_name' => __('Kategorie', 'wp-categories-page'),
                        'add_new_item' => __('Neue Kategorie hinzufügen', 'wp-categories-page'),
                        'edit_item' => __('Kategorie bearbeiten', 'wp-categories-page'),
                    ),
                    'public' => true,
                    'show_ui' => true,
                    'menu_icon' => 'dashicons-category',
                    'supports' => array('title'),
                    'has_archive' => false,
                    'rewrite' => array('slug' => 'kategorien'),
                    'show_in_rest' => true,
                    'show_in_menu' => 'wpcp-main-menu',
                    'menu_position' => 20,
                )
            );

            register_post_type(
                'wpcp_detail',
                array(
                    'labels' => array(
                        'name' => __('Kategorie-Details', 'wp-categories-page'),
                        'singular_name' => __('Kategorie-Detail', 'wp-categories-page'),
                        'add_new_item' => __('Neues Kategorie-Detail hinzufügen', 'wp-categories-page'),
                        'edit_item' => __('Kategorie-Detail bearbeiten', 'wp-categories-page'),
                    ),
                    'public' => false,
                    'show_ui' => true,
                    'menu_icon' => 'dashicons-list-view',
                    'supports' => array('title'),
                    'has_archive' => false,
                    'show_in_rest' => true,
                    'show_in_menu' => 'wpcp-main-menu',
                    'menu_position' => 21,
                )
            );
        }

        public function register_admin_menu() {
            add_menu_page(
                __('Meine Kategorien', 'wp-categories-page'),
                __('Meine Kategorien', 'wp-categories-page'),
                'edit_posts',
                'wpcp-main-menu',
                array($this, 'render_main_menu_page'),
                'dashicons-category',
                20
            );
        }

        public function render_main_menu_page() {
            wp_safe_redirect(admin_url('edit.php?post_type=wpcp_category'));
            exit;
        }

        public function register_meta_boxes() {
            add_meta_box(
                'wpcp_category_meta',
                __('Kategorie-Details', 'wp-categories-page'),
                array($this, 'render_category_meta_box'),
                'wpcp_category',
                'normal',
                'high'
            );

            add_meta_box(
                'wpcp_detail_meta',
                __('Detail-Angaben', 'wp-categories-page'),
                array($this, 'render_detail_meta_box'),
                'wpcp_detail',
                'normal',
                'high'
            );
        }

        public function render_category_meta_box($post) {
            wp_nonce_field('wpcp_save_category_meta', 'wpcp_category_meta_nonce');

            $heading = get_post_meta($post->ID, 'wpcp_category_heading', true);
            $image_id = (int) get_post_meta($post->ID, 'wpcp_category_image_id', true);
            $image_url = get_post_meta($post->ID, 'wpcp_category_image_url', true);
            $image_background = get_post_meta($post->ID, 'wpcp_category_image_background', true);
            $content = get_post_meta($post->ID, 'wpcp_category_content', true);
            $description = get_post_meta($post->ID, 'wpcp_category_description', true);
            $detail_reference = get_post_meta($post->ID, 'wpcp_category_detail_reference', true);
            $category_id = get_post_meta($post->ID, 'wpcp_category_id', true);

            echo $this->render_media_picker_field('wpcp_category', $image_id, $image_url);
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wpcp_category_heading"><?php esc_html_e('Überschrift', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_category_heading" name="wpcp_category_heading" value="<?php echo esc_attr($heading); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_category_id"><?php esc_html_e('Kategorie ID', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_category_id" name="wpcp_category_id" value="<?php echo esc_attr($category_id); ?>" placeholder="wissen" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_category_detail_reference"><?php esc_html_e('Referenz auf Details', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_category_detail_reference" name="wpcp_category_detail_reference" value="<?php echo esc_attr($detail_reference); ?>" placeholder="#wissen" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_category_image_background"><?php esc_html_e('Bild-Hintergrund', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_category_image_background" name="wpcp_category_image_background" value="<?php echo esc_attr($image_background); ?>" placeholder="linear-gradient(140deg, #1f7a4d, #2aa6b8)" /></td>
                    </tr>
                    <tr>
                        <th colspan="2"><label><?php esc_html_e('Inhalt', 'wp-categories-page'); ?>:</label></th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php
                            wp_editor(
                                $content,
                                'wpcp_category_content',
                                array(
                                    'textarea_name' => 'wpcp_category_content',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                )
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2"><label><?php esc_html_e('Beschreibung', 'wp-categories-page'); ?>:</label></th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php
                            wp_editor(
                                $description,
                                'wpcp_category_description',
                                array(
                                    'textarea_name' => 'wpcp_category_description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                )
                            );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

        public function render_detail_meta_box($post) {
            wp_nonce_field('wpcp_save_detail_meta', 'wpcp_detail_meta_nonce');

            $image_id = (int) get_post_meta($post->ID, 'wpcp_detail_image_id', true);
            $image_url = get_post_meta($post->ID, 'wpcp_detail_image_url', true);
            $detail_url = get_post_meta($post->ID, 'wpcp_detail_url', true);
            $labels = get_post_meta($post->ID, 'wpcp_detail_labels', true);
            $description = get_post_meta($post->ID, 'wpcp_detail_description', true);
            $details_id = get_post_meta($post->ID, 'wpcp_detail_details_id', true);
            $detail_heading = get_post_meta($post->ID, 'wpcp_detail_heading', true);
            $selected_category_ref = get_post_meta($post->ID, 'wpcp_detail_category_ref', true);
            $categories = get_posts(
                array(
                    'post_type' => 'wpcp_category',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wpcp_detail_heading"><?php esc_html_e('Überschrift', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_detail_heading" name="wpcp_detail_heading" value="<?php echo esc_attr($detail_heading); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_detail_category_ref"><?php esc_html_e('Kategorie ID', 'wp-categories-page'); ?>:</label></th>
                        <td>
                            <select class="widefat" id="wpcp_detail_category_ref" name="wpcp_detail_category_ref">
                                <option value=""><?php esc_html_e('Bitte wählen', 'wp-categories-page'); ?></option>
                                <?php foreach ($categories as $category) : ?>
                                    <?php $category_meta_id = trim((string) get_post_meta($category->ID, 'wpcp_category_id', true)); ?>
                                    <?php if ($category_meta_id === '') { continue; } ?>
                                    <option value="<?php echo esc_attr($category_meta_id); ?>" <?php selected($selected_category_ref, $category_meta_id); ?>>
                                        <?php echo esc_html(get_the_title($category) . ' (' . $category_meta_id . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e('Bild', 'wp-categories-page'); ?>:</label></th>
                        <td>
                            <?php echo $this->render_media_picker_field('wpcp_detail', $image_id, $image_url); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_detail_url"><?php esc_html_e('URL', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="url" class="widefat" id="wpcp_detail_url" name="wpcp_detail_url" value="<?php echo esc_attr($detail_url); ?>" placeholder="https://..." /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_detail_labels"><?php esc_html_e('Labels', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_detail_labels" name="wpcp_detail_labels" value="<?php echo esc_attr($labels); ?>" placeholder="Label 1, Label 2" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpcp_detail_details_id"><?php esc_html_e('Details ID', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_detail_details_id" name="wpcp_detail_details_id" value="<?php echo esc_attr($details_id); ?>" placeholder="wissen-eintrag-1" /></td>
                    </tr>
                    <tr>
                        <th colspan="2"><label><?php esc_html_e('Beschreibung', 'wp-categories-page'); ?>:</label></th>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <?php
                            wp_editor(
                                $description,
                                'wpcp_detail_description',
                                array(
                                    'textarea_name' => 'wpcp_detail_description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                )
                            );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

        private function render_media_picker_field($prefix, $image_id, $image_url) {
            $image_id = (int) $image_id;
            $image_url = $image_url ?: wp_get_attachment_image_url($image_id, 'medium');
            $preview = !empty($image_url)
                ? '<img src="' . esc_url($image_url) . '" alt="" style="max-width: 220px; max-height: 120px; display: block; margin-bottom: 10px; border-radius: 8px; object-fit: cover;" />'
                : '<span style="display: inline-block; margin-bottom: 10px; color: #666;">' . esc_html__('Kein Bild ausgewählt.', 'wp-categories-page') . '</span>';

            ob_start();
            ?>
            <div class="wpcp-media-picker" data-prefix="<?php echo esc_attr($prefix); ?>">
                <input type="hidden" id="<?php echo esc_attr($prefix); ?>_image_id" name="<?php echo esc_attr($prefix); ?>_image_id" value="<?php echo esc_attr((string) $image_id); ?>" />
                <input type="hidden" id="<?php echo esc_attr($prefix); ?>_image_url" name="<?php echo esc_attr($prefix); ?>_image_url" value="<?php echo esc_attr((string) $image_url); ?>" />
                <div class="wpcp-media-preview"><?php echo $preview; ?></div>
                <button type="button" class="button wpcp-media-select"><?php esc_html_e('Bild auswählen', 'wp-categories-page'); ?></button>
                <button type="button" class="button wpcp-media-remove"><?php esc_html_e('Entfernen', 'wp-categories-page'); ?></button>
            </div>
            <script>
            jQuery(function ($) {
                $(document).on('click', '.wpcp-media-select', function (event) {
                    event.preventDefault();
                    var $picker = $(this).closest('.wpcp-media-picker');
                    var prefix = $picker.data('prefix');
                    var frame = wp.media({
                        title: '<?php esc_html_e('Bild auswählen', 'wp-categories-page'); ?>',
                        multiple: false,
                        library: { type: 'image' },
                        button: { text: '<?php esc_html_e('Bild verwenden', 'wp-categories-page'); ?>' }
                    });

                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#' + prefix + '_image_id').val(attachment.id || '');
                        $('#' + prefix + '_image_url').val(attachment.url || '');
                        if (attachment.url) {
                            $picker.find('.wpcp-media-preview').html('<img src="' + attachment.url + '" alt="" style="max-width: 220px; max-height: 120px; display: block; margin-bottom: 10px; border-radius: 8px; object-fit: cover;" />');
                        }
                    });

                    frame.open();
                });

                $(document).on('click', '.wpcp-media-remove', function (event) {
                    event.preventDefault();
                    var $picker = $(this).closest('.wpcp-media-picker');
                    var prefix = $picker.data('prefix');
                    $('#' + prefix + '_image_id').val('');
                    $('#' + prefix + '_image_url').val('');
                    $picker.find('.wpcp-media-preview').html('<span style="display: inline-block; margin-bottom: 10px; color: #666;">' + '<?php esc_html_e('Kein Bild ausgewählt.', 'wp-categories-page'); ?>' + '</span>');
                });
            });
            </script>
            <?php
            return ob_get_clean();
        }

        public function save_category_meta($post_id) {
            if (!isset($_POST['wpcp_category_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpcp_category_meta_nonce'])), 'wpcp_save_category_meta')) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $heading = isset($_POST['wpcp_category_heading']) ? sanitize_text_field(wp_unslash($_POST['wpcp_category_heading'])) : '';
            $category_id = isset($_POST['wpcp_category_id']) ? sanitize_text_field(wp_unslash($_POST['wpcp_category_id'])) : '';
            $image_id = isset($_POST['wpcp_category_image_id']) ? absint($_POST['wpcp_category_image_id']) : 0;
            $image_url = isset($_POST['wpcp_category_image_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_category_image_url'])) : '';
            $image_background = isset($_POST['wpcp_category_image_background']) ? sanitize_text_field(wp_unslash($_POST['wpcp_category_image_background'])) : '';
            $detail_reference = isset($_POST['wpcp_category_detail_reference']) ? sanitize_text_field(wp_unslash($_POST['wpcp_category_detail_reference'])) : '';
            if ($image_id && empty($image_url)) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
            $content = isset($_POST['wpcp_category_content']) ? wp_kses_post(wp_unslash($_POST['wpcp_category_content'])) : '';
            $description = isset($_POST['wpcp_category_description']) ? wp_kses_post(wp_unslash($_POST['wpcp_category_description'])) : '';

            update_post_meta($post_id, 'wpcp_category_heading', $heading);
            update_post_meta($post_id, 'wpcp_category_id', $category_id);
            update_post_meta($post_id, 'wpcp_category_image_id', $image_id);
            update_post_meta($post_id, 'wpcp_category_image_url', $image_url);
            update_post_meta($post_id, 'wpcp_category_image_background', $image_background);
            update_post_meta($post_id, 'wpcp_category_detail_reference', $detail_reference);
            update_post_meta($post_id, 'wpcp_category_content', $content);
            update_post_meta($post_id, 'wpcp_category_description', $description);
        }

        public function save_detail_meta($post_id) {
            if (!isset($_POST['wpcp_detail_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpcp_detail_meta_nonce'])), 'wpcp_save_detail_meta')) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $image_id = isset($_POST['wpcp_detail_image_id']) ? absint($_POST['wpcp_detail_image_id']) : 0;
            $image_url = isset($_POST['wpcp_detail_image_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_detail_image_url'])) : '';
            if ($image_id && empty($image_url)) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
            $detail_url = isset($_POST['wpcp_detail_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_detail_url'])) : '';
            $labels = isset($_POST['wpcp_detail_labels']) ? sanitize_text_field(wp_unslash($_POST['wpcp_detail_labels'])) : '';
            $details_id = isset($_POST['wpcp_detail_details_id']) ? sanitize_text_field(wp_unslash($_POST['wpcp_detail_details_id'])) : '';
            $detail_heading = isset($_POST['wpcp_detail_heading']) ? sanitize_text_field(wp_unslash($_POST['wpcp_detail_heading'])) : '';
            $detail_category_ref = isset($_POST['wpcp_detail_category_ref']) ? sanitize_text_field(wp_unslash($_POST['wpcp_detail_category_ref'])) : '';
            $description = isset($_POST['wpcp_detail_description']) ? wp_kses_post(wp_unslash($_POST['wpcp_detail_description'])) : '';

            update_post_meta($post_id, 'wpcp_detail_image_id', $image_id);
            update_post_meta($post_id, 'wpcp_detail_image_url', $image_url);
            update_post_meta($post_id, 'wpcp_detail_url', $detail_url);
            update_post_meta($post_id, 'wpcp_detail_labels', $labels);
            update_post_meta($post_id, 'wpcp_detail_details_id', $details_id);
            update_post_meta($post_id, 'wpcp_detail_heading', $detail_heading);
            update_post_meta($post_id, 'wpcp_detail_category_ref', $detail_category_ref);
            update_post_meta($post_id, 'wpcp_detail_description', $description);
        }

        public function register_settings_page() {
            add_submenu_page(
                'wpcp-main-menu',
                __('Design', 'wp-categories-page'),
                __('Design', 'wp-categories-page'),
                'manage_options',
                'wpcp-style-settings',
                array($this, 'render_settings_page')
            );
        }

        public function register_settings() {
            register_setting('wpcp_style_group', 'wpcp_font_family');
            register_setting('wpcp_style_group', 'wpcp_text_color');
            register_setting('wpcp_style_group', 'wpcp_heading_color');
            register_setting('wpcp_style_group', 'wpcp_category_font_family');
            register_setting('wpcp_style_group', 'wpcp_category_text_color');
            register_setting('wpcp_style_group', 'wpcp_category_heading_color');
            register_setting('wpcp_style_group', 'wpcp_detail_font_family');
            register_setting('wpcp_style_group', 'wpcp_detail_text_color');
            register_setting('wpcp_style_group', 'wpcp_detail_heading_color');
            register_setting('wpcp_style_group', 'wpcp_heading_size');
            register_setting('wpcp_style_group', 'wpcp_card_background');
            register_setting('wpcp_style_group', 'wpcp_card_border');
            register_setting('wpcp_style_group', 'wpcp_overview_image_container_background');
            register_setting('wpcp_style_group', 'wpcp_overview_image_background');
            register_setting('wpcp_style_group', 'wpcp_detail_image_container_background');

            add_settings_section(
                'wpcp_style_section_typography',
                __('Schriftarten und Farben', 'wp-categories-page'),
                '__return_false',
                'wpcp_style_group'
            );
            add_settings_section(
                'wpcp_style_section_surfaces',
                __('Hintergründe und Container', 'wp-categories-page'),
                '__return_false',
                'wpcp_style_group'
            );

            add_settings_field('wpcp_category_font_family', __('Kategorie: Schriftart', 'wp-categories-page'), array($this, 'render_category_font_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_category_text_color', __('Kategorie: Textfarbe', 'wp-categories-page'), array($this, 'render_category_text_color_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_category_heading_color', __('Kategorie: Überschriftenfarbe', 'wp-categories-page'), array($this, 'render_category_heading_color_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_detail_font_family', __('Kategorie-Details: Schriftart', 'wp-categories-page'), array($this, 'render_detail_font_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_detail_text_color', __('Kategorie-Details: Textfarbe', 'wp-categories-page'), array($this, 'render_detail_text_color_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_detail_heading_color', __('Kategorie-Details: Überschriftenfarbe', 'wp-categories-page'), array($this, 'render_detail_heading_color_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_heading_size', __('Überschriftengröße', 'wp-categories-page'), array($this, 'render_heading_size_field'), 'wpcp_style_group', 'wpcp_style_section_typography');
            add_settings_field('wpcp_card_background', __('Kartenhintergrund', 'wp-categories-page'), array($this, 'render_card_bg_field'), 'wpcp_style_group', 'wpcp_style_section_surfaces');
            add_settings_field('wpcp_card_border', __('Kartenrahmen', 'wp-categories-page'), array($this, 'render_card_border_field'), 'wpcp_style_group', 'wpcp_style_section_surfaces');
            add_settings_field('wpcp_overview_image_container_background', __('Kategorie Bild-Container Hintergrund', 'wp-categories-page'), array($this, 'render_overview_image_container_bg_field'), 'wpcp_style_group', 'wpcp_style_section_surfaces');
            add_settings_field('wpcp_overview_image_background', __('Kategorie Bild Hintergrund', 'wp-categories-page'), array($this, 'render_overview_image_bg_field'), 'wpcp_style_group', 'wpcp_style_section_surfaces');
            add_settings_field('wpcp_detail_image_container_background', __('Kategorie-Details Bild-Container Hintergrund', 'wp-categories-page'), array($this, 'render_detail_image_container_bg_field'), 'wpcp_style_group', 'wpcp_style_section_surfaces');
        }

        private function get_font_preset_options() {
            $options = array();

            if (function_exists('wp_get_global_settings')) {
                $settings = wp_get_global_settings();
                if (isset($settings['typography']['fontFamilies']) && is_array($settings['typography']['fontFamilies'])) {
                    foreach ($settings['typography']['fontFamilies'] as $group => $entries) {
                        if (!is_array($entries)) {
                            continue;
                        }
                        foreach ($entries as $entry) {
                            if (!is_array($entry) || empty($entry['slug'])) {
                                continue;
                            }
                            $value = 'var(--wp--preset--font-family--' . sanitize_html_class($entry['slug']) . ')';
                            $label = !empty($entry['name']) ? $entry['name'] : $entry['slug'];
                            $options[$value] = sprintf('%s (%s)', $label, ucfirst((string) $group));
                        }
                    }
                }
            }

            if (defined('ELEMENTOR_VERSION')) {
                $kit_id = (int) get_option('elementor_active_kit');
                if ($kit_id > 0) {
                    $page_settings = get_post_meta($kit_id, '_elementor_page_settings', true);
                    if (is_array($page_settings)) {
                        foreach (array('system_typography', 'custom_typography') as $key) {
                            if (empty($page_settings[$key]) || !is_array($page_settings[$key])) {
                                continue;
                            }
                            foreach ($page_settings[$key] as $entry) {
                                if (!is_array($entry) || empty($entry['_id'])) {
                                    continue;
                                }
                                $preset_id = sanitize_html_class((string) $entry['_id']);
                                $value = 'var(--e-global-typography-' . $preset_id . '-font-family)';
                                $label = !empty($entry['title']) ? $entry['title'] : $preset_id;
                                $options[$value] = 'Elementor: ' . $label;
                            }
                        }
                    }
                }
            }

            if (empty($options)) {
                $options['var(--wp--preset--font-family--body)'] = __('Theme: Body', 'wp-categories-page');
            }

            return $options;
        }

        private function get_color_preset_options() {
            $options = array();

            if (function_exists('wp_get_global_settings')) {
                $settings = wp_get_global_settings();
                if (isset($settings['color']['palette']) && is_array($settings['color']['palette'])) {
                    foreach ($settings['color']['palette'] as $group => $entries) {
                        if (!is_array($entries)) {
                            continue;
                        }
                        foreach ($entries as $entry) {
                            if (!is_array($entry) || empty($entry['slug'])) {
                                continue;
                            }
                            $value = 'var(--wp--preset--color--' . sanitize_html_class($entry['slug']) . ')';
                            $label = !empty($entry['name']) ? $entry['name'] : $entry['slug'];
                            $options[$value] = sprintf('%s (%s)', $label, ucfirst((string) $group));
                        }
                    }
                }
            }

            if (defined('ELEMENTOR_VERSION')) {
                $kit_id = (int) get_option('elementor_active_kit');
                if ($kit_id > 0) {
                    $page_settings = get_post_meta($kit_id, '_elementor_page_settings', true);
                    if (is_array($page_settings)) {
                        foreach (array('system_colors', 'custom_colors') as $key) {
                            if (empty($page_settings[$key]) || !is_array($page_settings[$key])) {
                                continue;
                            }
                            foreach ($page_settings[$key] as $entry) {
                                if (!is_array($entry) || empty($entry['_id'])) {
                                    continue;
                                }
                                $preset_id = sanitize_html_class((string) $entry['_id']);
                                $value = 'var(--e-global-color-' . $preset_id . ')';
                                $label = !empty($entry['title']) ? $entry['title'] : $preset_id;
                                $options[$value] = 'Elementor: ' . $label;
                            }
                        }
                    }
                }
            }

            if (empty($options)) {
                $options['var(--wp--preset--color--foreground)'] = __('Theme: Vordergrund', 'wp-categories-page');
                $options['var(--wp--preset--color--primary)'] = __('Theme: Primär', 'wp-categories-page');
            }

            return $options;
        }

        private function render_preset_select_field($name, $value, $options, $description) {
            if (!array_key_exists($value, $options) && $value !== '') {
                $options = array($value => sprintf(__('Aktueller Wert: %s', 'wp-categories-page'), $value)) + $options;
            }

            echo '<select name="' . esc_attr($name) . '" class="regular-text">';
            foreach ($options as $option_value => $label) {
                echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            if ($description !== '') {
                echo '<p class="description">' . esc_html($description) . '</p>';
            }
        }

        public function render_category_font_field() {
            $value = get_option('wpcp_category_font_family', get_option('wpcp_font_family', 'var(--wp--preset--font-family--body)'));
            $this->render_preset_select_field('wpcp_category_font_family', $value, $this->get_font_preset_options(), __('Verwendet vordefinierte Schriftarten aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_category_text_color_field() {
            $value = get_option('wpcp_category_text_color', get_option('wpcp_text_color', 'var(--wp--preset--color--foreground)'));
            $this->render_preset_select_field('wpcp_category_text_color', $value, $this->get_color_preset_options(), __('Verwendet vordefinierte Farben aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_category_heading_color_field() {
            $value = get_option('wpcp_category_heading_color', get_option('wpcp_heading_color', 'var(--wp--preset--color--primary)'));
            $this->render_preset_select_field('wpcp_category_heading_color', $value, $this->get_color_preset_options(), __('Verwendet vordefinierte Farben aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_detail_font_field() {
            $value = get_option('wpcp_detail_font_family', get_option('wpcp_font_family', 'var(--wp--preset--font-family--body)'));
            $this->render_preset_select_field('wpcp_detail_font_family', $value, $this->get_font_preset_options(), __('Verwendet vordefinierte Schriftarten aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_detail_text_color_field() {
            $value = get_option('wpcp_detail_text_color', get_option('wpcp_text_color', 'var(--wp--preset--color--foreground)'));
            $this->render_preset_select_field('wpcp_detail_text_color', $value, $this->get_color_preset_options(), __('Verwendet vordefinierte Farben aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_detail_heading_color_field() {
            $value = get_option('wpcp_detail_heading_color', get_option('wpcp_heading_color', 'var(--wp--preset--color--primary)'));
            $this->render_preset_select_field('wpcp_detail_heading_color', $value, $this->get_color_preset_options(), __('Verwendet vordefinierte Farben aus Theme/Elementor.', 'wp-categories-page'));
        }

        public function render_heading_size_field() {
            $value = get_option('wpcp_heading_size', 'clamp(1.8rem, 3vw, 2.5rem)');
            echo '<input type="text" class="regular-text" name="wpcp_heading_size" value="' . esc_attr($value) . '" placeholder="clamp(1.8rem, 3vw, 2.5rem)" />';
        }

        public function render_card_bg_field() {
            $value = get_option('wpcp_card_background', '#ffffff');
            echo '<input type="text" class="regular-text" name="wpcp_card_background" value="' . esc_attr($value) . '" placeholder="#ffffff" />';
        }

        public function render_card_border_field() {
            $value = get_option('wpcp_card_border', '#e3dfd2');
            echo '<input type="text" class="regular-text" name="wpcp_card_border" value="' . esc_attr($value) . '" placeholder="#e3dfd2" />';
        }

        public function render_overview_image_container_bg_field() {
            $value = get_option('wpcp_overview_image_container_background', '#f4f1e8');
            echo '<input type="text" class="regular-text" name="wpcp_overview_image_container_background" value="' . esc_attr($value) . '" placeholder="#f4f1e8 oder linear-gradient(...)" />';
        }

        public function render_overview_image_bg_field() {
            $value = get_option('wpcp_overview_image_background', 'transparent');
            echo '<input type="text" class="regular-text" name="wpcp_overview_image_background" value="' . esc_attr($value) . '" placeholder="transparent oder linear-gradient(...)" />';
        }

        public function render_detail_image_container_bg_field() {
            $value = get_option('wpcp_detail_image_container_background', '#f4f1e8');
            echo '<input type="text" class="regular-text" name="wpcp_detail_image_container_background" value="' . esc_attr($value) . '" placeholder="#f4f1e8 oder linear-gradient(...)" />';
        }

        public function render_settings_page() {
            if (!current_user_can('manage_options')) {
                return;
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Design', 'wp-categories-page'); ?></h1>
                <form method="post" action="options.php">
                    <?php settings_fields('wpcp_style_group'); ?>
                    <?php do_settings_sections('wpcp_style_group'); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function shortcode_category_overview($atts) {
            $atts = shortcode_atts(
                array(
                    'categories' => '',
                    'type' => 'long',
                    'detail_page_url' => '',
                ),
                $atts,
                'category_overview'
            );

            $selected_slugs = array_filter(array_map('trim', explode(';', (string) $atts['categories'])));
            $categories = $this->get_category_posts($selected_slugs);

            if (empty($categories)) {
                return '';
            }

            $type = strtolower((string) $atts['type']);
            $items_html = array();

            foreach ($categories as $category) {
                $items_html[] = $this->render_overview_category($category, $type, (string) $atts['detail_page_url']);
            }

            return $this->render_shortcode_wrapper(
                '<div class="wpcp-overview wpcp-overview--' . esc_attr($type) . '">' . implode('', $items_html) . '</div>'
            );
        }

        public function shortcode_category_detail($atts) {
            $atts = shortcode_atts(
                array(
                    'category' => '',
                ),
                $atts,
                'category_detail'
            );

            $raw_category = trim((string) $atts['category']);
            if ($raw_category === '' && isset($_GET['category'])) {
                $raw_category = sanitize_text_field(wp_unslash($_GET['category']));
            }
            if ($raw_category === '') {
                return '';
            }

            $reference_key = ltrim($raw_category, '#');
            $reference_key = sanitize_title($reference_key);
            if ($reference_key === '') {
                return '';
            }

            $details = get_posts(
                array(
                    'post_type' => 'wpcp_detail',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );
            $details = array_values(array_filter($details, function ($detail) use ($reference_key) {
                $details_id = (string) get_post_meta($detail->ID, 'wpcp_detail_details_id', true);
                return sanitize_title(ltrim($details_id, '#')) === $reference_key;
            }));

            if (empty($details)) {
                return '';
            }

            $category = null;
            $first_detail_category_ref = trim((string) get_post_meta($details[0]->ID, 'wpcp_detail_category_ref', true));
            if ($first_detail_category_ref !== '') {
                $category = $this->get_category_by_category_id($first_detail_category_ref);
            }
            if (!$category) {
                $category = $this->get_category_by_detail_reference($reference_key);
            }

            $heading = $category ? get_post_meta($category->ID, 'wpcp_category_heading', true) : '';
            $image_url = $category ? get_post_meta($category->ID, 'wpcp_category_image_url', true) : '';
            $image_background = $category ? trim((string) get_post_meta($category->ID, 'wpcp_category_image_background', true)) : '';
            $description = $category ? get_post_meta($category->ID, 'wpcp_category_description', true) : '';
            $detail_reference = $category ? trim((string) get_post_meta($category->ID, 'wpcp_category_detail_reference', true)) : '#' . $reference_key;
            $detail_section_id = sanitize_html_class(ltrim($detail_reference, '#'));
            $category_title = $category ? get_the_title($category) : ucfirst(str_replace('-', ' ', $reference_key));
            $detail_image_style = $image_background !== '' ? ' style="background:' . esc_attr($image_background) . ';"' : '';

            $labels = array();
            foreach ($details as $detail) {
                $list = (string) get_post_meta($detail->ID, 'wpcp_detail_labels', true);
                foreach (preg_split('/[;,]/', $list) as $label) {
                    $item = trim((string) $label);
                    if ($item !== '') {
                        $labels[] = $item;
                    }
                }
            }
            $labels = array_values(array_unique($labels));

            $items_html = array();
            foreach ($details as $index => $detail) {
                $items_html[] = $this->render_detail_item($detail, $index + 1);
            }

            $filter_buttons = array();
            $filter_buttons[] = '<button class="wpcp-filter__button is-active" type="button" data-wpcp-filter="all">Alle</button>';
            foreach ($labels as $label) {
                $filter_buttons[] = '<button class="wpcp-filter__button" type="button" data-wpcp-filter="' . esc_attr(strtolower($label)) . '">' . esc_html($label) . '</button>';
            }

            $html = '<div class="wpcp-detail"' . ($detail_section_id !== '' ? ' id="' . esc_attr($detail_section_id) . '"' : '') . '>'
                . '<div class="wpcp-detail__header">'
                . '<div class="wpcp-detail__content">'
                . '<div class="wpcp-detail__eyebrow">Kategorie</div>'
                . '<h2>' . esc_html(!empty($heading) ? $heading : $category_title) . '</h2>'
                . (!empty($description) ? '<div class="wpcp-detail__description">' . wp_kses_post($description) . '</div>' : '')
                . '</div>'
                . (!empty($image_url) ? '<div class="wpcp-detail__image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($category_title) . '"' . $detail_image_style . ' /></div>' : '')
                . '</div>'
                . (!empty($filter_buttons) ? '<div class="wpcp-detail__filters">' . implode('', $filter_buttons) . '</div>' : '')
                . '<div class="wpcp-detail__list">' . implode('', $items_html) . '</div>'
                . '</div>';

            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    var filterButtons = document.querySelectorAll(".wpcp-filter__button");
                    var detailItems = document.querySelectorAll(".wpcp-detail-item");
                    filterButtons.forEach(function (button) {
                        button.addEventListener("click", function () {
                            var active = button.getAttribute("data-wpcp-filter");
                            filterButtons.forEach(function (node) {
                                node.classList.toggle("is-active", node === button);
                            });
                            detailItems.forEach(function (item) {
                                if (active === "all") {
                                    item.style.display = "block";
                                    return;
                                }
                                var labels = (item.getAttribute("data-wpcp-labels") || "").toLowerCase().split(",");
                                var visible = labels.some(function (label) {
                                    return label.trim() === active.trim();
                                });
                                item.style.display = visible ? "block" : "none";
                            });
                        });
                    });
                });
            </script>';

            return $this->render_shortcode_wrapper($html);
        }

        private function render_overview_category($category, $type, $detail_page_url = '') {
            $heading = get_post_meta($category->ID, 'wpcp_category_heading', true);
            $image_url = get_post_meta($category->ID, 'wpcp_category_image_url', true);
            $image_background = trim((string) get_post_meta($category->ID, 'wpcp_category_image_background', true));
            $content = get_post_meta($category->ID, 'wpcp_category_content', true);
            $detail_reference = trim((string) get_post_meta($category->ID, 'wpcp_category_detail_reference', true));
            $category_id = trim((string) get_post_meta($category->ID, 'wpcp_category_id', true));
            $title = !empty($heading) ? $heading : get_the_title($category);
            $card_html = '';
            $link_href = $detail_reference;
            $image_style_attr = $image_background !== '' ? ' style="background:' . esc_attr($image_background) . ';"' : '';

            if ($detail_page_url !== '') {
                $category_param = $detail_reference !== '' ? $detail_reference : ($category_id !== '' ? $category_id : $category->post_name);
                $link_href = add_query_arg('category', $category_param, $detail_page_url);
            }

            if ($type === 'short') {
                $thumb_html = !empty($image_url)
                    ? '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '"' . $image_style_attr . ' />'
                    : '<span class="wpcp-overview__thumb-fallback" aria-hidden="true"' . $image_style_attr . '></span>';

                $card_html = '<div class="wpcp-overview__card wpcp-overview__card--short">'
                    . '<span class="wpcp-overview__thumb">' . $thumb_html . '</span>'
                    . '<span class="wpcp-overview__short-title">' . esc_html($title) . '</span>'
                    . '</div>';
            } else {
                $card_html = '<div class="wpcp-overview__card wpcp-overview__card--long">'
                    . (!empty($image_url) ? '<div class="wpcp-overview__image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '"' . $image_style_attr . ' /></div>' : '')
                    . '<div class="wpcp-overview__meta">'
                    . '<h3>' . esc_html($title) . '</h3>'
                    . (!empty($content) ? '<div class="wpcp-overview__content">' . wp_kses_post($content) . '</div>' : '')
                    . '</div>'
                    . '</div>';
            }

            if ($link_href !== '') {
                return '<a class="wpcp-overview__link" href="' . esc_url($link_href) . '">' . $card_html . '</a>';
            }

            return $card_html;
        }

        private function render_detail_item($detail, $number) {
            $heading = trim((string) get_post_meta($detail->ID, 'wpcp_detail_heading', true));
            $url = get_post_meta($detail->ID, 'wpcp_detail_url', true);
            $description = get_post_meta($detail->ID, 'wpcp_detail_description', true);
            $labels = (string) get_post_meta($detail->ID, 'wpcp_detail_labels', true);
            $details_id = sanitize_html_class((string) get_post_meta($detail->ID, 'wpcp_detail_details_id', true));
            $title = $heading !== '' ? $heading : get_the_title($detail);
            $normalized_labels = array();
            foreach (preg_split('/[;,]/', $labels) as $label) {
                $entry = trim((string) $label);
                if ($entry !== '') {
                    $normalized_labels[] = esc_html($entry);
                }
            }

            $label_html = '';
            if (!empty($normalized_labels)) {
                $label_html = '<div class="wpcp-detail-item__tags">' . implode('', array_map(static function ($label) {
                    return '<span class="wpcp-detail-item__tag">' . $label . '</span>';
                }, $normalized_labels)) . '</div>';
            }

            return '<article class="wpcp-detail-item"' . ($details_id !== '' ? ' id="' . esc_attr($details_id) . '"' : '') . ' data-wpcp-labels="' . esc_attr(strtolower($labels)) . '">'
                . '<div class="wpcp-detail-item__header"><span class="wpcp-detail-item__number">' . esc_html((string) $number) . '</span><h3>' . esc_html($title) . '</h3></div>'
                . (!empty($description) ? '<div class="wpcp-detail-item__description">' . wp_kses_post($description) . '</div>' : '')
                . (!empty($url) ? '<div class="wpcp-detail-item__url"><a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Website öffnen', 'wp-categories-page') . '</a></div>' : '')
                . $label_html
                . '</article>';
        }

        private function get_category_posts($selected_slugs = array()) {
            $posts = get_posts(
                array(
                    'post_type' => 'wpcp_category',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );

            if (empty($selected_slugs)) {
                return $posts;
            }

            $filtered = array();
            foreach ($posts as $post) {
                if (in_array($post->post_name, $selected_slugs, true)) {
                    $filtered[] = $post;
                }
            }

            return $filtered;
        }

        private function get_category_by_detail_reference($reference) {
            $normalized_reference = '#' . sanitize_title($reference);
            $posts = get_posts(
                array(
                    'post_type' => 'wpcp_category',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );

            foreach ($posts as $post) {
                $candidate = (string) get_post_meta($post->ID, 'wpcp_category_detail_reference', true);
                if ('#' . sanitize_title(ltrim($candidate, '#')) === $normalized_reference) {
                    return $post;
                }
            }

            return null;
        }

        private function get_category_by_category_id($category_id) {
            $normalized_id = sanitize_title(ltrim((string) $category_id, '#'));
            if ($normalized_id === '') {
                return null;
            }

            $posts = get_posts(
                array(
                    'post_type' => 'wpcp_category',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );

            foreach ($posts as $post) {
                $candidate = (string) get_post_meta($post->ID, 'wpcp_category_id', true);
                if (sanitize_title(ltrim($candidate, '#')) === $normalized_id) {
                    return $post;
                }
            }

            return null;
        }

        private function render_shortcode_wrapper($content) {
            $category_font_family = get_option('wpcp_category_font_family', get_option('wpcp_font_family', 'var(--wp--preset--font-family--body)'));
            $category_text_color = get_option('wpcp_category_text_color', get_option('wpcp_text_color', 'var(--wp--preset--color--foreground)'));
            $category_heading_color = get_option('wpcp_category_heading_color', get_option('wpcp_heading_color', 'var(--wp--preset--color--primary)'));
            $detail_font_family = get_option('wpcp_detail_font_family', get_option('wpcp_font_family', 'var(--wp--preset--font-family--body)'));
            $detail_text_color = get_option('wpcp_detail_text_color', get_option('wpcp_text_color', 'var(--wp--preset--color--foreground)'));
            $detail_heading_color = get_option('wpcp_detail_heading_color', get_option('wpcp_heading_color', 'var(--wp--preset--color--primary)'));
            $heading_size = get_option('wpcp_heading_size', 'clamp(1.8rem, 3vw, 2.5rem)');
            $card_background = get_option('wpcp_card_background', '#ffffff');
            $card_border = get_option('wpcp_card_border', '#e3dfd2');
            $overview_image_container_background = get_option('wpcp_overview_image_container_background', '#f4f1e8');
            $overview_image_background = get_option('wpcp_overview_image_background', 'transparent');
            $detail_image_container_background = get_option('wpcp_detail_image_container_background', '#f4f1e8');

            $css = '<style>
                .wpcp-category-plugin {
                    --wpcp-category-font-family: ' . esc_attr($category_font_family) . ';
                    --wpcp-category-text-color: ' . esc_attr($category_text_color) . ';
                    --wpcp-category-heading-color: ' . esc_attr($category_heading_color) . ';
                    --wpcp-detail-font-family: ' . esc_attr($detail_font_family) . ';
                    --wpcp-detail-text-color: ' . esc_attr($detail_text_color) . ';
                    --wpcp-detail-heading-color: ' . esc_attr($detail_heading_color) . ';
                    --wpcp-heading-size: ' . esc_attr($heading_size) . ';
                    --wpcp-card-bg: ' . esc_attr($card_background) . ';
                    --wpcp-card-border: ' . esc_attr($card_border) . ';
                    --wpcp-overview-image-container-bg: ' . esc_attr($overview_image_container_background) . ';
                    --wpcp-overview-image-bg: ' . esc_attr($overview_image_background) . ';
                    --wpcp-detail-image-container-bg: ' . esc_attr($detail_image_container_background) . ';
                    line-height: 1.6;
                }
                .wpcp-category-plugin .wpcp-overview {
                    font-family: var(--wpcp-category-font-family);
                    color: var(--wpcp-category-text-color);
                }
                .wpcp-category-plugin .wpcp-detail {
                    font-family: var(--wpcp-detail-font-family);
                    color: var(--wpcp-detail-text-color);
                }
                .wpcp-category-plugin .wpcp-overview h2,
                .wpcp-category-plugin .wpcp-overview h3,
                .wpcp-category-plugin .wpcp-overview h4 {
                    color: var(--wpcp-category-heading-color);
                    line-height: 1.2;
                    margin: 0 0 0.75rem;
                }
                .wpcp-category-plugin .wpcp-detail h2,
                .wpcp-category-plugin .wpcp-detail h3,
                .wpcp-category-plugin .wpcp-detail h4 {
                    color: var(--wpcp-detail-heading-color);
                    line-height: 1.2;
                    margin: 0 0 0.75rem;
                }
                .wpcp-category-plugin .wpcp-overview {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    margin: 0 24px;
                }
                .wpcp-category-plugin .wpcp-overview--short {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 14px;
                }
                .wpcp-category-plugin .wpcp-detail {
                    display: grid;
                    gap: 1.5rem;
                }
                .wpcp-category-plugin .wpcp-overview__link {
                    display: flex;
                    text-decoration: none;
                    color: inherit;
                    flex: 1 1 300px;
                    min-width: 0;
                }
                .wpcp-category-plugin .wpcp-overview--short .wpcp-overview__link {
                    display: block;
                    min-width: 0;
                }
                .wpcp-category-plugin .wpcp-overview__card {
                    background: var(--wpcp-card-bg);
                    border: 1px solid var(--wpcp-card-border);
                    border-radius: 17px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(16, 38, 26, 0.08);
                }
                .wpcp-category-plugin .wpcp-detail-item {
                    background: var(--wpcp-card-bg);
                    border: 1px solid var(--wpcp-card-border);
                    border-radius: 18px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(16, 38, 26, 0.08);
                }
                .wpcp-category-plugin .wpcp-overview__card {
                    display: flex;
                    flex-direction: row;
                    width: 100%;
                    transition: transform 0.35s cubic-bezier(.2, .8, .2, 1), box-shadow 0.35s;
                }
                .wpcp-category-plugin .wpcp-overview__card--long {
                    flex-direction: column;
                }
                .wpcp-category-plugin .wpcp-overview__card--long:hover,
                .wpcp-category-plugin .wpcp-overview__link:hover .wpcp-overview__card--long,
                .wpcp-category-plugin .wpcp-overview__link:focus-visible .wpcp-overview__card--long {
                    transform: translateY(-6px);
                    box-shadow: 0 2px 4px rgba(16, 38, 26, .06), 0 28px 56px -20px rgba(16, 38, 26, .35);
                }
                .wpcp-category-plugin .wpcp-overview__card--short {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 14px;
                    border-radius: 18px;
                    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
                }
                .wpcp-category-plugin .wpcp-overview__card--short:hover,
                .wpcp-category-plugin .wpcp-overview--short .wpcp-overview__link:hover .wpcp-overview__card--short,
                .wpcp-category-plugin .wpcp-overview--short .wpcp-overview__link:focus-visible .wpcp-overview__card--short {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 30px rgba(16, 38, 26, 0.08);
                    border-color: var(--wpcp-category-heading-color);
                }
                .wpcp-category-plugin .wpcp-overview__thumb {
                    flex: none;
                    width: 52px;
                    height: 44px;
                    border-radius: 12px;
                    overflow: hidden;
                    position: relative;
                    background: var(--wpcp-overview-image-container-bg);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }
                .wpcp-category-plugin .wpcp-overview__thumb img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    display: block;
                }
                .wpcp-category-plugin .wpcp-overview__thumb-fallback {
                    display: block;
                    width: 100%;
                    height: 100%;
                    background: var(--wpcp-overview-image-bg);
                }
                .wpcp-category-plugin .wpcp-overview__short-title {
                    font-weight: 700;
                    font-size: 0.95rem;
                    line-height: 1.3;
                }
                .wpcp-category-plugin .wpcp-overview__image {
                    overflow: hidden;
                    background: var(--wpcp-overview-image-container-bg);
                    flex: 0 0 44%;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image {
                    flex: 0 0 auto;
                    width: 100%;
                    position: relative;
                    aspect-ratio: 4 / 2.7;
                    background: var(--wpcp-overview-image-container-bg);
                    overflow: hidden;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image::before,
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image::after {
                    content: "";
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, .14);
                    pointer-events: none;
                    z-index: 1;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image::before {
                    width: 70%;
                    aspect-ratio: 1;
                    right: -18%;
                    top: -40%;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image::after {
                    width: 46%;
                    aspect-ratio: 1;
                    left: -14%;
                    bottom: -38%;
                }
                .wpcp-category-plugin .wpcp-detail__image {
                    overflow: hidden;
                    background: var(--wpcp-detail-image-container-bg);
                }
                .wpcp-category-plugin .wpcp-overview__image img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    border-radius: 7px;
                    background: var(--wpcp-overview-image-bg);
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image img {
                    height: 100%;
                    position: absolute;
                    inset: 0;
                    border-radius: 0;
                    transition: transform .5s cubic-bezier(.2, .8, .2, 1);
                    filter: drop-shadow(0 10px 14px rgba(0, 0, 0, .18));
                    z-index: 0;
                }
                .wpcp-category-plugin .wpcp-overview__card--long:hover .wpcp-overview__image img,
                .wpcp-category-plugin .wpcp-overview__link:hover .wpcp-overview__card--long .wpcp-overview__image img,
                .wpcp-category-plugin .wpcp-overview__link:focus-visible .wpcp-overview__card--long .wpcp-overview__image img {
                    transform: scale(1.06) rotate(-1.5deg);
                }
                .wpcp-category-plugin .wpcp-detail__image img {
                    display: block;
                    width: 100%;
                    height: 220px;
                    object-fit: cover;
                }
                .wpcp-category-plugin .wpcp-overview__meta {
                    padding: 1.25rem;
                    flex: 1 1 auto;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__meta {
                    padding: 20px 22px 22px;
                }
                .wpcp-category-plugin .wpcp-detail__content {
                    padding: 1.25rem;
                }
                .wpcp-category-plugin .wpcp-overview__meta h3,
                .wpcp-category-plugin .wpcp-detail__header h2,
                .wpcp-category-plugin .wpcp-detail-item__header h3 {
                    font-size: var(--wpcp-heading-size);
                }
                .wpcp-category-plugin .wpcp-overview__content,
                .wpcp-category-plugin .wpcp-detail__description,
                .wpcp-category-plugin .wpcp-detail-item__description {
                    margin-top: 0.75rem;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__meta h3 {
                    margin: 0;
                    line-height: 1.15;
                    letter-spacing: -0.025em;
                }
                .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__content {
                    margin-top: 4px;
                }
                .wpcp-category-plugin .wpcp-detail__header {
                    display: grid;
                    grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr);
                    gap: 1.5rem;
                    align-items: center;
                    background: rgba(255,255,255,0.4);
                    border: 1px solid var(--wpcp-card-border);
                    border-radius: 22px;
                    padding: 1.5rem;
                }
                .wpcp-category-plugin .wpcp-detail__eyebrow {
                    display: inline-block;
                    margin-bottom: 0.75rem;
                    font-size: 0.72rem;
                    letter-spacing: 0.14em;
                    text-transform: uppercase;
                    color: var(--wpcp-detail-heading-color);
                    font-weight: 700;
                }
                .wpcp-category-plugin .wpcp-detail__filters {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    margin-top: 1rem;
                }
                .wpcp-category-plugin .wpcp-filter__button {
                    background: white;
                    border: 1px solid var(--wpcp-card-border);
                    border-radius: 999px;
                    padding: 0.5rem 0.9rem;
                    cursor: pointer;
                    font: inherit;
                    color: var(--wpcp-detail-text-color);
                }
                .wpcp-category-plugin .wpcp-filter__button.is-active {
                    background: var(--wpcp-detail-heading-color);
                    border-color: var(--wpcp-detail-heading-color);
                    color: white;
                }
                .wpcp-category-plugin .wpcp-detail__list {
                    display: grid;
                    gap: 1rem;
                }
                .wpcp-category-plugin .wpcp-detail-item {
                    padding: 1.25rem;
                }
                .wpcp-category-plugin .wpcp-detail-item__header {
                    display: flex;
                    align-items: center;
                    gap: 0.85rem;
                    margin-bottom: 0.85rem;
                }
                .wpcp-category-plugin .wpcp-detail-item__number {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 2.3rem;
                    height: 2.3rem;
                    border-radius: 50%;
                    background: rgba(31, 122, 77, 0.12);
                    color: var(--wpcp-detail-heading-color);
                    font-weight: 700;
                }
                .wpcp-category-plugin .wpcp-detail-item__url {
                    margin-top: 1rem;
                }
                .wpcp-category-plugin .wpcp-detail-item__url a {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0.7rem 1rem;
                    background: var(--wpcp-detail-heading-color);
                    color: #fff;
                    border-radius: 999px;
                    text-decoration: none;
                    font-weight: 600;
                }
                .wpcp-category-plugin .wpcp-detail-item__tags {
                    display: flex;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                    margin-top: 1rem;
                }
                .wpcp-category-plugin .wpcp-detail-item__tag {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0.35rem 0.7rem;
                    border-radius: 999px;
                    background: rgba(16, 38, 26, 0.04);
                    border: 1px solid var(--wpcp-card-border);
                    font-size: 0.8rem;
                }
                @media (max-width: 767px) {
                    .wpcp-category-plugin .wpcp-overview--short {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                    .wpcp-category-plugin .wpcp-overview {
                        margin: 0;
                    }
                    .wpcp-category-plugin .wpcp-overview__card {
                        flex-direction: column;
                    }
                    .wpcp-category-plugin .wpcp-overview__image {
                        flex-basis: auto;
                    }
                    .wpcp-category-plugin .wpcp-overview__image img {
                        height: 220px;
                    }
                    .wpcp-category-plugin .wpcp-overview__card--long .wpcp-overview__image img {
                        height: 100%;
                    }
                    .wpcp-category-plugin .wpcp-overview__card--short {
                        flex-direction: row;
                    }
                    .wpcp-category-plugin .wpcp-detail__header {
                        grid-template-columns: 1fr;
                    }
                }
                @media (max-width: 640px) {
                    .wpcp-category-plugin .wpcp-overview--short {
                        grid-template-columns: 1fr;
                    }
                }
            </style>';

            return '<div class="wpcp-category-plugin">' . $content . '</div>' . $css;
        }
    }
}

WP_Categories_Page::instance();
