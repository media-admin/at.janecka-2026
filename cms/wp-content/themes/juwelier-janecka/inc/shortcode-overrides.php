<?php
/**
 * Shortcode Overrides – Projektanpassungen
 *
 * Überschreibt Shortcodes aus dem Media Lab Agency Core Plugin
 * mit projektspezifischen Versionen.
 *
 * Prinzip: remove_shortcode() + add_shortcode() via init (Priority 20),
 * damit das Plugin seinen Shortcode zuerst registriert (Priority 10)
 * und wir ihn danach gezielt ersetzen können.
 *
 * @package Custom_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}