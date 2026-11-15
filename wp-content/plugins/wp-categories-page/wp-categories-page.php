<?php
/**
 * Plugin Name: WP Categories Page
 * Description: Verwaltung und Anzeige von Inhaltskategorien mit Shortcodes, WYSIWYG-Editoren und Styling-Optionen.
 * Version: 1.2.5
 * Author: Copilot
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPCP_PLUGIN_VERSION')) {
    define('WPCP_PLUGIN_VERSION', '1.2.5');
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
            $content = get_post_meta($post->ID, 'wpcp_category_content', true);
            $description = get_post_meta($post->ID, 'wpcp_category_description', true);

            echo $this->render_media_picker_field('wpcp_category', $image_id, $image_url);
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wpcp_category_heading"><?php esc_html_e('Überschrift', 'wp-categories-page'); ?>:</label></th>
                        <td><input type="text" class="widefat" id="wpcp_category_heading" name="wpcp_category_heading" value="<?php echo esc_attr($heading); ?>" /></td>
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

            $selected_category = (int) get_post_meta($post->ID, 'wpcp_detail_category_id', true);
            $image_id = (int) get_post_meta($post->ID, 'wpcp_detail_image_id', true);
            $image_url = get_post_meta($post->ID, 'wpcp_detail_image_url', true);
            $detail_url = get_post_meta($post->ID, 'wpcp_detail_url', true);
            $labels = get_post_meta($post->ID, 'wpcp_detail_labels', true);
            $description = get_post_meta($post->ID, 'wpcp_detail_description', true);

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
                        <th scope="row"><label for="wpcp_detail_category_id"><?php esc_html_e('Kategorie', 'wp-categories-page'); ?>:</label></th>
                        <td>
                            <select class="widefat" id="wpcp_detail_category_id" name="wpcp_detail_category_id">
                                <option value=""><?php esc_html_e('Bitte wählen', 'wp-categories-page'); ?></option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category->ID); ?>" <?php selected($selected_category, $category->ID); ?>><?php echo esc_html(get_the_title($category)); ?></option>
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
            $image_id = isset($_POST['wpcp_category_image_id']) ? absint($_POST['wpcp_category_image_id']) : 0;
            $image_url = isset($_POST['wpcp_category_image_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_category_image_url'])) : '';
            if ($image_id && empty($image_url)) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
            $content = isset($_POST['wpcp_category_content']) ? wp_kses_post(wp_unslash($_POST['wpcp_category_content'])) : '';
            $description = isset($_POST['wpcp_category_description']) ? wp_kses_post(wp_unslash($_POST['wpcp_category_description'])) : '';

            update_post_meta($post_id, 'wpcp_category_heading', $heading);
            update_post_meta($post_id, 'wpcp_category_image_id', $image_id);
            update_post_meta($post_id, 'wpcp_category_image_url', $image_url);
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

            $category_id = isset($_POST['wpcp_detail_category_id']) ? absint($_POST['wpcp_detail_category_id']) : 0;
            $image_id = isset($_POST['wpcp_detail_image_id']) ? absint($_POST['wpcp_detail_image_id']) : 0;
            $image_url = isset($_POST['wpcp_detail_image_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_detail_image_url'])) : '';
            if ($image_id && empty($image_url)) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
            $detail_url = isset($_POST['wpcp_detail_url']) ? esc_url_raw(wp_unslash($_POST['wpcp_detail_url'])) : '';
            $labels = isset($_POST['wpcp_detail_labels']) ? sanitize_text_field(wp_unslash($_POST['wpcp_detail_labels'])) : '';
            $description = isset($_POST['wpcp_detail_description']) ? wp_kses_post(wp_unslash($_POST['wpcp_detail_description'])) : '';

            update_post_meta($post_id, 'wpcp_detail_category_id', $category_id);
            update_post_meta($post_id, 'wpcp_detail_image_id', $image_id);
            update_post_meta($post_id, 'wpcp_detail_image_url', $image_url);
            update_post_meta($post_id, 'wpcp_detail_url', $detail_url);
            update_post_meta($post_id, 'wpcp_detail_labels', $labels);
            update_post_meta($post_id, 'wpcp_detail_description', $description);
        }

        public function register_settings_page() {
            add_options_page(
                __('Category Styling', 'wp-categories-page'),
                __('Category Styling', 'wp-categories-page'),
                'manage_options',
                'wpcp-style-settings',
                array($this, 'render_settings_page')
            );
        }

        public function register_settings() {
            register_setting('wpcp_style_group', 'wpcp_font_family');
            register_setting('wpcp_style_group', 'wpcp_text_color');
            register_setting('wpcp_style_group', 'wpcp_heading_color');
            register_setting('wpcp_style_group', 'wpcp_heading_size');
            register_setting('wpcp_style_group', 'wpcp_card_background');
            register_setting('wpcp_style_group', 'wpcp_card_border');

            add_settings_section(
                'wpcp_style_section',
                __('Globale Schrift- und Farbwerte', 'wp-categories-page'),
                '__return_false',
                'wpcp_style_group'
            );

            add_settings_field('wpcp_font_family', __('Schriftart', 'wp-categories-page'), array($this, 'render_font_field'), 'wpcp_style_group', 'wpcp_style_section');
            add_settings_field('wpcp_text_color', __('Textfarbe', 'wp-categories-page'), array($this, 'render_text_color_field'), 'wpcp_style_group', 'wpcp_style_section');
            add_settings_field('wpcp_heading_color', __('Überschriftenfarbe', 'wp-categories-page'), array($this, 'render_heading_color_field'), 'wpcp_style_group', 'wpcp_style_section');
            add_settings_field('wpcp_heading_size', __('Überschriftengröße', 'wp-categories-page'), array($this, 'render_heading_size_field'), 'wpcp_style_group', 'wpcp_style_section');
            add_settings_field('wpcp_card_background', __('Kartenhintergrund', 'wp-categories-page'), array($this, 'render_card_bg_field'), 'wpcp_style_group', 'wpcp_style_section');
            add_settings_field('wpcp_card_border', __('Kartenrahmen', 'wp-categories-page'), array($this, 'render_card_border_field'), 'wpcp_style_group', 'wpcp_style_section');
        }

        public function render_font_field() {
            $value = get_option('wpcp_font_family', 'var(--wp--preset--font-family--body, "Segoe UI", sans-serif)');
            echo '<input type="text" class="regular-text" name="wpcp_font_family" value="' . esc_attr($value) . '" placeholder="var(--wp--preset--font-family--body, \"Segoe UI\", sans-serif)" />';
            echo '<p class="description">' . esc_html__('CSS-Variable oder CSS-Definition verwenden, z. B. var(--wp--preset--font-family--body).', 'wp-categories-page') . '</p>';
        }

        public function render_text_color_field() {
            $value = get_option('wpcp_text_color', 'var(--wp--preset--color--foreground, #10261a)');
            echo '<input type="text" class="regular-text" name="wpcp_text_color" value="' . esc_attr($value) . '" placeholder="var(--wp--preset--color--foreground, #10261a)" />';
        }

        public function render_heading_color_field() {
            $value = get_option('wpcp_heading_color', 'var(--wp--preset--color--primary, #1f7a4d)');
            echo '<input type="text" class="regular-text" name="wpcp_heading_color" value="' . esc_attr($value) . '" placeholder="var(--wp--preset--color--primary, #1f7a4d)" />';
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

        public function render_settings_page() {
            if (!current_user_can('manage_options')) {
                return;
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Category Styling', 'wp-categories-page'); ?></h1>
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
                $items_html[] = $this->render_overview_category($category, $type);
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
            if ($raw_category === '') {
                return '';
            }

            $category = $this->get_category_by_slug($raw_category);
            if (!$category) {
                return '';
            }

            $details = get_posts(
                array(
                    'post_type' => 'wpcp_detail',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_key' => 'wpcp_detail_category_id',
                    'meta_value' => $category->ID,
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );

            $heading = get_post_meta($category->ID, 'wpcp_category_heading', true);
            $image_url = get_post_meta($category->ID, 'wpcp_category_image_url', true);
            $description = get_post_meta($category->ID, 'wpcp_category_description', true);

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

            $html = '<div class="wpcp-detail">'
                . '<div class="wpcp-detail__header">'
                . '<div class="wpcp-detail__content">'
                . '<div class="wpcp-detail__eyebrow">Kategorie</div>'
                . '<h2>' . esc_html(!empty($heading) ? $heading : get_the_title($category)) . '</h2>'
                . (!empty($description) ? '<div class="wpcp-detail__description">' . wp_kses_post($description) . '</div>' : '')
                . '</div>'
                . (!empty($image_url) ? '<div class="wpcp-detail__image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($category)) . '" /></div>' : '')
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

        private function render_overview_category($category, $type) {
            $heading = get_post_meta($category->ID, 'wpcp_category_heading', true);
            $image_url = get_post_meta($category->ID, 'wpcp_category_image_url', true);
            $content = get_post_meta($category->ID, 'wpcp_category_content', true);
            $title = !empty($heading) ? $heading : get_the_title($category);

            if ($type === 'short') {
                return '<div class="wpcp-overview__card wpcp-overview__card--short">'
                    . (!empty($image_url) ? '<div class="wpcp-overview__image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" /></div>' : '')
                    . '<div class="wpcp-overview__meta"><h3>' . esc_html($title) . '</h3></div>'
                    . '</div>';
            }

            return '<div class="wpcp-overview__card wpcp-overview__card--long">'
                . (!empty($image_url) ? '<div class="wpcp-overview__image"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" /></div>' : '')
                . '<div class="wpcp-overview__meta">'
                . '<h3>' . esc_html($title) . '</h3>'
                . (!empty($content) ? '<div class="wpcp-overview__content">' . wp_kses_post($content) . '</div>' : '')
                . '</div>'
                . '</div>';
        }

        private function render_detail_item($detail, $number) {
            $url = get_post_meta($detail->ID, 'wpcp_detail_url', true);
            $description = get_post_meta($detail->ID, 'wpcp_detail_description', true);
            $labels = (string) get_post_meta($detail->ID, 'wpcp_detail_labels', true);
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

            return '<article class="wpcp-detail-item" data-wpcp-labels="' . esc_attr(strtolower($labels)) . '">'
                . '<div class="wpcp-detail-item__header"><span class="wpcp-detail-item__number">' . esc_html((string) $number) . '</span><h3>' . esc_html(get_the_title($detail)) . '</h3></div>'
                . (!empty($url) ? '<div class="wpcp-detail-item__url"><a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Website öffnen', 'wp-categories-page') . '</a></div>' : '')
                . (!empty($description) ? '<div class="wpcp-detail-item__description">' . wp_kses_post($description) . '</div>' : '')
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

        private function get_category_by_slug($slug) {
            $posts = get_posts(
                array(
                    'post_type' => 'wpcp_category',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'name' => sanitize_title($slug),
                )
            );

            return $posts[0] ?? null;
        }

        private function render_shortcode_wrapper($content) {
            $font_family = get_option('wpcp_font_family', 'var(--wp--preset--font-family--body, "Segoe UI", sans-serif)');
            $text_color = get_option('wpcp_text_color', 'var(--wp--preset--color--foreground, #10261a)');
            $heading_color = get_option('wpcp_heading_color', 'var(--wp--preset--color--primary, #1f7a4d)');
            $heading_size = get_option('wpcp_heading_size', 'clamp(1.8rem, 3vw, 2.5rem)');
            $card_background = get_option('wpcp_card_background', '#ffffff');
            $card_border = get_option('wpcp_card_border', '#e3dfd2');

            $css = '<style>
                .wpcp-category-plugin {
                    --wpcp-font-family: ' . esc_attr($font_family) . ';
                    --wpcp-text-color: ' . esc_attr($text_color) . ';
                    --wpcp-heading-color: ' . esc_attr($heading_color) . ';
                    --wpcp-heading-size: ' . esc_attr($heading_size) . ';
                    --wpcp-card-bg: ' . esc_attr($card_background) . ';
                    --wpcp-card-border: ' . esc_attr($card_border) . ';
                    font-family: var(--wpcp-font-family);
                    color: var(--wpcp-text-color);
                    line-height: 1.6;
                }
                .wpcp-category-plugin h2,
                .wpcp-category-plugin h3,
                .wpcp-category-plugin h4 {
                    color: var(--wpcp-heading-color);
                    line-height: 1.2;
                    margin: 0 0 0.75rem;
                }
                .wpcp-category-plugin .wpcp-overview,
                .wpcp-category-plugin .wpcp-detail {
                    display: grid;
                    gap: 1.5rem;
                }
                .wpcp-category-plugin .wpcp-overview--long {
                    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                }
                .wpcp-category-plugin .wpcp-overview--short {
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                }
                .wpcp-category-plugin .wpcp-overview__card,
                .wpcp-category-plugin .wpcp-detail-item {
                    background: var(--wpcp-card-bg);
                    border: 1px solid var(--wpcp-card-border);
                    border-radius: 18px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(16, 38, 26, 0.08);
                }
                .wpcp-category-plugin .wpcp-overview__image,
                .wpcp-category-plugin .wpcp-detail__image {
                    overflow: hidden;
                    background: #f4f1e8;
                }
                .wpcp-category-plugin .wpcp-overview__image img,
                .wpcp-category-plugin .wpcp-detail__image img {
                    display: block;
                    width: 100%;
                    height: 220px;
                    object-fit: cover;
                }
                .wpcp-category-plugin .wpcp-overview__meta,
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
                    color: var(--wpcp-heading-color);
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
                    color: var(--wpcp-text-color);
                }
                .wpcp-category-plugin .wpcp-filter__button.is-active {
                    background: var(--wpcp-heading-color);
                    border-color: var(--wpcp-heading-color);
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
                    color: var(--wpcp-heading-color);
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
                    background: var(--wpcp-heading-color);
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
                    .wpcp-category-plugin .wpcp-detail__header {
                        grid-template-columns: 1fr;
                    }
                }
            </style>';

            return '<div class="wpcp-category-plugin">' . $content . '</div>' . $css;
        }
    }
}

WP_Categories_Page::instance();
