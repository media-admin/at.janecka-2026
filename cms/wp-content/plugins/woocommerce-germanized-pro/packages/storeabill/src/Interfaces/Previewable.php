<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Previewable Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface Previewable {

	public function set_template( $template );

	public function is_editor_preview();

	public function set_is_editor_preview( $is_editor_preview );

	public function get_item_preview_meta( $item_type, $item = false );

	public function get_preview_meta();
}
