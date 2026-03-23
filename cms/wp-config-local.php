<?php
/**
 * Local Development Configuration
 */

// Database
define('DB_NAME', 'janecka-2026_at');
define('DB_USER', 'media-admin');
define('DB_PASSWORD', 'Tr1-I7ad#1n');
define('DB_HOST', 'localhost');

// Database Table prefix
$table_prefix = 'jl_';

// URLs
define('WP_HOME', 'https://at.janecka-2026.localdev');
define('WP_SITEURL', 'https://at.janecka-2026.localdev/cms');

// Debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);
// define('ACF_DEBUG', true);


// Environment
define('WP_ENVIRONMENT_TYPE', 'local');

// Google Maps API Key
define('GOOGLE_MAPS_API_KEY', 'DEIN_API_KEY_HIER');