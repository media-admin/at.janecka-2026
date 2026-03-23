<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;
use Vendidero\StoreaBill\Interfaces\Previewable;

defined( 'ABSPATH' ) || exit;

class Preview extends CommercialInvoice implements Previewable {

	protected $editor_preview = false;

	public function __construct( $args = array() ) {
		parent::__construct( 0 );

		$args = wp_parse_args(
			$args,
			array(
				'is_editor_preview' => false,
			)
		);

		Helper::maybe_switch_render_lang( $this );

		$this->set_is_editor_preview( $args['is_editor_preview'] );
		$this->set_date_created( sab_string_to_datetime( 'now' ) );
		$this->set_number( 1 );
		$this->set_formatted_number( $this->format_number( $this->get_number() ) );
		$this->set_shipment_id( 0 );
		$this->set_order_id( 4031 );
		$this->set_incoterms( 'DAP' );
		$this->set_export_type( 'permanent' );
		$this->set_export_reason( 'sale' );
		$this->set_shipping_total( 7.5 );
		$this->set_fee_total( 1.5 );
		$this->set_insurance_total( 25 );
		$this->set_packaging_weight( 0.01 );
		$this->set_length( 10 );
		$this->set_width( 20 );
		$this->set_height( 5 );

		$this->set_address(
			array(
				'first_name' => _x( 'Max', 'example-address', 'woocommerce-germanized-pro' ),
				'last_name'  => _x( 'Mustermann', 'example-address', 'woocommerce-germanized-pro' ),
				'address_1'  => _x( 'Wasserwerkgasse 38', 'example-address', 'woocommerce-germanized-pro' ),
				'postcode'   => _x( '3000', 'example-address', 'woocommerce-germanized-pro' ),
				'city'       => _x( 'Bern', 'example-address', 'woocommerce-germanized-pro' ),
				'country'    => _x( 'CH', 'example-address', 'woocommerce-germanized-pro' ),
				'phone'      => _x( '+43 123 456 78', 'example-address', 'woocommerce-germanized-pro' ),
			)
		);

		$this->set_shipping_address(
			array(
				'first_name' => _x( 'Max', 'example-address', 'woocommerce-germanized-pro' ),
				'last_name'  => _x( 'Mustermann', 'example-address', 'woocommerce-germanized-pro' ),
				'address_1'  => _x( 'Stampfenbachstrasse 61', 'example-address', 'woocommerce-germanized-pro' ),
				'postcode'   => _x( '8006', 'example-address', 'woocommerce-germanized-pro' ),
				'city'       => _x( 'Zürich', 'example-address', 'woocommerce-germanized-pro' ),
				'country'    => _x( 'CH', 'example-address', 'woocommerce-germanized-pro' ),
				'phone'      => _x( '+43 123 456 78', 'example-address', 'woocommerce-germanized-pro' ),
			)
		);

		$attributes = array(
			array(
				'key'   => 'attribute_1',
				'value' => __( 'Value 1', 'woocommerce-germanized-pro' ),
				'label' => __( 'Attribute 1', 'woocommerce-germanized-pro' ),
				'order' => 1,
			),
			array(
				'key'   => 'attribute_2',
				'value' => __( 'Value 2', 'woocommerce-germanized-pro' ),
				'label' => __( 'Attribute 2', 'woocommerce-germanized-pro' ),
				'order' => 2,
			),
		);

		$item = new ProductItem();
		$item->set_attributes( $attributes );
		$item->set_name( _x( 'T-shirt, cotton', 'commercial-invoice', 'woocommerce-germanized-pro' ) );
		$item->set_quantity( 2 );
		$item->set_sku( '123' );
		$item->set_global_unique_id( '12345678' );
		$item->set_hs_code( '12345678' );
		$item->set_mid_code( 'DE12345678' );
		$item->set_manufacture_country( 'DE' );
		$item->set_weight( 2.5 );
		$item->set_length( 10.5 );
		$item->set_width( 5.2 );
		$item->set_height( 2.2 );
		$item->set_total( 20 );

		$this->add_item( $item );

		/**
		 * Editor previews do only allow previewing one item.
		 * While rendering the preview (e.g. as PDF) it is useful to include more than one item.
		 */
		if ( ! $this->is_editor_preview() ) {
			$item = new ProductItem();
			$item->set_attributes( $attributes );
			$item->set_name( _x( 'Hoodie, cotton', 'commercial-invoice', 'woocommerce-germanized-pro' ) );
			$item->set_quantity( 1 );
			$item->set_sku( '122' );
			$item->set_global_unique_id( '12345678' );
			$item->set_hs_code( '12345678' );
			$item->set_mid_code( 'NL12345678' );
			$item->set_manufacture_country( 'NL' );
			$item->set_weight( 3.5 );
			$item->set_length( 12.5 );
			$item->set_width( 5 );
			$item->set_height( 2 );
			$item->set_total( 30 );

			$this->add_item( $item );
		}

		foreach ( $this->get_items() as $item ) {
			foreach ( $this->get_item_preview_meta( $item->get_item_type(), $item ) as $meta ) {
				$meta = wp_parse_args(
					$meta,
					array(
						'type'    => '',
						'preview' => '',
					)
				);

				$item->update_meta_data( $meta['type'], $meta['preview'] );
			}
		}

		foreach ( $this->get_preview_meta() as $meta ) {
			$meta = wp_parse_args(
				$meta,
				array(
					'type'    => '',
					'preview' => '',
				)
			);

			$this->update_meta_data( $meta['type'], $meta['preview'] );
		}

		$this->calculate_totals();
		$this->calculate_weights();
	}

	public function get_shipping_provider_title() {
		return 'DHL';
	}

	public function get_tracking_id() {
		return '12345678';
	}

	public function get_shipment_number( $context = 'view' ) {
		return 123;
	}

	public function get_preview_meta() {
		$meta_fields = apply_filters( "storeabill_{$this->get_type()}_preview_meta_types", array(), $this );

		return $meta_fields;
	}

	public function get_item_preview_meta( $item_type, $item = false ) {
		$meta_fields = apply_filters( "storeabill_{$this->get_type()}_preview_{$item_type}_item_meta_types", array(), $item, $this );

		return $meta_fields;
	}

	public function is_editor_preview() {
		return true === $this->editor_preview;
	}

	public function set_is_editor_preview( $is_editor ) {
		$this->editor_preview = $is_editor;
	}

	public function set_template( $template ) {
		$this->template = $template;
	}

	public function save() {
		foreach ( $this->get_items() as $item ) {
			$item->apply_changes();
		}

		$this->apply_changes();

		return false;
	}
}
