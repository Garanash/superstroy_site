<?php

declare(strict_types=1);

add_action('init', static function (): void {
    remove_action('storefront_header', 'storefront_site_branding', 20);
    remove_action('storefront_header', 'storefront_secondary_navigation', 30);
    remove_action('storefront_header', 'storefront_product_search', 40);
    remove_action('storefront_header', 'storefront_header_cart', 60);
    add_action('storefront_header', 'stroymarket_site_branding', 20);
    add_action('storefront_header', 'storefront_product_search', 34);
    add_action('storefront_header', 'storefront_header_cart', 38);
}, 20);

add_action('wp_head', static function (): void {
    echo "<script>try{if(localStorage.getItem('sm-theme')==='dark')document.documentElement.classList.add('sm-dark');}catch(e){}</script>\n";
}, 0);

add_action('storefront_header', 'stroymarket_topbar', 8);

function stroymarket_topbar(): void
{
    echo '<div class="sm-topbar">';
    echo '<div class="sm-topbar-inner">';
    echo '<div class="sm-city">';
    echo '<button class="sm-city-btn" type="button" data-sm-city-open aria-haspopup="dialog">';
    echo '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="11" r="2.1" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>';
    echo '<span data-sm-city-label>Москва</span></button>';
    echo '<div class="sm-city-confirm" data-sm-city-confirm hidden>';
    echo '<p>Ваш город — <strong data-sm-city-label>Москва</strong>?</p>';
    echo '<button class="sm-btn sm-btn-small" type="button" data-sm-city-yes>Да</button>';
    echo '<button class="sm-top-link" type="button" data-sm-city-open>Другой</button>';
    echo '</div></div>';
    echo '<span class="sm-top-address" data-sm-office="' . esc_attr(STROYMARKET_ADDRESS) . '">' . esc_html(STROYMARKET_ADDRESS) . '</span>';
    echo '<a class="sm-top-phone" href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">' . esc_html(STROYMARKET_PHONE) . '</a>';
    echo '<a class="sm-top-mail" href="mailto:' . esc_attr(STROYMARKET_EMAIL) . '">' . esc_html(STROYMARKET_EMAIL) . '</a>';
    echo '<div class="sm-top-actions">';
    echo '<button class="sm-top-link" type="button" data-sm-dialog="sm-dialog-callback">Заказать звонок</button>';
    echo '<button class="sm-top-link" type="button" data-sm-dialog="sm-dialog-request">Отправить заявку</button>';
    echo '<button class="sm-theme" type="button" aria-pressed="false">';
    echo '<span class="sm-theme-track" aria-hidden="true"><span class="sm-theme-knob"></span></span>Тёмная тема</button>';
    echo '</div></div></div>';
}

function stroymarket_account_link(): void
{
    $url = function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('myaccount') : wp_login_url();
    $label = is_user_logged_in() ? 'Кабинет' : 'Войти / Регистрация';
    echo '<a class="sm-account" href="' . esc_url($url) . '">';
    echo '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M5.5 19.2a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
    echo '<span>' . esc_html($label) . '</span></a>';
}

add_action('storefront_header', 'stroymarket_account_link', 36);

function storefront_header_cart(): void
{
    if (!function_exists('storefront_is_woocommerce_activated') || !storefront_is_woocommerce_activated()) {
        return;
    }

    wp_enqueue_script('wc-cart-fragments');

    $on_cart = function_exists('is_cart') && is_cart();
    $hidden = apply_filters(
        'woocommerce_widget_cart_is_hidden',
        $on_cart || (function_exists('is_checkout') && is_checkout())
    );

    echo '<ul id="site-header-cart" class="site-header-cart menu">';
    echo '<li class="' . esc_attr($on_cart ? 'current-menu-item' : '') . '">';
    storefront_cart_link();
    echo '</li>';
    if (!$hidden) {
        echo '<li class="sm-mini-cart">';
        echo '<div class="widget woocommerce widget_shopping_cart"><div class="widget_shopping_cart_content">';
        woocommerce_mini_cart();
        echo '</div></div></li>';
    }
    echo '</ul>';
}

