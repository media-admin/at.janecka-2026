<?php
/**
 * WordPress Configuration - Environment Switcher
 * 
 * Loads environment-specific config based on server.
 */

// Determine environment
if (file_exists(__DIR__ . '/wp-config-local.php')) {
    // Local development
    require_once __DIR__ . '/wp-config-local.php';
} elseif (file_exists(__DIR__ . '/wp-config-staging.php')) {
    // Staging server
    require_once __DIR__ . '/wp-config-staging.php';
} elseif (file_exists(__DIR__ . '/wp-config-production.php')) {
    // Production server
    require_once __DIR__ . '/wp-config-production.php';
} else {
    die('No environment configuration found!');
}

// Shared configuration (all environments)

// Database Table prefix
$table_prefix = 'wp_';

// Authentication Unique Keys and Salts
// Generate: https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

// Database settings
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// WordPress Memory
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Auto-updates
define('AUTOMATIC_UPDATER_DISABLED', true);

// Absolute path
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Bootstrap WordPress
require_once ABSPATH . 'wp-settings.php';

// Better Stack (Logtail)
define('LOGTAIL_SOURCE_TOKEN', 'qqP84gVb14fpM7mesNM2EYn8');