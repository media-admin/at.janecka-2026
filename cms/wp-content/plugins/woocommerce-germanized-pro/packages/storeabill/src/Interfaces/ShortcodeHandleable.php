<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShortcodeHandleable
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface ShortcodeHandleable {

	public function destroy();

	public function setup();

	public function get_shortcodes();

	public function supports( $document_type );
}
