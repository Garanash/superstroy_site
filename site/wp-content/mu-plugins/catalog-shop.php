<?php
/**
 * Plugin Name: Каталог магазина
 * Description: Картинки товаров, цены и оформление заказа без внешних витрин.
 */

declare(strict_types=1);

const STROYMARKET_IMAGE_CACHE = WP_CONTENT_DIR . '/cache/catalog-images';
const STROYMARKET_IMAGE_TTL = 604800;

add_action('template_redirect', static function (): void {
    if (isset($_GET['sm_placeholder'])) {
        stroymarket_stream_placeholder();
    }
    if (!isset($_GET['sm_img'])) {
        return;
    }
    $product_id = absint(wp_unslash($_GET['sm_img']));
    if ($product_id < 1) {
        stroymarket_stream_placeholder();
    }
    $index = absint(wp_unslash($_GET['sm_i'] ?? 0));
    $url = stroymarket_image_source($product_id, $index);
    if ($url === null) {
        stroymarket_stream_placeholder();
    }
    stroymarket_stream_image($product_id, $index, $url);
}, 0);

add_filter('woocommerce_product_get_image', static function ($image, $product) {
    if (!$product instanceof WC_Product) {
        return $image;
    }
    return stroymarket_photo_html($product->get_id(), $product->get_name(), 'sm-photo');
}, 10, 2);

add_filter('woocommerce_placeholder_img_src', static fn (): string => stroymarket_placeholder_url());

add_filter('woocommerce_cart_item_thumbnail', static function ($thumbnail, $cart_item) {
    $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
    $product = $cart_item['data'] ?? null;
    $alt = $product instanceof WC_Product ? $product->get_name() : '';
    return stroymarket_photo_html($product_id, $alt, 'sm-photo sm-photo--cart');
}, 10, 2);

add_filter('woocommerce_store_api_cart_item_images', static function ($images, $cart_item) {
    $product = $cart_item['data'] ?? null;
    $product_id = $product instanceof WC_Product ? (int) $product->get_id() : (int) ($cart_item['product_id'] ?? 0);
    $src = stroymarket_public_image($product_id, 0) ?? stroymarket_placeholder_url();
    $name = $product instanceof WC_Product ? $product->get_name() : '';

    return [
        (object) [
            'id' => $product_id,
            'src' => $src,
            'thumbnail' => $src,
            'srcset' => '',
            'sizes' => '',
            'thumbnail_srcset' => '',
            'thumbnail_sizes' => '',
            'name' => $name,
            'alt' => $name,
        ],
    ];
}, 10, 2);

add_action('wp', static function (): void {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    add_action('woocommerce_before_single_product_summary', 'stroymarket_render_gallery', 20);
});

add_filter('woocommerce_get_price_suffix', static function ($suffix, $product) {
    if (!$product instanceof WC_Product) {
        return $suffix;
    }
    $unit = trim((string) $product->get_meta('_unit'));
    if ($unit === '') {
        return $suffix;
    }
    return ' <span class="price-unit">/ ' . esc_html($unit) . '</span>';
}, 10, 2);

add_filter('woocommerce_empty_price_html', static fn (): string => '<span class="price-on-request">Цена по запросу</span>');

add_filter('the_content', 'stroymarket_scrub_text');
add_filter('woocommerce_product_get_name', 'stroymarket_scrub_text');
add_filter('woocommerce_product_get_description', 'stroymarket_scrub_text');
add_filter('woocommerce_product_get_short_description', 'stroymarket_scrub_text');

add_action('woocommerce_init', static function (): void {
    if (get_option('stroymarket_commerce_ready')) {
        return;
    }
    stroymarket_configure_store();
    update_option('stroymarket_commerce_ready', '1', false);
});

function stroymarket_scrub_text($text)
{
    if (!is_string($text) || $text === '') {
        return $text;
    }
    $text = preg_replace('#https?://[^\s<>"\']+#iu', '', $text) ?? $text;
    $patterns = [
        '/петрович\w*/iu',
        '/русский\s+свет\w*/iu',
        '/мир\s+инструмента/iu',
        '/лемана\s*про/iu',
        '/\bлемана\b/iu',
        '/\bэтм\b/iu',
        '/клуб[аеу]?\s+друзей/iu',
        '/ооо\s*[«"]?\s*монолит\s+рус[»"]?/iu',
        '/монолит\s+рус/iu',
        '/дилерская\s+цена[^.]{0,80}\.?/iu',
        '/прайс\s+ооо[^.]{0,160}\.?/iu',
    ];
    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text) ?? $text;
    }
    return trim(preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text);
}

