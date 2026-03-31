<?php
/**
 * Bestellung abgeschlossen – Kunde
 *
 * Überschreibt: woocommerce/templates/emails/customer-completed-order.php
 * E-Mail-Typ:   customer_completed_order
 * Auslöser:     Bestellung-Status → "Abgeschlossen"
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf(
    esc_html__( 'Hallo %s,', 'customtheme' ),
    esc_html( $order->get_billing_first_name() )
); ?></p>

<p><?php esc_html_e( 'deine Bestellung wurde erfolgreich abgeschlossen. Wir hoffen, dass du mit deinem Einkauf zufrieden bist!', 'customtheme' ); ?></p>

<!-- Info-Box: Bestellnummer -->
<div class="order-info-box">
    <strong><?php esc_html_e( 'Bestellnummer:', 'customtheme' ); ?></strong>
    #<?php echo esc_html( $order->get_order_number() ); ?> &nbsp;·&nbsp;
    <strong><?php esc_html_e( 'Abgeschlossen:', 'customtheme' ); ?></strong>
    <?php echo esc_html( wc_format_datetime( $order->get_date_completed() ?: $order->get_date_modified() ) ); ?>
</div>

<?php do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<!-- Bewertung CTA (optional – via Filter deaktivierbar) -->
<?php if ( apply_filters( 'customtheme_email_show_review_cta', true ) && wc_review_ratings_enabled() ) : ?>
<p style="text-align:center;margin:28px 0 8px;">
    <?php esc_html_e( 'Wie war deine Erfahrung? Wir freuen uns über deine Bewertung:', 'customtheme' ); ?>
</p>
<p style="text-align:center;margin:0 0 28px;">
    <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"
       style="display:inline-block;background-color:<?php echo esc_attr( apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) ) ); ?>;
              color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
              font-size:15px;font-weight:600;text-decoration:none;
              padding:13px 28px;border-radius:6px;">
        <?php esc_html_e( 'Jetzt bewerten', 'customtheme' ); ?>
    </a>
</p>
<?php else : ?>
<p style="text-align:center;margin:28px 0;">
    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
       style="display:inline-block;background-color:<?php echo esc_attr( apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) ) ); ?>;
              color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
              font-size:15px;font-weight:600;text-decoration:none;
              padding:13px 28px;border-radius:6px;">
        <?php esc_html_e( 'Bestellung ansehen', 'customtheme' ); ?>
    </a>
</p>
<?php endif; ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
