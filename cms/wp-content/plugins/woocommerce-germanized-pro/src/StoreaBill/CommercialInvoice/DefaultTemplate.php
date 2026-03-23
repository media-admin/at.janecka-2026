<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Editor\Templates\Template;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class DefaultTemplate extends Template {

	public static function get_template_data() {
		return apply_filters(
			self::get_hook_prefix() . 'data',
			array(
				'margins'   => array(
					'top'    => '1',
					'left'   => '1',
					'right'  => '1',
					'bottom' => '1',
				),
				'font_size' => 12,
			)
		);
	}

	public static function get_screenshot_url() {
		return '';
	}

	public static function get_tags() {
		return array();
	}

	public static function get_title() {
		return __( 'Default', 'woocommerce-germanized-pro' );
	}

	public static function get_document_type() {
		return 'commercial_invoice';
	}

	public static function get_name() {
		return 'default';
	}

	protected static function get_light_color() {
		return '#a9a9a9';
	}

	public static function get_html() {
		Helper::switch_to_english_locale();

		$heading_bg_color = sab_hex_lighter( self::get_light_color(), 70 );
		$preview          = sab_get_document_preview( self::get_document_type(), true );
		ob_start();
		?>
		<!-- wp:storeabill/document-styles /-->
		<?php echo self::get_default_header(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"33.33%"} -->
				<div class="wp-block-column" style="flex-basis: 33.33%">
					<!-- wp:storeabill/sender-address -->
					<div class="wp-block-storeabill-sender-address sab-document-address has-text-align-left">
						<p class="address-heading">
							<strong><span style="font-size:10px" class="has-inline-text-size"><?php echo esc_html_x( 'Exporter', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></span></strong>
						</p>
						<p class="address-content">
							<span class="placeholder-content" contenteditable="false"><span class="editor-placeholder"></span>{content}</span>
						</p>
					</div>
					<!-- /wp:storeabill/sender-address -->
				</div>
				<!-- /wp:column -->
				<!-- wp:column {"width":"33.33%"} -->
				<div class="wp-block-column" style="flex-basis: 33.33%">
					<!-- wp:storeabill/address -->
					<div class="wp-block-storeabill-address sab-document-address has-text-align-left">
						<p class="address-heading">
							<strong><span style="font-size:10px" class="has-inline-text-size"><?php echo esc_html_x( 'Importer (bill to)', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></span></strong>
						</p>
						<p class="address-content">
							<span class="placeholder-content" contenteditable="false"><span class="editor-placeholder"></span>{content}</span>
						</p>
					</div>
					<!-- /wp:storeabill/address -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"33.33%"} -->
				<div class="wp-block-column" style="flex-basis: 33.33%">
					<!-- wp:storeabill/shipping-address {"hideIfEqualsBilling": true} -->
					<div class="wp-block-storeabill-shipping-address sab-document-address has-text-align-left">
						<p class="address-heading">
							<strong><span style="font-size:10px" class="has-inline-text-size"><?php echo esc_html_x( 'Shipping to', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></span></strong>
						</p>
						<p class="address-content">
							<span class="placeholder-content" contenteditable="false"><span class="editor-placeholder"></span>{content}</span>
						</p>
					</div>
					<!-- /wp:storeabill/shipping-address -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->

		<!-- wp:storeabill/document-title {"customFontSize":"18"} -->
		<p class="has-text-align-left" style="font-size:18px"><strong><?php echo esc_html( apply_filters( self::get_hook_prefix() . 'document_title', strtoupper( _x( 'Commercial Invoice', 'commercial-invoice-template', 'woocommerce-germanized-pro' ) ) ) ); ?></strong></p>
		<!-- /wp:storeabill/document-title -->

		<!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:paragraph -->
				<p>
					<?php echo esc_html_x( 'Invoice number', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Formatted document number', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_number"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_number() ); ?></span><br>
					<?php echo esc_html_x( 'Shipment number', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Formatted shipment number', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=shipment_number"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_shipment_number() ); ?></span><br>
					<?php do_action( self::get_hook_prefix() . 'after_document_details' ); ?><br>
				</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:storeabill/document-date {"align":"right"} -->
				<p class="has-text-align-right"><?php echo esc_html_x( 'Date', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>: <span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Document date', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" ><span class="editor-placeholder"></span>{content}</span></p>
				<!-- /wp:storeabill/document-date -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<?php do_action( self::get_hook_prefix() . 'before_item_table' ); ?>

		<!-- wp:storeabill/item-table {"className":"is-style-even","customBorderColor":"<?php echo esc_attr( self::get_light_color() ); ?>","borders":["horizontal"],"headingTextColor":"black","headingFontSize":"small","customHeadingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>","hasDenseLayout":<?php echo self::has_dense_layout() ? 'true' : 'false'; ?>} -->
		<div class="wp-block-storeabill-item-table is-style-even has-border-horizontal">
			<!-- wp:storeabill/item-table-column {"width":30,"headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-left has-small-font-size" style="flex-basis:30%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Description of goods', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-name -->
				<p class="wp-block-storeabill-item-name sab-block-item-content"><span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Name', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>"><span class="editor-placeholder"></span>{content}</span> (<span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr__( 'SKU', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document_item?data=sku"><span class="editor-placeholder"></span>123</span>)</p>
				<!-- /wp:storeabill/item-name -->
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"width":15,"align":"center","headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center has-small-font-size" style="flex-basis:15%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'HS Code', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-meta {"metaType":"hs_code"} -->
				<p class="wp-block-storeabill-item-meta sab-block-item-content">{content}</p>
				<!-- /wp:storeabill/item-meta -->

				<?php do_action( self::get_hook_prefix() . 'after_item_hs_code' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"width":15,"align":"center","headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center has-small-font-size" style="flex-basis:15%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Origin', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-meta {"metaType":"manufacture_country"} -->
				<p class="wp-block-storeabill-item-meta sab-block-item-content">{content}</p>
				<!-- /wp:storeabill/item-meta -->

				<?php do_action( self::get_hook_prefix() . 'after_item_manufacture_country' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"width":10,"align":"center","headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center has-small-font-size" style="flex-basis:10%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Quantity', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-quantity -->
				<p class="wp-block-storeabill-item-quantity sab-block-item-content"><span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Quantity', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>"><span class="editor-placeholder"></span>{content}</span></p>
				<!-- /wp:storeabill/item-quantity -->

				<?php do_action( self::get_hook_prefix() . 'after_item_quantity' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"width":15,"align":"center","headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-center has-small-font-size" style="flex-basis:15%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Weight', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-meta {"metaType":"weight"} -->
				<p class="wp-block-storeabill-item-meta sab-block-item-content">{content}</p>
				<!-- /wp:storeabill/item-meta -->

				<?php do_action( self::get_hook_prefix() . 'after_item_net_weight' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->

			<!-- wp:storeabill/item-table-column {"width":15,"align":"right","headingTextColor":"#000000","fontSize":"small","headingFontSize":11,"headingBackgroundColor":"<?php echo esc_attr( $heading_bg_color ); ?>"} -->
			<div class="wp-block-storeabill-item-table-column is-horizontally-aligned-right has-small-font-size" style="flex-basis:15%">
				<span class="item-column-heading-text"><strong><?php echo esc_html_x( 'Unit value', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></span>
				<!-- wp:storeabill/item-price {"showPricesIncludingTax":true} -->
				<p class="wp-block-storeabill-item-price sab-block-item-content"><span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Price', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>"><span class="editor-placeholder"></span>{content}</span></p>
				<!-- /wp:storeabill/item-price -->

				<?php do_action( self::get_hook_prefix() . 'after_item_price' ); ?>
			</div>
			<!-- /wp:storeabill/item-table-column -->
		</div>
		<!-- /wp:storeabill/item-table -->

		<!-- wp:group {"className":"reset-columns-margin","layout":{"type":"constrained"}} -->
		<div class="wp-block-group reset-columns-margin">
			<!-- wp:columns {"fontSize":"small"} -->
			<div class="wp-block-columns has-small-font-size">
				<!-- wp:column {"width":"30%"} -->
				<div class="wp-block-column" style="flex-basis:30%"></div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"15%"} -->
				<div class="wp-block-column" style="flex-basis:15%"></div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"15%"} -->
				<div class="wp-block-column" style="flex-basis:15%">
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><?php echo esc_html_x( 'Subtotal', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><?php echo esc_html_x( 'Shipping', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><?php echo esc_html_x( 'Insurance', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><?php echo esc_html_x( 'Fees', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><?php echo esc_html_x( 'Total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></strong></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"10%"} -->
				<div class="wp-block-column" style="flex-basis:10%">
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Item count', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=item_count"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_item_count() ); ?></span></strong></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"15%"} -->
				<div class="wp-block-column" style="flex-basis:15%">
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Total net weight', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=net_weight&format=weight"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_weight( $preview->get_net_weight() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Packaging weight', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=packaging_weight&format=weight"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_weight( $preview->get_packaging_weight() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center">&nbsp;</p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center">&nbsp;</p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"center"} -->
					<p class="has-text-align-center"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Total weight', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=weight&format=weight"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_weight( $preview->get_weight() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"15%"} -->
				<div class="wp-block-column" style="flex-basis:15%">
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Product total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=product_total&format=price"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_price( $preview->get_product_total() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Shipping total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=shipping_total&format=price"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_price( $preview->get_shipping_total() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Insurance total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=insurance_total&format=price"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_price( $preview->get_insurance_total() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Fees total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=fee_total&format=price"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_price( $preview->get_fee_total() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"align":"right"} -->
					<p class="has-text-align-right"><strong><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Total', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=total&format=price"><span class="editor-placeholder"></span><?php echo wp_kses_post( $preview->get_formatted_price( $preview->get_total() ) ); ?></span></strong></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->

		<?php do_action( self::get_hook_prefix() . 'after_item_table' ); ?>

		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:columns -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"40%"} -->
				<div class="wp-block-column" style="flex-basis:40%">
					<!-- wp:paragraph -->
					<p>
						<?php echo esc_html_x( 'Incoterms', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Incoterms', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_incoterms"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_formatted_incoterms() ); ?></span><br>
						<?php echo esc_html_x( 'Type of Export', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Type of Export', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_export_type"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_formatted_export_type() ); ?></span><br>
						<?php echo esc_html_x( 'Reason for Export', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Reason for Export', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_export_reason"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_formatted_export_reason() ); ?></span>
					</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"40%"} -->
				<div class="wp-block-column" style="flex-basis:40%">
					<!-- wp:paragraph -->
					<p>
						<?php echo esc_html_x( 'Office of Origin', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Shipping Provider', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=shipping_provider_title"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_shipping_provider_title() ); ?></span><br>
						<?php echo esc_html_x( 'Track & Trace', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>: <span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Shipment tracking number', 'commercial-invoice', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=tracking_id"><span class="editor-placeholder"></span><?php echo esc_html( $preview->get_tracking_id() ); ?></span><br>
					</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->

		<!-- wp:storeabill/preferential-origin-declaration -->
		<p class="has-text-align-left"><?php printf( esc_html_x( 'The exporter of the products covered by this document declares that, except where otherwise clearly indicated, these products are of %s preferential origin.', 'commercial-invoice-template', 'woocommerce-germanized-pro' ), '<span class="placeholder-content sab-tooltip" contenteditable="false" data-tooltip="Origin"><span class="editor-placeholder"></span>{content}</span>' ); ?></p>
		<!-- /wp:storeabill/preferential-origin-declaration -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html_x( 'I/We hereby certify that the information on this document is true and correct and that the contents of this shipment are as stated above.', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:heading {"level":4} -->
				<h4 class="wp-block-heading"><?php echo esc_html_x( 'Name', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></h4>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p><span class="document-shortcode sab-tooltip" contenteditable="false" data-tooltip="<?php echo esc_attr_x( 'Sender Name', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?>" data-shortcode="document?data=formatted_full_sender_name&format=upper"><span class="editor-placeholder"></span><?php echo esc_html( strtoupper( $preview->get_formatted_full_sender_name() ) ); ?></span></p>
				<!-- /wp:paragraph -->

				<!-- wp:separator {"customColor":"<?php echo esc_attr( self::get_light_color() ); ?>","className":"is-style-wide"} -->
				<hr class="wp-block-separator has-text-color has-background is-style-wide" style="background-color:<?php echo esc_attr( self::get_light_color() ); ?>;color:<?php echo esc_attr( self::get_light_color() ); ?>"/>
				<!-- /wp:separator -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:heading {"level":4} -->
				<h4 class="wp-block-heading"><?php echo esc_html_x( 'Signature', 'commercial-invoice-template', 'woocommerce-germanized-pro' ); ?></h4>
				<!-- /wp:heading -->

				<!-- wp:spacer {"height":18} -->
				<div style="height:18px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->

				<!-- wp:separator {"customColor":"<?php echo esc_attr( self::get_light_color() ); ?>","className":"is-style-wide"} -->
				<hr class="wp-block-separator has-text-color has-background is-style-wide" style="background-color:<?php echo esc_attr( self::get_light_color() ); ?>;color:<?php echo esc_attr( self::get_light_color() ); ?>"/>
				<!-- /wp:separator -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<?php echo self::get_default_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$html = ob_get_clean();

		Helper::restore_locale();

		return apply_filters( self::get_hook_prefix() . 'html', self::clean_html_whitespaces( $html ) );
	}
}
