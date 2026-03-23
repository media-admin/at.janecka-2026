<?php

/**
 * Item SKU block.
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class ItemGlobalUniqueId extends ItemTableColumnBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'item-global-unique-id';

	/**
	 * Append frontend scripts when rendering the Product Categories List block.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @param string $content Block content. Default empty string.
	 *
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		self::maybe_setup_document();
		self::maybe_setup_document_item();

		if ( ! isset( $GLOBALS['document_item'] ) ) {
			return $content;
		}

		/**
		 * @var Item $document_item
		 */
		$document_item = $GLOBALS['document_item'];
		$attributes    = $this->parse_attributes( $attributes );
		$output        = '';

		if ( is_callable( array( $document_item, 'get_global_unique_id' ) ) ) {
			$output = $document_item->get_global_unique_id();
		}

		return $this->wrap( $this->replace_placeholder( $content, wp_kses_post( $output ) ), $attributes );
	}
}
