<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h1><?php esc_html_e( 'Stunning Support', 'woocommerce-germanized-pro' ); ?></h1>

<p class="headliner no-border"><?php echo wp_kses_post( sprintf( __( 'Germanized Pro offers premium support through our ticket area. If you need any help feel free to create a <a href="%1$s" target="_blank">ticket</a> or search through our <a href="%2$s" target="_blank">knowlegde base</a>.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dashboard/new-ticket', 'https://vendidero.de/dokumentation/woocommerce-germanized' ) ); ?></p>
