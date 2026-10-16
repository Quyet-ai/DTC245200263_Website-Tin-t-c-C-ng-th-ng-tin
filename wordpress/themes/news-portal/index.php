<?php get_header(); ?>
<main class="wrap page-content">
    <div class="content-layout"><section class="post-grid">
        <?php if (have_posts()) : while (have_posts()) : the_post(); $cat = nhiptin_category(); ?>
            <article <?php post_class('post-card'); ?>><a class="post-thumb" href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail()) : the_post_thumbnail('large'); else : ?><span class="thumb-word">TIN MỚI</span><?php endif; ?></a><div class="post-body"><?php if ($cat) : ?><a class="meta" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a><?php endif; ?><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(nhiptin_excerpt(get_the_ID(), 22)); ?></p><span class="meta"><?php echo esc_html(nhiptin_date()); ?></span></div></article>
        <?php endwhile; else : ?><div class="empty-state"><h1>Chưa có nội dung</h1></div><?php endif; ?>
    </section></div>
</main>
<?php get_footer(); ?>
