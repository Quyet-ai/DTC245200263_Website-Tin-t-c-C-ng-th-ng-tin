<footer class="site-footer">
    <div class="wrap footer-inner">
        <div><a class="footer-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a><p><?php echo esc_html(get_bloginfo('description') ?: 'Tin tức đáng tin cậy, góc nhìn gần gũi.'); ?></p></div>
        <p>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?>. Nội dung được quản lý bằng WordPress.</p>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
