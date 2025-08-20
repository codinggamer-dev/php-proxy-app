<?php

/**
 * Database helper class for auth codes
 * Provides a consistent interface for code management operations
 */
class AuthCodesDB {
    private $pdo;
    
    /**
     * Initialize database if it doesn't exist with proper permissions
     */
    public static function ensureDatabase($dbPath) {
        if (!file_exists($dbPath)) {
            require_once(__DIR__ . '/init_db.php');
            return initDatabase($dbPath);
        }
        return true;
    }
    
    public function __construct($dbPath) {
        try {
            // Auto-initialize database if it doesn't exist
            self::ensureDatabase($dbPath);
            
            // Check if database file exists
            if (!file_exists($dbPath)) {
                throw new Exception("Database file does not exist: $dbPath. Please run 'php init_db.php' to initialize the database.");
            }
            
            // Check if database file is readable
            if (!is_readable($dbPath)) {
                throw new Exception("Database file is not readable: $dbPath. Please check file permissions.");
            }
            
            // Check if database file is writable
            if (!is_writable($dbPath)) {
                throw new Exception("Database file is not writable: $dbPath. Please check file permissions (should be 664 or similar).");
            }
            
            // Check if directory is writable (needed for SQLite temporary files)
            $dbDir = dirname($dbPath);
            if (!is_writable($dbDir)) {
                throw new Exception("Database directory is not writable: $dbDir. SQLite needs write access to create temporary files.");
            }
            
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Check for specific SQLite error 8 (readonly database)
            if (strpos($e->getMessage(), 'attempt to write a readonly database') !== false) {
                throw new Exception("Database is read-only. Please check file permissions on: $dbPath (should be 664) and directory permissions on: " . dirname($dbPath) . " (should be 775)");
            }
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get all codes data
     */
    public function getAllCodes() {
        $stmt = $this->pdo->query("SELECT name, code, admin_access, created_timestamp as timestamp FROM auth_codes ORDER BY created_timestamp DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get valid codes (for authentication)
     */
    public function getValidCodes() {
        $stmt = $this->pdo->query("SELECT code FROM auth_codes");
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }
    
    /**
     * Get code data by code value
     */
    public function getCodeData($code) {
        $stmt = $this->pdo->prepare("SELECT name, code, admin_access, created_timestamp as timestamp FROM auth_codes WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new code
     */
    public function addCode($name, $code, $adminAccess = 0) {
        $stmt = $this->pdo->prepare("INSERT INTO auth_codes (name, code, admin_access, created_timestamp) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $code, $adminAccess, time()]);
    }
    
    /**
     * Delete a code
     */
    public function deleteCode($code) {
        $stmt = $this->pdo->prepare("DELETE FROM auth_codes WHERE code = ?");
        return $stmt->execute([$code]);
    }
    
    /**
     * Update admin access for a code
     */
    public function updateAdminAccess($code, $adminAccess) {
        $stmt = $this->pdo->prepare("UPDATE auth_codes SET admin_access = ? WHERE code = ?");
        return $stmt->execute([$adminAccess, $code]);
    }
    
    /**
     * Check if code exists
     */
    public function codeExists($code) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM auth_codes WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if code has admin access
     */
    public function hasAdminAccess($code) {
        $stmt = $this->pdo->prepare("SELECT admin_access FROM auth_codes WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetchColumn();
        return $result == 1;
    }
}
?>