function stroymarket_site_branding(): void
{
    $logo_file = get_stylesheet_directory() . '/assets/logo.png';
    $logo_light_file = get_stylesheet_directory() . '/assets/logo-light.png';
    $logo = get_stylesheet_directory_uri() . '/assets/logo.png?v=' . filemtime($logo_file);
    $logo_light = get_stylesheet_directory_uri() . '/assets/logo-light.png?v=' . filemtime($logo_light_file);
    $name = get_bloginfo('name');
    echo '<div class="site-branding">';
    echo '<a class="sm-logo" href="' . esc_url(home_url('/')) . '">';
    echo '<img class="sm-logo-day" src="' . esc_url($logo) . '" alt="' . esc_attr($name) . '" width="150" height="48">';
    echo '<img class="sm-logo-night" src="' . esc_url($logo_light) . '" alt="' . esc_attr($name) . '" width="150" height="48">';
    echo '</a>';
    $description = get_bloginfo('description', 'display');
    if ($description !== '') {
        echo '<p class="site-description">' . esc_html($description) . '</p>';
    }
    echo '</div>';
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('woocommerce');
    add_theme_support('title-tag');
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'stroymarket',
        get_stylesheet_uri(),
        ['storefront-style'],
        (string) filemtime(get_stylesheet_directory() . '/style.css')
    );
}, 20);

add_filter('loop_shop_per_page', static fn (): int => 24, 20);

add_action('init', static function (): void {
    remove_filter('get_terms', 'wc_change_term_counts', 10);
}, 20);

add_filter('body_class', static function (array $classes): array {
    $classes = array_values(array_diff($classes, ['left-sidebar', 'right-sidebar']));
    $classes[] = 'stroymarket-full';
    if (stroymarket_catalog_pages()) {
        $classes[] = 'sm-with-catalog';
    }
    if (stroymarket_is_calculator_page()) {
        $classes[] = 'sm-calc-page';
    }
    return $classes;
}, 99);

add_action('wp', static function (): void {
    if (is_front_page() || (function_exists('is_woocommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page()))) {
        remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    }
});

add_action('init', static function (): void {
    remove_action('storefront_footer', 'storefront_credit', 20);
    add_action('storefront_footer', 'stroymarket_render_footer', 20);
});

add_filter('woocommerce_breadcrumb_defaults', static function (array $defaults): array {
    $defaults['delimiter'] = '<span class="breadcrumb-separator" aria-hidden="true">/</span>';
    return $defaults;
}, 20);

add_filter('wp_nav_menu_objects', static function (array $items, $args): array {
    $location = is_object($args) ? (string) ($args->theme_location ?? '') : '';
    if (!in_array($location, ['primary', 'handheld'], true)) {
        return $items;
    }
    $shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
    $cart_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('cart') : 0;
    $account_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('myaccount') : 0;
    $front_id = (int) get_option('page_on_front');
    $hidden_titles = ['Каталог', 'Главная', 'Корзина', 'Личный кабинет', 'Кабинет'];
    $hidden_ids = array_filter([$shop_id, $cart_id, $account_id, $front_id]);
    return array_values(array_filter(
        $items,
        static function ($item) use ($hidden_titles, $hidden_ids): bool {
            $title = trim(wp_strip_all_tags((string) ($item->title ?? '')));
            $page_id = ($item->object ?? '') === 'page' ? (int) ($item->object_id ?? 0) : 0;
            return !in_array($title, $hidden_titles, true) && !in_array($page_id, $hidden_ids, true);
        }
    ));
}, 20, 2);

add_filter('wp_nav_menu_items', static function (string $items, $args): string {
    $location = is_object($args) ? (string) ($args->theme_location ?? '') : '';
    if (!in_array($location, ['primary', 'handheld'], true)) {
        return $items;
    }
    return stroymarket_catalog_menu_item() . $items;
}, 10, 2);

function stroymarket_catalog_menu_item(): string
{
    if (!function_exists('wc_get_page_permalink')) {
        return '';
    }
    $shop_url = (string) wc_get_page_permalink('shop');
    if ($shop_url === '') {
        return '';
    }
    $html = '<li class="menu-item sm-menu-catalog menu-item-has-children">';
    $html .= '<a href="' . esc_url($shop_url) . '">Каталог</a>';
    $html .= '<div class="sm-catalog-panel">';
    if (function_exists('stroymarket_menu_categories')) {
        foreach (stroymarket_menu_categories() as $category) {
            $html .= '<a href="' . esc_url(stroymarket_category_url((string) $category['slug'])) . '">';
            $html .= '<span>' . esc_html((string) $category['name']) . '</span>';
            $html .= '<span class="sm-side-icon">' . stroymarket_category_icon((string) $category['name']) . '</span></a>';
        }
    }
    $html .= '<a class="sm-catalog-all" href="' . esc_url($shop_url) . '">';
    $html .= '<span>Весь каталог</span>';
    $html .= '<span class="sm-side-icon">' . stroymarket_category_icon('каталог') . '</span></a></div></li>';
    return $html;
}

function stroymarket_catalog_pages(): bool
{
    if (is_front_page() || is_search()) {
        return true;
    }
    return function_exists('is_woocommerce') && (is_shop() || is_product_taxonomy() || is_product());
}

function stroymarket_category_url(string $slug): string
{
    return home_url('/product-category/' . $slug . '/');
}

function stroymarket_category_rows(int $parent, int $limit): array
{
    $key = 'stroymarket_categories_' . $parent . '_' . $limit;
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $parent,
        'hide_empty' => true,
        'orderby' => $parent === 0 ? 'name' : 'count',
        'order' => $parent === 0 ? 'ASC' : 'DESC',
        'number' => $limit,
    ]);
    $rows = [];
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $rows[] = [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }
    }
    set_transient($key, $rows, 12 * HOUR_IN_SECONDS);
    return $rows;
}

