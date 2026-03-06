<?php
/**
 * ColourTech Industries - Categories Management
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle   = 'Categories Management';
$breadcrumbs = ['Categories' => ''];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add/Edit Category
    if (isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('categories.php');
        }

        $id               = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $name             = sanitize($_POST['name'] ?? '');
        $slug             = sanitize($_POST['slug'] ?? '');
        $description      = sanitize($_POST['description'] ?? '');
        $sort_order       = (int) ($_POST['sort_order'] ?? 0);
        $status           = sanitize($_POST['status'] ?? 'active');
        $meta_title       = sanitize($_POST['meta_title'] ?? '');
        $meta_description = sanitize($_POST['meta_description'] ?? '');
        $meta_keywords    = sanitize($_POST['meta_keywords'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Category name is required.';
        }

        if (empty($slug)) {
            $slug = generateUniqueSlug('categories', $name, $id);
        } else {
            if (slugExists('categories', $slug, $id)) {
                $errors[] = 'Slug already exists. Please use a different slug.';
            }
        }

        // Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['image'], CATEGORY_UPLOAD_DIR, 'cat_');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['filename'];
                if ($id && !empty($_POST['old_image'])) {
                    deleteFile(CATEGORY_UPLOAD_DIR . $_POST['old_image']);
                }
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            if ($id && !empty($_POST['old_image'])) {
                $imagePath = $_POST['old_image'];
            }
        }

        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO categories 
                        (name, slug, description, image, sort_order, meta_title, meta_description, meta_keywords, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $slug, $description, $imagePath, $sort_order, $meta_title, $meta_description, $meta_keywords, $status]);
                    $categoryId = $pdo->lastInsertId();
                    logActivity($_SESSION['admin_id'], 'created', 'categories', $categoryId, "Created category: $name");
                    setFlashMessage('success', 'Category added successfully!');

                } else {
                    $stmt = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, slug = ?, description = ?, image = ?, sort_order = ?,
                            meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $slug, $description, $imagePath, $sort_order, $meta_title, $meta_description, $meta_keywords, $status, $id]);
                    logActivity($_SESSION['admin_id'], 'updated', 'categories', $id, "Updated category: $name");
                    setFlashMessage('success', 'Category updated successfully!');
                }

                redirect('categories.php');

            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
        }
    }

    // Delete Category
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('categories.php');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();

            if ($category) {
                if (!empty($category['image'])) {
                    deleteFile(CATEGORY_UPLOAD_DIR . $category['image']);
                }
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                logActivity($_SESSION['admin_id'], 'deleted', 'categories', $id, "Deleted category: " . $category['name']);
                setFlashMessage('success', 'Category deleted successfully!');
            }
        }

        redirect('categories.php');
    }
}

// Fetch data
$categories = $pdo->query("
    SELECT c.*, 
           COUNT(DISTINCT sc.id) as sub_category_count,
           COUNT(DISTINCT p.id)  as product_count
    FROM categories c
    LEFT JOIN sub_categories sc ON c.id = sc.category_id
    LEFT JOIN products p ON sc.id = p.sub_category_id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
")->fetchAll();

include 'includes/header.php';
?>

<!-- ── Page Header ──────────────────────────────────────────────────────────── -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Categories Management</h4>
            <p class="mb-0">Manage your main product categories</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add New Category
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
                        <th>Image</th>
                        <th>Category Name</th>
                        <th>Slug</th>
                        <th>Sub-Categories</th>
                        <th>Products</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $index => $category): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if (!empty($category['image'])): ?>
                                    <img src="<?php echo CATEGORY_UPLOAD_DIR . $category['image']; ?>"
                                         alt="<?php echo htmlspecialchars($category['name']); ?>"
                                         style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                <?php else: ?>
                                    <div style="width:50px; height:50px; background:#f0f0f0; border-radius:5px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                <?php if ($category['description']): ?>
                                    <br><small class="text-muted"><?php echo truncateText($category['description'], 50); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                            <td><span class="badge bg-info"><?php echo $category['sub_category_count']; ?></span></td>
                            <td><span class="badge bg-primary"><?php echo $category['product_count']; ?></span></td>
                            <td><?php echo $category['sort_order']; ?></td>
                            <td><?php echo getStatusBadge($category['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info"
                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
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
     ADD / EDIT CATEGORY MODAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="categoryForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action"    id="formAction" value="add">
                <input type="hidden" name="id"        id="categoryId">
                <input type="hidden" name="old_image" id="oldImage">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist" style="flex-wrap:nowrap; overflow-x:auto;">
                        <li class="nav-item" style="white-space:nowrap;">
                            <a class="nav-link active" data-bs-toggle="tab" href="#catBasicInfo">
                                <i class="fas fa-info-circle me-1"></i>Basic Info
                            </a>
                        </li>
                        <li class="nav-item" style="white-space:nowrap;">
                            <a class="nav-link" data-bs-toggle="tab" href="#catSeoInfo">
                                <i class="fas fa-search me-1"></i>SEO
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- ── Basic Info Tab ──────────────────────────────── -->
                        <div class="tab-pane fade show active" id="catBasicInfo">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           placeholder="e.g. Paints &amp; Coatings"
                                           data-slug-source="true" data-slug-target="slug">
                                    <div class="form-text">Shown in navigation menu. Slug is auto-generated.</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="slug" class="form-label">Slug *</label>
                                    <input type="text" class="form-control" id="slug" name="slug" required
                                           placeholder="e.g. paints-coatings">
                                    <div class="form-text">Auto-generated URL. Lowercase, numbers, hyphens only. Must be unique.</div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              placeholder="e.g. Premium pigments for the paints and coatings industry."></textarea>
                                    <div class="form-text">Shown on the category page. Used as fallback meta description if SEO tab is blank.</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="image" class="form-label">Category Image</label>
                                    <input type="file" class="form-control" id="image" name="image"
                                           accept="image/*" data-preview="imagePreview">
                                    <div class="form-text">Breadcrumb &amp; OG image. Recommended: 1200×400 px. Max 5 MB. Replaces existing on upload.</div>
                                    <div class="mt-2">
                                        <img id="imagePreview" src="" alt="Preview"
                                             style="max-width:200px; max-height:120px; display:none; border-radius:5px; border:1px solid #dee2e6;">
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                                    <div class="form-text">Lower = appears first. Use 1, 2, 3…</div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active — visible</option>
                                        <option value="inactive">Inactive — hidden</option>
                                    </select>
                                    <div class="form-text">Only Active categories appear on the website.</div>
                                </div>

                            </div>
                        </div>
                        <!-- /Basic Info Tab -->

                        <!-- ── SEO Tab ─────────────────────────────────────── -->
                        <div class="tab-pane fade" id="catSeoInfo">
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
                                           placeholder="e.g. Paints & Coatings Pigments | ColourTech Industries"
                                           maxlength="70">
                                    <div class="d-flex justify-content-between">
                                        <div class="form-text">Browser tab &amp; Google headline. Ideal: 50–60 chars.</div>
                                        <small class="text-muted mt-1" id="catMetaTitleCount">0 / 60</small>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"
                                              placeholder="e.g. ColourTech Industries supplies premium AZO, Phthalo and Chrome pigments for paints and coatings worldwide."
                                              maxlength="165"></textarea>
                                    <div class="d-flex justify-content-between">
                                        <div class="form-text">Google snippet text. Ideal: 140–160 chars.</div>
                                        <small class="text-muted mt-1" id="catMetaDescCount">0 / 160</small>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords"
                                           placeholder="e.g. paints coatings pigments, AZO pigments, ColourTech Industries">
                                    <div class="form-text">Comma-separated. Include category name and pigment types.</div>
                                </div>

                            </div>
                        </div>
                        <!-- /SEO Tab -->

                    </div><!-- /tab-content -->
                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Category
                    </button>
                </div>

            </form>
        </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
</div>
<!-- /Category Modal -->


<!-- ── Hidden Delete Form ────────────────────────────────────────────────────── -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>


<?php
$extraJS = <<<'EOD'
<script>

// ── Image preview ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const imageInput   = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    imagePreview.src           = event.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
});

// ── Reset form & return to Basic Info tab ─────────────────────────────
function resetForm() {
    document.getElementById('categoryForm').reset();
    document.getElementById('formAction').value  = 'add';
    document.getElementById('categoryId').value  = '';
    document.getElementById('oldImage').value    = '';
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('imagePreview').style.display = 'none';

    const firstTab = document.querySelector('#categoryModal [data-bs-toggle="tab"]');
    if (firstTab) new bootstrap.Tab(firstTab).show();
}

// ── Populate form for editing ─────────────────────────────────────────
function editCategory(category) {
    document.getElementById('formAction').value       = 'edit';
    document.getElementById('categoryId').value       = category.id;
    document.getElementById('name').value             = category.name;
    document.getElementById('slug').value             = category.slug;
    document.getElementById('description').value      = category.description      || '';
    document.getElementById('sort_order').value       = category.sort_order;
    document.getElementById('status').value           = category.status;
    document.getElementById('meta_title').value       = category.meta_title       || '';
    document.getElementById('meta_description').value = category.meta_description || '';
    document.getElementById('meta_keywords').value    = category.meta_keywords    || '';
    document.getElementById('oldImage').value         = category.image            || '';
    document.getElementById('modalTitle').textContent = 'Edit Category';

    const firstTab = document.querySelector('#categoryModal [data-bs-toggle="tab"]');
    if (firstTab) new bootstrap.Tab(firstTab).show();

    const preview = document.getElementById('imagePreview');
    if (category.image) {
        preview.src           = '<?php echo CATEGORY_UPLOAD_DIR; ?>' + category.image;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }

    // Refresh SEO counters with pre-filled values
    ['meta_title', 'meta_description'].forEach(id =>
        document.getElementById(id).dispatchEvent(new Event('input'))
    );

    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

// ── Delete category ───────────────────────────────────────────────────
function deleteCategory(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis will also delete all sub-categories and products under this category!\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
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
        document.getElementById('categoryModal')
            .addEventListener('shown.bs.modal', update);
        update();
    }

    bindCounter('meta_title',       'catMetaTitleCount', 60);
    bindCounter('meta_description', 'catMetaDescCount',  160);
})();

</script>
EOD;

include 'includes/footer.php';
?>