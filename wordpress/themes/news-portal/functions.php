<?php
if (!defined('ABSPATH')) { exit; }

function nhiptin_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array('primary' => __('Thanh điều hướng chính', 'nhiptin')));
}
add_action('after_setup_theme', 'nhiptin_setup');

function nhiptin_assets() {
    $path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style('nhiptin-style', get_stylesheet_uri(), array(), file_exists($path) ? filemtime($path) : '1.0.0');
}
add_action('wp_enqueue_scripts', 'nhiptin_assets');

function nhiptin_category($post_id = null) {
    $categories = get_the_category($post_id ?: get_the_ID());
    foreach ($categories as $category) {
        if ($category->slug !== 'uncategorized') { return $category; }
    }
    return $categories ? $categories[0] : null;
}

function nhiptin_excerpt($post_id, $length = 28) {
    $text = get_the_excerpt($post_id);
    return wp_trim_words(wp_strip_all_tags($text), $length, '…');
}

function nhiptin_date($post_id = null) {
    return get_the_date('j \T\h\á\n\g n, Y', $post_id ?: get_the_ID());
}

function nhiptin_category_nav() {
    $current = is_category() ? get_queried_object_id() : 0;
    echo '<div class="nav-list"><a class="nav-home" href="' . esc_url(home_url('/')) . '">Trang chủ</a>';
    if (has_nav_menu('primary')) {
        wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'items_wrap' => '<ul class="nav-items">%3$s</ul>', 'fallback_cb' => false));
    } else {
        $categories = get_categories(array('hide_empty' => false, 'exclude' => array((int) get_option('default_category')), 'number' => 6));
        foreach ($categories as $category) {
            $class = $current === (int) $category->term_id ? ' class="current"' : '';
            echo '<a' . $class . ' href="' . esc_url(get_category_link($category)) . '">' . esc_html($category->name) . '</a>';
        }
    }
    echo '</div>';
}
