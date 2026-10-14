<?php

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    add_theme_support('title-tag');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'stroymarket',
        get_stylesheet_uri(),
        ['storefront-style'],
        '1.0.0'
    );
});

add_action('storefront_header', function () {
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => 0,
        'hide_empty' => true,
        'number' => 12,
        'orderby' => 'count',
        'order' => 'DESC',
    ]);
    if (is_wp_error($terms) || !$terms) {
        return;
    }

    echo '<nav class="shop-departments col-full" aria-label="Разделы">';
    foreach ($terms as $term) {
        $link = get_term_link($term);
        if (is_wp_error($link)) {
            continue;
        }
        echo '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
    }
    echo '</nav>';
}, 45);

function stroymarket_items_label($count)
{
    $count = (int) $count;
    $mod100 = abs($count) % 100;
    $mod10 = $mod100 % 10;
    if ($mod100 > 10 && $mod100 < 20) {
        $word = 'товаров';
    } elseif ($mod10 > 1 && $mod10 < 5) {
        $word = 'товара';
    } elseif ($mod10 === 1) {
        $word = 'товар';
    } else {
        $word = 'товаров';
    }
    return number_format($count, 0, '', ' ') . ' ' . $word;
}
