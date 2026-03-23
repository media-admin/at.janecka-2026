<?php
/**
 * Document title block.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Item;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class DocumentTitle extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'document-title';

	public function get_attributes() {
		return array(
			'align' => $this->get_schema_align(),
		);
	}

	/**
	 * Append frontend scripts when rendering the Product Categories List block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		self::maybe_setup_document();

		if ( ! isset( $GLOBALS['document'] ) ) {
			return $content;
		}

		/**
		 * @var Document $document
		 */
		$document         = $GLOBALS['document'];
		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = $content;

		$current_plain_title = wp_strip_all_tags( $this->content );
		$plain_title         = apply_filters( "storeabill_{$document->get_type()}_plain_title", $current_plain_title, $document, $this->content );

		if ( $current_plain_title !== $plain_title ) {
			$this->content = str_replace( $current_plain_title, $plain_title, $this->content );
		}

		return $this->content;
	}
}
