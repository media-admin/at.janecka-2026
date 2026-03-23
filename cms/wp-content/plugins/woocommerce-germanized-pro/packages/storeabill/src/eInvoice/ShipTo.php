<?php

namespace Vendidero\StoreaBill\eInvoice;

defined( 'ABSPATH' ) || exit;

class ShipTo extends Party {

	public function get_party_type() {
		return 'ship_to';
	}
}
