<?php
/**
 * @var $document \Vendidero\StoreaBill\Document\Document
 *
 * @version 1.1.0
 */
defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php echo apply_filters( 'storeabill_email_document_customer_salutation', sprintf( esc_html_x( 'Hi %s,', 'storeabill-core', 'woocommerce-germanized-pro' ), esc_html( sab_get_document_salutation( $document ) ) ), $document, $email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

	<p>
		<?php
			/* translators: %s: Document title */
			printf( esc_html_x( '%s has been attached to this email. Find details below for your reference:', 'storeabill-core', 'woocommerce-germanized-pro' ), wp_kses_post( $document->get_title() ) );
		?>
	</p>
<?php

do_action( 'storeabill_email_document_details', $document, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
