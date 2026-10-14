<?php

declare(strict_types=1);

const STROYMARKET_PHONE = '+7 495 215 11 60';
const STROYMARKET_PHONE_TEL = '+74952151160';
const STROYMARKET_EMAIL = 'info@superstroy.ru';
const STROYMARKET_ADDRESS = 'Москва, ул. Адмирала Корнилова, 37с7';
const STROYMARKET_TELEGRAM = 'https://t.me/SuperSM15';
const STROYMARKET_WHATSAPP = 'https://wa.me/74952151160';
const STROYMARKET_LEAD_FILE_BYTES = 10485760;

add_action('init', static function (): void {
    register_post_type('sm_lead', [
        'labels' => [
            'name' => 'Заявки',
            'singular_name' => 'Заявка',
            'menu_name' => 'Заявки',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email',
        'supports' => ['title', 'editor'],
        'capability_type' => 'post',
    ]);
}, 10);

add_action('admin_post_nopriv_sm_lead', 'stroymarket_save_lead');
add_action('admin_post_sm_lead', 'stroymarket_save_lead');

function stroymarket_save_lead(): void
{
    $back = wp_get_referer() ?: home_url('/');
    if (!isset($_POST['sm_lead_nonce']) || !wp_verify_nonce(sanitize_text_field((string) $_POST['sm_lead_nonce']), 'sm_lead')) {
        wp_safe_redirect(add_query_arg('sm_lead', 'error', $back));
        exit;
    }
    if (trim((string) ($_POST['sm_company_site'] ?? '')) !== '') {
        wp_safe_redirect(add_query_arg('sm_lead', 'ok', $back));
        exit;
    }
    $name = sanitize_text_field((string) ($_POST['sm_name'] ?? ''));
    $phone = sanitize_text_field((string) ($_POST['sm_phone'] ?? ''));
    if (!preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
        $phone = '';
    }
    $message = sanitize_textarea_field((string) ($_POST['sm_message'] ?? ''));
    $kind = sanitize_key((string) ($_POST['sm_kind'] ?? 'request'));
    if (!in_array($kind, ['callback', 'request'], true)) {
        $kind = 'request';
    }
    if ($phone === '' || $name === '') {
        wp_safe_redirect(add_query_arg('sm_lead', 'error', $back));
        exit;
    }
    $label = $kind === 'callback' ? 'Обратный звонок' : 'Заявка';
    $post_id = wp_insert_post([
        'post_type' => 'sm_lead',
        'post_status' => 'private',
        'post_title' => $label . ': ' . $name . ', ' . $phone,
        'post_content' => $message,
    ]);
    if (!is_int($post_id) || $post_id <= 0) {
        wp_safe_redirect(add_query_arg('sm_lead', 'error', $back));
        exit;
    }
    if (stroymarket_store_lead_file($post_id) === 'file') {
        wp_delete_post($post_id, true);
        wp_safe_redirect(add_query_arg('sm_lead', 'file', $back));
        exit;
    }
    wp_safe_redirect(add_query_arg('sm_lead', 'ok', $back));
    exit;
}

function stroymarket_store_lead_file(int $post_id): string
{
    if (!isset($_FILES['sm_file']) || !is_array($_FILES['sm_file'])) {
        return '';
    }
    $file = $_FILES['sm_file'];
    $name = (string) ($file['name'] ?? '');
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($name === '' || $error === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($error !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > STROYMARKET_LEAD_FILE_BYTES) {
        return 'file';
    }
    $checked = wp_check_filetype_and_ext((string) $file['tmp_name'], $name);
    $extension = strtolower((string) ($checked['ext'] ?? ''));
    if (!in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'], true)) {
        return 'file';
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attachment_id = media_handle_upload('sm_file', $post_id);
    if (is_wp_error($attachment_id)) {
        return 'file';
    }
    return '';
}

function stroymarket_dialog_close(): void
{
    echo '<form method="dialog" class="sm-dialog-dismiss">';
    echo '<button class="sm-dialog-close" type="submit" value="close" aria-label="Закрыть">';
    echo '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6 18 18M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></form>';
}

function stroymarket_lead_form(string $kind, string $title): void
{
    $message = $kind === 'callback'
        ? 'Когда удобно перезвонить'
        : 'Что нужно подобрать или привезти';
    static $form_index = 0;
    $form_index++;
    $name_id = 'sm-name-' . $kind . '-' . $form_index;
    $phone_id = 'sm-phone-' . $kind . '-' . $form_index;
    $message_id = 'sm-message-' . $kind . '-' . $form_index;
    echo '<form class="sm-lead-form" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="sm_lead">';
    echo '<input type="hidden" name="sm_kind" value="' . esc_attr($kind) . '">';
    wp_nonce_field('sm_lead', 'sm_lead_nonce');
    echo '<div class="sm-hp" aria-hidden="true"><label>Сайт<input type="text" name="sm_company_site" tabindex="-1" autocomplete="off"></label></div>';
    echo '<div class="sm-field"><label for="' . esc_attr($name_id) . '">Имя<span class="sm-req" aria-hidden="true">*</span></label>';
    echo '<input id="' . esc_attr($name_id) . '" type="text" name="sm_name" required autocomplete="name" maxlength="80"></div>';
    echo '<div class="sm-field"><label for="' . esc_attr($phone_id) . '">Телефон<span class="sm-req" aria-hidden="true">*</span></label>';
    echo '<input id="' . esc_attr($phone_id) . '" type="tel" name="sm_phone" required inputmode="tel" autocomplete="tel" placeholder="+7 (999) 999-99-99" data-phone-mask maxlength="18" pattern="\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}"></div>';
    echo '<div class="sm-field"><label for="' . esc_attr($message_id) . '">' . esc_html($message) . '</label>';
    echo '<textarea id="' . esc_attr($message_id) . '" name="sm_message" rows="3" maxlength="1000"></textarea></div>';
    if ($kind === 'request') {
        $file_id = 'sm-file-' . $kind . '-' . $form_index;
        echo '<div class="sm-field"><label for="' . esc_attr($file_id) . '">Файл заявки или сметы</label>';
        echo '<input id="' . esc_attr($file_id) . '" type="file" name="sm_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">';
        echo '<span class="sm-field-note">PDF, Word, Excel или фото, до 10 МБ.</span></div>';
    }
    echo '<button class="sm-btn" type="submit">' . esc_html($title) . '</button>';
    echo '</form>';
}

add_action('wp_footer', static function (): void {
    $ok = isset($_GET['sm_lead']) && $_GET['sm_lead'] === 'ok';
    $error = isset($_GET['sm_lead']) && $_GET['sm_lead'] === 'error';
    $file_error = isset($_GET['sm_lead']) && $_GET['sm_lead'] === 'file';
    $phone_icon = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M8.2 4.8h2.2l1.1 3-1.6 1.1a12 12 0 0 0 5.2 5.2l1.1-1.6 3 1.1v2.2c0 .8-.6 1.5-1.4 1.6A15 15 0 0 1 6.6 6.2c.1-.8.8-1.4 1.6-1.4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
    $form_icon = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M7 4.5h7l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9.5A1.5 1.5 0 0 1 5.5 19V6A1.5 1.5 0 0 1 7 4.5z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M14 4.5V9h4.5M8.5 13h7M8.5 16.5h5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
    echo '<div class="sm-rail">';
    echo '<button class="sm-rail-btn" type="button" data-sm-dialog="sm-dialog-callback" aria-label="Заказать звонок">';
    echo '<span class="sm-rail-label">Заказать звонок</span><span class="sm-rail-icon">' . $phone_icon . '</span></button>';
    echo '<button class="sm-rail-btn" type="button" data-sm-dialog="sm-dialog-request" aria-label="Отправить заявку">';
    echo '<span class="sm-rail-label">Отправить заявку</span><span class="sm-rail-icon">' . $form_icon . '</span></button>';
    echo '</div>';

    echo '<dialog class="sm-dialog" id="sm-dialog-callback">';
    echo '<div class="sm-dialog-head"><h2>Заказать звонок</h2>';
    stroymarket_dialog_close();
    echo '</div>';
    echo '<p>Перезвоним на номер, который вы укажете. Телефон офиса: <a href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">' . esc_html(STROYMARKET_PHONE) . '</a>.</p>';
    stroymarket_lead_form('callback', 'Жду звонка');
    echo '</dialog>';

    echo '<dialog class="sm-dialog sm-dialog-city" id="sm-dialog-city">';
    echo '<div class="sm-dialog-head"><h2>Ваш город</h2>';
    stroymarket_dialog_close();
    echo '</div>';
    echo '<p>Город определяется по сети и запоминается в браузере. Офис и склад — в Москве.</p>';
    echo '<div class="sm-city-list">';
    foreach (['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Казань', 'Нижний Новгород', 'Челябинск', 'Самара', 'Ростов-на-Дону', 'Уфа', 'Красноярск', 'Воронеж', 'Пермь', 'Волгоград', 'Краснодар'] as $city) {
        echo '<button type="button" data-sm-city-pick="' . esc_attr($city) . '">' . esc_html($city) . '</button>';
    }
    echo '</div></dialog>';

    echo '<dialog class="sm-dialog" id="sm-dialog-request">';
    echo '<div class="sm-dialog-head"><h2>Отправить заявку</h2>';
    stroymarket_dialog_close();
    echo '</div>';
    echo '<p>Пришлите список материалов. Подготовим предложение с учётом сроков и задач объекта.</p>';
    stroymarket_lead_form('request', 'Отправить заявку');
    echo '</dialog>';

    echo '<dialog class="sm-dialog" id="sm-dialog-manager">';
    echo '<div class="sm-dialog-head"><h2>Менеджер</h2>';
    stroymarket_dialog_close();
    echo '</div>';
    echo '<p>Напишите или позвоните — ответит менеджер офиса.</p>';
    echo '<div class="sm-manager">';
    echo '<a class="sm-tg" href="' . esc_url(STROYMARKET_TELEGRAM) . '" target="_blank" rel="noopener noreferrer">';
    echo '<span class="sm-tg-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22"><path fill="#fff" d="M20.4 4.6 3.7 11c-1.1.4-1.1 1.1-.2 1.3l4.3 1.3 1.6 5.1c.2.6.1.8.7.8.4 0 .6-.2.9-.5l2.2-2.1 4.4 3.3c.8.4 1.4.2 1.6-.8l2.8-13.7c.3-1.3-.5-1.9-1.6-1.1z"/></svg></span>';
    echo '<span>Telegram</span></a>';
    echo '<a href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">Позвонить ' . esc_html(STROYMARKET_PHONE) . '</a>';
    echo '<a href="mailto:' . esc_attr(STROYMARKET_EMAIL) . '">Написать на почту</a>';
    echo '<button type="button" data-sm-dialog="sm-dialog-callback">Заказать звонок</button>';
    echo '</div></dialog>';
    echo '<div class="sm-messenger">';
    echo '<button class="sm-messenger-toggle" type="button" data-sm-dialog="sm-dialog-manager" aria-label="Написать менеджеру">';
    echo '<span class="sm-messenger-pulse" aria-hidden="true"></span>';
    echo '<span class="sm-messenger-mark" aria-hidden="true"></span>';
    echo '</button></div>';

    if ($ok) {
        echo '<p class="sm-toast" role="status">Заявка принята. Свяжемся с вами.</p>';
    } elseif ($file_error) {
        echo '<p class="sm-toast sm-toast--error" role="alert">Файл не принят. Подойдут PDF, Word, Excel или фото до 10 МБ.</p>';
    } elseif ($error) {
        echo '<p class="sm-toast sm-toast--error" role="alert">Укажите имя и телефон и отправьте форму ещё раз.</p>';
    }
}, 30);

add_action('wp_enqueue_scripts', static function (): void {
    $path = get_stylesheet_directory() . '/assets/site.js';
    if (!is_file($path)) {
        return;
    }
    wp_enqueue_script('stroymarket-site', get_stylesheet_directory_uri() . '/assets/site.js', [], (string) filemtime($path), true);
}, 20);

function stroymarket_footer_links(array $groups, string $more_url = ''): void
{
    echo '<ul>';
    foreach ($groups as $group) {
        echo '<li><a href="' . esc_url(stroymarket_category_url($group['slug'])) . '">' . esc_html($group['name']) . '</a></li>';
    }
    if ($more_url !== '') {
        echo '<li><a href="' . esc_url($more_url) . '">Весь каталог</a></li>';
    }
    echo '</ul>';
}

function stroymarket_social_asset(string $file): string
{
    $path = get_stylesheet_directory() . '/assets/social/' . $file . '.png';
    $src = get_stylesheet_directory_uri() . '/assets/social/' . $file . '.png';
    if (is_file($path)) {
        $src .= '?v=' . filemtime($path);
    }
    return $src;
}

function stroymarket_theme_asset(string $relative): string
{
    $path = get_stylesheet_directory() . $relative;
    $src = get_stylesheet_directory_uri() . $relative;
    if (is_file($path)) {
        $src .= '?v=' . filemtime($path);
    }
    return $src;
}

function stroymarket_side_bottom(): void
{
    $banners = [
        ['/kalkulyator/', 'Калькуляторы', '/assets/calculators/calc-frame.jpg'],
        ['/poleznoe/#zayavka', 'Оставить заявку', '/assets/side/side-request.jpg'],
    ];
    echo '<div class="sm-side-banners">';
    foreach ($banners as [$path, $label, $file]) {
        echo '<a class="sm-side-banner" href="' . esc_url(home_url($path)) . '">';
        echo '<img src="' . esc_url(stroymarket_theme_asset($file)) . '" alt="" width="480" height="300">';
        echo '<span>' . esc_html($label) . '</span></a>';
    }
    echo '</div>';
    $contacts = home_url('/kontakty/');
    $networks = [
        ['dzen', 'Мы в Дзен', $contacts, false],
        ['vk', 'Мы в VK', $contacts, false],
        ['max', 'Мы в MAX', $contacts, false],
        ['telegram', 'Мы в Telegram', STROYMARKET_TELEGRAM, true],
    ];
    echo '<div class="sm-side-social">';
    foreach ($networks as [$file, $label, $url, $external]) {
        echo '<a class="sm-side-social-' . esc_attr($file) . '" href="' . esc_url($url) . '"';
        if ($external) {
            echo ' target="_blank" rel="noopener noreferrer"';
        }
        echo '><img src="' . esc_url(stroymarket_social_asset($file)) . '" alt="" width="26" height="26"><span>' . esc_html($label) . '</span></a>';
    }
    echo '</div>';
}

function stroymarket_footer_social(): void
{
    $contacts = home_url('/kontakty/');
    $networks = [
        ['vk', 'ВКонтакте', $contacts, false],
        ['telegram', 'Telegram', STROYMARKET_TELEGRAM, true],
        ['dzen', 'Дзен', $contacts, false],
        ['youtube', 'YouTube', $contacts, false],
        ['rutube', 'Rutube', $contacts, false],
        ['instagram', 'Instagram', $contacts, false],
        ['max', 'Макс', $contacts, false],
        ['whatsapp', 'WhatsApp', STROYMARKET_WHATSAPP, true],
    ];
    echo '<div class="sm-footer-social">';
    foreach ($networks as [$file, $label, $url, $external]) {
        echo '<a href="' . esc_url($url) . '"';
        if ($external) {
            echo ' target="_blank" rel="noopener noreferrer"';
        }
        echo ' aria-label="' . esc_attr($label) . '">';
        echo '<img src="' . esc_url(stroymarket_social_asset($file)) . '" alt="" width="36" height="36"></a>';
    }
    echo '</div>';
}

function stroymarket_render_footer(): void
{
    $groups = function_exists('stroymarket_menu_categories') ? stroymarket_menu_categories() : [];
    $shop = function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : home_url('/');
    $split = (int) ceil(count($groups) / 2);
    $phone_icon = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M8.2 4.8h2.2l1.1 3-1.6 1.1a12 12 0 0 0 5.2 5.2l1.1-1.6 3 1.1v2.2c0 .8-.6 1.5-1.4 1.6A15 15 0 0 1 6.6 6.2c.1-.8.8-1.4 1.6-1.4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
    $mail_icon = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4.5 7.5h15v9h-15z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m5 8 7 5 7-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
    $pin_icon = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="11" r="2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>';
    echo '<div class="sm-footer">';
    echo '<div><h2><a href="' . esc_url($shop) . '">Каталог</a></h2>';
    stroymarket_footer_links(array_slice($groups, 0, $split));
    echo '</div>';
    echo '<div><h2 class="sm-footer-gap" aria-hidden="true"></h2>';
    stroymarket_footer_links(array_slice($groups, $split), $shop);
    echo '</div>';
    echo '<div><h2><a href="' . esc_url(home_url('/poleznoe/')) . '">Полезное</a></h2><ul>';
    echo '<li><a href="' . esc_url(home_url('/poleznoe/#zayavka')) . '">Как оставить заявку</a></li>';
    echo '<li><a href="' . esc_url(home_url('/poleznoe/#oplata')) . '">Оплата</a></li>';
    echo '<li><a href="' . esc_url(home_url('/poleznoe/#dostavka')) . '">Доставка</a></li>';
    echo '<li><a href="' . esc_url(home_url('/kalkulyator/dostavka/')) . '">Стоимость доставки</a></li>';
    echo '</ul></div>';
    echo '<div><h2><a href="' . esc_url(home_url('/kompaniya/')) . '">Компания</a></h2><ul>';
    echo '<li><a href="' . esc_url(home_url('/kompaniya/')) . '">О компании</a></li>';
    echo '<li><a href="' . esc_url(home_url('/kalkulyator/')) . '">Калькуляторы</a></li>';
    echo '<li><a href="' . esc_url(home_url('/tekhnoblog/')) . '">Стройблог</a></li>';
    echo '<li><a href="' . esc_url(home_url('/kontakty/')) . '">Контакты</a></li>';
    echo '</ul></div>';
    echo '<div class="sm-footer-contacts">';
    echo '<p class="sm-footer-phone"><a href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">' . $phone_icon . esc_html(STROYMARKET_PHONE) . '</a></p>';
    echo '<button class="sm-footer-callback" type="button" data-sm-dialog="sm-dialog-callback">Заказать звонок</button>';
    echo '<p class="sm-footer-line"><a href="mailto:' . esc_attr(STROYMARKET_EMAIL) . '">' . $mail_icon . esc_html(STROYMARKET_EMAIL) . '</a></p>';
    echo '<p class="sm-footer-line">' . $pin_icon . '<span>' . esc_html(STROYMARKET_ADDRESS) . '</span></p>';
    echo '</div></div>';
    stroymarket_footer_social();
    echo '<div class="site-info"><p>© ' . esc_html(gmdate('Y')) . ' ООО «СуперСтрой-М». Поставка строительных материалов оптом.</p>';
    echo '<p>Стоимость на сайте не является публичной офертой. Итоговые условия фиксируются в счёте.</p></div>';
}
