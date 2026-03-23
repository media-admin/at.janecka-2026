<?php
/**
 * Konto-Aktivierung / Willkommens-E-Mail – neuer Kunde
 *
 * Überschreibt: woocommerce/templates/emails/customer-new-account.php
 * E-Mail-Typ:   customer_new_account
 * Auslöser:     Neues Kundenkonto angelegt (Frontend oder Admin)
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

<p><?php printf(
    /* translators: %s: site name */
    esc_html__( 'herzlich willkommen! Dein Konto bei %s wurde erfolgreich erstellt.', 'customtheme' ),
    esc_html( get_bloginfo( 'name' ) )
); ?></p>

<!-- Zugangsdaten-Box -->
<div class="order-info-box">
    <strong><?php esc_html_e( 'Benutzername:', 'customtheme' ); ?></strong>
    <?php echo esc_html( $user_login ); ?>
    <?php if ( $password_generated && ! empty( $user_pass ) ) : ?>
    <br><strong><?php esc_html_e( 'Passwort:', 'customtheme' ); ?></strong>
    <?php echo esc_html( $user_pass ); ?>
    <br><small style="color:#9b9b9b;">
        <?php esc_html_e( 'Bitte ändere dein Passwort nach dem ersten Login.', 'customtheme' ); ?>
    </small>
    <?php endif; ?>
</div>

<!-- CTA -->
<p style="text-align:center;margin:28px 0 8px;">
    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
       style="display:inline-block;background-color:<?php echo esc_attr( apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) ) ); ?>;
              color:#ffffff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
              font-size:15px;font-weight:600;text-decoration:none;
              padding:13px 28px;border-radius:6px;">
        <?php esc_html_e( 'Zum Kundenkonto', 'customtheme' ); ?>
    </a>
</p>

<?php if ( $password_generated && ! empty( $user_pass ) ) : ?>
<p style="text-align:center;font-size:13px;color:#9b9b9b;margin:12px 0 0;">
    <?php esc_html_e( 'Nach dem Login empfehlen wir, dein Passwort zu ändern.', 'customtheme' ); ?>
</p>
<?php endif; ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
