<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$upload_dir      = \Vendidero\StoreaBill\UploadManager::get_upload_dir();
$upload_dir_path = $upload_dir['basedir'];
$dirname         = basename( $upload_dir_path );
?>

<h1><?php esc_html_e( 'Invoices', 'woocommerce-germanized-pro' ); ?></h1>

<p class="headliner"><?php esc_html_e( 'Germanized Pro offers some nice invoicing functionality. You may activate our invoicing functionality now and configure it later within the corresponding settings.', 'woocommerce-germanized-pro' ); ?></p>

<p><?php esc_html_e( 'Please make sure to grant write access to the following directory:', 'woocommerce-germanized-pro' ); ?></p>

<pre><code>wp-content/uploads/<?php echo esc_html( $dirname ); ?></code></pre>
