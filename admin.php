<?php

require("vendor/autoload.php");
require_once("AuthCodesDB.php");

use Proxy\Config;
use Proxy\Proxy;

// Load config
Config::load('./config.php');
Config::load('./custom_config.php');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['auth_code'])) {
    header("HTTP/1.1 302 Found");
    header("Location: index.php?login=1");
    exit;
}

// Initialize database
$dbPath = Config::get('auth_codes_db', './auth_codes.db');
try {
    $authDB = new AuthCodesDB($dbPath);
    
    // Check if current user has admin access
    if (!$authDB->hasAdminAccess($_SESSION['auth_code'])) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access Denied: You don't have admin privileges.";
        exit;
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!empty($_POST['code_name']) && !empty($_POST['code_value'])) {
                    $name = trim($_POST['code_name']);
                    $code = trim($_POST['code_value']);
                    $adminAccess = isset($_POST['admin_access']) ? 1 : 0;
                    
                    // Validate code doesn't already exist
                    if (!$authDB->codeExists($code)) {
                        if ($authDB->addCode($name, $code, $adminAccess)) {
                            $message = "Code '{$name}' added successfully!";
                        } else {
                            $error = "Failed to add code!";
                        }
                    } else {
                        $error = "Code already exists!";
                    }
                } else {
                    $error = "Please fill in both name and code fields.";
                }
                break;
                
            case 'delete':
                if (!empty($_POST['delete_code'])) {
                    $codeToDelete = trim($_POST['delete_code']);
                    
                    if ($authDB->deleteCode($codeToDelete)) {
                        $message = "Code deleted successfully!";
                    } else {
                        $error = "Code not found!";
                    }
                }
                break;
                
            case 'toggle_admin':
                if (!empty($_POST['toggle_code'])) {
                    $code = trim($_POST['toggle_code']);
                    $currentAccess = $authDB->hasAdminAccess($code) ? 0 : 1;
                    
                    if ($authDB->updateAdminAccess($code, $currentAccess)) {
                        $message = "Admin access updated successfully!";
                    } else {
                        $error = "Failed to update admin access!";
                    }
                }
                break;
        }
    }
}

$codes = $authDB->getAllCodes();

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP-Proxy Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input, button { padding: 5px; margin: 2px; }
        .form-group { margin: 10px 0; }
        .btn { padding: 8px 15px; margin: 5px; text-decoration: none; border: none; border-radius: 3px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 PHP-Proxy Admin Panel</h1>
        
        <p><strong>Authenticated as:</strong> <?php echo htmlspecialchars($_SESSION['auth_name']); ?></p>
        
        <nav>
            <a href="index.php" class="btn btn-secondary">← Back to Proxy</a>
            <a href="index.php?logout=1" class="btn btn-danger">Logout</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <h2>Add New Login Code</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Code Name:</label>
                <input type="text" name="code_name" placeholder="e.g., user1, admin" required>
            </div>
            <div class="form-group">
                <label>Code Value:</label>
                <input type="text" name="code_value" placeholder="e.g., ABC123" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="admin_access" value="1">
                    Grant admin panel access
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Add Code</button>
        </form>
        
        <h2>Existing Login Codes</h2>
        <?php if (count($codes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Admin Access</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codes as $codeData): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($codeData['name']); ?></td>
                            <td><code><?php echo htmlspecialchars($codeData['code']); ?></code></td>
                            <td>
                                <span style="color: <?php echo $codeData['admin_access'] ? 'green' : 'red'; ?>">
                                    <?php echo $codeData['admin_access'] ? '✓ Admin' : '✗ User'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', $codeData['timestamp']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="toggle_code" value="<?php echo htmlspecialchars($codeData['code']); ?>">
                                    <button type="submit" class="btn btn-secondary">
                                        <?php echo $codeData['admin_access'] ? 'Remove Admin' : 'Grant Admin'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="delete_code" value="<?php echo htmlspecialchars($codeData['code']); ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this code? Users with this code will be immediately logged out.')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No codes found. Add some codes above.</p>
        <?php endif; ?>
        
        <h2>System Information</h2>
        <ul>
            <li><strong>Total Active Codes:</strong> <?php echo count($codes); ?></li>
            <li><strong>Auth Database:</strong> <?php echo htmlspecialchars($dbPath); ?></li>
            <li><strong>Session Timeout:</strong> <?php echo Config::get('auth_session_timeout', 3600); ?> seconds</li>
            <li><strong>PHP-Proxy Version:</strong> <?php echo Proxy::VERSION; ?></li>
        </ul>
        
        <h2>Important Notes</h2>
        <ul>
            <li>When you delete a code, users with that code will be automatically logged out on their next request.</li>
            <li>Login codes are case-sensitive.</li>
            <li>The auth_codes.txt file should not be publicly accessible.</li>
            <li>Consider using strong, unique codes for better security.</li>
        </ul>
    </div>
</body>
</html>