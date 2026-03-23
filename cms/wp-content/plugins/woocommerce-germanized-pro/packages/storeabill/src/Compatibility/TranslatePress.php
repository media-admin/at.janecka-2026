<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Interfaces\Compatibility;

defined( 'ABSPATH' ) || exit;

class TranslatePress implements Compatibility {

	public static function is_active() {
		return class_exists( 'TRP_Translate_Press' );
	}

	public static function init() {
		add_action(
			'storeabill_before_send_document_email',
			function ( $document ) {
				if ( is_callable( array( $document, 'get_order_id' ) ) && apply_filters( 'storeabill_translate_translatepress_emails', true ) ) {
					if ( $document->get_order_id() ) {
						global $TRP_EMAIL_ORDER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

						$TRP_EMAIL_ORDER = $document->get_order_id(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
					}
				}
			},
			10
		);
	}
}
