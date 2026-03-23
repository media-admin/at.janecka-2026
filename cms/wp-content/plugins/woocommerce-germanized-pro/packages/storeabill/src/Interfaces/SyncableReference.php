<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SyncableReference
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface SyncableReference extends Reference {

	/**
	 * Syncs an object with the current reference.
	 *
	 * @param \WC_Data $object
	 */
	public function sync( &$object, $args = array() );
}
