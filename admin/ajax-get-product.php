<?php
/**
 * AJAX - Get Product Data
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
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {
    // Fetch product data
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Return product data
    echo json_encode($product);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>