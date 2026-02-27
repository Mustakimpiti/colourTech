<?php
/**
 * ColourTech Industries - PDF Gate Handler
 */

session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// --- CAPTCHA validation ---
$userAnswer   = intval($_POST['captcha'] ?? -1);
$correctAnswer = intval(($_SESSION['pdf_captcha_n1'] ?? 0) + ($_SESSION['pdf_captcha_n2'] ?? 0));

// Regenerate CAPTCHA for next attempt regardless of outcome
$_SESSION['pdf_captcha_n1'] = rand(1, 9);
$_SESSION['pdf_captcha_n2'] = rand(1, 9);
$newQuestion = $_SESSION['pdf_captcha_n1'] . ' + ' . $_SESSION['pdf_captcha_n2'] . ' =';

if ($userAnswer !== $correctAnswer) {
    echo json_encode([
        'success'          => false,
        'message'          => 'Incorrect answer. Please try again.',
        'captcha_question' => $newQuestion,
    ]);
    exit;
}

// --- Sanitize inputs ---
$name       = trim(strip_tags($_POST['name']         ?? ''));
$email      = trim(strip_tags($_POST['email']        ?? ''));
$phone      = trim(strip_tags($_POST['phone']        ?? ''));
$company    = trim(strip_tags($_POST['company']      ?? ''));
$productId  = intval($_POST['product_id']            ?? 0);
$productName = trim(strip_tags($_POST['product_name'] ?? ''));
$pdfUrl     = trim($_POST['pdf_url']                 ?? '');
$pdfLabel   = trim(strip_tags($_POST['pdf_label']    ?? ''));

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.', 'captcha_question' => $newQuestion]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.', 'captcha_question' => $newQuestion]);
    exit;
}

// --- Save to contact_inquiries ---
$subject = 'PDF Download: ' . $productName . ' — ' . $pdfLabel;
$message  = "Product: $productName\n";
$message .= "Company: $company\n";
$message .= "Phone: $phone\n";
$message .= "File: $pdfUrl\n";

try {
    $pdo->prepare("
        INSERT INTO contact_inquiries (name, email, phone, subject, message, ip_address, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
    ")->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    echo json_encode([
        'success'          => true,
        'message'          => 'Success',
        'captcha_question' => $newQuestion,
    ]);

} catch (PDOException $e) {
    error_log('PDF gate error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.', 'captcha_question' => $newQuestion]);
}
exit;