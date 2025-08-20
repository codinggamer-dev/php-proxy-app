<?php
/**
 * Database initialization script
 * Creates the SQLite database for auth codes
 */

function initDatabase($dbPath) {
    try {
        // Create SQLite database
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL schema
        $sql = file_get_contents(__DIR__ . '/init_database.sql');
        $pdo->exec($sql);
        
        // Close the PDO connection to ensure file is fully written
        $pdo = null;
        
        // Set proper permissions for web server access
        // 664 allows owner and group to read/write, others to read
        if (file_exists($dbPath)) {
            if (!chmod($dbPath, 0664)) {
                echo "Warning: Could not set database file permissions. You may need to manually set permissions on: $dbPath\n";
            }
        }
        
        // Ensure the directory is writable for SQLite temporary files
        $dbDir = dirname($dbPath);
        if (!is_writable($dbDir)) {
            echo "Warning: Directory $dbDir is not writable. SQLite may not be able to create temporary files.\n";
            echo "Consider setting directory permissions to 775 or ensuring web server has write access.\n";
        }
        
        echo "Database initialized successfully at: $dbPath\n";
        return true;
    } catch (PDOException $e) {
        echo "Database initialization failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Initialize database if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $dbPath = __DIR__ . '/auth_codes.db';
    initDatabase($dbPath);
}
?>