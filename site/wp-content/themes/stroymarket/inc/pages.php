<?php

declare(strict_types=1);

const STROYMARKET_PAGES_VERSION = '1';

const STROYMARKET_DELIVERY_MOSCOW = 2500;
const STROYMARKET_DELIVERY_KM = 45;

add_action('init', static function (): void {
    if (get_option('stroymarket_site_pages') === STROYMARKET_PAGES_VERSION) {
        return;
    }
    $pages = stroymarket_seed_pages();
    stroymarket_seed_menu($pages);
    update_option('stroymarket_site_pages', STROYMARKET_PAGES_VERSION, false);
}, 30);

add_shortcode('sm_company', static function (): string {
    ob_start();
    echo '<p>ООО «СуперСтрой-М» поставляет строительные материалы оптом и комплектует заказы под объект.</p>';
    echo '<p>Склад: ' . esc_html(STROYMARKET_ADDRESS) . '. Доставка от 4 часов по Москве и от 1 дня по России.</p>';
    echo '<p>Телефон <a href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">' . esc_html(STROYMARKET_PHONE) . '</a>, почта <a href="mailto:' . esc_attr(STROYMARKET_EMAIL) . '">' . esc_html(STROYMARKET_EMAIL) . '</a>.</p>';
    return (string) ob_get_clean();
});

add_shortcode('sm_useful', static function (): string {
    ob_start();
    echo '<h2 id="zayavka">Как оставить заявку</h2>';
    echo '<p>Подойдут фото, рукописный список или спецификация. Мы уточняем спорные позиции и готовим предложение под сроки объекта.</p>';
    echo '<h2 id="oplata">Оплата</h2>';
    echo '<p>Работаем по счёту. Состав, цена и срок поставки фиксируются в счёте до отгрузки.</p>';
    echo '<h2 id="dostavka">Доставка</h2>';
    echo '<p>По Москве доставка от 4 часов, по России от 1 дня. Ориентир по стоимости можно посчитать в <a href="' . esc_url(home_url('/kalkulyator/dostavka/')) . '">калькуляторе доставки</a>. Точную сумму подтверждаем в счёте.</p>';
    return (string) ob_get_clean();
});

add_shortcode('sm_contacts', static function (): string {
    ob_start();
    echo '<p>' . esc_html(STROYMARKET_ADDRESS) . '</p>';
    echo '<p><a href="tel:' . esc_attr(STROYMARKET_PHONE_TEL) . '">' . esc_html(STROYMARKET_PHONE) . '</a></p>';
    echo '<p><a href="mailto:' . esc_attr(STROYMARKET_EMAIL) . '">' . esc_html(STROYMARKET_EMAIL) . '</a></p>';
    stroymarket_lead_form('request', 'Отправить заявку');
    return (string) ob_get_clean();
});

add_action('init', static function (): void {
    if (get_option('stroymarket_blog_name') === 'stroyblog') {
        return;
    }
    $page = get_page_by_path('tekhnoblog');
    if ($page instanceof WP_Post && $page->post_title !== 'Стройблог') {
        wp_update_post([
            'ID' => $page->ID,
            'post_title' => 'Стройблог',
        ]);
    }
    global $wpdb;
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'nav_menu_item' AND post_title = %s",
        'Техноблог'
    ));
    $items = array_map('get_post', is_array($ids) ? $ids : []);
    foreach ($items as $item) {
        if ($item instanceof WP_Post) {
            wp_update_post([
                'ID' => $item->ID,
                'post_title' => 'Стройблог',
            ]);
        }
    }
    update_option('stroymarket_blog_name', 'stroyblog', false);
}, 40);

function stroymarket_blog_url(int $year, string $tag): string
{
    $args = [];
    if ($year > 0) {
        $args['sm_year'] = (string) $year;
    }
    if ($tag !== '') {
        $args['sm_tag'] = $tag;
    }
    return add_query_arg($args, home_url('/tekhnoblog/'));
}

