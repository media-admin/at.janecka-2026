<?php
/**
 * @var \Vendidero\StoreaBill\Document\Document[] $documents
 * @var string $document_title
 *
 * @version 1.1.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="sab-documents-download">
	<h3><?php printf( esc_html_x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), esc_html( $document_title ) ); ?></h3>

	<?php foreach ( $documents as $document ) : ?>
		<a class="button button-document-download<?php echo esc_attr( sab_wp_theme_get_element_class_name( 'button' ) ? ' ' . sab_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( $document->get_download_url( apply_filters( 'storeabill_woo_customer_force_document_download', false ) ) ); ?>" target="_blank"><?php printf( esc_html_x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), apply_filters( 'storeabill_woo_customer_document_name', esc_html( $document->get_title() ), $document ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	<?php endforeach; ?>
</div>
