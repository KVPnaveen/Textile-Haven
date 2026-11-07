<?php
/**
 * Security Configuration
 * Modify these settings for production deployment
 */

return [
    // Session settings
    'session' => [
        'lifetime' => 3600, // 1 hour
        'regeneration_interval' => 300, // 5 minutes
        'secure_cookies' => false, // Set to true in production with HTTPS
        'http_only' => true,
        'same_site' => 'Strict'
    ],
    
    // Rate limiting
    'rate_limiting' => [
        'login_attempts' => 5,
        'login_time_window' => 300, // 5 minutes
        'api_requests' => 100,
        'api_time_window' => 60 // 1 minute
    ],
    
    // File upload settings
    'file_upload' => [
        'max_size' => 2097152, // 2MB
        'allowed_types' => [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif'
        ],
        'upload_dir' => 'assets/images/',
        'secure_filename' => true
    ],
    
    // Password settings
    'password' => [
        'min_length' => 8,
        'require_special_chars' => false,
        'require_numbers' => false,
        'require_uppercase' => false
    ],
    
    // Security headers
    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data: blob:; font-src 'self' https://cdn.tailwindcss.com; connect-src 'self'; frame-ancestors 'none';"
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'log_file' => 'logs/security.log',
        'log_level' => 'info', // debug, info, warning, error
        'max_log_size' => 10485760 // 10MB
    ],
    
    // Database
    'database' => [
        'use_prepared_statements' => true,
        'emulate_prepares' => false,
        'log_queries' => false
    ],
    
    // CSRF protection
    'csrf' => [
        'enabled' => true,
        'token_length' => 32,
        'regenerate_on_login' => true
    ],
    
    // Input validation
    'validation' => [
        'max_string_length' => 1000,
        'max_email_length' => 100,
        'max_username_length' => 50,
        'sanitize_all_inputs' => true
    ]
]; 