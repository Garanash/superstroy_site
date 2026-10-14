<?php
/**
 * Plugin Name: Фото товаров
 * Description: Показывает фотографии товара через локальный адрес магазина.
 */

const CATALOG_IMAGE_CACHE_LIMIT = 8388608;

function stroymarket_image_list($product_id)
{
    $raw = get_post_meta($product_id, '_catalog_images', true);
    $decoded = json_decode(is_string($raw) ? $raw : '', true);
    if (!is_array($decoded)) {
        return [];
    }

    $images = [];
    foreach ($decoded as $url) {
        if (is_string($url) && preg_match('#^https://#i', $url)) {
            $images[] = $url;
        }
    }
    return $images;
}

function stroymarket_media_url($product_id, $index)
{
    return add_query_arg(
        [
            'catalog_media' => (int) $product_id,
            'i' => (int) $index,
        ],
        home_url('/')
    );
}

function stroymarket_placeholder_image()
{
    status_header(200);
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800"><rect width="800" height="800" fill="#e7efe9"/><rect x="230" y="210" width="340" height="280" rx="18" fill="none" stroke="#1b4332" stroke-width="10"/><circle cx="330" cy="300" r="28" fill="#1b4332"/><path d="M260 450l95-80 70 55 55-40 60 65" fill="none" stroke="#1b4332" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    exit;
}

function stroymarket_cache_path($product_id, $index)
{
    $directory = WP_CONTENT_DIR . '/cache/catalog-images';
    if (!is_dir($directory)) {
        wp_mkdir_p($directory);
    }
    return $directory . '/' . (int) $product_id . '-' . (int) $index . '.bin';
}

function stroymarket_stream_image($product_id, $index)
{
    $images = stroymarket_image_list($product_id);
    if (!isset($images[$index])) {
        stroymarket_placeholder_image();
    }

    $cache = stroymarket_cache_path($product_id, $index);
    $type_path = $cache . '.type';
    if (is_readable($cache) && is_readable($type_path)) {
        $type = trim((string) file_get_contents($type_path));
        if (strpos($type, 'image/') === 0) {
            header('Content-Type: ' . $type);
            header('Cache-Control: public, max-age=604800');
            readfile($cache);
            exit;
        }
    }

    $response = wp_remote_get($images[$index], [
        'timeout' => 12,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    ]);
    if (is_wp_error($response)) {
        stroymarket_placeholder_image();
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $type = (string) wp_remote_retrieve_header($response, 'content-type');
    $type = trim(strtok($type, ';'));
    $body = wp_remote_retrieve_body($response);
    if ($code !== 200 || strpos($type, 'image/') !== 0 || $body === '' || strlen($body) > CATALOG_IMAGE_CACHE_LIMIT) {
        stroymarket_placeholder_image();
    }

    file_put_contents($cache, $body);
    file_put_contents($type_path, $type);
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=604800');
    echo $body;
    exit;
}

add_action('init', function () {
    if (!isset($_GET['catalog_media'])) {
        return;
    }
    $product_id = (int) $_GET['catalog_media'];
    $index = isset($_GET['i']) ? (int) $_GET['i'] : 0;
    if ($product_id < 1 || $index < 0) {
        status_header(404);
        exit;
    }
    stroymarket_stream_image($product_id, $index);
}, 0);

function stroymarket_img_tag($product, $index, $class)
{
    $product_id = $product->get_id();
    if (count(stroymarket_image_list($product_id)) <= $index) {
        return '';
    }
    return '<img src="' . esc_url(stroymarket_media_url($product_id, $index)) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($class) . '" loading="lazy" />';
}

add_filter('woocommerce_product_get_image', function ($image, $product) {
    $tag = stroymarket_img_tag($product, 0, 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail');
    return $tag !== '' ? $tag : $image;
}, 10, 2);

add_filter('woocommerce_cart_item_thumbnail', function ($thumbnail, $cart_item) {
    if (!isset($cart_item['data']) || !is_object($cart_item['data'])) {
        return $thumbnail;
    }
    $tag = stroymarket_img_tag($cart_item['data'], 0, 'attachment-woocommerce_thumbnail');
    return $tag !== '' ? $tag : $thumbnail;
}, 10, 2);

add_action('wp', function () {
    if (!is_product()) {
        return;
    }
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    add_action('woocommerce_before_single_product_summary', 'stroymarket_product_gallery', 20);
});

function stroymarket_product_gallery()
{
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }

    $count = count(stroymarket_image_list($product->get_id()));
    echo '<div class="catalog-gallery">';
    if ($count === 0) {
        echo wc_placeholder_img('woocommerce_single');
        echo '</div>';
        return;
    }

    echo stroymarket_img_tag($product, 0, 'catalog-gallery__main');
    if ($count > 1) {
        echo '<div class="catalog-gallery__thumbs">';
        for ($index = 0; $index < $count; $index++) {
            echo stroymarket_img_tag($product, $index, 'catalog-gallery__thumb');
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<script>document.currentScript.previousElementSibling.querySelectorAll(".catalog-gallery__thumb").forEach(function(thumb){thumb.addEventListener("click",function(){var main=thumb.closest(".catalog-gallery").querySelector(".catalog-gallery__main");if(main){main.src=thumb.src;}});});</script>';
}
