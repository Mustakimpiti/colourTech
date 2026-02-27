<?php
/**
 * Sample Request Handler — no captcha
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once 'includes/db.php'; // adjust path if needed

// Sanitize inputs
$product_id       = (int)($_POST['product_id']       ?? 0);
$product_name     = trim($_POST['product_name']      ?? '');
$name             = trim($_POST['name']              ?? '');
$email            = trim($_POST['email']             ?? '');
$phone            = trim($_POST['phone']             ?? '');
$company          = trim($_POST['company']           ?? '');
$country          = trim($_POST['country']           ?? '');
$used_application = trim($_POST['used_application']  ?? '');
$message          = trim($_POST['message']           ?? '');

// Validate required fields
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and Email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Build full message
$fullMessage  = "Product: $product_name\n";
$fullMessage .= "Company: $company\n";
$fullMessage .= "Country: $country\n";
$fullMessage .= "Used Application: $used_application\n";
$fullMessage .= "Phone: $phone\n";
if (!empty($message)) {
    $fullMessage .= "\n$message";
}

$subject = "Sample Request: $product_name";

try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_inquiries (name, email, phone, subject, message, ip_address, status)
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");
    $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $fullMessage,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode(['success' => true, 'message' => 'Request submitted successfully!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
?>