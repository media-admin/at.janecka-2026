<?php
/**
 * Notification Overrides
 * Projektspezifische Anpassungen für Media Lab Agency Core Notifications.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Titel in Banner-Notifications unterdrücken.
 * Filter: media_lab_notification_banner_title (Agency Core Plugin)
 */
add_filter( 'media_lab_notification_banner_title', '__return_empty_string' );
