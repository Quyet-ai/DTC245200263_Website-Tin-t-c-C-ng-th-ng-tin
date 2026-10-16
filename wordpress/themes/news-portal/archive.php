<?php get_header(); ?>
<main class="wrap page-content">
    <header class="archive-head"><h1><?php the_archive_title(); ?></h1><?php the_archive_description('<div>', '</div>'); ?></header>
    <div class="content-layout">
        <section class="post-grid">
            <?php if (have_posts()) : while (have_posts()) : the_post(); $cat = nhiptin_category(); ?>
                <article <?php post_class('post-card'); ?>><a class="post-thumb" href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail()) : the_post_thumbnail('large'); else : ?><span class="thumb-word">TIN MỚI</span><?php endif; ?></a><div class="post-body"><?php if ($cat) : ?><a class="meta" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a><?php endif; ?><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(nhiptin_excerpt(get_the_ID(), 22)); ?></p><span class="meta"><?php echo esc_html(nhiptin_date()); ?></span></div></article>
            <?php endwhile; else : ?><div class="empty-state"><h2>Chưa có bài viết</h2><p>Chuyên mục này chưa có nội dung.</p></div><?php endif; ?>
        </section>
        <aside class="sidebar"><section class="sidebar-box"><h2>Chuyên mục</h2><?php wp_list_categories(array('title_li' => '', 'show_count' => true, 'exclude' => array((int) get_option('default_category')))); ?></section></aside>
    </div>
    <nav class="pagination" aria-label="Phân trang"><?php echo wp_kses_post(paginate_links(array('type' => 'plain'))); ?></nav>
</main>
<?php get_footer(); ?>
