<?php
/**
 * ColourTech Industries - Contact Inquiries Management
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle = 'Contact Inquiries';
$breadcrumbs = ['Contact Inquiries' => ''];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Inquiry Status
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('inquiries.php');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');

        if ($id && in_array($status, ['new', 'read', 'replied', 'archived'])) {
            $stmt = $pdo->prepare("UPDATE contact_inquiries SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            logActivity($_SESSION['admin_id'], 'updated', 'contact_inquiries', $id, "Updated inquiry status to: $status");
            setFlashMessage('success', 'Inquiry status updated successfully!');
        }

        redirect('inquiries.php');
    }

    // Delete Inquiry
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('inquiries.php');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM contact_inquiries WHERE id = ?");
            $stmt->execute([$id]);
            $inquiry = $stmt->fetch();

            if ($inquiry) {
                $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
                $stmt->execute([$id]);

                logActivity($_SESSION['admin_id'], 'deleted', 'contact_inquiries', $id, "Deleted inquiry from: " . $inquiry['name']);
                setFlashMessage('success', 'Inquiry deleted successfully!');
            }
        }

        redirect('inquiries.php');
    }

    // Bulk Action
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_action') {

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect('inquiries.php');
        }

        $bulk_action = sanitize($_POST['bulk_action'] ?? '');
        $selected_ids = json_decode($_POST['bulk_selected_ids'] ?? '[]', true) ?: [];

        if (!empty($selected_ids) && !empty($bulk_action)) {
            $selected_ids = array_map('intval', $selected_ids);
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

            if ($bulk_action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                setFlashMessage('success', count($selected_ids) . ' inquiry/inquiries deleted successfully!');
            } elseif (in_array($bulk_action, ['new', 'read', 'replied', 'archived'])) {
                $stmt = $pdo->prepare("UPDATE contact_inquiries SET status = ? WHERE id IN ($placeholders)");
                $stmt->execute(array_merge([$bulk_action], $selected_ids));
                setFlashMessage('success', count($selected_ids) . ' inquiry/inquiries updated to "' . $bulk_action . '"!');
            }
        } else {
            setFlashMessage('warning', 'Please select at least one inquiry and a bulk action.');
        }

        redirect('inquiries.php');
    }
}

// Filters
$statusFilter = sanitize($_GET['status'] ?? '');
$searchFilter = sanitize($_GET['search'] ?? '');

// Build query
$whereConditions = [];
$queryParams = [];

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $queryParams[] = $statusFilter;
}

if (!empty($searchFilter)) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchTerm = "%$searchFilter%";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereSQL = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get inquiries
$stmt = $pdo->prepare("
    SELECT * FROM contact_inquiries
    $whereSQL
    ORDER BY created_at DESC
");
$stmt->execute($queryParams);
$inquiries = $stmt->fetchAll();

// Count by status
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) as count FROM contact_inquiries GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalCount = array_sum($statusCounts);
$newCount = $statusCounts['new'] ?? 0;
$readCount = $statusCounts['read'] ?? 0;
$repliedCount = $statusCounts['replied'] ?? 0;
$archivedCount = $statusCounts['archived'] ?? 0;

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Contact Inquiries</h4>
            <p class="mb-0">Manage and respond to customer contact inquiries</p>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <i class="fas fa-inbox"></i>
            </div>
            <h3><?php echo $totalCount; ?></h3>
            <p>Total Inquiries</p>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <i class="fas fa-envelope"></i>
            </div>
            <h3><?php echo $newCount; ?></h3>
            <p>New Inquiries</p>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <i class="fas fa-envelope-open"></i>
            </div>
            <h3><?php echo $readCount; ?></h3>
            <p>Read</p>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                <i class="fas fa-reply"></i>
            </div>
            <h3><?php echo $repliedCount; ?></h3>
            <p>Replied</p>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Search by name, email, subject..."
                    value="<?php echo htmlspecialchars($searchFilter); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Status</label>
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New
                        (<?php echo $newCount; ?>)</option>
                    <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read
                        (<?php echo $readCount; ?>)</option>
                    <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied
                        (<?php echo $repliedCount; ?>)</option>
                    <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived
                        (<?php echo $archivedCount; ?>)</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
                <a href="inquiries.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inquiries Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5>
                <?php if (!empty($statusFilter) || !empty($searchFilter)): ?>
                    Filtered Results (<?php echo count($inquiries); ?>)
                <?php else: ?>
                    All Inquiries (<?php echo count($inquiries); ?>)
                <?php endif; ?>
            </h5>
        </div>
    </div>
    <div class="card-body">

        <!-- Bulk Action Bar (its own form, outside the table) -->
        <form method="POST" id="bulkForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="bulk_action">
            <input type="hidden" name="bulk_selected_ids" id="bulkSelectedIds">

            <div class="d-flex align-items-center gap-3 mb-3" id="bulkActionBar" style="display:none;">
                <span class="text-muted" id="selectedCount">0 selected</span>
                <select class="form-select form-select-sm" name="bulk_action" style="width:auto;">
                    <option value="">-- Bulk Action --</option>
                    <option value="read">Mark as Read</option>
                    <option value="replied">Mark as Replied</option>
                    <option value="archived">Mark as Archived</option>
                    <option value="new">Mark as New</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-danger" onclick="return prepareBulkSubmit()">
                    <i class="fas fa-check me-1"></i>Apply
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th width="40">#</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Message Preview</th>
                        <th>Phone</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No inquiries found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($inquiries as $index => $inquiry): ?>
                        <tr class="<?php echo $inquiry['status'] === 'new' ? 'table-warning' : ''; ?>">
                            <td>
                                <input type="checkbox" class="form-check-input row-checkbox"
                                    value="<?php echo $inquiry['id']; ?>">
                            </td>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong>
                                <br>
                                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>"
                                    class="text-muted small">
                                    <i></i><?php echo htmlspecialchars($inquiry['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo $inquiry['subject'] ? htmlspecialchars($inquiry['subject']) : '<span class="text-muted">—</span>'; ?>
                            </td>
                            <td>
                                <span
                                    class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth(strip_tags($inquiry['message']), 0, 80, '...')); ?></span>
                            </td>
                            <td>
                                <?php if ($inquiry['phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($inquiry['phone']); ?>" class="text-muted small">
                                        <i></i><?php echo htmlspecialchars($inquiry['phone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo date('d M Y', strtotime($inquiry['created_at'])); ?></small>
                                <br>
                                <small
                                    class="text-muted"><?php echo date('h:i A', strtotime($inquiry['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $statusConfig = [
                                    'new' => ['bg-danger', 'New'],
                                    'read' => ['bg-info', 'Read'],
                                    'replied' => ['bg-success', 'Replied'],
                                    'archived' => ['bg-secondary', 'Archived'],
                                ];
                                $cfg = $statusConfig[$inquiry['status']] ?? ['bg-secondary', ucfirst($inquiry['status'])];
                                echo '<span class="badge ' . $cfg[0] . '">' . $cfg[1] . '</span>';
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary"
                                    onclick="viewInquiry(<?php echo htmlspecialchars(json_encode($inquiry)); ?>)"
                                    title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-info"
                                    onclick="changeStatus(<?php echo $inquiry['id']; ?>, '<?php echo $inquiry['status']; ?>')"
                                    title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteInquiry(<?php echo $inquiry['id']; ?>, '<?php echo htmlspecialchars($inquiry['name']); ?>')"
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

<!-- View Inquiry Modal -->
<div class="modal fade" id="viewInquiryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope me-2"></i>Inquiry Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Sender Name</label>
                        <p class="fw-bold mb-0" id="viewName">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <p class="mb-0" id="viewStatus">—</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Email Address</label>
                        <p class="mb-0" id="viewEmail">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Phone Number</label>
                        <p class="mb-0" id="viewPhone">—</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Subject</label>
                        <p class="mb-0" id="viewSubject">—</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Received On</label>
                        <p class="mb-0" id="viewDate">—</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Message</label>
                    <div class="p-3 bg-light rounded" id="viewMessage" style="white-space:pre-wrap; min-height:80px;">—
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">IP Address</label>
                    <p class="mb-0 text-muted small" id="viewIP">—</p>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <a href="#" id="replyEmailBtn" class="btn btn-success">
                        <i class="fas fa-reply me-2"></i>Reply via Email
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="markReadBtn" onclick="markAsReadFromModal()">
                        <i class="fas fa-check me-2"></i>Mark as Read
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" id="statusForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="statusInquiryId">

                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Select New Status</label>
                    <select class="form-select" name="status" id="newStatus">
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Mark as Read Form (hidden, used from view modal) -->
<form method="POST" id="markReadForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="status" value="read">
    <input type="hidden" name="id" id="markReadId">
</form>

<?php
$extraJS = <<<'EOD'
<script>
// ---- View Inquiry ----
let currentInquiryId = null;
let currentInquiryStatus = null;

function viewInquiry(inquiry) {
    currentInquiryId = inquiry.id;
    currentInquiryStatus = inquiry.status;

    const statusLabels = {
        new: '<span class="badge bg-danger">New</span>',
        read: '<span class="badge bg-info">Read</span>',
        replied: '<span class="badge bg-success">Replied</span>',
        archived: '<span class="badge bg-secondary">Archived</span>'
    };

    document.getElementById('viewName').textContent    = inquiry.name    || '—';
    document.getElementById('viewStatus').innerHTML   = statusLabels[inquiry.status] || inquiry.status;
    document.getElementById('viewEmail').innerHTML    = `<a href="mailto:${inquiry.email}">${inquiry.email}</a>`;
    document.getElementById('viewPhone').textContent  = inquiry.phone   || '—';
    document.getElementById('viewSubject').textContent = inquiry.subject || '—';
    document.getElementById('viewMessage').textContent = inquiry.message || '—';
    document.getElementById('viewIP').textContent     = inquiry.ip_address || '—';
    document.getElementById('replyEmailBtn').href     = `mailto:${inquiry.email}?subject=Re: ${encodeURIComponent(inquiry.subject || 'Your Inquiry')}`;

    // Format date
    const d = new Date(inquiry.created_at.replace(' ', 'T'));
    document.getElementById('viewDate').textContent = d.toLocaleString('en-IN', {
        day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true
    });

    // Show/hide Mark as Read button
    const markBtn = document.getElementById('markReadBtn');
    if (inquiry.status === 'new') {
        markBtn.style.display = 'inline-block';
    } else {
        markBtn.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('viewInquiryModal')).show();
}

function markAsReadFromModal() {
    document.getElementById('markReadId').value = currentInquiryId;
    document.getElementById('markReadForm').submit();
}

// ---- Change Status ----
function changeStatus(id, currentStatus) {
    document.getElementById('statusInquiryId').value = id;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

// ---- Delete ----
function deleteInquiry(id, name) {
    if (confirm(`Are you sure you want to delete the inquiry from "${name}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// ---- Select All / Bulk Actions ----
document.getElementById('selectAll').addEventListener('change', function () {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});

document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    const bar = document.getElementById('bulkActionBar');
    document.getElementById('selectedCount').textContent = checked + ' selected';
    bar.style.display = checked > 0 ? 'flex' : '';
}

function prepareBulkSubmit() {
    const action = document.querySelector('[name="bulk_action"]').value;
    if (!action) { alert('Please select a bulk action.'); return false; }

    // Collect checked IDs and store them in the hidden field
    const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) { alert('Please select at least one inquiry.'); return false; }
    document.getElementById('bulkSelectedIds').value = JSON.stringify(ids);

    if (action === 'delete') {
        return confirm('Are you sure you want to delete the selected inquiries? This cannot be undone.');
    }
    return true;
}
</script>
EOD;

include 'includes/footer.php';
?>