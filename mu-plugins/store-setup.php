<?php
/**
 * Plugin Name: Настройки магазина
 * Description: Валюта, доставка и публичные тексты витрины.
 */

const STROYMARKET_TEXT_PATTERNS = [
    '~https?://\S+~iu',
    '~петрович\w*~iu',
    '~русский\s+свет\w*~iu',
    '~мир\s+инструмента~iu',
    '~лемана\s*про~iu',
    '~\bлемана\b~iu',
    '~(?<![\p{L}\p{N}])этм(?![\p{L}\p{N}])~iu',
    '~монолит\s+рус~iu',
    '~клуб[аеу]?\s+друзей~iu',
    '~дилерская\s+цена[^.]{0,80}\.?~iu',
    '~прайс\s+ооо[^.]{0,160}\.?~iu',
];

function stroymarket_clean_text($text)
{
    if (!is_string($text) || $text === '') {
        return $text;
    }

    $clean = preg_replace(STROYMARKET_TEXT_PATTERNS, ' ', $text);
    if (!is_string($clean)) {
        return $text;
    }

    $clean = preg_replace('~\s+~u', ' ', $clean);
    $clean = preg_replace('~\s+([,.;:])~u', '$1', $clean);
    return trim((string) $clean, " \t\n\r\0\x0B,.;:-");
}

add_filter('the_title', function ($title, $post_id) {
    if (is_admin() || get_post_type($post_id) !== 'product') {
        return $title;
    }
    return stroymarket_clean_text($title);
}, 10, 2);

add_filter('the_content', function ($content) {
    if (is_admin()) {
        return $content;
    }
    return stroymarket_clean_text($content);
});

add_filter('woocommerce_product_get_name', function ($name) {
    return stroymarket_clean_text((string) $name);
});

add_filter('woocommerce_product_get_sku', function ($sku) {
    return stroymarket_clean_text((string) $sku);
});

add_filter('get_term', function ($term) {
    if ($term instanceof WP_Term && $term->taxonomy === 'product_cat') {
        $term->name = stroymarket_clean_text($term->name);
    }
    return $term;
});

add_filter('woocommerce_get_price_suffix', function ($suffix, $product) {
    $unit = $product->get_meta('_unit');
    if (!is_string($unit) || $unit === '') {
        return $suffix;
    }
    return ' / ' . esc_html(stroymarket_clean_text($unit));
}, 10, 2);

add_filter('woocommerce_empty_price_html', function () {
    return '<span class="price-on-request">Цена по запросу</span>';
});

add_filter('loop_shop_per_page', function () {
    return 12;
});

add_filter('storefront_copyright_text', function () {
    return '© ' . wp_date('Y') . ' СтройМаркет';
});

function stroymarket_configure_shop()
{
    if (get_option('stroymarket_configured') === '1' || !class_exists('WooCommerce')) {
        return;
    }

    update_option('woocommerce_coming_soon', 'no');
    update_option('woocommerce_store_pages_only', 'no');
    update_option('woocommerce_currency', 'RUB');
    update_option('woocommerce_currency_pos', 'right_space');
    update_option('woocommerce_price_thousand_sep', ' ');
    update_option('woocommerce_price_decimal_sep', ',');
    update_option('woocommerce_price_num_decimals', '2');
    update_option('woocommerce_default_country', 'RU');
    update_option('woocommerce_allowed_countries', 'specific');
    update_option('woocommerce_specific_allowed_countries', ['RU']);
    update_option('woocommerce_ship_to_countries', 'specific');
    update_option('woocommerce_specific_ship_to_countries', ['RU']);
    update_option('woocommerce_calc_taxes', 'no');
    update_option('woocommerce_hide_out_of_stock_items', 'no');
    update_option('blogdescription', 'Строительные материалы и товары для дома');
    update_option('woocommerce_cod_settings', [
        'enabled' => 'yes',
        'title' => 'Оплата при получении',
        'description' => 'Наличными или картой при получении заказа.',
        'instructions' => 'Оплатите заказ при получении.',
        'enable_for_methods' => '',
        'enable_for_virtual' => 'yes',
    ]);
    update_option('woocommerce_bacs_settings', ['enabled' => 'no']);
    update_option('woocommerce_cheque_settings', ['enabled' => 'no']);

    if (class_exists('WC_Shipping_Zones') && WC_Shipping_Zones::get_zones() === []) {
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name('Россия');
        $zone->save();
        $zone->add_location('RU', 'country');
        $pickup_id = (string) $zone->add_shipping_method('local_pickup');
        $delivery_id = (string) $zone->add_shipping_method('flat_rate');
        update_option('woocommerce_local_pickup_' . $pickup_id . '_settings', [
            'title' => 'Самовывоз',
            'tax_status' => 'none',
            'cost' => '0',
        ]);
        update_option('woocommerce_flat_rate_' . $delivery_id . '_settings', [
            'title' => 'Доставка',
            'tax_status' => 'none',
            'cost' => '490',
            'class_costs' => '',
            'calculation_type' => 'class',
        ]);
    }

    update_option('stroymarket_configured', '1');
    flush_rewrite_rules(false);
}

add_action('init', 'stroymarket_configure_shop', 30);
