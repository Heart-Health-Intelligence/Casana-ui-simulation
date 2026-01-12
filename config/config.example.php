<?php
/**
 * Casana Application Configuration - EXAMPLE
 * 
 * Copy this file to config.php and update the values for your environment.
 * DO NOT commit config.php to version control!
 */

$config = [
    // API Configuration
    'api' => [
        'base_url' => 'https://casana.mcchord.net/api',
        'key' => 'your-api-key-here', // Get this from your Casana API dashboard
        'timeout' => 30,
    ],
    
    // Application Settings
    'app' => [
        'name' => 'Casana Heart Seat',
        'env' => 'development', // 'development', 'staging', 'production'
        'debug' => true, // Set to false in production
    ],
    
    // Data Storage (for demo features like notes/followups)
    'storage' => [
        'data_dir' => __DIR__ . '/../data',
    ],
    
    // Security
    'security' => [
        'require_admin_auth' => false, // Set to true in production
        'admin_password_hash' => null, // Use password_hash() to generate
    ],
];

// Helper function to get config values
function config($key, $default = null) {
    global $config;
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// Ensure data directory exists
if (!is_dir($config['storage']['data_dir'])) {
    mkdir($config['storage']['data_dir'], 0755, true);
}

return $config;
