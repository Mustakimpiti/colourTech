<?php
/**
 * ColourTech Industries - Products Management
 * WITH MULTIPLE IMAGE UPLOAD SUPPORT
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle = 'Products Management';
$breadcrumbs = ['Products' => ''];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add/Edit Product
    if (isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('products.php');
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $sub_category_id = (int)($_POST['sub_category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $product_code = sanitize($_POST['product_code'] ?? '');
        $color_name = sanitize($_POST['color_name'] ?? '');
        $color_code = sanitize($_POST['color_code'] ?? '');
        $color_index = sanitize($_POST['color_index'] ?? '');
        $short_description = sanitize($_POST['short_description'] ?? '');
        $full_description = $_POST['full_description'] ?? ''; // Allow HTML
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $meta_title = sanitize($_POST['meta_title'] ?? '');
        $meta_description = sanitize($_POST['meta_description'] ?? '');
        $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
        
        // Handle technical specs (convert array to JSON)
        $technical_specs = [];
        if (isset($_POST['spec_key']) && is_array($_POST['spec_key'])) {
            foreach ($_POST['spec_key'] as $index => $key) {
                $key = trim($key);
                $value = trim($_POST['spec_value'][$index] ?? '');
                if (!empty($key) && !empty($value)) {
                    $technical_specs[$key] = $value;
                }
            }
        }
        $technical_specs_json = json_encode($technical_specs);
        
        // Handle applications (convert array to JSON)
        $applications = [];
        if (isset($_POST['applications']) && is_array($_POST['applications'])) {
            foreach ($_POST['applications'] as $application) {
                $application = trim($application);
                if (!empty($application)) {
                    $applications[] = $application;
                }
            }
        }
        $applications_json = json_encode($applications);
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }
        
        if (empty($sub_category_id)) {
            $errors[] = 'Please select a sub-category.';
        }
        
        if (empty($slug)) {
            $slug = generateUniqueSlug('products', $name, $id);
        } else {
            if (slugExists('products', $slug, $id)) {
                $errors[] = 'Slug already exists. Please use a different slug.';
            }
        }
        
        // Handle main image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image'], PRODUCT_UPLOAD_DIR, 'prod_');
            
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['filename'];
                
                // Delete old image if editing
                if ($id && !empty($_POST['old_image'])) {
                    deleteFile(PRODUCT_UPLOAD_DIR . $_POST['old_image']);
                }
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            // Keep old image if editing
            if ($id && !empty($_POST['old_image'])) {
                $imagePath = $_POST['old_image'];
            }
        }
        
        // Handle PDF file 1 upload
        $pdf_file_1 = '';
        $pdf_file_1_label = sanitize($_POST['pdf_file_1_label'] ?? 'Technical Datasheet');
        $pdf_file_1_size = null;
        
        if (isset($_FILES['pdf_file_1']) && $_FILES['pdf_file_1']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadPDF($_FILES['pdf_file_1'], PDF_UPLOAD_DIR, 'pdf_');
            
            if ($uploadResult['success']) {
                $pdf_file_1 = $uploadResult['filename'];
                $pdf_file_1_size = $uploadResult['size'];
                
                // Delete old PDF if editing
                if ($id && !empty($_POST['old_pdf_1'])) {
                    deleteFile(PDF_UPLOAD_DIR . $_POST['old_pdf_1']);
                }
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            // Keep old PDF if editing
            if ($id && !empty($_POST['old_pdf_1'])) {
                $pdf_file_1 = $_POST['old_pdf_1'];
                $pdf_file_1_size = (int)($_POST['old_pdf_1_size'] ?? 0);
            }
        }
        
        // Handle PDF file 2 upload
        $pdf_file_2 = '';
        $pdf_file_2_label = sanitize($_POST['pdf_file_2_label'] ?? 'Safety Datasheet');
        $pdf_file_2_size = null;
        
        if (isset($_FILES['pdf_file_2']) && $_FILES['pdf_file_2']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadPDF($_FILES['pdf_file_2'], PDF_UPLOAD_DIR, 'pdf_');
            
            if ($uploadResult['success']) {
                $pdf_file_2 = $uploadResult['filename'];
                $pdf_file_2_size = $uploadResult['size'];
                
                // Delete old PDF if editing
                if ($id && !empty($_POST['old_pdf_2'])) {
                    deleteFile(PDF_UPLOAD_DIR . $_POST['old_pdf_2']);
                }
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            // Keep old PDF if editing
            if ($id && !empty($_POST['old_pdf_2'])) {
                $pdf_file_2 = $_POST['old_pdf_2'];
                $pdf_file_2_size = (int)($_POST['old_pdf_2_size'] ?? 0);
            }
        }
        
        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    // Insert new product
                    $stmt = $pdo->prepare("
                        INSERT INTO products 
                        (sub_category_id, name, slug, product_code, color_name, color_code, color_index,
                         short_description, full_description, technical_specs, applications, image,
                         pdf_file_1, pdf_file_1_label, pdf_file_1_size, pdf_file_2, pdf_file_2_label, pdf_file_2_size,
                         sort_order, meta_title, meta_description, meta_keywords, status, featured) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $sub_category_id, $name, $slug, $product_code, $color_name, $color_code, $color_index,
                        $short_description, $full_description, $technical_specs_json, $applications_json, $imagePath,
                        $pdf_file_1, $pdf_file_1_label, $pdf_file_1_size, $pdf_file_2, $pdf_file_2_label, $pdf_file_2_size,
                        $sort_order, $meta_title, $meta_description, $meta_keywords, $status, $featured
                    ]);
                    
                    $productId = $pdo->lastInsertId();
                    
                    logActivity($_SESSION['admin_id'], 'created', 'products', $productId, "Created product: $name");
                    setFlashMessage('success', 'Product added successfully!');
                    
                } else {
                    // Update existing product
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET sub_category_id = ?, name = ?, slug = ?, product_code = ?, color_name = ?, 
                            color_code = ?, color_index = ?, short_description = ?, full_description = ?,
                            technical_specs = ?, applications = ?, image = ?,
                            pdf_file_1 = ?, pdf_file_1_label = ?, pdf_file_1_size = ?,
                            pdf_file_2 = ?, pdf_file_2_label = ?, pdf_file_2_size = ?,
                            sort_order = ?, meta_title = ?, meta_description = ?, meta_keywords = ?,
                            status = ?, featured = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $sub_category_id, $name, $slug, $product_code, $color_name, $color_code, $color_index,
                        $short_description, $full_description, $technical_specs_json, $applications_json, $imagePath,
                        $pdf_file_1, $pdf_file_1_label, $pdf_file_1_size, $pdf_file_2, $pdf_file_2_label, $pdf_file_2_size,
                        $sort_order, $meta_title, $meta_description, $meta_keywords, $status, $featured, $id
                    ]);
                    
                    logActivity($_SESSION['admin_id'], 'updated', 'products', $id, "Updated product: $name");
                    setFlashMessage('success', 'Product updated successfully!');
                }
                
                redirect('products.php');
                
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }
    
    // Delete Product
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('products.php');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id) {
            // Get product info before deleting
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Delete main image file
                if (!empty($product['image'])) {
                    deleteFile(PRODUCT_UPLOAD_DIR . $product['image']);
                }
                
                // Delete PDF files
                if (!empty($product['pdf_file_1'])) {
                    deleteFile(PDF_UPLOAD_DIR . $product['pdf_file_1']);
                }
                if (!empty($product['pdf_file_2'])) {
                    deleteFile(PDF_UPLOAD_DIR . $product['pdf_file_2']);
                }
                
                // Delete all additional images
                $images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
                $images->execute([$id]);
                
                foreach ($images->fetchAll() as $image) {
                    deleteFile(PRODUCT_UPLOAD_DIR . $image['image_path']);
                }
                
                // Delete product (cascades to images)
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                
                logActivity($_SESSION['admin_id'], 'deleted', 'products', $id, "Deleted product: " . $product['name']);
                setFlashMessage('success', 'Product deleted successfully!');
            }
        }
        
        redirect('products.php');
    }
    
    // Upload Multiple Images
    if (isset($_POST['action']) && $_POST['action'] === 'upload_images') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        
        if ($product_id && isset($_FILES['images'])) {
            $uploadedCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Get current image count for sort order
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $count = $stmt->fetchColumn();
            
            // Check if first image will be primary
            $isFirstImage = ($count == 0);
            
            // Loop through each uploaded file
            $fileCount = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                // Check if file was uploaded
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    // Create a single file array for the uploadImage function
                    $singleFile = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];
                    
                    $uploadResult = uploadImage($singleFile, PRODUCT_UPLOAD_DIR, 'prod_gallery_');
                    
                    if ($uploadResult['success']) {
                        // Insert image
                        $stmt = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_path, sort_order, is_primary) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $product_id,
                            $uploadResult['filename'],
                            $count + $i + 1,
                            ($isFirstImage && $i == 0) ? 1 : 0 // Only first image of first upload is primary
                        ]);
                        
                        $uploadedCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $_FILES['images']['name'][$i] . ': ' . $uploadResult['message'];
                    }
                } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errorCount++;
                    $errors[] = $_FILES['images']['name'][$i] . ': Upload error';
                }
            }
            
            // Build success/error message
            $messages = [];
            if ($uploadedCount > 0) {
                $messages[] = "$uploadedCount image(s) uploaded successfully!";
                logActivity($_SESSION['admin_id'], 'created', 'product_images', $product_id, "Uploaded $uploadedCount images for product ID: $product_id");
            }
            if ($errorCount > 0) {
                $messages[] = "$errorCount image(s) failed to upload.";
                if (!empty($errors)) {
                    $messages[] = implode('<br>', $errors);
                }
            }
            
            $messageType = ($errorCount > 0 && $uploadedCount == 0) ? 'error' : (($errorCount > 0) ? 'warning' : 'success');
            setFlashMessage($messageType, implode('<br>', $messages));
        }
        
        redirect('products.php');
    }
    
    // Delete Image
    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        
        if ($image_id) {
            $stmt = $pdo->prepare("SELECT * FROM product_images WHERE id = ?");
            $stmt->execute([$image_id]);
            $image = $stmt->fetch();
            
            if ($image) {
                deleteFile(PRODUCT_UPLOAD_DIR . $image['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                $stmt->execute([$image_id]);
                
                logActivity($_SESSION['admin_id'], 'deleted', 'product_images', $image_id, "Deleted image: " . $image['image_path']);
                setFlashMessage('success', 'Image deleted successfully!');
            }
        }
        
        redirect('products.php');
    }
    
    // Set Primary Image
    if (isset($_POST['action']) && $_POST['action'] === 'set_primary') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        
        if ($image_id && $product_id) {
            // Reset all to non-primary
            $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Set new primary
            $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
            $stmt->execute([$image_id]);
            
            setFlashMessage('success', 'Primary image updated!');
        }
        
        redirect('products.php');
    }
}

// Get filter parameters
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filterSubCategory = isset($_GET['sub_category']) ? (int)$_GET['sub_category'] : 0;
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query with filters
$query = "
    SELECT p.*, 
           sc.name as sub_category_name,
           sc.slug as sub_category_slug,
           c.id as category_id,
           c.name as category_name
    FROM products p
    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
    LEFT JOIN categories c ON sc.category_id = c.id
    WHERE 1=1
";

$params = [];

if ($filterCategory > 0) {
    $query .= " AND c.id = ?";
    $params[] = $filterCategory;
}

if ($filterSubCategory > 0) {
    $query .= " AND sc.id = ?";
    $params[] = $filterSubCategory;
}

if (!empty($filterStatus)) {
    $query .= " AND p.status = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY c.sort_order ASC, sc.sort_order ASC, p.sort_order ASC, p.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all active categories for dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")->fetchAll();

// Get all active sub-categories for dropdown
$subCategories = $pdo->query("
    SELECT sc.*, c.name as category_name 
    FROM sub_categories sc
    LEFT JOIN categories c ON sc.category_id = c.id
    WHERE sc.status = 'active' 
    ORDER BY c.sort_order ASC, sc.sort_order ASC, sc.name ASC
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Products Management</h4>
            <p class="mb-0">Manage your product catalog</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add New Product
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" name="category" id="filterCategory" onchange="updateSubCategories(this.value)">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sub-Category</label>
                <select class="form-select" name="sub_category" id="filterSubCategory">
                    <option value="">All Sub-Categories</option>
                    <?php foreach ($subCategories as $subCat): ?>
                        <option value="<?php echo $subCat['id']; ?>" 
                                data-category="<?php echo $subCat['category_id']; ?>"
                                <?php echo $filterSubCategory == $subCat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subCat['category_name'] . ' > ' . $subCat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="discontinued" <?php echo $filterStatus === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th>Code</th>
                        <th>Color</th>
                        <th>Gallery</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo PRODUCT_UPLOAD_DIR . $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['featured']): ?>
                                    <span class="badge bg-warning text-dark ms-1">Featured</span>
                                <?php endif; ?>
                                <?php if ($product['color_name']): ?>
                                    <br><small class="text-muted">
                                        <?php if ($product['color_code']): ?>
                                            <span class="d-inline-block" style="width: 15px; height: 15px; background: <?php echo $product['color_code']; ?>; border: 1px solid #ddd; border-radius: 3px; vertical-align: middle;"></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($product['color_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['sub_category_name'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <?php if ($product['product_code']): ?>
                                    <code><?php echo htmlspecialchars($product['product_code']); ?></code>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['color_index']): ?>
                                    <small><?php echo htmlspecialchars($product['color_index']); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $imageStmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
                                $imageStmt->execute([$product['id']]);
                                $imageCount = $imageStmt->fetchColumn();
                                ?>
                                <span class="badge bg-info"><?php echo $imageCount; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?php echo number_format($product['views']); ?></span>
                            </td>
                            <td><?php echo getStatusBadge($product['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="manageImages(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" title="Manage Images">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="editProduct(<?php echo $product['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="old_image" id="oldImage">
                <input type="hidden" name="old_pdf_1" id="oldPdf1">
                <input type="hidden" name="old_pdf_1_size" id="oldPdf1Size">
                <input type="hidden" name="old_pdf_2" id="oldPdf2">
                <input type="hidden" name="old_pdf_2_size" id="oldPdf2Size">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist" style="flex-wrap: nowrap; overflow-x: auto;">
                        <li class="nav-item" style="white-space: nowrap;">
                            <a class="nav-link active" data-bs-toggle="tab" href="#basicInfo">Basic Info</a>
                        </li>
                        <li class="nav-item" style="white-space: nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#colorInfo">Color</a>
                        </li>
                        <li class="nav-item" style="white-space: nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#technicalInfo">Tech Specs</a>
                        </li>
                        <li class="nav-item" style="white-space: nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#mediaInfo">Media</a>
                        </li>
                        <li class="nav-item" style="white-space: nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#seoInfo">SEO</a>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Basic Information Tab -->
                        <div class="tab-pane fade show active" id="basicInfo">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sub_category_id" class="form-label">Sub-Category *</label>
                                    <select class="form-select" id="sub_category_id" name="sub_category_id" required>
                                        <option value="">-- Select Sub-Category --</option>
                                        <?php 
                                        $currentCat = '';
                                        foreach ($subCategories as $subCat): 
                                            if ($currentCat != $subCat['category_name']) {
                                                if ($currentCat != '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($subCat['category_name']) . '">';
                                                $currentCat = $subCat['category_name'];
                                            }
                                        ?>
                                            <option value="<?php echo $subCat['id']; ?>">
                                                <?php echo htmlspecialchars($subCat['name']); ?>
                                            </option>
                                        <?php 
                                        endforeach; 
                                        if ($currentCat != '') echo '</optgroup>';
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required data-slug-source="true" data-slug-target="slug">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label">Slug *</label>
                                    <input type="text" class="form-control" id="slug" name="slug" required>
                                    <small class="text-muted">URL-friendly version (auto-generated)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="product_code" class="form-label">Product Code</label>
                                    <input type="text" class="form-control" id="product_code" name="product_code" placeholder="e.g., PY-1, PY-12">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="short_description" class="form-label">Short Description</label>
                                    <textarea class="form-control" id="short_description" name="short_description" rows="2" placeholder="Brief description for listing pages"></textarea>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="full_description" class="form-label">Full Description</label>
                                    <textarea class="form-control" id="full_description" name="full_description" rows="5" placeholder="Detailed description for product pages"></textarea>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="discontinued">Discontinued</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1">
                                        <label class="form-check-label" for="featured">
                                            Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Color Details Tab -->
                        <div class="tab-pane fade" id="colorInfo">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="color_name" class="form-label">Color Name</label>
                                    <input type="text" class="form-control" id="color_name" name="color_name" placeholder="e.g., Lemon Yellow, Bright Red">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="color_code" class="form-label">Color Code (HEX)</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="color_code" name="color_code" value="#000000">
                                        <input type="text" class="form-control" id="color_code_text" placeholder="#000000" maxlength="7">
                                    </div>
                                    <small class="text-muted">HEX color code for display</small>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="color_index" class="form-label">Color Index</label>
                                    <input type="text" class="form-control" id="color_index" name="color_index" placeholder="e.g., C.I. Pigment Yellow 1">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Technical Specs Tab -->
                        <div class="tab-pane fade" id="technicalInfo">
                            <div class="mb-3">
                                <label class="form-label">Technical Specifications</label>
                                <div id="specsContainer">
                                    <div class="row mb-2">
                                        <div class="col-5">
                                            <input type="text" class="form-control" name="spec_key[]" placeholder="Specification Name">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control" name="spec_value[]" placeholder="Value">
                                        </div>
                                        <div class="col-1">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeSpec(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSpec()">
                                    <i class="fas fa-plus me-1"></i>Add Specification
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Applications</label>
                                <div id="applicationsContainer">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="applications[]" placeholder="Application area">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeApplication(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addApplication()">
                                    <i class="fas fa-plus me-1"></i>Add Application
                                </button>
                            </div>
                        </div>
                        
                        <!-- Media & Files Tab -->
                        <div class="tab-pane fade" id="mediaInfo">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="image" class="form-label">Main Product Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" data-preview="imagePreview">
                                    <small class="text-muted">Max size: 5MB (JPG, PNG, GIF, WebP)</small>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="" alt="Preview" style="max-width: 200px; max-height: 200px; display: none; border-radius: 5px;">
                                    </div>
                                </div>
                                
                                <div class="col-12"><hr></div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="pdf_file_1_label" class="form-label">PDF 1 Label</label>
                                    <input type="text" class="form-control" id="pdf_file_1_label" name="pdf_file_1_label" value="Technical Datasheet">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="pdf_file_1" class="form-label">PDF File 1</label>
                                    <input type="file" class="form-control" id="pdf_file_1" name="pdf_file_1" accept=".pdf">
                                    <small class="text-muted">Max size: 10MB (PDF only)</small>
                                    <div id="pdf1Current" class="mt-2" style="display: none;">
                                        <small class="text-success"><i class="fas fa-file-pdf"></i> Current file uploaded</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="pdf_file_2_label" class="form-label">PDF 2 Label</label>
                                    <input type="text" class="form-control" id="pdf_file_2_label" name="pdf_file_2_label" value="Safety Datasheet">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="pdf_file_2" class="form-label">PDF File 2</label>
                                    <input type="file" class="form-control" id="pdf_file_2" name="pdf_file_2" accept=".pdf">
                                    <small class="text-muted">Max size: 10MB (PDF only)</small>
                                    <div id="pdf2Current" class="mt-2" style="display: none;">
                                        <small class="text-success"><i class="fas fa-file-pdf"></i> Current file uploaded</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Tab -->
                        <div class="tab-pane fade" id="seoInfo">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords">
                                    <small class="text-muted">Comma-separated keywords</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Images Modal -->
<div class="modal fade" id="imagesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagesModalTitle">Manage Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Upload Form - UPDATED FOR MULTIPLE FILES -->
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="upload_images">
                    <input type="hidden" name="product_id" id="uploadProductId">
                    
                    <div class="input-group">
                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Images
                        </button>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> You can select multiple images at once (Hold Ctrl/Cmd). Max size: 5MB per image (JPG, PNG, GIF, WebP)
                    </small>
                </form>
                
                <!-- Images Grid -->
                <div id="imagesGrid" class="row">
                    <!-- Images will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Forms (hidden) -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<form method="POST" id="deleteImageForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete_image">
    <input type="hidden" name="image_id" id="deleteImageId">
</form>

<form method="POST" id="setPrimaryForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="set_primary">
    <input type="hidden" name="image_id" id="primaryImageId">
    <input type="hidden" name="product_id" id="primaryProductId">
</form>

<?php
$extraJS = <<<'EOD'
<script>
// Filter subcategories based on selected category
function updateSubCategories(categoryId) {
    const subCategorySelect = document.getElementById('filterSubCategory');
    const options = subCategorySelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block';
        } else {
            const optionCategory = option.getAttribute('data-category');
            if (!categoryId || optionCategory == categoryId) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Reset selection if current selection is hidden
    const currentOption = subCategorySelect.options[subCategorySelect.selectedIndex];
    if (currentOption && currentOption.style.display === 'none') {
        subCategorySelect.value = '';
    }
}

// Color picker sync
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('color_code');
    const colorText = document.getElementById('color_code_text');
    
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
        });
        
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorPicker.value = this.value;
            }
        });
    }
    
    // Image preview functionality
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imagePreview.src = event.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
});

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('productId').value = '';
    document.getElementById('oldImage').value = '';
    document.getElementById('oldPdf1').value = '';
    document.getElementById('oldPdf2').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('pdf1Current').style.display = 'none';
    document.getElementById('pdf2Current').style.display = 'none';
    
    // Reset to first tab
    const firstTab = document.querySelector('[data-bs-toggle="tab"]');
    if (firstTab) {
        new bootstrap.Tab(firstTab).show();
    }
    
    // Reset specs to one empty field
    document.getElementById('specsContainer').innerHTML = `
        <div class="row mb-2">
            <div class="col-5">
                <input type="text" class="form-control" name="spec_key[]" placeholder="Specification Name">
            </div>
            <div class="col-6">
                <input type="text" class="form-control" name="spec_value[]" placeholder="Value">
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-outline-danger" onclick="removeSpec(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    // Reset applications to one empty field
    document.getElementById('applicationsContainer').innerHTML = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" name="applications[]" placeholder="Application area">
            <button type="button" class="btn btn-outline-danger" onclick="removeApplication(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}

function editProduct(productId) {
    // Fetch product data via AJAX
    fetch('ajax-get-product.php?id=' + productId)
        .then(response => response.json())
        .then(product => {
            if (product.error) {
                alert(product.error);
                return;
            }
            
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('sub_category_id').value = product.sub_category_id;
            document.getElementById('name').value = product.name;
            document.getElementById('slug').value = product.slug;
            document.getElementById('product_code').value = product.product_code || '';
            document.getElementById('color_name').value = product.color_name || '';
            document.getElementById('color_code').value = product.color_code || '#000000';
            document.getElementById('color_code_text').value = product.color_code || '#000000';
            document.getElementById('color_index').value = product.color_index || '';
            document.getElementById('short_description').value = product.short_description || '';
            document.getElementById('full_description').value = product.full_description || '';
            document.getElementById('sort_order').value = product.sort_order;
            document.getElementById('status').value = product.status;
            document.getElementById('featured').checked = product.featured == 1;
            document.getElementById('meta_title').value = product.meta_title || '';
            document.getElementById('meta_description').value = product.meta_description || '';
            document.getElementById('meta_keywords').value = product.meta_keywords || '';
            document.getElementById('oldImage').value = product.image || '';
            document.getElementById('pdf_file_1_label').value = product.pdf_file_1_label || 'Technical Datasheet';
            document.getElementById('pdf_file_2_label').value = product.pdf_file_2_label || 'Safety Datasheet';
            document.getElementById('oldPdf1').value = product.pdf_file_1 || '';
            document.getElementById('oldPdf1Size').value = product.pdf_file_1_size || '';
            document.getElementById('oldPdf2').value = product.pdf_file_2 || '';
            document.getElementById('oldPdf2Size').value = product.pdf_file_2_size || '';
            document.getElementById('modalTitle').textContent = 'Edit Product';
            
            // Show current image
            if (product.image) {
                const preview = document.getElementById('imagePreview');
                preview.src = 'uploads/products/' + product.image;
                preview.style.display = 'block';
            } else {
                document.getElementById('imagePreview').style.display = 'none';
            }
            
            // Show PDF indicators
            if (product.pdf_file_1) {
                document.getElementById('pdf1Current').style.display = 'block';
            }
            if (product.pdf_file_2) {
                document.getElementById('pdf2Current').style.display = 'block';
            }
            
            // Parse and populate technical specs
            let specs = {};
            try {
                specs = JSON.parse(product.technical_specs || '{}');
            } catch(e) {
                specs = {};
            }
            
            let specsHTML = '';
            const specEntries = Object.entries(specs);
            if (specEntries.length > 0) {
                specEntries.forEach(([key, value]) => {
                    specsHTML += `
                        <div class="row mb-2">
                            <div class="col-5">
                                <input type="text" class="form-control" name="spec_key[]" value="${key}" placeholder="Specification Name">
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control" name="spec_value[]" value="${value}" placeholder="Value">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-outline-danger" onclick="removeSpec(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            } else {
                specsHTML = `
                    <div class="row mb-2">
                        <div class="col-5">
                            <input type="text" class="form-control" name="spec_key[]" placeholder="Specification Name">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control" name="spec_value[]" placeholder="Value">
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-outline-danger" onclick="removeSpec(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            }
            document.getElementById('specsContainer').innerHTML = specsHTML;
            
            // Parse and populate applications
            let applications = [];
            try {
                applications = JSON.parse(product.applications || '[]');
            } catch(e) {
                applications = [];
            }
            
            let applicationsHTML = '';
            if (applications.length > 0) {
                applications.forEach(app => {
                    applicationsHTML += `
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="applications[]" value="${app}" placeholder="Application area">
                            <button type="button" class="btn btn-outline-danger" onclick="removeApplication(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                });
            } else {
                applicationsHTML = `
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="applications[]" placeholder="Application area">
                        <button type="button" class="btn btn-outline-danger" onclick="removeApplication(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }
            document.getElementById('applicationsContainer').innerHTML = applicationsHTML;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('productModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product data');
        });
}

function deleteProduct(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function addSpec() {
    const container = document.getElementById('specsContainer');
    const newSpec = document.createElement('div');
    newSpec.className = 'row mb-2';
    newSpec.innerHTML = `
        <div class="col-5">
            <input type="text" class="form-control" name="spec_key[]" placeholder="Specification Name">
        </div>
        <div class="col-6">
            <input type="text" class="form-control" name="spec_value[]" placeholder="Value">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger" onclick="removeSpec(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newSpec);
}

function removeSpec(button) {
    const container = document.getElementById('specsContainer');
    if (container.children.length > 1) {
        button.closest('.row').remove();
    } else {
        alert('At least one specification field is required.');
    }
}

function addApplication() {
    const container = document.getElementById('applicationsContainer');
    const newApp = document.createElement('div');
    newApp.className = 'input-group mb-2';
    newApp.innerHTML = `
        <input type="text" class="form-control" name="applications[]" placeholder="Application area">
        <button type="button" class="btn btn-outline-danger" onclick="removeApplication(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newApp);
}

function removeApplication(button) {
    const container = document.getElementById('applicationsContainer');
    if (container.children.length > 1) {
        button.parentElement.remove();
    } else {
        alert('At least one application field is required.');
    }
}

function manageImages(productId, productName) {
    document.getElementById('uploadProductId').value = productId;
    document.getElementById('imagesModalTitle').textContent = 'Manage Images - ' + productName;
    
    // Load images via AJAX
    fetch('ajax-get-product-images.php?product_id=' + productId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.images && data.images.length > 0) {
                data.images.forEach(function(image) {
                    const isPrimary = image.is_primary == 1;
                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <img src="uploads/products/${image.image_path}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                <div class="card-body p-2">
                                    ${isPrimary ? '<span class="badge bg-success mb-2">Primary Image</span>' : ''}
                                    <div class="btn-group btn-group-sm w-100">
                                        ${!isPrimary ? `<button class="btn btn-primary" onclick="setPrimary(${image.id}, ${productId})"><i class="fas fa-star"></i> Set Primary</button>` : ''}
                                        <button class="btn btn-danger" onclick="deleteImage(${image.id})"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="col-12"><p class="text-center text-muted">No images uploaded yet.</p></div>';
            }
            
            document.getElementById('imagesGrid').innerHTML = html;
        });
    
    // Show modal
    new bootstrap.Modal(document.getElementById('imagesModal')).show();
}

function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        document.getElementById('deleteImageId').value = imageId;
        document.getElementById('deleteImageForm').submit();
    }
}

function setPrimary(imageId, productId) {
    document.getElementById('primaryImageId').value = imageId;
    document.getElementById('primaryProductId').value = productId;
    document.getElementById('setPrimaryForm').submit();
}
</script>
EOD;

include 'includes/footer.php';
?>