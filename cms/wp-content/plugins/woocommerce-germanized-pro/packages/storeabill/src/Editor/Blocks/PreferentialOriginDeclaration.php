<?php
/**
 * All products block.
 *
 * @package WooCommerce\Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class PreferentialOriginDeclaration extends DynamicBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'preferential-origin-declaration';

	public function get_attributes() {
		return array(
			'align'         => $this->get_schema_align(),
			'virtualNotice' => $this->get_schema_string(),
		);
	}

	protected function replace_placeholder( $content, $replacement ) {
		if ( empty( $content ) ) {
			$content = '{content}';
		}

		return str_replace( '{content}', $replacement, $content );
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
			return '';
		}

		/**
		 * @var Document $document
		 */
		$document         = $GLOBALS['document'];
		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = '';
		$countries        = array();
		$show_origin      = false;

		if ( is_callable( array( $document, 'get_countries_of_origin' ) ) ) {
			$countries = $document->get_countries_of_origin();
		}

		if ( ! empty( $countries ) ) {
			foreach ( $countries as $country ) {
				if ( in_array( $country, Countries::get_eu_countries(), true ) ) {
					$show_origin = true;
				}
			}
		}

		if ( apply_filters( 'storeabill_document_show_preferential_origin_declaration', $show_origin, $document ) ) {
			$this->content = apply_filters( 'storeabill_document_preferential_origin_declaration', $content, $document );
			$this->content = $this->replace_placeholder( $content, apply_filters( 'storeabill_document_preferential_origin', 'EU', $document ) );
		}

		return $this->content;
	}
}
