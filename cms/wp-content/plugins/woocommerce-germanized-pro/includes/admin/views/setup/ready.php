<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h1><?php esc_html_e( 'You are ready!', 'woocommerce-germanized-pro' ); ?></h1>

<p class="headliner"><?php esc_html_e( 'Congratulations! You are ready to go. You should now head over to the settings to configure your installation.', 'woocommerce-germanized-pro' ); ?></p>

<?php if ( get_option( '_wc_gzdp_needs_legacy_invoice_import' ) ) : ?>
	<div class="notice notice-warning"><p><?php echo wp_kses_post( sprintf( __( 'We have automatically imported your PDF layout into our new <a href="%s" target="_blank">visual document editor</a>. Please check your layout and make adjustments if necessary.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/doc/woocommerce-germanized/pdf-vorlagen-bearbeiten' ) ); ?></p></div>
	<ul class="button-wrapper">
		<li><i class="dashicons dashicons-edit-page"></i> <a href="<?php echo esc_url( sab_get_default_document_template( 'invoice' )->get_edit_url() ); ?>" target="_blank"><?php esc_html_e( 'Manage invoice layout', 'woocommerce-germanized-pro' ); ?></a></li>
		<li><i class="dashicons dashicons-edit-page"></i> <a href="<?php echo esc_url( sab_get_default_document_template( 'invoice_cancellation' )->get_edit_url() ); ?>" target="_blank"><?php esc_html_e( 'Manage cancellation layout', 'woocommerce-germanized-pro' ); ?></a></li>
		<li><i class="dashicons dashicons-edit-page"></i> <a href="<?php echo esc_url( sab_get_default_document_template( 'packing_slip' )->get_edit_url() ); ?>" target="_blank"><?php esc_html_e( 'Manage packing slip layout', 'woocommerce-germanized-pro' ); ?></a></li>
		<li><i class="dashicons dashicons-edit-page"></i> <a href="<?php echo esc_url( sab_get_default_document_template( 'post_document' )->get_edit_url() ); ?>" target="_blank"><?php esc_html_e( 'Manage legal page layout', 'woocommerce-germanized-pro' ); ?></a></li>
	</ul>
<?php endif; ?>

<div class="wc-gzdp-setup-grid">
	<div class="wc-gzdp-setup-grid-item">
		<h3><?php esc_html_e( 'More', 'woocommerce-germanized-pro' ); ?></h3>

		<ul class="more">
			<li><i class="dashicons dashicons-book"></i> <a href="https://vendidero.de/dokumentation/woocommerce-germanized" target="_blank"><?php esc_html_e( 'Knowledge Base', 'woocommerce-germanized-pro' ); ?></a></li>
			<li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://wordpress.org/support/" target="_blank"><?php esc_html_e( 'Learn how to use WordPress', 'woocommerce-germanized-pro' ); ?></a></li>
			<li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://docs.woocommerce.com/" target="_blank"><?php esc_html_e( 'Learn how to use WooCommerce', 'woocommerce-germanized-pro' ); ?></a></li>
		</ul>
	</div>
</div>
