<?php

namespace Vendidero\StoreaBill\eInvoice;

defined( 'ABSPATH' ) || exit;

class Buyer extends Party {

	public function get_party_type() {
		return 'buyer';
	}
}
