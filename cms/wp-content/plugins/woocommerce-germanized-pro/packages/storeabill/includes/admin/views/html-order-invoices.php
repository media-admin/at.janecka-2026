<?php
/**
 * Order invoices HTML.
 *
 * @package StoreaBill/Admin
 *
 * @var $sab_order Vendidero\StoreaBill\Interfaces\Order
 */
defined( 'ABSPATH' ) || exit;

$active_invoice     = isset( $active_invoice ) ? $active_invoice : false;
$needs_sync         = $sab_order->needs_sync();
$needs_codification = $sab_order->needs_finalization();
$needs_billing      = $sab_order->needs_billing();
?>

<div id="sab-order-invoices" class="sab-order-documents">
	<div class="sab-panel-sortable-header hndle ui-sortable-handle"></div>
	<div id="sab-panel-order-invoices" class="">
		<div class="panel-title spread panel-inner">
			<h2 class="order-documents-title"><?php echo esc_html_x( 'Invoices', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h2>
			<?php if ( ! $needs_billing ) : ?>
				<?php include 'html-order-payment-status.php'; ?>
			<?php endif; ?>
		</div>

		<div class="notice-wrapper panel-inner"></div>

		<?php require 'html-order-invoice-list.php'; ?>

		<?php if ( $needs_codification || $needs_sync || has_action( 'storeabill_invoices_order_meta_box_actions' ) ) : ?>
			<div class="panel-footer panel-inner">
				<div class="panel-actions">
					<div class="panel-actions-left"></div>

					<div class="panel-actions-right">
						<?php if ( $needs_sync ) : ?>
							<div class="order-invoice-sync">
								<a class="button button-secondary order-invoice-sync sab-tip" id="sab-order-invoice-sync" href="#" data-tip="<?php echo esc_attr_x( 'Sync invoices with order', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"><?php echo ( ( ! $needs_codification ) ? esc_html_x( 'Create invoice', 'storeabill-core', 'woocommerce-germanized-pro' ) : esc_html_x( 'Sync', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></a>
							</div>
						<?php endif; ?>
						<?php if ( $needs_codification ) : ?>
							<div class="order-invoice-finalize">
								<a class="button button-primary order-invoice-finalize sab-tip" id="sab-order-invoice-finalize" href="#" data-tip="<?php echo esc_attr_x( 'Invoices will not be editable afterwards', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"><?php echo esc_html_x( 'Finalize', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
							</div>
						<?php endif; ?>
					</div>

					<?php
					/**
					 * Action that fires in the action container for invoices of a specific order.
					 *
					 * @param \Vendidero\StoreaBill\Interfaces\Order $sab_order The order.
					 *
					 * @since 1.0.0
					 * @package Vendidero/StoreaBill
					 */
					do_action( 'storeabill_invoices_order_meta_box_actions', $sab_order );
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
