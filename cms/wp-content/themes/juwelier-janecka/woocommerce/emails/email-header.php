<?php
/**
 * E-Mail-Header – Custom Template Override
 *
 * Überschreibt: woocommerce/templates/emails/email-header.php
 *
 * Konfiguration via WooCommerce → Einstellungen → E-Mails:
 *   - Logo: WC-Option "woocommerce_email_header_image"
 *   - Farben: WC-Optionen "woocommerce_email_background_color" etc.
 *   - Absender: "woocommerce_email_from_name" / "woocommerce_email_from_address"
 *
 * Projektspezifische Anpassung via Filter:
 *   add_filter( 'customtheme_email_primary_color', fn() => '#003366' );
 *   add_filter( 'customtheme_email_logo_url',      fn() => 'https://...' );
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

// ── Konfiguration ─────────────────────────────────────────────────────────────

$primary_color   = apply_filters( 'customtheme_email_primary_color',    get_option( 'woocommerce_email_base_color', '#ff0000' ) );
$bg_color        = apply_filters( 'customtheme_email_bg_color',         get_option( 'woocommerce_email_background_color', '#f7f7f7' ) );
$body_bg         = apply_filters( 'customtheme_email_body_bg_color',    get_option( 'woocommerce_email_body_background_color', '#ffffff' ) );
$text_color      = apply_filters( 'customtheme_email_text_color',       get_option( 'woocommerce_email_text_color', '#1a1a1a' ) );

// Logo: zuerst Filter, dann WC-Option, dann Site-Name als Fallback
$logo_url = apply_filters( 'customtheme_email_logo_url', '' );
if ( empty( $logo_url ) ) {
    $logo_url = get_option( 'woocommerce_email_header_image', '' );
}

$site_name = get_bloginfo( 'name' );
$site_url  = home_url( '/' );

?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ?: 'de' ); ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html( $email_heading ?? $site_name ); ?></title>
    <?php do_action( 'woocommerce_email_styles', $email ); ?>
</head>
<body>

<!-- Preheader (unsichtbar, aber in E-Mail-Client-Vorschau sichtbar) -->
<?php if ( ! empty( $additional_content ) ) : ?>
<span style="display:none;max-height:0;overflow:hidden;">
    <?php echo wp_kses_post( $additional_content ); ?>
</span>
<?php endif; ?>

<!-- Äußerer Wrapper -->
<table border="0" cellpadding="0" cellspacing="0" width="100%"
       style="background-color:<?php echo esc_attr( $bg_color ); ?>;margin:0;padding:0;">
    <tr>
        <td align="center" valign="top" style="padding:40px 20px;">

            <!-- E-Mail-Container -->
            <table border="0" cellpadding="0" cellspacing="0" width="600"
                   style="max-width:600px;width:100%;background-color:<?php echo esc_attr( $body_bg ); ?>;
                          border-radius:8px;overflow:hidden;
                          box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                <!-- ── HEADER ──────────────────────────────────────────────── -->
                <tr>
                    <td align="center" valign="middle"
                        style="background-color:<?php echo esc_attr( $primary_color ); ?>;
                               padding:32px 40px;">
                        <?php if ( $logo_url ) : ?>
                            <a href="<?php echo esc_url( $site_url ); ?>" target="_blank"
                               style="text-decoration:none;">
                                <img src="<?php echo esc_url( $logo_url ); ?>"
                                     alt="<?php echo esc_attr( $site_name ); ?>"
                                     style="max-height:60px;max-width:240px;
                                            height:auto;width:auto;
                                            display:block;border:0;">
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $site_url ); ?>" target="_blank"
                               style="text-decoration:none;
                                      font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
                                      font-size:24px;font-weight:700;
                                      color:#ffffff;letter-spacing:-0.5px;">
                                <?php echo esc_html( $site_name ); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- ── BETREFF-ZEILE ────────────────────────────────────────── -->
                <?php if ( ! empty( $email_heading ) ) : ?>
                <tr>
                    <td align="left" valign="top"
                        style="background-color:<?php echo esc_attr( $primary_color ); ?>;
                               padding:0 40px 28px 40px;">
                        <h1 style="margin:0;padding:0;
                                   font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
                                   font-size:22px;font-weight:600;
                                   color:#ffffff;line-height:1.3;">
                            <?php echo esc_html( $email_heading ); ?>
                        </h1>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- ── INHALT (wird von WC befüllt) ───────────────────────── -->
                <tr>
                    <td align="left" valign="top"
                        style="padding:36px 40px 0 40px;
                               font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
                               font-size:15px;line-height:1.6;
                               color:<?php echo esc_attr( $text_color ); ?>;">
