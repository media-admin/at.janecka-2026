<?php

namespace Vendidero\Shiptastic\Hermes\ShippingProvider\Services;

use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class SignatureService extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'        => 'signatureService',
			'label'     => _x( 'Signature', 'hermes', 'woocommerce-germanized-pro' ),
			'countries' => array( 'FR' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			if ( $this->supports_country( $shipment->get_country() ) ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}
}
