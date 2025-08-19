<?php

define('PROXY_START', microtime(true));

require("vendor/autoload.php");

use Proxy\Config;
use Proxy\Http\Request;
use Proxy\Proxy;

if (!function_exists('curl_version')) {
    die("cURL extension is not loaded!");
}

// load config...
Config::load('./config.php');

// custom config file to be written to by a bash script or something
Config::load('./custom_config.php');

if (!Config::get('app_key')) {
    die("app_key inside config.php cannot be empty!");
}

if (!Config::get('expose_php')) {
    header_remove('X-Powered-By');
}

// start the session
if (Config::get('session_enable')) {
    session_start();
}

// how are our URLs be generated from this point? this must be set here so the proxify_url function below can make use of it
if (Config::get('url_mode') == 2) {
    Config::set('encryption_key', md5(Config::get('app_key') . $_SERVER['REMOTE_ADDR']));
} elseif (Config::get('url_mode') == 3) {
    Config::set('encryption_key', md5(Config::get('app_key') . session_id()));
}

if (Config::get('session_enable')) {
    // very important!!! otherwise requests are queued while waiting for session file to be unlocked
    session_write_close();
}

// Handle authentication if enabled
if (Config::get('auth_enable')) {
    // Start session for authentication
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Handle login form submission
    if (isset($_POST['login_code'])) {
        require_once('./plugins/AuthPlugin.php');
        $loginCode = trim($_POST['login_code']);
        
        if (AuthPlugin::processLogin($loginCode)) {
            // Login successful, redirect to homepage
            header("HTTP/1.1 302 Found");
            header("Location: index.php");
            exit;
        } else {
            // Login failed
            $loginError = "Invalid login code. Please try again.";
        }
    }
    
    // Handle logout request
    if (isset($_GET['logout'])) {
        require_once('./plugins/AuthPlugin.php');
        AuthPlugin::logout();
        header("HTTP/1.1 302 Found");
        header("Location: index.php?login=1");
        exit;
    }
    
    // Handle admin panel request
    if (isset($_GET['admin']) && isUserAuthenticated()) {
        require_once('./admin.php');
        exit;
    }
    
    // Check if showing login page
    if (isset($_GET['login']) || !isUserAuthenticated()) {
        echo render_template("./templates/login.php", array(
            'version' => Proxy::VERSION,
            'error_msg' => isset($loginError) ? $loginError : null
        ));
        exit;
    }
    
    // Close session to avoid blocking
    session_write_close();
}

// Helper function to check authentication
function isUserAuthenticated() {
    if (!isset($_SESSION['auth_code']) || !isset($_SESSION['auth_time'])) {
        return false;
    }
    
    // Check session timeout
    $sessionTimeout = Config::get('auth_session_timeout', 3600);
    if (time() - $_SESSION['auth_time'] > $sessionTimeout) {
        return false;
    }
    
    // Verify the code still exists
    $codesFile = Config::get('auth_codes_file', './auth_codes.txt');
    $validCodes = array();
    
    if (file_exists($codesFile)) {
        $lines = file($codesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') {
                continue;
            }
            $parts = explode(':', $line);
            if (count($parts) >= 2) {
                $validCodes[] = $parts[1];
            }
        }
    }
    
    return in_array($_SESSION['auth_code'], $validCodes);
}

// form submit in progress...
if (isset($_POST['url'])) {

    $url = $_POST['url'];
    $url = add_http($url);

    header("HTTP/1.1 302 Found");
    header('Location: ' . proxify_url($url));
    exit;

} elseif (!isset($_GET['q'])) {

    // must be at homepage - should we redirect somewhere else?
    if (Config::get('index_redirect')) {

        // redirect to...
        header("HTTP/1.1 302 Found");
        header("Location: " . Config::get('index_redirect'));

    } else {
        // Start session to check auth status for template
        if (Config::get('auth_enable') && session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        echo render_template("./templates/main.php", array('version' => Proxy::VERSION));
    }

    exit;
}

// decode q parameter to get the real URL
$url = url_decrypt($_GET['q']);

$proxy = new Proxy();

// load plugins
foreach (Config::get('plugins', array()) as $plugin) {

    $plugin_class = $plugin . 'Plugin';

    if (file_exists('./plugins/' . $plugin_class . '.php')) {

        // use user plugin from /plugins/
        require_once('./plugins/' . $plugin_class . '.php');

    } elseif (class_exists('\\Proxy\\Plugin\\' . $plugin_class)) {

        // does the native plugin from php-proxy package with such name exist?
        $plugin_class = '\\Proxy\\Plugin\\' . $plugin_class;
    }

    // otherwise plugin_class better be loaded already through composer.json and match namespace exactly \\Vendor\\Plugin\\SuperPlugin
    // $proxy->getEventDispatcher()->addSubscriber(new $plugin_class());

    $proxy->addSubscriber(new $plugin_class());
}

try {

    // request sent to index.php
    $request = Request::createFromGlobals();

    // remove all GET parameters such as ?q=
    $request->get->clear();

    // forward it to some other URL
    $response = $proxy->forward($request, $url);

    // if that was a streaming response, then everything was already sent and script will be killed before it even reaches this line
    $response->send();

} catch (Exception $ex) {

    // if the site is on server2.proxy.com then you may wish to redirect it back to proxy.com
    if (Config::get("error_redirect")) {

        $url = render_string(Config::get("error_redirect"), array(
            'error_msg' => rawurlencode($ex->getMessage())
        ));

        // Cannot modify header information - headers already sent
        header("HTTP/1.1 302 Found");
        header("Location: {$url}");

    } else {

        echo render_template("./templates/main.php", array(
            'url' => $url,
            'error_msg' => $ex->getMessage(),
            'version' => Proxy::VERSION
        ));

    }
}
