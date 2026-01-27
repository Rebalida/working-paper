<?php

require_once __DIR__ . '/models/User.php';

class Auth {
    
    /**
     * Start session if not already started
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Attempt to log in a user
     */
    public static function login($email, $password) {
        self::init();
        
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            return true;
        }
        
        return false;
    }

    /**
     * Log out the current user
     */
    public static function logout() {
        self::init();
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public static function check() {
        self::init();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        self::init();
        return self::check() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Get current user ID
     */
    public static function userId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user data
     */
    public static function user() {
        self::init();
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
        ];
    }

    /**
     * Require authentication (redirect if not logged in)
     */
    public static function require() {
        self::init();
        if (!self::check()) {
            header('Location: /public/login.php');
            exit;
        }
    }

    /**
     * Require admin authentication
     */
    public static function requireAdmin() {
        self::init();
        if (!self::isAdmin()) {
            header('Location: /public/login.php');
            exit;
        }
    }
}