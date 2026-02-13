<?php
/**
 * ColourTech Industries - Sub-Categories Management
 * WITH MULTIPLE IMAGE UPLOAD SUPPORT
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle = 'Sub-Categories Management';
$breadcrumbs = ['Sub-Categories' => ''];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add/Edit Sub-Category
    if (isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('sub-categories.php');
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $short_description = sanitize($_POST['short_description'] ?? '');
        $full_description = $_POST['full_description'] ?? ''; // Allow HTML
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        $meta_title = sanitize($_POST['meta_title'] ?? '');
        $meta_description = sanitize($_POST['meta_description'] ?? '');
        $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
        
        // Handle features (convert array to JSON)
        $features = [];
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            foreach ($_POST['features'] as $feature) {
                $feature = trim($feature);
                if (!empty($feature)) {
                    $features[] = $feature;
                }
            }
        }
        $features_json = json_encode($features);
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Sub-category name is required.';
        }
        
        if (empty($category_id)) {
            $errors[] = 'Please select a parent category.';
        }
        
        if (empty($slug)) {
            $slug = generateUniqueSlug('sub_categories', $name, $id);
        } else {
            if (slugExists('sub_categories', $slug, $id)) {
                $errors[] = 'Slug already exists. Please use a different slug.';
            }
        }
        
        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    // Insert new sub-category
                    $stmt = $pdo->prepare("
                        INSERT INTO sub_categories 
                        (category_id, name, slug, short_description, full_description, features, sort_order, 
                         meta_title, meta_description, meta_keywords, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $category_id, $name, $slug, $short_description, $full_description, $features_json,
                        $sort_order, $meta_title, $meta_description, $meta_keywords, $status
                    ]);
                    
                    $subCategoryId = $pdo->lastInsertId();
                    
                    logActivity($_SESSION['admin_id'], 'created', 'sub_categories', $subCategoryId, "Created sub-category: $name");
                    setFlashMessage('success', 'Sub-category added successfully!');
                    
                } else {
                    // Update existing sub-category
                    $stmt = $pdo->prepare("
                        UPDATE sub_categories 
                        SET category_id = ?, name = ?, slug = ?, short_description = ?, full_description = ?, 
                            features = ?, sort_order = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $category_id, $name, $slug, $short_description, $full_description, $features_json,
                        $sort_order, $meta_title, $meta_description, $meta_keywords, $status, $id
                    ]);
                    
                    logActivity($_SESSION['admin_id'], 'updated', 'sub_categories', $id, "Updated sub-category: $name");
                    setFlashMessage('success', 'Sub-category updated successfully!');
                }
                
                redirect('sub-categories.php');
                
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }
    
    // Delete Sub-Category
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('sub-categories.php');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id) {
            // Get sub-category info before deleting
            $stmt = $pdo->prepare("SELECT * FROM sub_categories WHERE id = ?");
            $stmt->execute([$id]);
            $subCategory = $stmt->fetch();
            
            if ($subCategory) {
                // Delete all images
                $images = $pdo->prepare("SELECT * FROM sub_category_images WHERE sub_category_id = ?");
                $images->execute([$id]);
                
                foreach ($images->fetchAll() as $image) {
                    deleteFile(SUBCATEGORY_UPLOAD_DIR . $image['image_path']);
                }
                
                // Delete sub-category (cascades to products and images)
                $stmt = $pdo->prepare("DELETE FROM sub_categories WHERE id = ?");
                $stmt->execute([$id]);
                
                logActivity($_SESSION['admin_id'], 'deleted', 'sub_categories', $id, "Deleted sub-category: " . $subCategory['name']);
                setFlashMessage('success', 'Sub-category deleted successfully!');
            }
        }
        
        redirect('sub-categories.php');
    }
    
    // Upload Multiple Images
    if (isset($_POST['action']) && $_POST['action'] === 'upload_images') {
        $sub_category_id = (int)($_POST['sub_category_id'] ?? 0);
        
        if ($sub_category_id && isset($_FILES['images'])) {
            $uploadedCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Get current image count for sort order
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sub_category_images WHERE sub_category_id = ?");
            $stmt->execute([$sub_category_id]);
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
                    
                    $uploadResult = uploadImage($singleFile, SUBCATEGORY_UPLOAD_DIR, 'subcat_');
                    
                    if ($uploadResult['success']) {
                        // Insert image
                        $stmt = $pdo->prepare("
                            INSERT INTO sub_category_images (sub_category_id, image_path, sort_order, is_primary) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $sub_category_id,
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
                logActivity($_SESSION['admin_id'], 'created', 'sub_category_images', $sub_category_id, "Uploaded $uploadedCount images for sub-category ID: $sub_category_id");
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
        
        redirect('sub-categories.php');
    }
    
    // Delete Image
    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        
        if ($image_id) {
            $stmt = $pdo->prepare("SELECT * FROM sub_category_images WHERE id = ?");
            $stmt->execute([$image_id]);
            $image = $stmt->fetch();
            
            if ($image) {
                deleteFile(SUBCATEGORY_UPLOAD_DIR . $image['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM sub_category_images WHERE id = ?");
                $stmt->execute([$image_id]);
                
                logActivity($_SESSION['admin_id'], 'deleted', 'sub_category_images', $image_id, "Deleted image: " . $image['image_path']);
                setFlashMessage('success', 'Image deleted successfully!');
            }
        }
        
        redirect('sub-categories.php');
    }
    
    // Set Primary Image
    if (isset($_POST['action']) && $_POST['action'] === 'set_primary') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $sub_category_id = (int)($_POST['sub_category_id'] ?? 0);
        
        if ($image_id && $sub_category_id) {
            // Reset all to non-primary
            $stmt = $pdo->prepare("UPDATE sub_category_images SET is_primary = 0 WHERE sub_category_id = ?");
            $stmt->execute([$sub_category_id]);
            
            // Set new primary
            $stmt = $pdo->prepare("UPDATE sub_category_images SET is_primary = 1 WHERE id = ?");
            $stmt->execute([$image_id]);
            
            setFlashMessage('success', 'Primary image updated!');
        }
        
        redirect('sub-categories.php');
    }
}

// Get all sub-categories with category info
$subCategories = $pdo->query("
    SELECT sc.*, 
           c.name as category_name,
           COUNT(DISTINCT p.id) as product_count
    FROM sub_categories sc
    LEFT JOIN categories c ON sc.category_id = c.id
    LEFT JOIN products p ON sc.id = p.sub_category_id
    GROUP BY sc.id
    ORDER BY c.sort_order ASC, sc.sort_order ASC, sc.name ASC
")->fetchAll();

// Get all active categories for dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Sub-Categories Management</h4>
            <p class="mb-0">Manage sub-categories under main categories</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subCategoryModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add New Sub-Category
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Sub-Category Name</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Images</th>
                        <th>Products</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subCategories as $index => $subCategory): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($subCategory['name']); ?></strong>
                                <?php if ($subCategory['short_description']): ?>
                                    <br><small class="text-muted"><?php echo truncateText($subCategory['short_description'], 50); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($subCategory['category_name']); ?></span>
                            </td>
                            <td><code><?php echo htmlspecialchars($subCategory['slug']); ?></code></td>
                            <td>
                                <?php
                                $imageStmt = $pdo->prepare("SELECT COUNT(*) FROM sub_category_images WHERE sub_category_id = ?");
                                $imageStmt->execute([$subCategory['id']]);
                                $imageCount = $imageStmt->fetchColumn();
                                ?>
                                <span class="badge bg-info"><?php echo $imageCount; ?> image<?php echo $imageCount != 1 ? 's' : ''; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $subCategory['product_count']; ?></span>
                            </td>
                            <td><?php echo $subCategory['sort_order']; ?></td>
                            <td><?php echo getStatusBadge($subCategory['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="manageImages(<?php echo $subCategory['id']; ?>, '<?php echo htmlspecialchars($subCategory['name']); ?>')" title="Manage Images">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="editSubCategory(<?php echo htmlspecialchars(json_encode($subCategory)); ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSubCategory(<?php echo $subCategory['id']; ?>, '<?php echo htmlspecialchars($subCategory['name']); ?>')" title="Delete">
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

<!-- Add/Edit Sub-Category Modal -->
<div class="modal fade" id="subCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" id="subCategoryForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="subCategoryId">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Sub-Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Parent Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Sub-Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required data-slug-source="true" data-slug-target="slug">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="slug" class="form-label">Slug *</label>
                            <input type="text" class="form-control" id="slug" name="slug" required>
                            <small class="text-muted">URL-friendly version (auto-generated)</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="2" placeholder="Brief description for listing pages"></textarea>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="full_description" class="form-label">Full Description</label>
                            <textarea class="form-control" id="full_description" name="full_description" rows="4" placeholder="Detailed description for detail pages"></textarea>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Features</label>
                            <div id="featuresContainer">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="features[]" placeholder="Enter a feature">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFeature()">
                                <i class="fas fa-plus me-1"></i>Add Feature
                            </button>
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
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <hr>
                            <h6>SEO Settings (Optional)</h6>
                        </div>
                        
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
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Sub-Category
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
                    <input type="hidden" name="sub_category_id" id="uploadSubCategoryId">
                    
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
    <input type="hidden" name="sub_category_id" id="primarySubCategoryId">
</form>

<?php
$extraJS = <<<'EOD'
<script>
function resetForm() {
    document.getElementById('subCategoryForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('subCategoryId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Sub-Category';
    
    // Reset features to one empty field
    document.getElementById('featuresContainer').innerHTML = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" name="features[]" placeholder="Enter a feature">
            <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}

function editSubCategory(subCategory) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('subCategoryId').value = subCategory.id;
    document.getElementById('category_id').value = subCategory.category_id;
    document.getElementById('name').value = subCategory.name;
    document.getElementById('slug').value = subCategory.slug;
    document.getElementById('short_description').value = subCategory.short_description || '';
    document.getElementById('full_description').value = subCategory.full_description || '';
    document.getElementById('sort_order').value = subCategory.sort_order;
    document.getElementById('status').value = subCategory.status;
    document.getElementById('meta_title').value = subCategory.meta_title || '';
    document.getElementById('meta_description').value = subCategory.meta_description || '';
    document.getElementById('meta_keywords').value = subCategory.meta_keywords || '';
    document.getElementById('modalTitle').textContent = 'Edit Sub-Category';
    
    // Parse and populate features
    let features = [];
    try {
        features = JSON.parse(subCategory.features || '[]');
    } catch(e) {
        features = [];
    }
    
    let featuresHTML = '';
    if (features.length > 0) {
        features.forEach(function(feature) {
            featuresHTML += `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="features[]" value="${feature}" placeholder="Enter a feature">
                    <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });
    } else {
        featuresHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" name="features[]" placeholder="Enter a feature">
                <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
    
    document.getElementById('featuresContainer').innerHTML = featuresHTML;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('subCategoryModal')).show();
}

function deleteSubCategory(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis will also delete all products under this sub-category!\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function addFeature() {
    const container = document.getElementById('featuresContainer');
    const newFeature = document.createElement('div');
    newFeature.className = 'input-group mb-2';
    newFeature.innerHTML = `
        <input type="text" class="form-control" name="features[]" placeholder="Enter a feature">
        <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newFeature);
}

function removeFeature(button) {
    const container = document.getElementById('featuresContainer');
    if (container.children.length > 1) {
        button.parentElement.remove();
    } else {
        alert('At least one feature field is required.');
    }
}

function manageImages(subCategoryId, subCategoryName) {
    document.getElementById('uploadSubCategoryId').value = subCategoryId;
    document.getElementById('imagesModalTitle').textContent = 'Manage Images - ' + subCategoryName;
    
    // Load images via AJAX
    fetch('ajax-get-images.php?sub_category_id=' + subCategoryId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.images && data.images.length > 0) {
                data.images.forEach(function(image) {
                    const isPrimary = image.is_primary == 1;
                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <img src="uploads/sub-categories/${image.image_path}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                <div class="card-body p-2">
                                    ${isPrimary ? '<span class="badge bg-success mb-2">Primary Image</span>' : ''}
                                    <div class="btn-group btn-group-sm w-100">
                                        ${!isPrimary ? `<button class="btn btn-primary" onclick="setPrimary(${image.id}, ${subCategoryId})"><i class="fas fa-star"></i> Set Primary</button>` : ''}
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

function setPrimary(imageId, subCategoryId) {
    document.getElementById('primaryImageId').value = imageId;
    document.getElementById('primarySubCategoryId').value = subCategoryId;
    document.getElementById('setPrimaryForm').submit();
}
</script>
EOD;

include 'includes/footer.php';
?>