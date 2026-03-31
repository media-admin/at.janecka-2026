<?php
/**
 * Passwort-Reset – Kunde
 *
 * Überschreibt: woocommerce/templates/emails/customer-reset-password.php
 * E-Mail-Typ:   customer_reset_password
 * Auslöser:     Passwort-vergessen-Formular im Kundenkonto
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf(
    esc_html__( 'Hallo %s,', 'customtheme' ),
    esc_html( $user_login )
); ?></p>

<p><?php esc_html_e( 'du hast ein neues Passwort für dein Konto angefordert. Klicke auf den Button um ein neues Passwort zu setzen:', 'customtheme' ); ?></p>

<!-- CTA -->
<p style="text-align:center;margin:32px 0;">
    <a href="<?php echo esc_url( add_query_arg( [ 'key' => $reset_key, 'login' => rawurlencode( $user_login ) ], wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>"
       style="display:inline-block;background-color:<?php echo esc_attr( apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) ) ); ?>;
              color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
              font-size:15px;font-weight:600;text-decoration:none;
              padding:13px 28px;border-radius:6px;">
        <?php esc_html_e( 'Passwort zurücksetzen', 'customtheme' ); ?>
    </a>
</p>

<p style="font-size:13px;color:#9b9b9b;text-align:center;">
    <?php esc_html_e( 'Der Link ist 24 Stunden gültig. Falls du kein neues Passwort angefordert hast, kannst du diese E-Mail ignorieren.', 'customtheme' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
