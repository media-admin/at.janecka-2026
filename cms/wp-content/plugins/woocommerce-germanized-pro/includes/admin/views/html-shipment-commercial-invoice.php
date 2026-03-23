<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice $commercial_invoice
 * @var \Vendidero\Shiptastic\Shipment $shipment
 */
$actions = array();

if ( $commercial_invoice ) {
	$actions['download'] = array(
		'url'     => $commercial_invoice->get_download_url(),
		'name'    => _x( 'Download commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ),
		'action'  => 'download_commercial_invoice',
		'classes' => 'download',
		'target'  => '_blank',
	);

	$actions['refresh'] = array(
		'name'              => _x( 'Refresh commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ),
		'action'            => 'create_commercial_invoice',
		'classes'           => 'create-commercial-invoice has-shipment-modal refresh',
		'custom_attributes' => array(
			'id'                => 'wc-gzdp-modal-create-commercial-invoice-' . $shipment->get_id(),
			'data-reference'    => $shipment->get_id(),
			'data-id'           => 'wc-gzdp-modal-create-commercial-invoice',
			'data-nonce-params' => 'wc_gzdp_admin_shipment_documents_params',
			'data-load-async'   => true,
		),
	);

	$actions['delete'] = array(
		'classes'           => 'remove-commercial-invoice delete',
		'name'              => _x( 'Delete commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ),
		'action'            => 'delete_commercial_invoice',
		'target'            => '_blank',
		'custom_attributes' => array(
			'data-commercial_invoice' => $commercial_invoice->get_id(),
		),
	);
} else {
	$actions['create'] = array(
		'name'              => _x( 'Create commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ),
		'action'            => 'create_commercial_invoice',
		'classes'           => 'create-commercial-invoice has-shipment-modal create',
		'custom_attributes' => array(
			'id'                => 'wc-gzdp-modal-create-commercial-invoice-' . $shipment->get_id(),
			'data-reference'    => $shipment->get_id(),
			'data-id'           => 'wc-gzdp-modal-create-commercial-invoice',
			'data-nonce-params' => 'wc_gzdp_admin_shipment_documents_params',
			'data-load-async'   => true,
		),
	);
}
?>

<div class="wc-gzdp-shipment-commercial-invoice wc-stc-shipment-action-wrapper column column-spaced col-auto" data-commercial_invoice="<?php echo esc_attr( $commercial_invoice ? $commercial_invoice->get_id() : '' ); ?>">
	<h4><?php echo ( ( $commercial_invoice ) ? wp_kses_post( $commercial_invoice->get_title() ) : esc_html__( 'Commercial Invoice', 'woocommerce-germanized-pro' ) ); ?></h4>

	<div class="shipment-inner-actions">
		<div class="commercial-invoice-actions-wrapper shipment-inner-actions-wrapper commercial-invoice-actions-create">
			<?php echo wc_gzd_render_shipment_action_buttons( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php require 'html-shipment-commercial-invoice-backbone.php'; ?>
		</div>
	</div>
</div>