add_shortcode('sm_blog', static function (): string {
    $year = isset($_GET['sm_year']) ? (int) wp_unslash($_GET['sm_year']) : 0;
    $tag = isset($_GET['sm_tag']) ? sanitize_title((string) wp_unslash($_GET['sm_tag'])) : '';
    if ($year < 2000 || $year > 2100) {
        $year = 0;
    }
    global $wpdb;
    $years = $wpdb->get_col(
        "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY YEAR(post_date) DESC"
    );
    $years = array_map('intval', is_array($years) ? $years : []);
    $tags = get_tags(['hide_empty' => true]);
    if (!is_array($tags)) {
        $tags = [];
    }
    $query = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 12,
    ];
    if ($year > 0) {
        $query['date_query'] = [['year' => $year]];
    }
    if ($tag !== '') {
        $query['tag'] = $tag;
    }
    $posts = get_posts($query);
    $placeholder = function_exists('stroymarket_placeholder_url') ? stroymarket_placeholder_url() : '';

    ob_start();
    echo '<div class="sm-blog">';
    echo '<nav class="sm-blog-years" aria-label="Период">';
    $all_years = $year === 0 ? ' is-current' : '';
    echo '<a class="' . trim($all_years) . '" href="' . esc_url(stroymarket_blog_url(0, $tag)) . '">За все время</a>';
    foreach ($years as $item_year) {
        $current = $item_year === $year ? ' is-current' : '';
        echo '<a class="' . trim($current) . '" href="' . esc_url(stroymarket_blog_url($item_year, $tag)) . '">' . esc_html((string) $item_year) . '</a>';
    }
    echo '</nav>';
    echo '<section class="sm-blog-tags" aria-label="Теги">';
    echo '<h2>Теги</h2><div class="sm-blog-tag-list">';
    $all_tags = $tag === '' ? ' is-current' : '';
    echo '<a class="' . trim($all_tags) . '" href="' . esc_url(stroymarket_blog_url($year, '')) . '">Все статьи</a>';
    foreach ($tags as $item) {
        if (!$item instanceof WP_Term) {
            continue;
        }
        $current = $item->slug === $tag ? ' is-current' : '';
        echo '<a class="' . trim($current) . '" href="' . esc_url(stroymarket_blog_url($year, $item->slug)) . '">' . esc_html($item->name) . '</a>';
    }
    echo '</div></section>';
    if (!$posts) {
        echo '<p class="sm-blog-empty">Здесь будут заметки о подборе материалов, комплектации и поставке. Первые материалы ещё готовятся.</p>';
    } else {
        echo '<div class="sm-blog-grid">';
        foreach ($posts as $post) {
            $url = get_permalink($post);
            $image = get_the_post_thumbnail_url($post, 'medium_large');
            $photo = is_string($image) && $image !== '' ? $image : $placeholder;
            $categories = get_the_category($post->ID);
            $section = is_array($categories) && isset($categories[0]) ? $categories[0]->name : '';
            echo '<a class="sm-blog-card" href="' . esc_url((string) $url) . '">';
            if ($photo !== '') {
                echo '<span class="sm-photo"><img src="' . esc_url($photo) . '" alt="" width="480" height="300" loading="lazy" decoding="async"></span>';
            }
            echo '<span class="sm-blog-card-body">';
            if ($section !== '') {
                echo '<span class="sm-blog-kicker">' . esc_html($section) . '</span>';
            }
            echo '<time class="sm-blog-date" datetime="' . esc_attr(get_the_date('c', $post)) . '">' . esc_html(get_the_date('j F Y', $post)) . '</time>';
            echo '<span class="sm-blog-card-title">' . esc_html(get_the_title($post)) . '</span>';
            echo '</span></a>';
        }
        echo '</div>';
    }
    echo '</div>';
    return (string) ob_get_clean();
});

function stroymarket_is_calculator_page(): bool
{
    if (!is_page()) {
        return false;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return false;
    }
    if ($post->post_name === 'kalkulyator') {
        return true;
    }
    $parent = $post->post_parent > 0 ? get_post((int) $post->post_parent) : null;
    return $parent instanceof WP_Post && $parent->post_name === 'kalkulyator';
}

function stroymarket_calc_image(string $file, string $alt): string
{
    $relative = '/assets/calculators/' . $file . '.jpg';
    $path = get_stylesheet_directory() . $relative;
    $src = get_stylesheet_directory_uri() . $relative;
    if (is_file($path)) {
        $src .= '?v=' . filemtime($path);
    }
    return '<img class="sm-calc-photo" src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" width="960" height="540">';
}

add_shortcode('sm_calculators', static function (): string {
    $items = [
        ['dostavka', 'Стоимость доставки', 'calc-delivery', 'Погрузка материалов в фургон'],
        ['shemy', 'Калькулятор электрических схем', 'calc-power', 'Кабель, коробка и автомат'],
        ['cement', 'Количество цемента на фундамент', 'calc-cement', 'Бетонный фундамент с арматурой'],
        ['karkas', 'Количество досок на каркас', 'calc-frame', 'Деревянный каркас стены'],
    ];
    $html = '<div class="sm-calc-index">';
    foreach ($items as [$slug, $label, $file, $alt]) {
        $html .= '<a class="sm-calc-card" href="' . esc_url(home_url('/kalkulyator/' . $slug . '/')) . '">';
        $html .= stroymarket_calc_image($file, $alt);
        $html .= '<span>' . esc_html($label) . '</span></a>';
    }
    return $html . '</div>';
});

