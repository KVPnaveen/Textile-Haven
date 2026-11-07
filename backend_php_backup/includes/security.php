<?php

/**
 * Security Module for Textile Haven
 * Implements comprehensive security measures
 */

class Security
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Security();
        }
        return self::$instance;
    }

    /**
     * Get instance and initialize for existing session
     */
    public static function getInstanceForExistingSession()
    {
        $instance = self::getInstance();
        $instance->initForExistingSession();
        return $instance;
    }

    /**
     * Initialize security settings
     */
    public function init()
    {
        // Set secure headers
        $this->setSecurityHeaders();

        // Configure session security (only if session hasn't started)
        $this->configureSession();

        // Start secure session
        $this->startSecureSession();
    }

    /**
     * Initialize security settings for files that already have session_start()
     */
    public function initForExistingSession()
    {
        // Set secure headers
        $this->setSecurityHeaders();

        // Skip session configuration since session is already active
        // Just ensure session is secure
        $this->ensureSecureSession();
    }

    /**
     * Set security headers
     */
    private function setSecurityHeaders()
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
            "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
            "img-src 'self' data: blob:; " .
            "font-src 'self' https://cdn.tailwindcss.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';";
        header("Content-Security-Policy: " . $csp);
    }

    /**
     * Configure secure session settings
     */
    private function configureSession()
    {
        // Only configure session if it hasn't started yet
        if (session_status() == PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', 3600); // 1 hour
            ini_set('session.cookie_lifetime', 3600); // 1 hour
        }
    }

    /**
     * Start secure session
     */
    private function startSecureSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Ensure session is secure (for existing sessions)
     */
    private function ensureSecureSession()
    {
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Generate CSRF token
     */
    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }

    /**
     * Sanitize input
     */
    public function sanitizeInput($input, $type = 'string')
    {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Validate input
     */
    public function validateInput($input, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? '';

            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required.';
                continue;
            }

            if (!empty($value)) {
                // Length validation
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min_length'] . ' characters.';
                }

                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = ucfirst($field) . ' must be no more than ' . $rule['max_length'] . ' characters.';
                }

                // Email validation
                if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Please enter a valid email address.';
                }

                // Numeric validation
                if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                    $errors[$field] = ucfirst($field) . ' must be a number.';
                }

                // Range validation
                if (isset($rule['min']) && is_numeric($value) && $value < $rule['min']) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min'] . '.';
                }

                if (isset($rule['max']) && is_numeric($value) && $value > $rule['max']) {
                    $errors[$field] = ucfirst($field) . ' must be no more than ' . $rule['max'] . '.';
                }
            }
        }

        return $errors;
    }

    /**
     * Rate limiting for login attempts
     */
    public function checkRateLimit($action, $identifier, $maxAttempts = 5, $timeWindow = 300)
    {
        $key = "rate_limit_{$action}_{$identifier}";

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        }

        $rateLimit = &$_SESSION[$key];

        // Reset if time window has passed
        if (time() - $rateLimit['first_attempt'] > $timeWindow) {
            $rateLimit = ['attempts' => 0, 'first_attempt' => time()];
        }

        // Check if limit exceeded
        if ($rateLimit['attempts'] >= $maxAttempts) {
            return false;
        }

        $rateLimit['attempts']++;
        return true;
    }

    /**
     * Log security events
     */
    public function logSecurityEvent($event, $details = [])
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];

        $logFile = dirname(__DIR__) . '/logs/security.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = 2097152)
    {
        $errors = [];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed: ' . $this->getUploadErrorMessage($file['error']);
            return $errors;
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size.';
        }

        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'File type not allowed.';
            }
        }

        // Check for malicious content
        if (!$this->isFileSafe($file['tmp_name'])) {
            $errors[] = 'File appears to be unsafe.';
            // Log the rejection for debugging
            $this->logSecurityEvent('file_upload_rejected', [
                'reason' => 'file_unsafe',
                'filename' => $file['name'],
                'mime_type' => $mimeType ?? 'unknown'
            ]);
        }

        return $errors;
    }

    /**
     * Check if file is safe
     */
    private function isFileSafe($filePath)
    {
        // Check for executable content first
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $dangerousTypes = [
            'application/x-executable',
            'application/x-dosexec',
            'application/x-msdownload',
            'application/x-msi',
            'application/x-php',
            'text/x-php'
        ];

        if (in_array($mimeType, $dangerousTypes)) {
            return false;
        }

        // For image files, be more permissive - only check for obvious malicious content
        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($mimeType, $imageTypes)) {
            // For common image formats, be very permissive
            // Only check if the file starts with obvious PHP tags
            $content = file_get_contents($filePath, false, null, 0, 100);

            // Only reject if the file starts with PHP tags (very obvious attack)
            if (preg_match('/^<\?php|^<\?=|^<\?/i', $content)) {
                return false;
            }

            // For most legitimate image files, just trust the MIME type check
            return true;
        }

        return true;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }

    /**
     * Generate secure random string
     */
    public function generateRandomString($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash password using PHP's secure password hashing
     * Uses PASSWORD_DEFAULT which automatically selects the best available algorithm
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password using PHP's secure password verification
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password hash needs to be updated (for algorithm upgrades)
     */
    public function passwordNeedsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}

// Initialize security
$security = Security::getInstance();
$security->init();
