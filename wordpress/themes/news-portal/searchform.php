<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="nhiptin-search"><?php echo esc_html_x('Tìm kiếm:', 'label', 'nhiptin'); ?></label>
    <input id="nhiptin-search" type="search" placeholder="Tìm kiếm tin tức…" value="<?php echo get_search_query(); ?>" name="s">
    <button type="submit" aria-label="Tìm kiếm">⌕</button>
</form>