add_shortcode('sm_calc_delivery', static function (): string {
    $moscow = STROYMARKET_DELIVERY_MOSCOW;
    $km = STROYMARKET_DELIVERY_KM;
    ob_start();
    ?>
    <div class="sm-calc-layout">
    <form class="sm-calc" data-calc="delivery" data-moscow="<?php echo esc_attr((string) STROYMARKET_DELIVERY_MOSCOW); ?>" data-km-rate="<?php echo esc_attr((string) STROYMARKET_DELIVERY_KM); ?>">
        <p><label>Куда везти
            <select name="zone">
                <option value="moscow">Москва, в пределах МКАД</option>
                <option value="region">Московская область</option>
                <option value="other">Другой город</option>
            </select>
        </label></p>
        <p data-km hidden><label>Километры за МКАД<input type="number" name="km" min="0" max="500" value="10"></label></p>
        <p class="sm-calc-result" aria-live="polite"></p>
        <p class="sm-calc-note">Ориентир для планирования: Москва <?php echo esc_html((string) $moscow); ?> ₽, область — эта сумма плюс <?php echo esc_html((string) $km); ?> ₽ за километр за МКАД. Это не оферта, точную стоимость подтверждаем в счёте. В другой город считаем отдельно.</p>
    </form>
    <?php echo stroymarket_calc_image('calc-delivery', 'Погрузка материалов в фургон'); ?>
    </div>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('sm_calc_power', static function (): string {
    ob_start();
    ?>
    <div class="sm-calc-layout">
    <form class="sm-calc" data-calc="power">
        <div class="sm-lines">
            <p><label>Линия<input type="text" name="line_name" value="Освещение"></label>
                <label>Мощность, Вт<input type="number" name="line_power" min="1" value="500"></label></p>
        </div>
        <button class="sm-btn sm-btn-line" type="button" data-add-line>Добавить линию</button>
        <div class="sm-calc-result" aria-live="polite"></div>
        <p class="sm-calc-note">Ток считается как мощность / 220 В. Сечение — ориентир для медной линии: до 10 А — 1,5 мм², до 16 А — 2,5 мм², до 25 А — 4 мм², до 32 А — 6 мм², дальше нужен отдельный расчёт. Это не проект электроснабжения.</p>
    </form>
    <?php echo stroymarket_calc_image('calc-power', 'Кабель, коробка и автомат'); ?>
    </div>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('sm_calc_cement', static function (): string {
    ob_start();
    ?>
    <div class="sm-calc-layout">
    <form class="sm-calc" data-calc="cement">
        <p><label>Длина, м<input type="number" name="length" min="0.1" step="0.1" value="10"></label></p>
        <p><label>Ширина, м<input type="number" name="width" min="0.1" step="0.1" value="8"></label></p>
        <p><label>Высота, м<input type="number" name="height" min="0.05" step="0.01" value="0.3"></label></p>
        <p><label>Марка бетона
            <select name="grade">
                <option value="250">М150</option>
                <option value="290" selected>М200</option>
                <option value="340">М250</option>
                <option value="380">М300</option>
            </select>
        </label></p>
        <p class="sm-calc-result" aria-live="polite"></p>
        <p class="sm-calc-note">Объём — длина × ширина × высота. Цемент берём по выбранной марке на кубометр, мешок 50 кг. Запас на потери сюда не заложен, его лучше заложить отдельно.</p>
    </form>
    <?php echo stroymarket_calc_image('calc-cement', 'Бетонный фундамент с арматурой'); ?>
    </div>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('sm_calc_frame', static function (): string {
    ob_start();
    ?>
    <div class="sm-calc-layout">
    <form class="sm-calc" data-calc="frame">
        <p><label>Длина стены, м<input type="number" name="length" min="0.5" step="0.1" value="6"></label></p>
        <p><label>Высота стены, м<input type="number" name="height" min="0.5" step="0.1" value="2.7"></label></p>
        <p><label>Шаг стоек, мм<input type="number" name="step" min="300" max="1200" step="50" value="600"></label></p>
        <p><label>Длина доски, м<input type="number" name="board" min="1" step="0.1" value="6"></label></p>
        <p class="sm-calc-result" aria-live="polite"></p>
        <p class="sm-calc-note">Стойки: длина стены / шаг, плюс одна крайняя. Обвязка: нижняя доска и две верхние на всю длину. Проёмы и укосины в расчёт не входят.</p>
    </form>
    <?php echo stroymarket_calc_image('calc-frame', 'Деревянный каркас стены'); ?>
    </div>
    <?php
    return (string) ob_get_clean();
});

function stroymarket_seed_pages(): array
{
    $definitions = [
        'kompaniya' => ['Компания', '[sm_company]', 0],
        'kalkulyator' => ['Калькулятор', '[sm_calculators]', 0],
        'poleznoe' => ['Полезное', '[sm_useful]', 0],
        'tekhnoblog' => ['Стройблог', '[sm_blog]', 0],
        'kontakty' => ['Контакты', '[sm_contacts]', 0],
    ];
    $ids = [];
    foreach ($definitions as $slug => $page) {
        $ids[$slug] = stroymarket_upsert_page($slug, $page[0], $page[1], 0);
    }
    $children = [
        'dostavka' => ['Стоимость доставки', '[sm_calc_delivery]'],
        'shemy' => ['Калькулятор электрических схем', '[sm_calc_power]'],
        'cement' => ['Калькулятор количества цемента на фундамент', '[sm_calc_cement]'],
        'karkas' => ['Количество досок на каркас', '[sm_calc_frame]'],
    ];
    foreach ($children as $slug => $page) {
        $ids[$slug] = stroymarket_upsert_page($slug, $page[0], $page[1], $ids['kalkulyator']);
    }
    return $ids;
}

function stroymarket_upsert_page(string $slug, string $title, string $content, int $parent): int
{
    $existing = get_page_by_path($parent ? 'kalkulyator/' . $slug : $slug);
    if ($existing instanceof WP_Post) {
        wp_update_post([
            'ID' => $existing->ID,
            'post_title' => $title,
            'post_content' => $content,
            'post_parent' => $parent,
            'post_status' => 'publish',
        ]);
        return (int) $existing->ID;
    }
    $id = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => $title,
        'post_content' => $content,
        'post_parent' => $parent,
    ]);
    return is_int($id) ? $id : 0;
}

function stroymarket_seed_menu(array $pages): void
{
    $locations = get_nav_menu_locations();
    $menu_id = (int) ($locations['primary'] ?? 0);
    if ($menu_id < 1) {
        $menu_id = (int) wp_create_nav_menu('Главное меню');
        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
    if ($menu_id < 1) {
        return;
    }
    $existing = wp_get_nav_menu_items($menu_id);
    $titles = [];
    if (is_array($existing)) {
        foreach ($existing as $item) {
            $titles[] = trim(wp_strip_all_tags((string) $item->title));
        }
    }
    $parent = stroymarket_menu_page($menu_id, $pages['kalkulyator'], 'Калькулятор', 0, 30, $titles);
    $children = [
        'dostavka' => ['Стоимость доставки', 31],
        'shemy' => ['Калькулятор электрических схем', 32],
        'cement' => ['Калькулятор количества цемента на фундамент', 33],
        'karkas' => ['Количество досок на каркас', 34],
    ];
    foreach ($children as $key => $child) {
        stroymarket_menu_page($menu_id, $pages[$key], $child[0], $parent, $child[1], $titles);
    }
    stroymarket_menu_page($menu_id, $pages['kompaniya'], 'Компания', 0, 20, $titles);
    stroymarket_menu_page($menu_id, $pages['poleznoe'], 'Полезное', 0, 40, $titles);
    stroymarket_menu_page($menu_id, $pages['tekhnoblog'], 'Стройблог', 0, 50, $titles);
    stroymarket_menu_page($menu_id, $pages['kontakty'], 'Контакты', 0, 60, $titles);
}

function stroymarket_menu_page(int $menu_id, int $page_id, string $title, int $parent, int $position, array &$titles): int
{
    if ($page_id < 1 || in_array($title, $titles, true)) {
        if (in_array($title, $titles, true)) {
            $items = wp_get_nav_menu_items($menu_id);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (trim(wp_strip_all_tags((string) $item->title)) === $title) {
                        return (int) $item->ID;
                    }
                }
            }
        }
        return 0;
    }
    $item_id = wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title' => $title,
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-parent-id' => $parent,
        'menu-item-position' => $position,
    ]);
    if (is_int($item_id)) {
        $titles[] = $title;
        return $item_id;
    }
    return 0;
}
