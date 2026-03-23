<?php

namespace Vendidero\Shiptastic\DPD\Interfaces;

use Vendidero\Shiptastic\DPD\Label\Retoure;
use Vendidero\Shiptastic\DPD\Label\Simple;
use Vendidero\Shiptastic\ShipmentError;

interface LabelApi {

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return ShipmentError|true
	 */
	public function get_label( $label );

	public function get_international_customs_terms();

	public function get_international_customs_paper();

	public function test_connection();

	public function get_parcel_shops( $args, $limit = 20 );

	public function get_parcel_shop_by_id( $id, $args );
}
