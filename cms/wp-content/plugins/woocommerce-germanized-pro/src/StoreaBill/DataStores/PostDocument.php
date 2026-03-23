<?php

namespace Vendidero\Germanized\Pro\StoreaBill\DataStores;

use Vendidero\StoreaBill\DataStores\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Page data store.
 *
 * @version 1.0.0
 */
class PostDocument extends Document {

	protected function get_search_related_properties( $document ) {
		return array();
	}
}