function stroymarket_placeholder_url(): string
{
    return add_query_arg(['sm_placeholder' => '1', 'v' => '4'], home_url('/'));
}

function stroymarket_photo_html(int $product_id, string $alt, string $class): string
{
    $photo = stroymarket_public_image($product_id, 0);
    $placeholder = stroymarket_placeholder_url();
    $src = $photo ?? $placeholder;
    $fallback = $photo === null ? ' is-placeholder' : '';

    return '<span class="' . esc_attr($class . $fallback) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" width="640" height="640" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=\'' . esc_url($placeholder) . '\';this.parentElement.classList.add(\'is-placeholder\');"></span>';
}

function stroymarket_stream_placeholder(): void
{
    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="640" viewBox="0 0 640 640" role="img" aria-label="Нет фото">
  <rect width="640" height="640" fill="#f8f8f8"/>
  <rect x="176" y="188" width="288" height="220" rx="16" fill="#ffffff" stroke="#222222" stroke-width="8"/>
  <circle cx="262" cy="262" r="26" fill="#f3c805"/>
  <path d="M206 372l78-70 48 42 46-62 56 90" fill="none" stroke="#222222" stroke-width="8" stroke-linejoin="round" stroke-linecap="round"/>
  <text x="320" y="470" text-anchor="middle" font-family="Segoe UI, sans-serif" font-size="28" fill="#626262">Нет фото</text>
</svg>
SVG;
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=' . STROYMARKET_IMAGE_TTL);
    header('X-Content-Type-Options: nosniff');
    echo $svg;
    exit;
}

function stroymarket_public_image(int $product_id, int $index): ?string
{
    if (stroymarket_image_source($product_id, $index) === null) {
        return null;
    }
    return add_query_arg(
        [
            'sm_img' => $product_id,
            'sm_i' => $index,
        ],
        home_url('/')
    );
}

function stroymarket_image_source(int $product_id, int $index): ?string
{
    $raw = get_post_meta($product_id, '_catalog_images', true);
    $urls = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($urls) || !isset($urls[$index]) || !is_string($urls[$index])) {
        return null;
    }
    $url = $urls[$index];
    if (!preg_match('#^https://#', $url)) {
        return null;
    }
    $path = (string) parse_url($url, PHP_URL_PATH);
    if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $path)) {
        return null;
    }
    return $url;
}

function stroymarket_stream_image(int $product_id, int $index, string $url): void
{
    if (!is_dir(STROYMARKET_IMAGE_CACHE)) {
        wp_mkdir_p(STROYMARKET_IMAGE_CACHE);
    }
    $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        $extension = 'jpg';
    }
    $file = STROYMARKET_IMAGE_CACHE . '/' . $product_id . '-' . $index . '.' . $extension;
    if (!is_file($file)) {
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'redirection' => 3,
            'user-agent' => 'Mozilla/5.0',
            'headers' => ['Accept' => 'image/avif,image/webp,image/*,*/*;q=0.8'],
        ]);
        $body = is_wp_error($response) ? '' : (string) wp_remote_retrieve_body($response);
        $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        $type = is_wp_error($response) ? '' : (string) wp_remote_retrieve_header($response, 'content-type');
        if ($code !== 200 || $body === '' || stripos($type, 'image/') !== 0) {
            stroymarket_stream_placeholder();
        }
        file_put_contents($file, $body);
    }
    $mime = stroymarket_detect_mime($file);
    if (!str_starts_with($mime, 'image/')) {
        unlink($file);
        stroymarket_stream_placeholder();
    }
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=' . STROYMARKET_IMAGE_TTL);
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);
    exit;
}

