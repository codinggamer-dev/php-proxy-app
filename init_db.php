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