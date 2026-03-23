<?php

namespace Vendidero\Shiptastic\DPD\Label;

use Vendidero\Shiptastic\Interfaces\ShipmentReturnLabel;

defined( 'ABSPATH' ) || exit;

/**
 * DPD ReturnLabel class.
 */
class Retoure extends Simple implements ShipmentReturnLabel {

	protected function get_hook_prefix() {
		return 'woocommerce_shiptastic_dpd_return_label_get_';
	}

	public function get_type() {
		return 'return';
	}
}
