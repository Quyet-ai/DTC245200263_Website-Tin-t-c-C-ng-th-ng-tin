<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="topline"><div class="wrap topline-inner"><span><strong><?php echo esc_html(wp_date('l, j \T\h\á\n\g n, Y')); ?></strong> · Tin tức mỗi ngày</span><span>Đọc nhanh · Hiểu sâu · Cập nhật liên tục</span></div></div>
<header class="site-header">
    <div class="wrap masthead">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php bloginfo('name'); ?>">
            <span class="brand-mark">N</span>
            <span><span class="brand-name"><?php bloginfo('name'); ?></span><span class="brand-tag">BẢN TIN • GÓC NHÌN • ĐỜI SỐNG</span></span>
        </a>
        <?php get_search_form(); ?>
    </div>
</header>
<nav class="main-nav" aria-label="Điều hướng chính"><div class="wrap"><?php nhiptin_category_nav(); ?></div></nav>
<div class="ticker"><div class="wrap ticker-inner"><span class="ticker-label">TIN MỚI</span><span class="ticker-copy"><?php echo esc_html(get_bloginfo('description') ?: 'Những câu chuyện đáng chú ý, được chọn lọc mỗi ngày.'); ?></span></div></div>
