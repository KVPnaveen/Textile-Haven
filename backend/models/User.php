<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password)
    {
        global $security;

        try {
            // Validate input
            $validationRules = [
                'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50],
                'email' => ['required' => true, 'email' => true, 'max_length' => 100],
                'password' => ['required' => true, 'min_length' => 8, 'max_length' => 255]
            ];

            $input = ['username' => $username, 'email' => $email, 'password' => $password];
            $errors = $security->validateInput($input, $validationRules);

            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(' ', $errors)];
            }

            // Sanitize input
            $username = $security->sanitizeInput($username);
            $email = $security->sanitizeInput($email, 'email');

            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered.'];
            }

            // Hash password using security module
            $hashedPassword = $security->hashPassword($password);

            // Insert new user
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashedPassword]);

            // Log successful registration
            $security->logSecurityEvent('user_registered', ['email' => $email, 'username' => $username]);

            return ['success' => true, 'message' => 'Registration successful!'];
        } catch (PDOException $e) {
            $security->logSecurityEvent('registration_failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Login user (accepts email or username)
     */
    public function login($identifier, $password)
    {
        global $security;

        try {
            // Check rate limiting
            $identifierKey = $identifier;
            if (!$security->checkRateLimit('login', $identifierKey, 5, 300)) {
                $security->logSecurityEvent('login_rate_limit_exceeded', ['identifier' => $identifier]);
                return ['success' => false, 'message' => 'Too many login attempts. Please try again in 5 minutes.'];
            }

            // Sanitize input
            $identifier = $security->sanitizeInput($identifier);

            // Check if identifier is email or username
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            if ($isEmail) {
                $stmt = $this->pdo->prepare("SELECT id, username, email, password, is_admin FROM users WHERE email = ?");
            } else {
                $stmt = $this->pdo->prepare("SELECT id, username, email, password, is_admin FROM users WHERE username = ?");
            }

            $stmt->execute([$identifier]);
            $user = $stmt->fetch();

            if ($user && $security->verifyPassword($password, $user['password'])) {
                // Check if password hash needs to be updated
                if ($security->passwordNeedsRehash($user['password'])) {
                    $newHash = $security->hashPassword($password);
                    $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newHash, $user['id']]);

                    // Log password rehash
                    $security->logSecurityEvent('password_rehashed', ['user_id' => $user['id']]);
                }

                // Regenerate session ID on successful login
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['login_time'] = time();

                // Log successful login
                $security->logSecurityEvent('user_login_success', [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'is_admin' => $user['is_admin']
                ]);

                return [
                    'success' => true,
                    'message' => 'Login successful!',
                    'user_id' => $user['id'],
                    'is_admin' => $user['is_admin'],
                    'redirect_url' => $user['is_admin'] ? '../../backend/admin/' : '../../frontend/pages/index.php'
                ];
            } else {
                // Log failed login attempt
                $security->logSecurityEvent('user_login_failed', ['identifier' => $identifier]);
                return ['success' => false, 'message' => 'Invalid email/username or password.'];
            }
        } catch (PDOException $e) {
            $security->logSecurityEvent('login_database_error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully!'];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, is_admin, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $username, $email)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $userId]);

            // Update session data
            if ($this->isLoggedIn() && $_SESSION['user_id'] == $userId) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
            }

            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Profile update failed.'];
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        global $security;

        try {
            // Verify current password
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !$security->verifyPassword($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }

            // Hash new password using security module
            $hashedPassword = $security->hashPassword($newPassword);

            // Update password
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            // Log password change
            $security->logSecurityEvent('password_changed', ['user_id' => $userId]);

            return ['success' => true, 'message' => 'Password changed successfully!'];
        } catch (PDOException $e) {
            $security->logSecurityEvent('password_change_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Password change failed.'];
        }
    }
}
