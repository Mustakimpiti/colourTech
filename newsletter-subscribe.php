<?php
/**
 * ColourTech Industries - Newsletter Subscription Handler
 */

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// --- DB Connection ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'colourtech_db');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// --- Sanitize & Validate Email ---
$email = trim(strip_tags($_POST['email'] ?? ''));

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($email) > 150) {
    echo json_encode(['success' => false, 'message' => 'Email address is too long.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// --- Insert or Update ---
try {
    $stmt = $pdo->prepare("SELECT id, status FROM newsletters WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'This email is already subscribed!']);
        } else {
            // Re-subscribe
            $pdo->prepare("UPDATE newsletters SET status = 'active', unsubscribed_at = NULL, ip_address = ?, subscribed_at = NOW() WHERE id = ?")
                ->execute([$ip, $existing['id']]);
            echo json_encode(['success' => true, 'message' => 'Welcome back! You have been re-subscribed successfully.']);
        }
        exit;
    }

    $pdo->prepare("INSERT INTO newsletters (email, status, ip_address, subscribed_at) VALUES (?, 'active', ?, NOW())")
        ->execute([$email, $ip]);

    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed.']);
    } else {
        error_log('Newsletter subscribe error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
    }
}
exit;