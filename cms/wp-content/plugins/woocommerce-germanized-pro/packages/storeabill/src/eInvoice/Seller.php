<?php

namespace Vendidero\StoreaBill\eInvoice;

defined( 'ABSPATH' ) || exit;

class Seller extends Party {

	public function get_party_type() {
		return 'seller';
	}
}
