<?php
/**
 * WooCommerce Template Wrapper
 *
 * @package JuwelierJanecka
 */

get_header();
do_action( 'woocommerce_before_main_content' );
woocommerce_content();
do_action( 'woocommerce_after_main_content' );
get_footer();