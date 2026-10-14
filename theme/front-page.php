<?php get_header(); ?>

<main class="col-full">
  <section class="home-hero">
    <div>
      <h1>СтройМаркет</h1>
      <p>Строительные материалы, инструмент, электрика и товары для дома. Выбирайте в каталоге и оформляйте заказ.</p>
      <form class="home-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="search" name="s" placeholder="Название или артикул" aria-label="Поиск по каталогу">
        <input type="hidden" name="post_type" value="product">
        <button type="submit">Найти</button>
      </form>
    </div>
    <div class="home-stat">
      <?php $counts = wp_count_posts('product'); ?>
      <b><?php echo esc_html(stroymarket_items_label($counts->publish ?? 0)); ?></b>
      <span>в каталоге магазина</span>
    </div>
  </section>

  <h2 class="section-title">Разделы</h2>
  <?php
  $terms = get_terms([
      'taxonomy' => 'product_cat',
      'parent' => 0,
      'hide_empty' => true,
      'orderby' => 'count',
      'order' => 'DESC',
  ]);
  ?>
  <?php if (!is_wp_error($terms) && $terms) : ?>
    <ul class="department-grid">
      <?php foreach ($terms as $term) : ?>
        <?php $link = get_term_link($term); ?>
        <?php if (is_wp_error($link)) { continue; } ?>
        <li>
          <a href="<?php echo esc_url($link); ?>">
            <strong><?php echo esc_html($term->name); ?></strong>
            <span><?php echo esc_html(stroymarket_items_label($term->count)); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else : ?>
    <p>Каталог скоро появится на витрине.</p>
  <?php endif; ?>

  <p><a class="button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">Открыть весь каталог</a></p>
</main>

<?php get_footer(); ?>