function stroymarket_hidden_category(string $slug, string $name): bool
{
    if (in_array($slug, ['podarochnye-sertifikaty-magazinov', 'turisticheskie-sertifikaty'], true)) {
        return true;
    }
    $name = mb_strtolower($name, 'UTF-8');
    return str_contains($name, 'подарочные сертификаты') || str_contains($name, 'туристические сертификаты');
}

function stroymarket_menu_categories(): array
{
    $key = 'stroymarket_menu_categories_v1';
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    $rows = array_values(array_filter(
        stroymarket_category_rows(0, 40),
        static fn (array $row): bool => !stroymarket_hidden_category((string) $row['slug'], (string) $row['name'])
    ));
    $by_slug = [];
    foreach ($rows as $row) {
        $by_slug[(string) $row['slug']] = $row;
    }
    if (!isset($by_slug['elektroinstrument'])) {
        $term = get_term_by('slug', 'elektroinstrument', 'product_cat');
        if ($term instanceof WP_Term && !stroymarket_hidden_category($term->slug, $term->name)) {
            $by_slug['elektroinstrument'] = [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }
    }
    $ordered = [];
    foreach (['elektrika', 'santehnika', 'elektroinstrument', 'krepezh', 'finishnaya-otdelka'] as $slug) {
        if (!isset($by_slug[$slug])) {
            continue;
        }
        $ordered[] = $by_slug[$slug];
        unset($by_slug[$slug]);
    }
    foreach ($rows as $row) {
        $slug = (string) $row['slug'];
        if (isset($by_slug[$slug])) {
            $ordered[] = $row;
            unset($by_slug[$slug]);
        }
    }
    set_transient($key, $ordered, 12 * HOUR_IN_SECONDS);
    return $ordered;
}

add_filter('get_terms', static function ($terms, $taxonomies) {
    if (is_admin() || !is_array($terms) || !in_array('product_cat', (array) $taxonomies, true)) {
        return $terms;
    }
    return array_values(array_filter($terms, static function ($term): bool {
        if (!$term instanceof WP_Term) {
            return true;
        }
        return !stroymarket_hidden_category($term->slug, $term->name);
    }));
}, 20, 2);

function stroymarket_current_branch(): array
{
    $term = null;
    if (is_tax('product_cat')) {
        $queried = get_queried_object();
        $term = $queried instanceof WP_Term ? $queried : null;
    } elseif (function_exists('is_product') && is_product()) {
        $terms = wc_get_product_terms(get_queried_object_id(), 'product_cat');
        if (is_array($terms)) {
            foreach ($terms as $candidate) {
                if (!$term instanceof WP_Term || (int) $candidate->parent > 0) {
                    $term = $candidate;
                }
            }
        }
    }
    if (!$term instanceof WP_Term) {
        return ['root' => 0, 'active' => []];
    }
    $ancestors = array_map('intval', get_ancestors($term->term_id, 'product_cat'));
    $root = $ancestors ? (int) $ancestors[array_key_last($ancestors)] : (int) $term->term_id;
    return [
        'root' => $root,
        'active' => array_merge([(int) $term->term_id], $ancestors),
    ];
}

add_action('storefront_content_top', static function (): void {
    if (!stroymarket_catalog_pages()) {
        return;
    }
    $branch = stroymarket_current_branch();
    $categories = stroymarket_menu_categories();
    $child_map = stroymarket_category_children_map(array_column($categories, 'id'));
    echo '<nav class="sm-side" aria-label="Номенклатурные группы"><p class="sm-side-title">Номенклатурные группы</p>';
    echo '<div class="sm-side-scroll"><ul>';
    foreach ($categories as $category) {
        $kids = $child_map[$category['id']] ?? [];
        $current = in_array($category['id'], $branch['active'], true);
        $fly = $kids ? ' data-fly="sm-fly-' . (int) $category['id'] . '"' : '';
        echo '<li class="sm-side-item' . ($current ? ' is-current' : '') . '"' . $fly . '>';
        stroymarket_side_link($category, $branch['active']);
        echo '</li>';
    }
    echo '</ul></div>';
    foreach ($categories as $category) {
        $kids = $child_map[$category['id']] ?? [];
        if (!$kids) {
            continue;
        }
        echo '<div class="sm-side-fly" id="sm-fly-' . (int) $category['id'] . '" hidden>';
        echo '<p class="sm-side-fly-title">' . esc_html($category['name']) . '</p>';
        foreach ($kids as $child) {
            echo '<a href="' . esc_url(stroymarket_category_url($child['slug'])) . '">' . esc_html($child['name']) . '</a>';
        }
        echo '<a class="sm-side-more" href="' . esc_url(stroymarket_category_url($category['slug'])) . '">Все подразделы</a>';
        echo '</div>';
    }
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
    if ($shop_url) {
        echo '<a class="sm-side-all" href="' . esc_url($shop_url) . '">Весь каталог</a>';
    }
    stroymarket_side_bottom();
    echo '</nav>';
});

function stroymarket_category_children_map(array $parent_ids): array
{
    $parent_ids = array_values(array_unique(array_filter(array_map('intval', $parent_ids))));
    sort($parent_ids);
    if (!$parent_ids) {
        return [];
    }
    $key = 'stroymarket_side_children_' . md5(implode(',', $parent_ids));
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    global $wpdb;
    $in = implode(',', $parent_ids);
    $rows = $wpdb->get_results(
        "SELECT term_id, name, slug, parent FROM (
            SELECT t.term_id, t.name, t.slug, tt.parent,
                ROW_NUMBER() OVER (PARTITION BY tt.parent ORDER BY tt.count DESC) AS rn
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'product_cat' AND tt.count > 0 AND tt.parent IN ({$in})
        ) ranked WHERE rn <= 10"
    );
    if (!is_array($rows)) {
        $rows = [];
    }
    $map = [];
    foreach ($rows as $row) {
        $parent = (int) $row->parent;
        $map[$parent][] = [
            'id' => (int) $row->term_id,
            'name' => (string) $row->name,
            'slug' => (string) $row->slug,
        ];
    }
    set_transient($key, $map, 12 * HOUR_IN_SECONDS);
    return $map;
}

