<?php
/**
 * E-Mail-Footer – Custom Template Override
 *
 * Überschreibt: woocommerce/templates/emails/email-footer.php
 *
 * @package CustomTheme\WooCommerceEmails
 * @since   1.16.0
 */

defined( 'ABSPATH' ) || exit;

$primary_color = apply_filters( 'customtheme_email_primary_color', get_option( 'woocommerce_email_base_color', '#ff0000' ) );
$bg_color      = apply_filters( 'customtheme_email_bg_color',      get_option( 'woocommerce_email_background_color', '#f7f7f7' ) );
$site_name     = get_bloginfo( 'name' );
$site_url      = home_url( '/' );

// Footer-Links konfigurierbar via Filter
$footer_links = apply_filters( 'customtheme_email_footer_links', [
    __( 'Datenschutz', 'customtheme' ) => get_privacy_policy_url() ?: $site_url,
    __( 'Impressum',   'customtheme' ) => $site_url . 'impressum/',
    __( 'Kontakt',     'customtheme' ) => $site_url . 'kontakt/',
] );

$footer_text = apply_filters(
    'customtheme_email_footer_text',
    sprintf(
        wp_kses( __( '{site_name} · <a href="{site_url}" style="color:#9b9b9b;">%s</a>', 'customtheme' ), [ 'a' => [ 'href' => [], 'style' => [] ] ] ),
        esc_url( $site_url )
    )
);

?>
                    </td><!-- Ende Inhalt-TD aus email-header.php -->
                </tr>

                <!-- ── TRENNLINIE ───────────────────────────────────────────── -->
                <tr>
                    <td style="padding:32px 40px 0;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td style="border-top:1px solid #e0e0e0;font-size:0;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ── FOOTER-LINKS ─────────────────────────────────────────── -->
                <?php if ( $footer_links ) : ?>
                <tr>
                    <td align="center" style="padding:20px 40px 4px;">
                        <p style="margin:0;padding:0;
                                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
                                  font-size:12px;color:#9b9b9b;line-height:2;">
                            <?php
                            $link_items = [];
                            foreach ( $footer_links as $label => $url ) {
                                $link_items[] = '<a href="' . esc_url( $url ) . '" target="_blank"'
                                    . ' style="color:#9b9b9b;text-decoration:underline;">'
                                    . esc_html( $label ) . '</a>';
                            }
                            echo implode( ' &nbsp;·&nbsp; ', $link_items );
                            ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- ── FOOTER-TEXT ──────────────────────────────────────────── -->
                <tr>
                    <td align="center" style="padding:8px 40px 32px;">
                        <p style="margin:0;padding:0;
                                  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
                                  font-size:12px;color:#9b9b9b;line-height:1.6;">
                            <?php echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text', $site_name ) ) ); ?>
                        </p>
                    </td>
                </tr>

            </table><!-- Ende E-Mail-Container -->

        </td>
    </tr>
</table><!-- Ende äußerer Wrapper -->

</body>
</html>
