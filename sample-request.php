<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $productName = trim($_POST['product_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email)) {
        $subject = "Sample Request: " . $productName;
        $fullMessage = "Product: $productName\nCompany: $company\nPhone: $phone\n\n$message";

        $stmt = $pdo->prepare("
            INSERT INTO contact_inquiries (name, email, phone, subject, message, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $subject, $fullMessage, $_SERVER['REMOTE_ADDR']]);
    }
}

// Redirect back to the product page
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referer);
exit;