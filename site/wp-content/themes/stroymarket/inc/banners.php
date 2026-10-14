<?php

declare(strict_types=1);

add_action('init', static function (): void {
    register_post_type('sm_banner', [
        'labels' => [
            'name' => 'Баннеры главной',
            'singular_name' => 'Баннер',
            'add_new' => 'Добавить баннер',
            'add_new_item' => 'Новый баннер',
            'edit_item' => 'Редактировать баннер',
            'new_item' => 'Новый баннер',
            'view_item' => 'Смотреть баннер',
            'search_items' => 'Найти баннер',
            'not_found' => 'Баннеров пока нет',
            'not_found_in_trash' => 'В корзине баннеров нет',
            'menu_name' => 'Баннеры главной',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 21,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => ['title', 'thumbnail', 'page-attributes'],
        'capability_type' => 'post',
    ]);
}, 10);

add_action('init', static function (): void {
    if (get_option('stroymarket_banners_seeded')) {
        return;
    }
    $existing = get_posts([
        'post_type' => 'sm_banner',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    if ($existing) {
        update_option('stroymarket_banners_seeded', 1, false);
        return;
    }
    $slides = [
        [
            'title' => 'Стройматериалы оптом с точным подбором',
            'text' => 'Комплектуем заказ под объект и организуем поставку. Доставка от 4 часов по Москве.',
            'url' => home_url('/product-category/stroymaterialy/'),
            'label' => 'Открыть раздел',
            'order' => 0,
        ],
        [
            'title' => 'Электрика',
            'text' => 'Кабель, свет, щитовое и низковольтное оборудование.',
            'url' => home_url('/product-category/elektrika/'),
            'label' => 'Смотреть раздел',
            'order' => 1,
        ],
        [
            'title' => 'Инструмент',
            'text' => 'Электроинструмент, ручной инструмент и расходные материалы.',
            'url' => home_url('/product-category/instrument/'),
            'label' => 'Смотреть раздел',
            'order' => 2,
        ],
    ];
    foreach ($slides as $slide) {
        $id = wp_insert_post([
            'post_type' => 'sm_banner',
            'post_status' => 'publish',
            'post_title' => $slide['title'],
            'menu_order' => $slide['order'],
        ], true);
        if (!is_int($id)) {
            continue;
        }
        update_post_meta($id, '_sm_banner_text', $slide['text']);
        update_post_meta($id, '_sm_banner_url', $slide['url']);
        update_post_meta($id, '_sm_banner_label', $slide['label']);
    }
    update_option('stroymarket_banners_seeded', 1, false);
}, 20);

add_action('add_meta_boxes_sm_banner', static function (): void {
    add_meta_box(
        'sm_banner_link',
        'Ссылка и текст',
        static function (WP_Post $post): void {
            $text = (string) get_post_meta($post->ID, '_sm_banner_text', true);
            $url = (string) get_post_meta($post->ID, '_sm_banner_url', true);
            $label = (string) get_post_meta($post->ID, '_sm_banner_label', true);
            wp_nonce_field('sm_banner_save', 'sm_banner_nonce');
            echo '<p><label for="sm_banner_text"><strong>Текст на слайде</strong></label><br>';
            echo '<textarea id="sm_banner_text" name="sm_banner_text" rows="3" class="large-text">' . esc_textarea($text) . '</textarea></p>';
            echo '<p><label for="sm_banner_url"><strong>Ссылка</strong></label><br>';
            echo '<input id="sm_banner_url" name="sm_banner_url" type="url" class="large-text" value="' . esc_attr($url) . '" placeholder="https://"></p>';
            echo '<p><label for="sm_banner_label"><strong>Текст кнопки</strong></label><br>';
            echo '<input id="sm_banner_label" name="sm_banner_label" type="text" class="regular-text" value="' . esc_attr($label) . '" placeholder="Подробнее"></p>';
            echo '<p class="description">Картинку задайте в блоке «Изображение записи». Кадр слайдера 1440×370. Загружайте файл 2880×740 px (JPG или WebP): важное содержимое держите ближе к центру, слева остаётся место под текст. Порядок на главной задаётся числом «Порядок»: чем меньше, тем раньше слайд.</p>';
        },
        'sm_banner',
        'normal',
        'high'
    );
});

add_action('save_post_sm_banner', static function (int $post_id): void {
    if (!isset($_POST['sm_banner_nonce']) || !wp_verify_nonce(sanitize_text_field((string) $_POST['sm_banner_nonce']), 'sm_banner_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, '_sm_banner_text', sanitize_textarea_field((string) ($_POST['sm_banner_text'] ?? '')));
    update_post_meta($post_id, '_sm_banner_url', esc_url_raw((string) ($_POST['sm_banner_url'] ?? '')));
    update_post_meta($post_id, '_sm_banner_label', sanitize_text_field((string) ($_POST['sm_banner_label'] ?? '')));
});

add_filter('enter_title_here', static function (string $title, WP_Post $post): string {
    return $post->post_type === 'sm_banner' ? 'Заголовок на баннере' : $title;
}, 10, 2);

add_filter('manage_sm_banner_posts_columns', static function (array $columns): array {
    $next = [];
    foreach ($columns as $key => $label) {
        if ($key === 'title') {
            $next['sm_thumb'] = 'Фото';
        }
        $next[$key] = $label;
        if ($key === 'title') {
            $next['sm_url'] = 'Ссылка';
        }
    }
    return $next;
});

add_action('manage_sm_banner_posts_custom_column', static function (string $column, int $post_id): void {
    if ($column === 'sm_thumb') {
        echo get_the_post_thumbnail($post_id, [72, 40]) ?: '—';
        return;
    }
    if ($column === 'sm_url') {
        $url = (string) get_post_meta($post_id, '_sm_banner_url', true);
        echo $url !== '' ? esc_html($url) : '—';
    }
}, 10, 2);

add_action('pre_get_posts', static function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'sm_banner') {
        return;
    }
    if ($query->get('orderby') === '') {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
});

function stroymarket_home_banners(): array
{
    $posts = get_posts([
        'post_type' => 'sm_banner',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'ASC'],
    ]);
    $slides = [];
    foreach ($posts as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        $slides[] = [
            'title' => get_the_title($post),
            'text' => (string) get_post_meta($post->ID, '_sm_banner_text', true),
            'url' => (string) get_post_meta($post->ID, '_sm_banner_url', true),
            'label' => (string) get_post_meta($post->ID, '_sm_banner_label', true),
            'image' => (string) get_the_post_thumbnail_url($post, 'full'),
        ];
    }
    return $slides;
}

function stroymarket_render_home_slider(): bool
{
    $slides = stroymarket_home_banners();
    if (!$slides) {
        return false;
    }
    $many = count($slides) > 1;
    echo '<section class="sm-slider" aria-roledescription="карусель" aria-label="Баннеры">';
    echo '<div class="sm-slider-viewport"><div class="sm-slider-track">';
    foreach ($slides as $index => $slide) {
        $plain = $slide['image'] === '' ? ' sm-slide--plain' : '';
        $hidden = $index === 0 ? 'false' : 'true';
        $tag = $slide['url'] !== '' ? 'a' : 'div';
        $href = $tag === 'a' ? ' href="' . esc_url($slide['url']) . '"' : '';
        echo '<' . $tag . ' class="sm-slide' . $plain . '"' . $href . ' aria-hidden="' . $hidden . '">';
        if ($slide['image'] !== '') {
            echo '<img src="' . esc_url($slide['image']) . '" alt="" width="1440" height="370">';
        }
        echo '<span class="sm-slide-copy">';
        $heading = $index === 0 ? 'h1' : 'p';
        echo '<' . $heading . ' class="sm-slide-title">' . esc_html($slide['title']) . '</' . $heading . '>';
        if ($slide['text'] !== '') {
            echo '<span class="sm-slide-text">' . esc_html($slide['text']) . '</span>';
        }
        if ($slide['url'] !== '') {
            $label = $slide['label'] !== '' ? $slide['label'] : 'Подробнее';
            echo '<span class="sm-btn">' . esc_html($label) . '</span>';
        }
        echo '</span></' . $tag . '>';
    }
    echo '</div></div>';
    if ($many) {
        echo '<button class="sm-slider-prev" type="button" aria-label="Предыдущий баннер"></button>';
        echo '<button class="sm-slider-next" type="button" aria-label="Следующий баннер"></button>';
        echo '<div class="sm-slider-dots">';
        foreach ($slides as $index => $slide) {
            $current = $index === 0 ? 'true' : 'false';
            echo '<button class="sm-slider-dot" type="button" aria-label="' . esc_attr($slide['title']) . '" aria-current="' . $current . '"></button>';
        }
        echo '</div>';
    }
    echo '</section>';
    return true;
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!is_front_page()) {
        return;
    }
    $path = get_stylesheet_directory() . '/assets/slider.js';
    if (!is_file($path)) {
        return;
    }
    wp_enqueue_script('stroymarket-slider', get_stylesheet_directory_uri() . '/assets/slider.js', [], (string) filemtime($path), true);
}, 20);
