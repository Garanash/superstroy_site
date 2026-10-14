<?php

declare(strict_types=1);

get_header();

$shop_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
$shop_url = $shop_id > 0 ? get_permalink($shop_id) : home_url('/shop/');
?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php if (!function_exists('stroymarket_render_home_slider') || !stroymarket_render_home_slider()) : ?>
        <section class="sm-hero">
            <h1>Стройматериалы оптом с точным подбором</h1>
            <p>Подбираем материалы под задачи объекта, комплектуем заказ и организуем поставку. Доставка от 4 часов по Москве и от 1 дня по России.</p>
            <div class="sm-actions">
                <a class="sm-btn" href="<?php echo esc_url($shop_url); ?>">Перейти в каталог</a>
                <a class="sm-btn sm-btn-line" href="tel:+74952151160">+7 495 215 11 60</a>
            </div>
        </section>
        <?php endif; ?>

        <?php stroymarket_render_home_strips(); ?>

        <?php stroymarket_render_best_prices(); ?>

        <section class="sm-section">
            <h2>Как мы помогаем оптимизировать бюджет</h2>
            <ul class="sm-cards">
                <li>
                    <h3>Не навязываем лишнее</h3>
                    <p>В поставку входит только то, что нужно для задачи, без позиций «на всякий случай».</p>
                </li>
                <li>
                    <h3>Проверяем состав</h3>
                    <p>Смотрим список, чтобы не осталось недостач, лишних позиций и дополнительных затрат.</p>
                </li>
                <li>
                    <h3>Снижаем затраты на логистику</h3>
                    <p>Собираем поставку так, чтобы не дозаказывать недостающее и не оплачивать лишние доставки.</p>
                </li>
                <li>
                    <h3>Подбираем аналоги</h3>
                    <p>Если позиция недоступна или невыгодна по сроку и цене, предложим замену.</p>
                </li>
                <li>
                    <h3>Учитываем сроки объекта</h3>
                    <p>Подбираем решения под график работ, чтобы не переплачивать за срочные замены и простой бригады.</p>
                </li>
                <li>
                    <h3>Любая комплектация</h3>
                    <p>Собираем заказ под задачу проекта и без привязки к кратности упаковок.</p>
                </li>
            </ul>
        </section>

        <section class="sm-section">
            <h2>Как строится работа</h2>
            <ol class="sm-steps">
                <li>
                    <h3>Принимаем заявку</h3>
                    <p>Подойдут фото, рукописный список или спецификация. Идеальный файл не нужен.</p>
                </li>
                <li>
                    <h3>Уточняем позиции</h3>
                    <p>Задаём вопросы по спорным и важным позициям, чтобы привезти нужное, а не «что-то похожее».</p>
                </li>
                <li>
                    <h3>Разбираем по профилям</h3>
                    <p>За каждую часть заявки отвечают специалисты по своим направлениям.</p>
                </li>
                <li>
                    <h3>Считаем и согласовываем</h3>
                    <p>Готовим расчёт, выставляем счёт и согласовываем условия работы.</p>
                </li>
            </ol>
        </section>

        <section class="sm-section sm-company">
            <div>
                <h2>Суперстрой М</h2>
                <p>ООО «СуперСтрой-М» поставляет строительные материалы оптом и комплектует заказы под объект. Склад в Москве: ул. Адмирала Корнилова, 37с7. Доставка от 4 часов по Москве и от 1 дня по России.</p>
                <a class="sm-btn" href="<?php echo esc_url($shop_url); ?>">Открыть каталог</a>
            </div>
            <div class="sm-note">
                <h2>Контакты</h2>
                <p>Москва, ул. Адмирала Корнилова, 37с7</p>
                <p><a href="tel:+74952151160">+7 495 215 11 60</a></p>
                <p><a href="mailto:info@superstroy.ru">info@superstroy.ru</a></p>
                <p>Отправьте список материалов — подготовим предложение с учётом сроков, бюджета и задач объекта.</p>
            </div>
        </section>

        <?php
        $brands = [
            ['name' => 'EKF', 'file' => 'ekf.svg'],
            ['name' => 'IEK', 'file' => 'iek.svg'],
            ['name' => 'DKC', 'file' => 'dkc.svg'],
            ['name' => 'ARMAT', 'file' => 'armat.svg', 'dark' => true],
            ['name' => 'Systeme', 'file' => 'systeme.svg'],
            ['name' => 'REXANT', 'file' => 'rexant.png'],
            ['name' => 'Navigator', 'file' => 'navigator.svg'],
            ['name' => 'CHINT', 'file' => 'chint.svg'],
            ['name' => 'VALTEC', 'file' => 'valtec.svg'],
            ['name' => 'OSRAM', 'file' => 'osram.svg'],
            ['name' => 'Knauf', 'file' => 'knauf.svg'],
            ['name' => 'Ballu', 'file' => 'ballu.svg'],
            ['name' => 'STOUT', 'file' => 'stout.png'],
            ['name' => 'ABB', 'file' => 'abb.svg'],
            ['name' => 'Makita', 'file' => 'makita.svg'],
            ['name' => 'ЛСР', 'file' => 'lsr.jpg'],
        ];
        $brand_uri = get_stylesheet_directory_uri() . '/assets/brands/';
        ?>
        <section class="sm-section sm-marquee" id="postavshchiki" aria-label="Наши поставщики и наши покупатели">
            <div class="sm-marquee-head">
                <h2>Наши поставщики и наши покупатели</h2>
            </div>
            <div class="sm-marquee-window">
                <div class="sm-marquee-track">
                    <?php foreach ([1, 2] as $copy) : ?>
                        <div class="sm-marquee-set"<?php echo $copy === 2 ? ' aria-hidden="true"' : ''; ?>>
                            <?php foreach ($brands as $brand) : ?>
                                <a class="sm-logo-card<?php echo !empty($brand['dark']) ? ' sm-logo-card--dark' : ''; ?>" href="<?php echo esc_url(home_url('/?s=' . rawurlencode($brand['name']) . '&post_type=product')); ?>" <?php echo $copy === 2 ? 'tabindex="-1"' : ''; ?>>
                                    <img src="<?php echo esc_url($brand_uri . $brand['file']); ?>" alt="<?php echo esc_attr($brand['name']); ?>" width="128" height="40">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>
<?php
get_footer();
