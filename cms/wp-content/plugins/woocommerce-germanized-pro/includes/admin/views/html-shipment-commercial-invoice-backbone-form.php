<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Shiptastic\Shipment $shipment
 */
defined( 'ABSPATH' ) || exit;

$commercial_invoice  = wc_gzdp_get_commercial_invoice_by_shipment( $shipment );
$commercial_shipment = \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_shipment( $shipment );
$countries           = WC()->countries->get_countries();
$countries           = array_merge( array( '0' => _x( 'Select a country', 'commercial-invoice', 'woocommerce-germanized-pro' ) ), $countries );

$currency_code_options = get_woocommerce_currencies();

foreach ( $currency_code_options as $code => $name ) {
	$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
}
?>
<style>
	.commercial-invoice-items {
		margin-bottom: 2em;
	}
	.commercial-invoice-items .commercial-invoice-items-header h4 {
		margin-top: 0;
	}
	.commercial-invoice-items .commercial-invoice-item {
		align-items: center;
	}
	.commercial-invoice-items .commercial-invoice-item p.form-field {
		margin: 0 !important;
	}
	.commercial-invoice-items .commercial-invoice-item p.form-field label {
		display: none;
	}
</style>
<div class="wc-stc-shipment-commercial-invoice-admin-fields">
	<div class="commercial-invoice-items">
		<div class="columns commercial-invoice-items-header">
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'Item', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h4>
			</div>
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'Customs description', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h4>
			</div>
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'HS Code', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h4>
			</div>
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'MID Code', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h4>
			</div>
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'CO', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?> <?php echo wc_help_tip( _x( 'Country of origin', 'commercial-invoice', 'woocommerce-germanized-pro' ) ); ?></h4>
			</div>
			<div class="column col-2">
				<h4><?php echo esc_html_x( 'Value', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?></h4>
			</div>
		</div>

		<?php
		foreach ( $shipment->get_items() as $item ) :
			$commercial_shipment_item = $commercial_shipment->get_item( $item );
			$invoice_item             = $commercial_invoice ? $commercial_invoice->get_item_by_reference_id( $item->get_id() ) : false;
			$customs_description      = $invoice_item ? $invoice_item->get_name() : $commercial_shipment_item->get_name();
			?>
			<div class="columns commercial-invoice-item" data-id="<?php echo esc_attr( $commercial_shipment_item->get_id() ); ?>">
				<div class="column col-2">
					<?php echo esc_html( $commercial_shipment_item->get_quantity() ); ?> &times; <?php echo esc_html( $commercial_shipment_item->get_sku() ); ?>
				</div>
				<div class="column col-2">
					<?php
					woocommerce_wp_text_input(
						array(
							'name'  => 'items[' . esc_attr( $commercial_shipment_item->get_id() ) . '][name]',
							'id'    => 'commercial_invoice_item_' . esc_attr( $commercial_shipment_item->get_id() ) . '_name',
							'label' => '',
							'value' => $customs_description,
						)
					);
					?>
				</div>
				<div class="column col-2">
					<?php
					woocommerce_wp_text_input(
						array(
							'name'  => 'items[' . esc_attr( $commercial_shipment_item->get_id() ) . '][hs_code]',
							'id'    => 'commercial_invoice_item_' . esc_attr( $commercial_shipment_item->get_id() ) . '_hs_code',
							'label' => '',
							'value' => $invoice_item ? $invoice_item->get_hs_code() : $commercial_shipment_item->get_hs_code(),
						)
					);
					?>
				</div>
				<div class="column col-2">
					<?php
					woocommerce_wp_text_input(
						array(
							'name'  => 'items[' . esc_attr( $commercial_shipment_item->get_id() ) . '][mid_code]',
							'id'    => 'commercial_invoice_item_' . esc_attr( $commercial_shipment_item->get_id() ) . '_mid_code',
							'label' => '',
							'value' => $invoice_item ? $invoice_item->get_mid_code() : $commercial_shipment_item->get_mid_code(),
						)
					);
					?>
				</div>
				<div class="column col-2">
					<?php
					woocommerce_wp_select(
						array(
							'name'    => 'items[' . esc_attr( $commercial_shipment_item->get_id() ) . '][manufacture_country]',
							'id'      => 'commercial_invoice_item_' . esc_attr( $commercial_shipment_item->get_id() ) . '_manufacture_country',
							'label'   => '',
							'value'   => $invoice_item ? $invoice_item->get_manufacture_country() : $commercial_shipment_item->get_manufacture_country(),
							'options' => $countries,
						)
					);
					?>
				</div>
				<div class="column col-2">
					<?php
					woocommerce_wp_text_input(
						array(
							'name'      => 'items[' . esc_attr( $commercial_shipment_item->get_id() ) . '][total]',
							'id'        => 'commercial_invoice_item_' . esc_attr( $commercial_shipment_item->get_id() ) . '_total',
							'label'     => '',
							'value'     => wc_format_decimal( $invoice_item ? $invoice_item->get_total() : $commercial_shipment_item->get_total(), '' ),
							'data_type' => 'price',
						)
					);
					?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="commercial-invoice-data">
		<div class="columns">
			<div class="column col-6">
				<?php
				woocommerce_wp_select(
					array(
						'name'    => 'commercial_invoice_invoice_type',
						'id'      => 'commercial_invoice_invoice_type',
						'label'   => _x( 'Invoice Type', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'   => $commercial_invoice ? $commercial_invoice->get_invoice_type() : \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_default_invoice_type( $shipment ),
						'options' => \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_available_invoice_types(),
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_select(
					array(
						'name'    => 'commercial_invoice_incoterms',
						'id'      => 'commercial_invoice_incoterms',
						'label'   => _x( 'Incoterms', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'   => $commercial_invoice ? $commercial_invoice->get_incoterms() : \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_default_incoterms( $shipment ),
						'options' => \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_available_incoterms(),
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_select(
					array(
						'name'    => 'commercial_invoice_export_type',
						'id'      => 'commercial_invoice_export_type',
						'label'   => _x( 'Export type', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'   => $commercial_invoice ? $commercial_invoice->get_export_type() : \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_default_export_type( $shipment ),
						'options' => \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_available_export_types(),
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_select(
					array(
						'name'    => 'commercial_invoice_export_reason',
						'id'      => 'commercial_invoice_export_reason',
						'label'   => _x( 'Export reason', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'   => $commercial_invoice ? $commercial_invoice->get_export_reason() : \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_default_export_reason( $shipment ),
						'options' => \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper::get_available_export_reasons(),
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_text_input(
					array(
						'name'      => 'commercial_invoice_insurance_total',
						'id'        => 'commercial_invoice_insurance_total',
						'label'     => _x( 'Insurance amount', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'     => wc_format_decimal( $commercial_invoice ? $commercial_invoice->get_insurance_total() : '0.0', '' ),
						'data_type' => 'price',
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_text_input(
					array(
						'name'      => 'commercial_invoice_shipping_total',
						'id'        => 'commercial_invoice_shipping_total',
						'label'     => _x( 'Shipping total', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'     => wc_format_decimal( $commercial_invoice ? $commercial_invoice->get_shipping_total() : $commercial_shipment->get_shipping_total(), '' ),
						'data_type' => 'price',
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_text_input(
					array(
						'name'      => 'commercial_invoice_fee_total',
						'id'        => 'commercial_invoice_fee_total',
						'label'     => _x( 'Fees total', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'     => wc_format_decimal( $commercial_invoice ? $commercial_invoice->get_fee_total() : $commercial_shipment->get_fee_total(), '' ),
						'data_type' => 'price',
					)
				);
				?>
			</div>
			<div class="column col-6">
				<?php
				woocommerce_wp_select(
					array(
						'name'    => 'commercial_invoice_currency',
						'id'      => 'commercial_invoice_currency',
						'label'   => _x( 'Currency', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'value'   => $commercial_invoice ? $commercial_invoice->get_currency() : ( $shipment->get_order() ? $shipment->get_order()->get_currency() : get_woocommerce_currency() ),
						'options' => $currency_code_options,
					)
				);
				?>
			</div>
		</div>
	</div>

	<input type="hidden" name="shipment_id" id="wc-stc-shipment-commercial-invoice-admin-shipment-id" value="<?php echo esc_attr( $shipment->get_id() ); ?>" />
</div>