function stroymarket_detect_mime(string $path): string
{
    $handle = fopen($path, 'rb');
    $head = $handle ? (string) fread($handle, 16) : '';
    if (is_resource($handle)) {
        fclose($handle);
    }
    if (str_starts_with($head, "\xFF\xD8\xFF")) {
        return 'image/jpeg';
    }
    if (str_starts_with($head, "\x89PNG")) {
        return 'image/png';
    }
    if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    if (str_starts_with($head, 'GIF8')) {
        return 'image/gif';
    }
    return 'application/octet-stream';
}

function stroymarket_render_gallery(): void
{
    $product = wc_get_product(get_the_ID());
    if (!$product instanceof WC_Product) {
        return;
    }
    $sources = [];
    for ($index = 0; $index < 6; $index++) {
        $src = stroymarket_public_image($product->get_id(), $index);
        if ($src === null) {
            break;
        }
        $sources[] = $src;
    }
    $placeholder = stroymarket_placeholder_url();
    $alt = esc_attr($product->get_name());
    echo '<div class="images catalog-gallery">';
    if (!$sources) {
        echo '<span class="sm-photo sm-photo--stage is-placeholder"><img class="catalog-gallery__main" src="' . esc_url($placeholder) . '" alt="' . $alt . '" width="640" height="640"></span>';
        echo '</div>';
        return;
    }
    echo '<span class="sm-photo sm-photo--stage"><img class="catalog-gallery__main" src="' . esc_url($sources[0]) . '" alt="' . $alt . '" width="640" height="640" onerror="this.onerror=null;this.src=\'' . esc_url($placeholder) . '\';this.parentElement.classList.add(\'is-placeholder\');"></span>';
    if (count($sources) > 1) {
        echo '<div class="catalog-gallery__thumbs">';
        foreach ($sources as $number => $src) {
            $label = 'Фото ' . ($number + 1);
            echo '<button type="button" aria-label="' . esc_attr($label) . '"><img src="' . esc_url($src) . '" alt="" width="96" height="96" loading="lazy" onerror="this.onerror=null;this.src=\'' . esc_url($placeholder) . '\';"></button>';
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<script>(function(gallery){if(!gallery)return;var main=gallery.querySelector(".catalog-gallery__main");gallery.querySelectorAll(".catalog-gallery__thumbs button").forEach(function(button){button.addEventListener("click",function(){var image=button.querySelector("img");if(!image||!main)return;main.src=image.src;gallery.querySelectorAll("button").forEach(function(item){item.classList.remove("is-active");});button.classList.add("is-active");});});})(document.currentScript.previousElementSibling);</script>';
}

function stroymarket_configure_store(): void
{
    update_option('woocommerce_coming_soon', 'no');
    update_option('woocommerce_store_pages_only', 'no');
    update_option('woocommerce_currency', 'RUB');
    update_option('woocommerce_currency_pos', 'right_space');
    update_option('woocommerce_price_thousand_sep', ' ');
    update_option('woocommerce_price_decimal_sep', ',');
    update_option('woocommerce_default_country', 'RU');
    update_option('woocommerce_allowed_countries', 'all');
    update_option('woocommerce_calc_taxes', 'no');
    update_option('woocommerce_hide_out_of_stock_items', 'no');
    update_option('woocommerce_shop_page_display', '');
    update_option('woocommerce_category_archive_display', 'both');
    update_option('woocommerce_cod_settings', [
        'enabled' => 'yes',
        'title' => 'Оплата при получении',
        'description' => 'Наличными или картой при получении заказа.',
        'instructions' => '',
        'enable_for_methods' => '',
        'enable_for_virtual' => 'yes',
    ]);

    if (!class_exists('WC_Shipping_Zones') || WC_Shipping_Zones::get_zones()) {
        return;
    }
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Россия');
    $zone->save();
    $zone->add_location('RU', 'country');
    $pickup_id = $zone->add_shipping_method('local_pickup');
    $delivery_id = $zone->add_shipping_method('flat_rate');
    update_option('woocommerce_local_pickup_' . $pickup_id . '_settings', [
        'title' => 'Самовывоз',
        'tax_status' => 'none',
        'cost' => '0',
    ]);
    update_option('woocommerce_flat_rate_' . $delivery_id . '_settings', [
        'title' => 'Доставка',
        'tax_status' => 'none',
        'cost' => '0',
    ]);
}
