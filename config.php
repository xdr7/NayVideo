<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'naysvideo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL
define('BASE_URL', 'http://localhost/naysvideo/');

// Create Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Upload function
function uploadFile($file, $type)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = [
        'video' => ['mp4', 'mov', 'avi'],
        'image' => ['jpg', 'jpeg', 'png']
    ];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed[$type])) return null;

    $dir = $type === 'video' ? 'uploads/' : 'thumbnails/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = uniqid() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    return $filename;
}

?>
