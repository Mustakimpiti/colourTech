<?php
/**
 * PDF Gate Handler
 * Saves the user's details to contact_inquiries, then returns JSON { success: true }.
 * The actual PDF download is triggered client-side after this response.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once 'includes/db.php'; // adjust path if needed — provides $pdo

// Sanitize inputs
$product_id   = (int) ($_POST['product_id']   ?? 0);
$product_name = trim($_POST['product_name']   ?? '');
$name         = trim($_POST['name']           ?? '');
$email        = trim($_POST['email']          ?? '');
$phone        = trim($_POST['phone']          ?? '');
$company      = trim($_POST['company']        ?? '');
$pdf_label    = trim($_POST['pdf_label']      ?? 'PDF');
$pdf_url      = trim($_POST['pdf_url']        ?? '');

// Validate required fields
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your name.']);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Build subject and message
$subject = 'PDF Download: ' . $pdf_label . ' — ' . $product_name;

$message  = "PDF Downloaded: $pdf_label\n";
$message .= "Product: $product_name\n";
$message .= "Company: $company\n";
$message .= "Phone: $phone\n";
$message .= "File: $pdf_url\n";

// Save to contact_inquiries (same table your admin panel reads)
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
        $message,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Still let the user download even if DB fails
    echo json_encode(['success' => true]);
}
?>