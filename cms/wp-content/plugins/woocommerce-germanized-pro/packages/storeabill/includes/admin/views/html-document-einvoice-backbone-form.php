<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\StoreaBill\Document\Document $document
 * @var $fields
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="storeabill-create-einvoice-fields" id="storeabill-create-einvoice-fields-<?php echo esc_attr( $document->get_id() ); ?>">
	<?php \Vendidero\StoreaBill\WooCommerce\Admin\Settings::render_document_fields( $fields, $document, true ); ?>

	<input type="hidden" name="document_id" id="storeabill-admin-document-id" value="<?php echo esc_attr( $document->get_id() ); ?>" />
	<input type="hidden" name="display_type" id="storeabill-admin-document-display-type" value="<?php echo esc_attr( isset( $display_type ) ? $display_type : 'order' ); ?>" />
</div>
