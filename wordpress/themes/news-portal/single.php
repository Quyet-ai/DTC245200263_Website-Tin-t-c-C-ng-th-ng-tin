<?php get_header(); ?>
<main class="wrap page-content">
    <div class="article-layout">
        <div>
        <?php while (have_posts()) : the_post(); $cat = nhiptin_category(); ?>
            <article <?php post_class('article'); ?>>
                <?php if ($cat) : ?><a class="category-pill" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a><?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <p class="article-intro"><?php echo esc_html(nhiptin_excerpt(get_the_ID(), 35)); ?></p>
                <p class="meta">Bởi <?php the_author(); ?> · <?php echo esc_html(nhiptin_date()); ?></p>
                <?php if (has_post_thumbnail()) : ?><div class="article-featured"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
                <div class="article-content"><?php the_content(); ?></div>
            </article>
            <?php the_post_navigation(array('prev_text' => '← %title', 'next_text' => '%title →')); ?>
        <?php endwhile; ?>
        </div>
        <aside class="sidebar"><section class="sidebar-box"><h2>Bài viết mới</h2>
            <?php $recent = new WP_Query(array('post_status' => 'publish', 'posts_per_page' => 5, 'post__not_in' => array(get_the_ID()))); while ($recent->have_posts()) : $recent->the_post(); ?>
                <a class="side-link" href="<?php the_permalink(); ?>"><?php the_title(); ?><span class="meta"><br><?php echo esc_html(nhiptin_date()); ?></span></a>
            <?php endwhile; wp_reset_postdata(); ?>
        </section></aside>
    </div>
</main>
<?php get_footer(); ?>
