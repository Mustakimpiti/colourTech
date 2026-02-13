<?php
/**
 * ColourTech Industries - Database Configuration
 * Admin Panel Configuration File
 */

// Prevent direct access
defined('ADMIN_ACCESS') or define('ADMIN_ACCESS', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change this to your database username
define('DB_PASS', '');              // Change this to your database password
define('DB_NAME', 'colourtech_db');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'ColourTech Industries');
define('ADMIN_EMAIL', 'admin@colourtech.com');

// URL Configuration
define('SITE_URL', 'http://localhost/colourtech'); // Change this to your site URL
define('ADMIN_URL', SITE_URL . '/admin');

// Upload Directories (relative to admin folder)
define('UPLOAD_DIR', 'uploads/');
define('CATEGORY_UPLOAD_DIR', UPLOAD_DIR . 'categories/');
define('SUBCATEGORY_UPLOAD_DIR', UPLOAD_DIR . 'sub-categories/');
define('PRODUCT_UPLOAD_DIR', UPLOAD_DIR . 'products/');
define('PDF_UPLOAD_DIR', UPLOAD_DIR . 'pdfs/');
define('PDF_TECHNICAL_DIR', PDF_UPLOAD_DIR . 'technical/');
define('PDF_SAFETY_DIR', PDF_UPLOAD_DIR . 'safety/');

// File Upload Limits
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_PDF_SIZE', 10 * 1024 * 1024);  // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_PDF_TYPES', ['application/pdf']);

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Pagination
define('RECORDS_PER_PAGE', 20);

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Timezone
date_default_timezone_set('Asia/Kolkata'); // India timezone

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Autoload Helper Functions
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Sanitize Input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate Slug from String
 */
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Check if Slug Exists
 */
function slugExists($table, $slug, $excludeId = null) {
    $pdo = getDB();
    
    if ($excludeId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE slug = ?");
        $stmt->execute([$slug]);
    }
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Generate Unique Slug
 */
function generateUniqueSlug($table, $string, $excludeId = null) {
    $slug = generateSlug($string);
    $originalSlug = $slug;
    $counter = 1;
    
    while (slugExists($table, $slug, $excludeId)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Upload Image File
 */
function uploadImage($file, $uploadDir, $prefix = '') {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error.'];
    }
    
    // Validate file type
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit of 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Upload PDF File
 */
function uploadPDF($file, $uploadDir, $prefix = '') {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error.'];
    }
    
    // Validate file type
    if (!in_array($file['type'], ALLOWED_PDF_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PDF files allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_PDF_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit of 10MB.'];
    }
    
    // Generate unique filename
    $filename = $prefix . time() . '_' . uniqid() . '.pdf';
    $filepath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $filesize = round($file['size'] / 1024); // Size in KB
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath, 'filesize' => $filesize];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Delete File
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format Date
 */
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Format Date Time
 */
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Get Time Ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDateTime($datetime);
    }
}

/**
 * Redirect Function
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Get Flash Message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Set Flash Message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

/**
 * Log Activity
 */
function logActivity($adminId, $action, $tableName, $recordId = null, $description = null) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs 
        (admin_id, action, table_name, record_id, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $adminId,
        $action,
        $tableName,
        $recordId,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Check if User is Logged In
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']);
}

/**
 * Require Login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Get Current Admin Info
 */
function getCurrentAdmin() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['admin_id']]);
    
    return $stmt->fetch();
}

/**
 * Truncate Text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get Status Badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'discontinued' => '<span class="badge bg-danger">Discontinued</span>',
        'new' => '<span class="badge bg-primary">New</span>',
        'read' => '<span class="badge bg-info">Read</span>',
        'replied' => '<span class="badge bg-success">Replied</span>',
        'archived' => '<span class="badge bg-secondary">Archived</span>',
        'unsubscribed' => '<span class="badge bg-warning">Unsubscribed</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}