<?php
/**
 * Shipment commercial invoice HTML for meta box.
 */
defined( 'ABSPATH' ) || exit;

/**
 * @var \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice $commercial_invoice
 * @var \Vendidero\Shiptastic\Shipment $shipment
 */
?>
<script type="text/template" id="tmpl-wc-gzdp-modal-create-commercial-invoice-<?php echo esc_attr( $shipment->get_id() ); ?>" class="wc-gzdp-shipment-commercial-invoice-<?php echo esc_attr( $shipment->get_type() ); ?>">
	<div class="wc-backbone-modal wc-stc-admin-shipment-modal wc-gzdp-modal-create-commercial-invoice">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_x( 'Create commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text">Close modal panel</span>
					</button>
				</header>
				<article class="shiptastic-shipments shiptastic-create-commercial-invoice" data-shipment-type="<?php echo esc_attr( $shipment->get_type() ); ?>">
					<div class="notice-wrapper"></div>

					<form action="" method="post" class="wc-stc-create-shipment-commercial-invoice-form">
						<div class="wc-stc-shipment-create-commercial-invoice"></div>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Create', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
