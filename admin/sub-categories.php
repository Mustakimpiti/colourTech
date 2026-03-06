<?php
/**
 * ColourTech Industries - Sub-Categories Management
 * WITH MULTIPLE IMAGE UPLOAD SUPPORT
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle   = 'Sub-Categories Management';
$breadcrumbs = ['Sub-Categories' => ''];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add/Edit Sub-Category
    if (isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('sub-categories.php');
        }

        $id                = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $category_id       = (int) ($_POST['category_id'] ?? 0);
        $name              = sanitize($_POST['name'] ?? '');
        $slug              = sanitize($_POST['slug'] ?? '');
        $short_description = sanitize($_POST['short_description'] ?? '');
        $full_description  = $_POST['full_description'] ?? '';
        $sort_order        = (int) ($_POST['sort_order'] ?? 0);
        $status            = sanitize($_POST['status'] ?? 'active');
        $meta_title        = sanitize($_POST['meta_title'] ?? '');
        $meta_description  = sanitize($_POST['meta_description'] ?? '');
        $meta_keywords     = sanitize($_POST['meta_keywords'] ?? '');

        $features = [];
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            foreach ($_POST['features'] as $feature) {
                $feature = trim($feature);
                if (!empty($feature)) $features[] = $feature;
            }
        }
        $features_json = json_encode($features);

        $errors = [];

        if (empty($name))        $errors[] = 'Sub-category name is required.';
        if (empty($category_id)) $errors[] = 'Please select a parent category.';

        if (empty($slug)) {
            $slug = generateUniqueSlug('sub_categories', $name, $id);
        } else {
            if (slugExists('sub_categories', $slug, $id))
                $errors[] = 'Slug already exists. Please use a different slug.';
        }

        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO sub_categories 
                        (category_id, name, slug, short_description, full_description, features, sort_order, 
                         meta_title, meta_description, meta_keywords, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$category_id, $name, $slug, $short_description, $full_description,
                        $features_json, $sort_order, $meta_title, $meta_description, $meta_keywords, $status]);
                    $subCategoryId = $pdo->lastInsertId();
                    logActivity($_SESSION['admin_id'], 'created', 'sub_categories', $subCategoryId, "Created sub-category: $name");
                    setFlashMessage('success', 'Sub-category added successfully!');

                } else {
                    $stmt = $pdo->prepare("
                        UPDATE sub_categories 
                        SET category_id = ?, name = ?, slug = ?, short_description = ?, full_description = ?, 
                            features = ?, sort_order = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$category_id, $name, $slug, $short_description, $full_description,
                        $features_json, $sort_order, $meta_title, $meta_description, $meta_keywords, $status, $id]);
                    logActivity($_SESSION['admin_id'], 'updated', 'sub_categories', $id, "Updated sub-category: $name");
                    setFlashMessage('success', 'Sub-category updated successfully!');
                }

                redirect('sub-categories.php');

            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) setFlashMessage('error', implode('<br>', $errors));
    }

    // Delete Sub-Category
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('sub-categories.php');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM sub_categories WHERE id = ?");
            $stmt->execute([$id]);
            $subCategory = $stmt->fetch();

            if ($subCategory) {
                $images = $pdo->prepare("SELECT * FROM sub_category_images WHERE sub_category_id = ?");
                $images->execute([$id]);
                foreach ($images->fetchAll() as $image) {
                    deleteFile(SUBCATEGORY_UPLOAD_DIR . $image['image_path']);
                }
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
        $sub_category_id = (int) ($_POST['sub_category_id'] ?? 0);

        if ($sub_category_id && isset($_FILES['images'])) {
            $uploadedCount = 0;
            $errorCount    = 0;
            $errors        = [];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sub_category_images WHERE sub_category_id = ?");
            $stmt->execute([$sub_category_id]);
            $count        = $stmt->fetchColumn();
            $isFirstImage = ($count == 0);
            $fileCount    = count($_FILES['images']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                    ];
                    $uploadResult = uploadImage($singleFile, SUBCATEGORY_UPLOAD_DIR, 'subcat_');
                    if ($uploadResult['success']) {
                        $stmt = $pdo->prepare("INSERT INTO sub_category_images (sub_category_id, image_path, sort_order, is_primary) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$sub_category_id, $uploadResult['filename'], $count + $i + 1, ($isFirstImage && $i == 0) ? 1 : 0]);
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

            $messages = [];
            if ($uploadedCount > 0) {
                $messages[] = "$uploadedCount image(s) uploaded successfully!";
                logActivity($_SESSION['admin_id'], 'created', 'sub_category_images', $sub_category_id, "Uploaded $uploadedCount images for sub-category ID: $sub_category_id");
            }
            if ($errorCount > 0) {
                $messages[] = "$errorCount image(s) failed to upload.";
                if (!empty($errors)) $messages[] = implode('<br>', $errors);
            }

            $messageType = ($errorCount > 0 && $uploadedCount == 0) ? 'error' : (($errorCount > 0) ? 'warning' : 'success');
            setFlashMessage($messageType, implode('<br>', $messages));
        }

        redirect('sub-categories.php');
    }

    // Delete Image
    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $image_id = (int) ($_POST['image_id'] ?? 0);

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
        $image_id        = (int) ($_POST['image_id'] ?? 0);
        $sub_category_id = (int) ($_POST['sub_category_id'] ?? 0);

        if ($image_id && $sub_category_id) {
            $stmt = $pdo->prepare("UPDATE sub_category_images SET is_primary = 0 WHERE sub_category_id = ?");
            $stmt->execute([$sub_category_id]);
            $stmt = $pdo->prepare("UPDATE sub_category_images SET is_primary = 1 WHERE id = ?");
            $stmt->execute([$image_id]);
            setFlashMessage('success', 'Primary image updated!');
        }

        redirect('sub-categories.php');
    }
}

// Fetch data
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

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")->fetchAll();

include 'includes/header.php';
?>

<!-- ── Page Header ──────────────────────────────────────────────────────────── -->
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

<!-- ── Table ─────────────────────────────────────────────────────────────────── -->
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
                        <th width="150">Actions</th>
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
                            <td><span class="badge bg-primary"><?php echo $subCategory['product_count']; ?></span></td>
                            <td><?php echo $subCategory['sort_order']; ?></td>
                            <td><?php echo getStatusBadge($subCategory['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success"
                                    onclick="manageImages(<?php echo $subCategory['id']; ?>, '<?php echo htmlspecialchars($subCategory['name']); ?>')"
                                    title="Manage Images">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn btn-sm btn-info"
                                    onclick="editSubCategory(<?php echo htmlspecialchars(json_encode($subCategory)); ?>)"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="deleteSubCategory(<?php echo $subCategory['id']; ?>, '<?php echo htmlspecialchars($subCategory['name']); ?>')"
                                    title="Delete">
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


<!-- ═══════════════════════════════════════════════════════════════════════════
     ADD / EDIT SUB-CATEGORY MODAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="subCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" id="subCategoryForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id"     id="subCategoryId">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Sub-Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist" style="flex-wrap:nowrap; overflow-x:auto;">
                        <li class="nav-item" style="white-space:nowrap;">
                            <a class="nav-link active" data-bs-toggle="tab" href="#scBasicInfo">
                                <i class="fas fa-info-circle me-1"></i>Basic Info
                            </a>
                        </li>
                        <li class="nav-item" style="white-space:nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#scSeoInfo">
                                <i class="fas fa-search me-1"></i>SEO
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- ── Basic Info Tab ──────────────────────────────── -->
                        <div class="tab-pane fade show active" id="scBasicInfo">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Parent Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">The main category this belongs to (e.g. Paints &amp; Coatings).</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Sub-Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           placeholder="e.g. AZO Organic Pigments"
                                           data-slug-source="true" data-slug-target="slug">
                                    <div class="form-text">Display name shown on the website. Slug is auto-generated.</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="slug" class="form-label">Slug *</label>
                                    <input type="text" class="form-control" id="slug" name="slug" required
                                           placeholder="e.g. azo-organic-pigments">
                                    <div class="form-text">Auto-generated URL. Lowercase, numbers, hyphens only. Must be unique.</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="short_description" class="form-label">Short Description</label>
                                    <textarea class="form-control" id="short_description" name="short_description" rows="2"
                                              placeholder="e.g. High-quality AZO organic pigments for superior colour performance."></textarea>
                                    <div class="form-text">Shown on listing pages. Keep under 160 chars for best SEO.</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="full_description" class="form-label">Full Description</label>
                                    <textarea class="form-control" id="full_description" name="full_description" rows="4"
                                              placeholder="Detailed description — properties, advantages, recommended applications..."></textarea>
                                    <div class="form-text">Shown on the detail page. HTML allowed (bold, lists, etc.).</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label fw-semibold">Features</label>
                                    <div class="text-muted small mb-2">
                                        Shown as checkmark bullets on the product page (e.g. <em>Excellent colour strength</em>, <em>High stability</em>).
                                    </div>
                                    <div id="featuresContainer">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="features[]"
                                                   placeholder="e.g. Excellent colour strength">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)" title="Remove">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addFeature()">
                                        <i class="fas fa-plus me-1"></i>Add Feature
                                    </button>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                                    <div class="form-text">Lower = appears first. Use 1, 2, 3…</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active — visible</option>
                                        <option value="inactive">Inactive — hidden</option>
                                    </select>
                                    <div class="form-text">Only Active sub-categories appear on the website.</div>
                                </div>

                            </div>
                        </div>
                        <!-- /Basic Info Tab -->

                        <!-- ── SEO Tab ─────────────────────────────────────── -->
                        <div class="tab-pane fade" id="scSeoInfo">
                            <div class="row">

                                <div class="col-12 mb-3">
                                    <div class="alert alert-secondary py-2 mb-0" style="font-size:0.82rem;">
                                        <i class="fas fa-search me-1"></i>
                                        Leave blank to use auto-generated values. Fill in only to override defaults.
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title"
                                           placeholder="e.g. AZO Organic Pigments for Paints & Coatings | ColourTech Industries"
                                           maxlength="70">
                                    <div class="d-flex justify-content-between">
                                        <div class="form-text">Browser tab &amp; Google headline. Ideal: 50–60 chars.</div>
                                        <small class="text-muted mt-1" id="scMetaTitleCount">0 / 60</small>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"
                                              placeholder="e.g. ColourTech offers high-quality AZO organic pigments with excellent colour strength for paints, coatings, and industrial use."
                                              maxlength="165"></textarea>
                                    <div class="d-flex justify-content-between">
                                        <div class="form-text">Google snippet text. Ideal: 140–160 chars.</div>
                                        <small class="text-muted mt-1" id="scMetaDescCount">0 / 160</small>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords"
                                           placeholder="e.g. AZO organic pigments, azo pigments paints, ColourTech Industries">
                                    <div class="form-text">Comma-separated. Include sub-category name and industry terms.</div>
                                </div>

                            </div>
                        </div>
                        <!-- /SEO Tab -->

                    </div><!-- /tab-content -->
                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Sub-Category
                    </button>
                </div>

            </form>
        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div>
<!-- /Sub-Category Modal -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     MANAGE IMAGES MODAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="imagesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagesModalTitle">Manage Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                        <i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple. Max 5 MB per image (JPG, PNG, GIF, WebP).
                    </small>
                </form>
                <div id="imagesGrid" class="row"></div>
            </div>
        </div>
    </div>
</div>


<!-- ── Hidden Action Forms ───────────────────────────────────────────────────── -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<form method="POST" id="deleteImageForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete_image">
    <input type="hidden" name="image_id" id="deleteImageId">
</form>

<form method="POST" id="setPrimaryForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="set_primary">
    <input type="hidden" name="image_id" id="primaryImageId">
    <input type="hidden" name="sub_category_id" id="primarySubCategoryId">
</form>


<?php
$extraJS = <<<'EOD'
<script>

// ── Reset form & return to Basic Info tab ─────────────────────────────
function resetForm() {
    document.getElementById('subCategoryForm').reset();
    document.getElementById('formAction').value    = 'add';
    document.getElementById('subCategoryId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Sub-Category';

    const firstTab = document.querySelector('#subCategoryModal [data-bs-toggle="tab"]');
    if (firstTab) new bootstrap.Tab(firstTab).show();

    document.getElementById('featuresContainer').innerHTML = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" name="features[]" placeholder="e.g. Excellent colour strength">
            <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
}

// ── Populate form for editing ─────────────────────────────────────────
function editSubCategory(subCategory) {
    document.getElementById('formAction').value           = 'edit';
    document.getElementById('subCategoryId').value        = subCategory.id;
    document.getElementById('category_id').value          = subCategory.category_id;
    document.getElementById('name').value                 = subCategory.name;
    document.getElementById('slug').value                 = subCategory.slug;
    document.getElementById('short_description').value    = subCategory.short_description || '';
    document.getElementById('full_description').value     = subCategory.full_description  || '';
    document.getElementById('sort_order').value           = subCategory.sort_order;
    document.getElementById('status').value               = subCategory.status;
    document.getElementById('meta_title').value           = subCategory.meta_title        || '';
    document.getElementById('meta_description').value     = subCategory.meta_description  || '';
    document.getElementById('meta_keywords').value        = subCategory.meta_keywords     || '';
    document.getElementById('modalTitle').textContent     = 'Edit Sub-Category';

    const firstTab = document.querySelector('#subCategoryModal [data-bs-toggle="tab"]');
    if (firstTab) new bootstrap.Tab(firstTab).show();

    // Populate features
    let features = [];
    try { features = JSON.parse(subCategory.features || '[]'); } catch(e) {}

    let featuresHTML = '';
    if (features.length > 0) {
        features.forEach(function(feature) {
            featuresHTML += `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="features[]" value="${feature}" placeholder="e.g. Excellent colour strength">
                    <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
        });
    } else {
        featuresHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" name="features[]" placeholder="e.g. Excellent colour strength">
                <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
    }
    document.getElementById('featuresContainer').innerHTML = featuresHTML;

    // Refresh SEO counters with pre-filled values
    ['meta_title', 'meta_description'].forEach(id =>
        document.getElementById(id).dispatchEvent(new Event('input'))
    );

    new bootstrap.Modal(document.getElementById('subCategoryModal')).show();
}

// ── Delete sub-category ───────────────────────────────────────────────
function deleteSubCategory(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis will also delete all products under this sub-category!\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// ── Features dynamic rows ─────────────────────────────────────────────
function addFeature() {
    const container  = document.getElementById('featuresContainer');
    const newFeature = document.createElement('div');
    newFeature.className = 'input-group mb-2';
    newFeature.innerHTML = `
        <input type="text" class="form-control" name="features[]" placeholder="e.g. Excellent colour strength">
        <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)" title="Remove">
            <i class="fas fa-times"></i>
        </button>`;
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

// ── Manage Images modal ───────────────────────────────────────────────
function manageImages(subCategoryId, subCategoryName) {
    document.getElementById('uploadSubCategoryId').value    = subCategoryId;
    document.getElementById('imagesModalTitle').textContent = 'Manage Images — ' + subCategoryName;

    fetch('ajax-get-images.php?sub_category_id=' + subCategoryId)
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (data.images && data.images.length > 0) {
                data.images.forEach(function(image) {
                    const isPrimary = image.is_primary == 1;
                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <img src="uploads/sub-categories/${image.image_path}" class="card-img-top" style="height:200px; object-fit:cover;">
                                <div class="card-body p-2">
                                    ${isPrimary ? '<span class="badge bg-success mb-2">Primary Image</span>' : ''}
                                    <div class="btn-group btn-group-sm w-100">
                                        ${!isPrimary ? `<button class="btn btn-primary" onclick="setPrimary(${image.id}, ${subCategoryId})"><i class="fas fa-star"></i> Set Primary</button>` : ''}
                                        <button class="btn btn-danger" onclick="deleteImage(${image.id})"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
            } else {
                html = '<div class="col-12"><p class="text-center text-muted">No images uploaded yet.</p></div>';
            }
            document.getElementById('imagesGrid').innerHTML = html;
        });

    new bootstrap.Modal(document.getElementById('imagesModal')).show();
}

function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        document.getElementById('deleteImageId').value = imageId;
        document.getElementById('deleteImageForm').submit();
    }
}

function setPrimary(imageId, subCategoryId) {
    document.getElementById('primaryImageId').value       = imageId;
    document.getElementById('primarySubCategoryId').value = subCategoryId;
    document.getElementById('setPrimaryForm').submit();
}

// ── SEO character counters ────────────────────────────────────────────
(function () {
    function bindCounter(inputId, counterId, limit) {
        const el      = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!el || !counter) return;

        function update() {
            const len = el.value.length;
            counter.textContent = len + ' / ' + limit;
            counter.style.color = len > limit
                ? '#dc3545'
                : (len >= limit * 0.85 ? '#fd7e14' : '#6c757d');
        }
        el.addEventListener('input', update);
        document.getElementById('subCategoryModal')
            .addEventListener('shown.bs.modal', update);
        update();
    }

    bindCounter('meta_title',       'scMetaTitleCount', 60);
    bindCounter('meta_description', 'scMetaDescCount',  160);
})();

</script>
EOD;

include 'includes/footer.php';
?>