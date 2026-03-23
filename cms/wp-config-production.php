<?php
/**
 * Production Configuration
 */

// Database
define('DB_NAME', 'production_database');
define('DB_USER', 'production_user');
define('DB_PASSWORD', 'VERY_SECURE_PASSWORD');
define('DB_HOST', 'localhost');

// URLs
define('WP_HOME', 'https://your-domain.com');
define('WP_SITEURL', 'https://your-domain.com');

// Debug (disabled)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// Environment
define('WP_ENVIRONMENT_TYPE', 'production');

// Google Maps API Key
define('GOOGLE_MAPS_API_KEY', 'DEIN_API_KEY_HIER');

// Security
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('FORCE_SSL_ADMIN', true);

// Sentry Configuration
define('SENTRY_DSN', 'https://your-sentry-dsn@sentry.io/your-project-id');
define('SENTRY_RELEASE', '1.0.0');