function stroymarket_icon_svg(string $markup): string
{
    return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">' . $markup . '</svg>';
}

function stroymarket_category_icon(string $name): string
{
    $name = mb_strtolower($name, 'UTF-8');
    $stroke = 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';
    if (str_contains($name, 'электроинструмент')) {
        return stroymarket_icon_svg('<path d="M14.5 5.5a4 4 0 0 0-5.4 5.1L4 15.7 8.3 20l5.1-5.1a4 4 0 0 0 5.1-5.4L16 12l-2-2 2.5-2.5z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'электр')) {
        return stroymarket_icon_svg('<path d="M13 2 5 13h6l-1 9 9-13h-6l0-7z" fill="currentColor" stroke="none"/>');
    }
    if (str_contains($name, 'сантех')) {
        return stroymarket_icon_svg('<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'строймат')) {
        return stroymarket_icon_svg('<path d="M4 9h7V4H4v5zm9 0h7V4h-7v5zM4 20h7v-5H4v5zm9 0h7v-5h-7v5z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'крепеж')) {
        return stroymarket_icon_svg('<path d="M14 4 8 10m0 0-4 4 4 4 4-4m-4-4 8 8" ' . $stroke . '/><circle cx="16.5" cy="7.5" r="2" ' . $stroke . '/>');
    }
    if (str_contains($name, 'аренд')) {
        return stroymarket_icon_svg('<circle cx="8" cy="14" r="3" ' . $stroke . '/><path d="M10.2 12.2 19 4l2 2-2 1-1 2-2 1-5.8 2.2z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'инструмент')) {
        return stroymarket_icon_svg('<path d="M14.5 5.5a4 4 0 0 0-5.4 5.1L4 15.7 8.3 20l5.1-5.1a4 4 0 0 0 5.1-5.4L16 12l-2-2 2.5-2.5z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'инженер')) {
        return stroymarket_icon_svg('<path d="M5 8h8a4 4 0 0 1 0 8H9" ' . $stroke . '/><path d="M19 16h-6a4 4 0 0 1 0-8h2" ' . $stroke . '/>');
    }
    if (str_contains($name, 'сад') || str_contains($name, 'досуг')) {
        return stroymarket_icon_svg('<path d="M12 21V11" ' . $stroke . '/><path d="M12 13c0-5 4-8 8-8-1 6-4 8-8 8zM12 15c0-4-3.5-7-8-7 1 5 4 7 8 7z" ' . $stroke . '/>');
    }
    if (str_contains($name, 'сертификат') || str_contains($name, 'сувенир')) {
        return stroymarket_icon_svg('<rect x="4" y="8" width="16" height="11" rx="1.5" ' . $stroke . '/><path d="M12 8v11M8.5 8c0-2 1.4-3.5 3.5-3.5S15.5 6 15.5 8" ' . $stroke . '/>');
    }
    if (str_contains($name, 'дом')) {
        return stroymarket_icon_svg('<path d="M4 11.5 12 4l8 7.5V20H4v-8.5z" ' . $stroke . '/><path d="M10 20v-6h4v6" ' . $stroke . '/>');
    }
    if (str_contains($name, 'отдел')) {
        return stroymarket_icon_svg('<path d="M5 19h14" ' . $stroke . '/><path d="M7 19V9l10-3v13" ' . $stroke . '/><path d="M10 19v-4h4v4" ' . $stroke . '/>');
    }
    return stroymarket_icon_svg('<rect x="4" y="4" width="7" height="7" rx="1.2" ' . $stroke . '/><rect x="13" y="4" width="7" height="7" rx="1.2" ' . $stroke . '/><rect x="4" y="13" width="7" height="7" rx="1.2" ' . $stroke . '/><rect x="13" y="13" width="7" height="7" rx="1.2" ' . $stroke . '/>');
}

function stroymarket_category_covers(array $term_ids): array
{
    $term_ids = array_values(array_unique(array_filter(array_map('intval', $term_ids))));
    sort($term_ids);
    if (!$term_ids) {
        return [];
    }
    $key = 'stroymarket_covers_v2_' . md5(implode(',', $term_ids));
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'sm_cat_product';
    $map = [];
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
        $rows = $wpdb->get_results(
            'SELECT term_id, MIN(product_id) AS product_id FROM ' . $table . ' WHERE term_id IN (' . implode(',', $term_ids) . ') GROUP BY term_id'
        );
        foreach ($rows as $row) {
            $url = function_exists('stroymarket_public_image') ? stroymarket_public_image((int) $row->product_id, 0) : null;
            if (is_string($url) && $url !== '') {
                $map[(int) $row->term_id] = $url;
            }
        }
        $missing = array_diff($term_ids, array_keys($map));
        foreach ($missing as $term_id) {
            $product_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT c.product_id FROM {$table} c INNER JOIN {$wpdb->postmeta} m ON m.post_id = c.product_id AND m.meta_key = '_catalog_images' AND m.meta_value <> '' AND m.meta_value <> '[]' WHERE c.term_id = %d LIMIT 1",
                    $term_id
                )
            );
            $url = $product_id > 0 && function_exists('stroymarket_public_image') ? stroymarket_public_image($product_id, 0) : null;
            if (is_string($url) && $url !== '') {
                $map[$term_id] = $url;
            }
        }
    }
    set_transient($key, $map, 12 * HOUR_IN_SECONDS);
    return $map;
}

function stroymarket_catalog_index_groups(): array
{
    $key = 'stroymarket_catalog_index_v1';
    $cached = get_transient($key);
    if (is_array($cached)) {
        return $cached;
    }
    $parents = stroymarket_menu_categories();
    $parent_ids = array_values(array_filter(array_map(static fn (array $row): int => (int) $row['id'], $parents)));
    $children = [];
    if ($parent_ids) {
        global $wpdb;
        $rows = $wpdb->get_results(
            'SELECT t.term_id, t.name, t.slug, tt.parent
            FROM ' . $wpdb->terms . ' t
            INNER JOIN ' . $wpdb->term_taxonomy . ' tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = \'product_cat\' AND tt.count > 0 AND tt.parent IN (' . implode(',', $parent_ids) . ')
            ORDER BY t.name ASC'
        );
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $slug = (string) $row->slug;
                $name = (string) $row->name;
                if ($slug === 'elektroinstrument' || stroymarket_hidden_category($slug, $name)) {
                    continue;
                }
                $children[(int) $row->parent][] = [
                    'id' => (int) $row->term_id,
                    'name' => $name,
                    'slug' => $slug,
                ];
            }
        }
    }
    $groups = [];
    foreach ($parents as $parent) {
        $parent['children'] = $children[(int) $parent['id']] ?? [];
        $groups[] = $parent;
    }
    set_transient($key, $groups, 12 * HOUR_IN_SECONDS);
    return $groups;
}

