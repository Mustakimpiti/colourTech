<?php
/**
 * AJAX - Get Product Images
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get product ID
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$productId) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {
    // Fetch images
    $stmt = $pdo->prepare("
        SELECT * FROM product_images 
        WHERE product_id = ? 
        ORDER BY is_primary DESC, sort_order ASC
    ");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return images data
    echo json_encode(['images' => $images]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>