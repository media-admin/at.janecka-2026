<?php
/**
 * Staging Configuration
 */

// Database
define('DB_NAME', 'staging_database');
define('DB_USER', 'staging_user');
define('DB_PASSWORD', 'SECURE_PASSWORD');
define('DB_HOST', 'localhost');

// URLs
define('WP_HOME', 'https://staging.your-domain.com');
define('WP_SITEURL', 'https://staging.your-domain.com');

// Debug (enabled but logged)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Environment
define('WP_ENVIRONMENT_TYPE', 'staging');

// Google Maps API Key
define('GOOGLE_MAPS_API_KEY', 'DEIN_API_KEY_HIER');

// Disable indexing
define('DISALLOW_FILE_EDIT', true);

// Sentry Configuration
define('SENTRY_DSN', 'https://your-sentry-dsn@sentry.io/your-project-id');
define('SENTRY_RELEASE', '1.0.0'); // Update bei jedem Deploy