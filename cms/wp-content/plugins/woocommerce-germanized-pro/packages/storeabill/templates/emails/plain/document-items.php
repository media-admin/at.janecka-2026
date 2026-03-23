<?php
/**
 * @var \Vendidero\StoreaBill\Document\Item[] $items
 *
 * @version 1.1.0
 */
defined( 'ABSPATH' ) || exit;

foreach ( $items as $item_id => $item ) :
	if ( ! apply_filters( 'storeabill_email_document_item_visible', true, $item ) ) {
		continue;
	}
	foreach ( $columns as $column ) {
		if ( 'name' === $column ) {
			echo apply_filters( 'storeabill_email_document_item_name', esc_html( wp_strip_all_tags( $item->get_name() ) ), $item, $plain_text ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'quantity' === $column ) {
			echo apply_filters( 'storeabill_email_document_item_quantity', ' X ' . esc_html( wp_strip_all_tags( $item->get_quantity() ) ), $item, $plain_text ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$getter = "get_$column";
			if ( is_callable( array( $item, $getter ) ) ) {
				echo apply_filters( 'storeabill_email_document_item_' . $column, esc_html( wp_strip_all_tags( $document->$getter() ) ), $item, $plain_text ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo apply_filters( 'storeabill_email_document_item_' . $column, '', $item, $plain_text ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		do_action( "storeabill_email_after_document_item_column_{$column}", $item, $document, $plain_text );
	}

	echo "\n\n";
endforeach;
