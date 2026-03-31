<?php
/**
 * E-Mail CSS-Styles (inline)
 *
 * Überschreibt: woocommerce/templates/emails/email-styles.php
 * Wird via woocommerce_email_styles-Hook als <style>-Block ausgegeben.
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

$primary   = apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) );
$text      = apply_filters( 'customtheme_email_text_color',    get_option( 'woocommerce_email_text_color', '#1a1a1a' ) );
$body_bg   = apply_filters( 'customtheme_email_body_bg_color', get_option( 'woocommerce_email_body_background_color', '#ffffff' ) );

?>
<style type="text/css">
/* ── Reset ──────────────────────────────────────────────────────────────── */
body, table, td, p, a, li, blockquote {
    -webkit-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
}
table, td {
    mso-table-lspace: 0;
    mso-table-rspace: 0;
}
img {
    -ms-interpolation-mode: bicubic;
    border: 0;
    outline: none;
    text-decoration: none;
}

/* ── Basis ───────────────────────────────────────────────────────────────── */
body {
    margin: 0 !important;
    padding: 0 !important;
    background-color: <?php echo esc_attr( get_option( 'woocommerce_email_background_color', '#f7f7f7' ) ); ?>;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    color: <?php echo esc_attr( $text ); ?>;
}

/* ── Typografie ──────────────────────────────────────────────────────────── */
h1, h2, h3 {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-weight: 600;
    color: <?php echo esc_attr( $text ); ?>;
    margin: 0 0 16px;
    line-height: 1.3;
}
h1 { font-size: 24px; }
h2 { font-size: 18px; }
h3 { font-size: 16px; }

p {
    margin: 0 0 16px;
    font-size: 15px;
    line-height: 1.6;
    color: <?php echo esc_attr( $text ); ?>;
}

a {
    color: <?php echo esc_attr( $primary ); ?>;
    text-decoration: none;
}
a:hover { text-decoration: underline; }

/* ── Bestelldetails-Tabelle ──────────────────────────────────────────────── */
.order-details,
.td {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-size: 15px;
    color: <?php echo esc_attr( $text ); ?>;
}

table.order_details {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 24px;
}

table.order_details th {
    background-color: <?php echo esc_attr( $body_bg ); ?>;
    border-bottom: 2px solid #e0e0e0;
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9b9b9b;
    text-align: left;
}

table.order_details td {
    padding: 12px 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}

table.order_details tr:last-child td {
    border-bottom: none;
}

/* ── Totals ──────────────────────────────────────────────────────────────── */
.order-total td,
.order-total th {
    font-size: 16px;
    font-weight: 700;
    color: <?php echo esc_attr( $text ); ?>;
    padding-top: 14px;
    border-top: 2px solid #e0e0e0;
}

/* ── Adress-Boxen ────────────────────────────────────────────────────────── */
.address {
    color: <?php echo esc_attr( $text ); ?>;
    font-size: 14px;
    line-height: 1.6;
    padding: 16px;
    background-color: #f9f9f9;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

/* ── Button ──────────────────────────────────────────────────────────────── */
.button,
.button a {
    display: inline-block;
    background-color: <?php echo esc_attr( $primary ); ?>;
    color: #ffffff !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    padding: 13px 28px;
    border-radius: 6px;
    mso-padding-alt: 0;
    line-height: normal;
}

/* ── Info-Box ────────────────────────────────────────────────────────────── */
.order-info-box {
    background-color: #f9f9f9;
    border-left: 4px solid <?php echo esc_attr( $primary ); ?>;
    border-radius: 0 6px 6px 0;
    padding: 16px 20px;
    margin: 0 0 24px;
    font-size: 14px;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media only screen and (max-width: 620px) {
    table.order_details th,
    table.order_details td {
        padding: 8px 10px;
    }
    .button a {
        display: block !important;
        text-align: center;
    }
}
</style>
