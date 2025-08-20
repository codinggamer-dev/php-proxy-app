<?php

use Proxy\Config;
use Proxy\Event\ProxyEvent;
use Proxy\Plugin\AbstractPlugin;

require_once(__DIR__ . '/../AuthCodesDB.php');

class AuthPlugin extends AbstractPlugin
{
    /**
     * Check if user is authenticated before any proxy request
     */
    public function onBeforeRequest(ProxyEvent $event)
    {
        // Only check auth if enabled in config
        if (!Config::get('auth_enable')) {
            return;
        }

        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            // Redirect to login page
            $this->redirectToLogin();
            exit;
        }
    }

    /**
     * Check if current user is authenticated
     */
    private function isAuthenticated()
    {
        // Check if user has valid session
        if (!isset($_SESSION['auth_code']) || !isset($_SESSION['auth_time'])) {
            return false;
        }

        // Check session timeout
        $sessionTimeout = Config::get('auth_session_timeout', 3600);
        if (time() - $_SESSION['auth_time'] > $sessionTimeout) {
            $this->clearAuth();
            return false;
        }

        // Verify the code still exists in the auth file
        $validCodes = $this->getValidCodes();
        if (!in_array($_SESSION['auth_code'], $validCodes)) {
            $this->clearAuth();
            return false;
        }

        // Update session time to extend session
        $_SESSION['auth_time'] = time();
        return true;
    }

    /**
     * Get list of valid authentication codes from database
     */
    private function getValidCodes()
    {
        try {
            $dbPath = Config::get('auth_codes_db', './auth_codes.db');
            $authDB = new AuthCodesDB($dbPath);
            return $authDB->getValidCodes();
        } catch (Exception $e) {
            // Fallback to empty array if database fails
            return array();
        }
    }

    /**
     * Clear authentication session
     */
    private function clearAuth()
    {
        unset($_SESSION['auth_code']);
        unset($_SESSION['auth_time']);
        unset($_SESSION['auth_name']);
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin()
    {
        header("HTTP/1.1 302 Found");
        header("Location: index.php?login=1");
    }

    /**
     * Process login attempt
     */
    public static function processLogin($code)
    {
        try {
            $dbPath = Config::get('auth_codes_db', './auth_codes.db');
            $authDB = new AuthCodesDB($dbPath);
            $codeData = $authDB->getCodeData($code);
            
            if ($codeData) {
                // Start session if not started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['auth_code'] = $code;
                $_SESSION['auth_time'] = time();
                $_SESSION['auth_name'] = $codeData['name'];
                return true;
            }
        } catch (Exception $e) {
            // Database error, deny login
        }
        
        return false;
    }

    /**
     * Static method to get valid codes
     */
    private static function getValidCodesStatic()
    {
        try {
            $dbPath = Config::get('auth_codes_db', './auth_codes.db');
            $authDB = new AuthCodesDB($dbPath);
            return $authDB->getValidCodes();
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Get codes data with names
     */
    private static function getCodesData()
    {
        try {
            $dbPath = Config::get('auth_codes_db', './auth_codes.db');
            $authDB = new AuthCodesDB($dbPath);
            return $authDB->getAllCodes();
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Logout current user
     */
    public static function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['auth_code']);
        unset($_SESSION['auth_time']);
        unset($_SESSION['auth_name']);
        
        session_destroy();
    }
}