<?php get_header(); ?>
<main class="wrap page-content">
    <?php
    $featured_query = new WP_Query(array('post_status' => 'publish', 'posts_per_page' => 1, 'ignore_sticky_posts' => true));
    if ($featured_query->have_posts()) :
        $featured_query->the_post();
        $featured_id = get_the_ID();
        $featured_category = nhiptin_category($featured_id);
    ?>
    <div class="lead-layout">
        <article class="lead-card">
            <?php if (has_post_thumbnail()) : the_post_thumbnail('full', array('class' => 'lead-image')); else : ?><div class="lead-art" aria-hidden="true"><span class="lead-word">ĐIỂM<br>NHÌN</span></div><?php endif; ?>
            <div class="lead-shade"></div>
            <div class="lead-copy">
                <?php if ($featured_category) : ?><a class="category-pill" href="<?php echo esc_url(get_category_link($featured_category)); ?>"><?php echo esc_html($featured_category->name); ?></a><?php endif; ?>
                <h1 class="lead-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                <p class="lead-excerpt"><?php echo esc_html(nhiptin_excerpt($featured_id, 34)); ?></p>
                <span class="meta"><?php echo esc_html(nhiptin_date($featured_id)); ?> · <?php echo esc_html(get_the_author()); ?></span>
            </div>
        </article>
        <aside class="side-stories" aria-label="Tin mới">
            <h2>Đọc nhiều trong ngày</h2>
            <?php
            $side_query = new WP_Query(array('post_status' => 'publish', 'posts_per_page' => 4, 'offset' => 1, 'ignore_sticky_posts' => true));
            $number = 1;
            if ($side_query->have_posts()) : while ($side_query->have_posts()) : $side_query->the_post(); ?>
                <article class="side-story"><span class="story-number">0<?php echo esc_html($number++); ?></span><div><?php $cat = nhiptin_category(); if ($cat) : ?><span class="meta"><?php echo esc_html($cat->name); ?></span><?php endif; ?><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><span class="meta"><?php echo esc_html(nhiptin_date()); ?></span></div></article>
            <?php endwhile; else : ?><p class="empty-story">Những bài viết mới sẽ được chọn lọc và giới thiệu tại đây.</p><?php endif; wp_reset_postdata(); ?>
        </aside>
    </div>
    <?php wp_reset_postdata(); ?>

    <div class="content-layout">
        <section>
            <h2 class="section-heading">Tin mới nhất <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>">Tất cả tin</a></h2>
            <div class="post-grid">
            <?php
            $latest_query = new WP_Query(array('post_status' => 'publish', 'posts_per_page' => 6, 'offset' => 1, 'ignore_sticky_posts' => true));
            if ($latest_query->have_posts()) : while ($latest_query->have_posts()) : $latest_query->the_post(); $cat = nhiptin_category(); ?>
                <article <?php post_class('post-card'); ?>>
                    <a class="post-thumb" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                        <?php if (has_post_thumbnail()) : the_post_thumbnail('large'); else : ?><span class="thumb-word">TIN MỚI</span><?php endif; ?>
                    </a>
                    <div class="post-body">
                        <?php if ($cat) : ?><a class="meta" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a><?php endif; ?>
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p><?php echo esc_html(nhiptin_excerpt(get_the_ID(), 22)); ?></p>
                        <span class="meta"><?php echo esc_html(nhiptin_date()); ?></span>
                    </div>
                </article>
            <?php endwhile; else : ?>
                <div class="empty-state"><h2>Đang chờ tin mới</h2><p>Hãy đăng bài viết đầu tiên từ trang quản trị WordPress để bắt đầu xây dựng cổng thông tin.</p><a class="text-link" href="<?php echo esc_url(admin_url('post-new.php')); ?>">Viết bài mới →</a></div>
            <?php endif; wp_reset_postdata(); ?>
            </div>
        </section>
        <aside class="sidebar">
            <section class="sidebar-box"><h2>Chuyên mục</h2>
                <?php $cats = get_categories(array('hide_empty' => false, 'exclude' => array((int) get_option('default_category')), 'number' => 8)); if ($cats) : foreach ($cats as $cat) : ?>
                    <a class="side-link" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?><span class="meta"> · <?php echo esc_html($cat->count); ?> bài</span></a>
                <?php endforeach; else : ?><p class="meta">Thêm chuyên mục trong Bài viết → Chuyên mục.</p><?php endif; ?>
            </section>
            <section class="sidebar-box"><h2>Về chúng tôi</h2><p class="meta">Cổng thông tin tổng hợp những câu chuyện đáng chú ý, góc nhìn hữu ích và kiến thức gần gũi với bạn đọc.</p></section>
        </aside>
    </div>
    <?php else : ?>
        <div class="empty-state"><h1>Chào mừng đến với <?php bloginfo('name'); ?></h1><p>Hãy đăng bài viết đầu tiên từ trang quản trị để trang chủ bắt đầu hiển thị tin tức.</p><a class="text-link" href="<?php echo esc_url(admin_url('post-new.php')); ?>">Viết bài mới →</a></div>
    <?php endif; wp_reset_postdata(); ?>
</main>
<?php get_footer(); ?>
