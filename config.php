<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'appointment_system';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

require_once 'vendor/autoload.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$client = new Google_Client();
$client->setAuthConfig('client_secret_650394377864-qcknvpqtkrclaqvqn1bmhm8cjfkkn46t.apps.googleusercontent.com.json'); 
$client->setRedirectUri('http://localhost:8000'); 
$client->setAccessType('offline');
$client->setPrompt('consent'); 
$client->addScope(Google_Service_Calendar::CALENDAR);


function getClient() {
    global $client;
    
    error_log("Session data: " . print_r($_SESSION, true));
    
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        try {
            $client->setAccessToken($_SESSION['access_token']);
            
            if ($client->isAccessTokenExpired()) {
                if (isset($_SESSION['refresh_token'])) {
                    $client->fetchAccessTokenWithRefreshToken($_SESSION['refresh_token']);
                    $_SESSION['access_token'] = $client->getAccessToken();
                } else {
                    unset($_SESSION['access_token']);
                }
            }
        } catch (Exception $e) {
            error_log('Token error: ' . $e->getMessage());
            unset($_SESSION['access_token']);
        }
    }
    
    return $client;
}
?>
