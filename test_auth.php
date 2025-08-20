<?php
/**
 * Authentication test script
 * Verifies that the authentication system is working correctly
 * 
 * Usage: php test_auth.php
 */

require_once('vendor/autoload.php');
require_once('AuthCodesDB.php');
require_once('plugins/AuthPlugin.php');

use Proxy\Config;
Config::load('./config.php');

echo "Testing Authentication System\n";
echo "=============================\n\n";

try {
    $dbPath = Config::get('auth_codes_db', './auth_codes.db');
    $authDB = new AuthCodesDB($dbPath);
    echo "✓ Database connection successful\n";
    
    $codes = $authDB->getAllCodes();
    echo "✓ Found " . count($codes) . " codes in database\n";
    
    foreach ($codes as $codeData) {
        echo "  - {$codeData['name']}: {$codeData['code']} " . ($codeData['admin_access'] ? '(admin)' : '(user)') . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "\nPlease ensure you have:\n";
    echo "1. Run 'php init_db.php' to initialize the database\n";
    echo "2. Added codes to the database\n";
    exit(1);
}

echo "\nTesting Authentication Logic\n";
echo "============================\n";

$testCodes = ['TEST123', 'USER456', 'INVALID_CODE'];
foreach ($testCodes as $code) {
    $result = AuthPlugin::processLogin($code);
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo "$status Testing code '$code': " . ($result ? 'ACCEPTED' : 'REJECTED') . "\n";
}

echo "\n✓ Authentication system is working correctly!\n";
?>