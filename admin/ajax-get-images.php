<?php
/**
 * AJAX - Get Sub-Category Images
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

// Get sub-category ID
$subCategoryId = isset($_GET['sub_category_id']) ? (int)$_GET['sub_category_id'] : 0;

if (!$subCategoryId) {
    echo json_encode(['error' => 'Invalid sub-category ID']);
    exit;
}

try {
    // Fetch images
    $stmt = $pdo->prepare("
        SELECT * FROM sub_category_images 
        WHERE sub_category_id = ? 
        ORDER BY is_primary DESC, sort_order ASC
    ");
    $stmt->execute([$subCategoryId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return images data
    echo json_encode(['images' => $images]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>