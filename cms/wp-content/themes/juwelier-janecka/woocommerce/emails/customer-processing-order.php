<?php
/**
 * Bestellbestätigung – Kunde
 *
 * Überschreibt: woocommerce/templates/emails/customer-processing-order.php
 * E-Mail-Typ:   customer_processing_order
 * Auslöser:     Bestellung eingegangen (Status → "In Bearbeitung")
 *
 * Verfügbare Variablen:
 *   $order          WC_Order
 *   $email_heading  string
 *   $sent_to_admin  bool
 *   $plain_text     bool
 *   $email          WC_Email
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf(
    /* translators: %s: customer first name */
    esc_html__( 'Hallo %s,', 'customtheme' ),
    esc_html( $order->get_billing_first_name() )
); ?></p>

<p><?php esc_html_e( 'vielen Dank für deine Bestellung! Wir haben sie erhalten und bearbeiten sie so schnell wie möglich.', 'customtheme' ); ?></p>

<!-- Info-Box: Bestellnummer -->
<div class="order-info-box">
    <strong><?php esc_html_e( 'Bestellnummer:', 'customtheme' ); ?></strong>
    #<?php echo esc_html( $order->get_order_number() ); ?> &nbsp;·&nbsp;
    <strong><?php esc_html_e( 'Datum:', 'customtheme' ); ?></strong>
    <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
</div>

<?php do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<!-- CTA Button -->
<p style="text-align:center;margin:28px 0;">
    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
       style="display:inline-block;background-color:<?php echo esc_attr( apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) ) ); ?>;
              color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
              font-size:15px;font-weight:600;text-decoration:none;
              padding:13px 28px;border-radius:6px;">
        <?php esc_html_e( 'Bestellung ansehen', 'customtheme' ); ?>
    </a>
</p>

<p><?php esc_html_e( 'Du hast Fragen zu deiner Bestellung? Wir helfen gerne!', 'customtheme' ); ?>
<br><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt aufnehmen', 'customtheme' ); ?></a></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
