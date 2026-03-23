<?php
/**
 * Convert imported content to Gutenberg blocks if the option is enabled.
 *
 * This filter processes post content during import and converts it to block format
 * when the 'is_convert_to_blocks' option is enabled. It intelligently handles:
 * - Content that already contains block markup (preserves it)
 * - HTML/classic content (converts to blocks)
 * - Page builder tags and custom markup (preserves them)
 *
 * @param string $content The post content to process
 * @param int $import_id The ID of the current import
 *
 * @return string The processed content (with or without block conversion)
 */
function pmxi_pmxi_the_content( $content, $import_id ) {
	// Get the import record to access options
	$import = new PMXI_Import_Record();
	$import->getById( $import_id );
	
	// Only proceed if the import exists and the option is enabled
	if ( $import->isEmpty() || empty( $import->options['is_convert_to_blocks'] ) ) {
		return $content;
	}
	
	// Only convert content for post types (not for taxonomies, comments, etc.)
	if ( ! empty( $import->options['custom_type'] ) ) {
		$custom_type = $import->options['custom_type'];

		// Skip conversion for non-post types
		if ( in_array( $custom_type, array( 'taxonomies', 'comments', 'woo_reviews', 'import_users', 'shop_customer', 'shop_order', 'gf_entries' ) ) ) {
			return $content;
		}

		// For actual post types, check if they support the block editor
		// This ensures we don't add block markup to post types that use the Classic Editor
		// (e.g., WooCommerce products, custom post types with Classic Editor enabled)
		if ( post_type_exists( $custom_type ) && function_exists( 'use_block_editor_for_post_type' ) ) {
			if ( ! use_block_editor_for_post_type( $custom_type ) ) {
				return $content;
			}
		}
	}
	
	// If content is empty, return as-is
	if ( empty( trim( $content ) ) ) {
		return $content;
	}
	
	// Check if WordPress block functions are available
	if ( ! function_exists( 'has_blocks' ) || ! function_exists( 'parse_blocks' ) ) {
		return $content;
	}
	
	// If content already has block markup, preserve it
	if ( has_blocks( $content ) ) {
		return $content;
	}
	
	// Convert classic/HTML content to blocks
	return pmxi_convert_classic_content_to_blocks( $content );
}

/**
 * Convert classic/HTML content to Gutenberg blocks.
 *
 * This function implements a conversion strategy that matches Gutenberg's native behavior:
 * 1. Detects if content contains only phrasing (inline) content
 * 2. If only inline HTML (span, strong, em, a, etc.), wraps in Paragraph blocks
 * 3. If block-level HTML (div, table, ul, etc.), wraps in Classic block
 * 4. If plain text, wraps in Paragraph blocks
 *
 * This approach ensures:
 * - Content behaves the same as if manually pasted into Gutenberg
 * - Inline formatting (bold, italic, links) uses Paragraph blocks
 * - Complex structures (divs, tables, lists) use Classic blocks
 * - Page builder tags are preserved in Classic blocks
 * - No content is lost or mangled during conversion
 *
 * @param string $content The classic content to convert
 *
 * @return string The content wrapped in appropriate blocks
 */
function pmxi_convert_classic_content_to_blocks( $content ) {
	// Trim the content
	$content = trim( $content );

	// If empty after trimming, return empty
	if ( empty( $content ) ) {
		return '';
	}

	// Check if content contains only phrasing (inline) content
	if ( pmxi_is_phrasing_content( $content ) ) {
		// Content has only inline HTML or plain text - use Paragraph blocks
		// This matches Gutenberg's behavior when pasting inline-formatted text

		// Split by double line breaks to create separate paragraphs
		$paragraphs = preg_split( '/\n\s*\n/', $content );
		$blocks = array();

		foreach ( $paragraphs as $paragraph ) {
			$paragraph = trim( $paragraph );
			if ( ! empty( $paragraph ) ) {
				// Replace single line breaks with <br> tags
				$paragraph = str_replace( "\n", '<br>', $paragraph );

				// Wrap in <p> tags if not already wrapped
				if ( ! preg_match( '/^\s*<p[\s>]/i', $paragraph ) ) {
					$paragraph = '<p>' . $paragraph . '</p>';
				}

				$blocks[] = '<!-- wp:paragraph -->' . "\n" . $paragraph . "\n" . '<!-- /wp:paragraph -->';
			}
		}

		return implode( "\n\n", $blocks );
	} else {
		// Content has block-level HTML - use Classic block to preserve everything
		// This is the safest approach for:
		// - Block-level elements (div, table, ul, ol, etc.)
		// - Page builder shortcodes/tags
		// - Complex HTML structures
		// - Custom markup
		// - Embedded content
		return '<!-- wp:freeform -->' . "\n" . $content . "\n" . '<!-- /wp:freeform -->';
	}
}

/**
 * Determine if content contains only phrasing (inline) content.
 *
 * This function checks whether the content contains only inline-level HTML elements
 * (phrasing content in HTML5 terminology) or plain text. This matches Gutenberg's
 * logic for determining whether to use a Paragraph block vs. a Classic block.
 *
 * Phrasing content includes: span, a, strong, em, code, etc.
 * Block-level content includes: div, p, table, ul, ol, h1-h6, etc.
 *
 * @param string $content The content to check
 *
 * @return bool True if content contains only phrasing content, false if it contains block-level elements
 */
function pmxi_is_phrasing_content( $content ) {
	// List of inline/phrasing elements that are allowed in paragraph blocks
	// Based on HTML5 phrasing content specification
	$phrasing_elements = array(
		'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'del',
		'dfn', 'em', 'i', 'ins', 'kbd', 'mark', 'q', 's', 'samp', 'small',
		'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr', 'img'
	);

	// Check if content has any HTML tags
	if ( ! preg_match( '/<[^>]+>/', $content ) ) {
		// Plain text - can use paragraph block
		return true;
	}

	// Build a regex pattern to match any tag that is NOT a phrasing element
	// This will help us detect block-level elements
	$phrasing_pattern = implode( '|', array_map( 'preg_quote', $phrasing_elements ) );

	// Check for any HTML tag that is NOT in the phrasing elements list
	// This regex matches opening or closing tags that are not phrasing elements
	$has_block_elements = preg_match(
		'/<\/?(?!' . $phrasing_pattern . '\b)[a-z][a-z0-9]*\b[^>]*>/i',
		$content
	);

	// If we found block-level elements, return false
	// If we only found phrasing elements (or no elements), return true
	return ! $has_block_elements;
}