function stroymarket_render_catalog_index(): void
{
    echo '<div class="sm-catalog-index">';
    echo '<h1>Каталог</h1>';
    echo '<div class="sm-catalog-grid">';
    foreach (stroymarket_catalog_index_groups() as $group) {
        $name = (string) $group['name'];
        $url = stroymarket_category_url((string) $group['slug']);
        echo '<article class="sm-catalog-group">';
        echo '<a class="sm-catalog-group-mark" href="' . esc_url($url) . '" aria-label="' . esc_attr($name) . '">';
        echo '<span class="sm-side-icon">' . stroymarket_category_icon($name) . '</span></a>';
        echo '<div>';
        echo '<h2><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></h2>';
        if ($group['children']) {
            echo '<ul class="sm-catalog-group-links">';
            foreach ($group['children'] as $child) {
                echo '<li><a href="' . esc_url(stroymarket_category_url((string) $child['slug'])) . '">' . esc_html((string) $child['name']) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</div></article>';
    }
    echo '</div></div>';
}

function stroymarket_query_is_catalog_index(WP_Query $query): bool
{
    return $query->is_main_query()
        && !$query->is_search()
        && !$query->is_tax()
        && $query->is_post_type_archive('product');
}

add_action('pre_get_posts', static function ($query): void {
    if (is_admin() || !$query instanceof WP_Query || !stroymarket_query_is_catalog_index($query)) {
        return;
    }
    $query->set('post__in', [0]);
    $query->set('posts_per_page', 1);
}, 20);

add_filter('woocommerce_show_page_title', static function (bool $show): bool {
    return (function_exists('is_shop') && is_shop() && !is_search()) ? false : $show;
});

add_filter('woocommerce_get_loop_display_mode', static function (string $mode): string {
    return (function_exists('is_shop') && is_shop() && !is_search()) ? 'products' : $mode;
});

add_action('woocommerce_no_products_found', static function (): void {
    if (!function_exists('is_shop') || !is_shop() || is_search()) {
        return;
    }
    remove_action('woocommerce_no_products_found', 'wc_no_products_found', 10);
    stroymarket_render_catalog_index();
}, 1);

function stroymarket_side_link(array $category, array $active): void
{
    $class = in_array($category['id'], $active, true) ? ' class="is-current"' : '';
    echo '<a' . $class . ' href="' . esc_url(stroymarket_category_url($category['slug'])) . '">';
    echo '<span class="sm-side-icon">' . stroymarket_category_icon((string) $category['name']) . '</span>';
    echo '<span class="sm-side-name">' . esc_html($category['name']) . '</span></a>';
}

add_filter('woocommerce_product_loop_start', static function (string $html): string {
    if (!function_exists('woocommerce_get_product_subcategories')) {
        return $html;
    }
    if (!is_shop() && !is_product_category()) {
        return $html;
    }
    $parent = is_product_category() ? (int) get_queried_object_id() : 0;
    $terms = woocommerce_get_product_subcategories($parent);
    $ids = [];
    if (is_array($terms)) {
        foreach ($terms as $term) {
            $ids[] = (int) $term->term_id;
        }
    }
    $GLOBALS['stroymarket_loop_covers'] = stroymarket_category_covers($ids);
    return $html;
}, 9);

remove_action('woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10);
add_action('woocommerce_before_subcategory_title', static function ($category): void {
    if (!$category instanceof WP_Term) {
        return;
    }
    $covers = $GLOBALS['stroymarket_loop_covers'] ?? [];
    $id = (int) $category->term_id;
    if (!is_array($covers) || !array_key_exists($id, $covers)) {
        $covers = stroymarket_category_covers([$id]);
    }
    $placeholder = function_exists('stroymarket_placeholder_url') ? stroymarket_placeholder_url() : '';
    $photo = $covers[$id] ?? $placeholder;
    if ($photo === '') {
        return;
    }
    echo '<span class="sm-photo"><img src="' . esc_url($photo) . '" alt="" width="640" height="640" loading="lazy" decoding="async"></span>';
});

add_filter('woocommerce_product_add_to_cart_text', static function (string $text, $product): string {
    if (!$product instanceof WC_Product || !$product->is_purchasable()) {
        return 'Подробнее';
    }
    return 'В корзину';
}, 20, 2);

add_action('woocommerce_after_shop_loop_item_title', static function (): void {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $class = $product->is_in_stock() ? 'sm-stock sm-stock-in' : 'sm-stock sm-stock-out';
    $label = $product->is_in_stock() ? 'В наличии' : 'Под заказ';
    echo '<span class="' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}, 12);

add_filter('woocommerce_get_availability_text', static function (string $text, $product): string {
    if ($product instanceof WC_Product && !$product->is_in_stock()) {
        return 'Под заказ';
    }
    return $text;
}, 20, 2);

add_filter('woocommerce_product_query_tax_query', static function (array $tax_query): array {
    foreach ($tax_query as $key => $clause) {
        if (!is_array($clause)) {
            continue;
        }
        if (($clause['taxonomy'] ?? '') === 'product_visibility' && ($clause['operator'] ?? '') === 'NOT IN') {
            unset($tax_query[$key]);
        }
    }
    return $tax_query;
});

add_filter('posts_pre_query', static function ($posts, $query) {
    if (!$query instanceof WP_Query || is_admin() || !$query->is_main_query() || !$query->is_tax('product_cat')) {
        return $posts;
    }
    $term = $query->get_queried_object();
    if (!$term instanceof WP_Term || $term->taxonomy !== 'product_cat') {
        return $posts;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'sm_cat_product';
    static $table_ready = null;
    if ($table_ready === null) {
        $table_ready = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
    if (!$table_ready) {
        return $posts;
    }
    $per_page = max(1, (int) $query->get('posts_per_page'));
    $paged = max(1, (int) $query->get('paged'));
    $count_key = 'stroymarket_cat_total_' . $term->term_id;
    $total = get_transient($count_key);
    if (!is_int($total)) {
        $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE term_id = %d", $term->term_id));
        set_transient($count_key, $total, 12 * HOUR_IN_SECONDS);
    }
    $query->found_posts = $total;
    $query->max_num_pages = (int) ceil($total / $per_page);
    if ($total < 1) {
        return [];
    }
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT product_id FROM {$table} WHERE term_id = %d ORDER BY product_id DESC LIMIT %d OFFSET %d",
        $term->term_id,
        $per_page,
        ($paged - 1) * $per_page
    ));
    $ids = array_map('intval', $ids);
    if (!$ids) {
        return [];
    }
    return get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post__in' => $ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($ids),
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'update_post_term_cache' => false,
    ]);
}, 10, 2);

add_action('pre_get_posts', static function ($query): void {
    if (is_admin() || !$query instanceof WP_Query || !$query->is_main_query()) {
        return;
    }
    if (!$query->is_search() && ($query->is_tax('product_cat') || $query->is_post_type_archive('product'))) {
        $query->set('no_found_rows', true);
    }
});

add_filter('found_posts', static function (int $found, $query): int {
    if (!$query instanceof WP_Query || !$query->is_main_query() || is_admin()) {
        return $found;
    }
    if ($query->is_tax('product_cat')) {
        $term = $query->get_queried_object();
        if ($term instanceof WP_Term) {
            return max((int) $term->count, $found);
        }
    }
    return $found;
}, 10, 2);

add_action('wp_enqueue_scripts', static function (): void {
    wp_dequeue_script('wc-cart-fragments');
}, 100);

add_filter('woocommerce_output_related_products_args', static function (array $args): array {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});

require __DIR__ . '/inc/banners.php';
require __DIR__ . '/inc/leads.php';
require __DIR__ . '/inc/pages.php';

function stroymarket_showcase_products(): array
{
    $ids = get_transient('stroymarket_home_products');
    if (!is_array($ids) || !$ids) {
        global $wpdb;
        $candidates = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' LIMIT 48",
                '_catalog_images'
            )
        );
        $ids = [];
        foreach ($candidates as $candidate) {
            $product = wc_get_product((int) $candidate);
            if ($product instanceof WC_Product && $product->get_price() !== '') {
                $ids[] = (int) $candidate;
            }
            if (count($ids) >= 8) {
                break;
            }
        }
        set_transient('stroymarket_home_products', $ids, HOUR_IN_SECONDS);
    }
    $products = [];
    foreach (array_slice((array) $ids, 0, 12) as $id) {
        $product = wc_get_product((int) $id);
        if ($product instanceof WC_Product) {
            $products[] = $product;
        }
    }
    return $products;
}

function stroymarket_home_sale_products(): array
{
    global $wpdb;
    $ids = $wpdb->get_col(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sale_price' AND meta_value <> '' AND CAST(meta_value AS DECIMAL(12,2)) > 0 LIMIT 4"
    );
    $products = [];
    foreach ($ids as $id) {
        $product = wc_get_product((int) $id);
        if ($product instanceof WC_Product && $product->is_visible()) {
            $products[] = $product;
        }
    }
    return $products;
}

function stroymarket_home_card_image(int $product_id): string
{
    $image = function_exists('stroymarket_public_image') ? (string) stroymarket_public_image($product_id, 0) : '';
    if ($image === '' && function_exists('stroymarket_placeholder_url')) {
        $image = stroymarket_placeholder_url();
    }
    return $image;
}

function stroymarket_render_home_strips(): void
{
    echo '<section class="sm-feed" aria-label="Наши акции">';
    echo '<div class="sm-feed-head"><h2>Наши акции</h2>';
    $sales = function_exists('wc_get_product') ? stroymarket_home_sale_products() : [];
    if ($sales) {
        echo '<a href="' . esc_url(home_url('/shop/')) . '">Все акции</a>';
    }
    echo '</div>';
    if (!$sales) {
        echo '<p class="sm-feed-empty">Сейчас действующих акций нет. Цены на сайте совпадают с каталогом.</p>';
    } else {
        echo '<div class="sm-feed-grid">';
        foreach ($sales as $product) {
            $regular = (float) $product->get_regular_price();
            $sale = (float) $product->get_sale_price();
            $percent = $regular > $sale && $regular > 0 ? (int) round((1 - $sale / $regular) * 100) : 0;
            echo '<a class="sm-feed-card" href="' . esc_url($product->get_permalink()) . '">';
            echo '<span class="sm-feed-photo">';
            $image = stroymarket_home_card_image($product->get_id());
            if ($image !== '') {
                echo '<img src="' . esc_url($image) . '" alt="" width="320" height="200">';
            }
            if ($percent > 0) {
                echo '<span class="sm-feed-badge">' . esc_html((string) $percent) . '%</span>';
            }
            echo '</span><span class="sm-feed-body"><span class="sm-feed-title">' . esc_html($product->get_name()) . '</span></span></a>';
        }
        echo '</div>';
    }
    echo '</section>';

    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 4,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    echo '<section class="sm-feed" aria-label="Новости">';
    echo '<div class="sm-feed-head"><h2>Новости</h2><a href="' . esc_url(home_url('/tekhnoblog/')) . '">Все новости</a></div>';
    if (!$posts) {
        echo '<p class="sm-feed-empty">Здесь будут новости компании. Первые материалы ещё готовятся.</p>';
    } else {
        echo '<div class="sm-feed-grid">';
        foreach ($posts as $post) {
            $thumb = get_the_post_thumbnail_url($post, 'medium_large');
            $photo = is_string($thumb) && $thumb !== '' ? $thumb : (function_exists('stroymarket_placeholder_url') ? stroymarket_placeholder_url() : '');
            echo '<a class="sm-feed-card" href="' . esc_url((string) get_permalink($post)) . '">';
            if ($photo !== '') {
                echo '<span class="sm-feed-photo"><img src="' . esc_url($photo) . '" alt="" width="320" height="200"></span>';
            }
            echo '<span class="sm-feed-body"><time class="sm-feed-note" datetime="' . esc_attr(get_the_date('c', $post)) . '">' . esc_html(get_the_date('j F Y', $post)) . '</time>';
            echo '<span class="sm-feed-title">' . esc_html(get_the_title($post)) . '</span></span></a>';
        }
        echo '</div>';
    }
    echo '</section>';

    echo '<section class="sm-feed" aria-label="Видео">';
    echo '<div class="sm-feed-head"><h2>Видео</h2></div>';
    echo '<p class="sm-feed-empty">Видеозаписи ещё готовятся. Когда они появятся, их можно будет посмотреть здесь.</p>';
    echo '</section>';
}

function stroymarket_render_best_prices(): void
{
    $products = stroymarket_showcase_products();
    if (!$products) {
        return;
    }
    echo '<section class="sm-section sm-prices" aria-label="Лучшие цены на рынке">';
    echo '<h2>Лучшие цены на рынке</h2>';
    echo '<div class="sm-price-row">';
    foreach (array_slice($products, 0, 8) as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }
        $id = $product->get_id();
        $image = function_exists('stroymarket_public_image') ? (string) stroymarket_public_image($id, 0) : '';
        if ($image === '' && function_exists('stroymarket_placeholder_url')) {
            $image = stroymarket_placeholder_url();
        }
        $price = $product->get_price();
        echo '<a class="sm-price-card" href="' . esc_url(get_permalink($id)) . '">';
        if ($image !== '') {
            echo '<img src="' . esc_url($image) . '" alt="" width="240" height="160">';
        }
        echo '<span class="sm-price-name">' . esc_html($product->get_name()) . '</span>';
        echo '<span class="sm-price-value">' . ($price !== '' ? wp_kses_post(wc_price($price)) : 'Цена по запросу') . '</span>';
        echo '</a>';
    }
    echo '</div></section>';
}
