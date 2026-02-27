<?php
/**
 * ColourTech Industries - Newsletter Subscribers Management
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle = 'Newsletter Subscribers';
$breadcrumbs = ['Newsletter Subscribers' => ''];

// Handle unsubscribe / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('newsletters.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');

    if ($id && $action === 'unsubscribe') {
        $pdo->prepare("UPDATE newsletters SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE id = ?")
            ->execute([$id]);
        setFlashMessage('success', 'Subscriber marked as unsubscribed.');
    } elseif ($id && $action === 'resubscribe') {
        $pdo->prepare("UPDATE newsletters SET status = 'active', unsubscribed_at = NULL WHERE id = ?")
            ->execute([$id]);
        setFlashMessage('success', 'Subscriber re-activated.');
    } elseif ($id && $action === 'delete') {
        $pdo->prepare("DELETE FROM newsletters WHERE id = ?")->execute([$id]);
        setFlashMessage('success', 'Subscriber deleted.');
    }
    redirect('newsletters.php');
}

// Counts
$activeCount = $pdo->query("SELECT COUNT(*) FROM newsletters WHERE status = 'active'")->fetchColumn();
$unsubscribedCount = $pdo->query("SELECT COUNT(*) FROM newsletters WHERE status = 'unsubscribed'")->fetchColumn();
$totalCount = $activeCount + $unsubscribedCount;

// Filter
$statusFilter = sanitize($_GET['status'] ?? '');
$searchFilter = sanitize($_GET['search'] ?? '');

$where = [];
$params = [];
if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if ($searchFilter) {
    $where[] = "email LIKE ?";
    $params[] = "%$searchFilter%";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM newsletters $whereSQL ORDER BY subscribed_at DESC");
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Newsletter Subscribers</h4>
            <p class="mb-0">Manage email subscribers for ColourTech Industries newsletter</p>
        </div>
        <a href="newsletters.php?export=csv" class="btn btn-success">
            <i class="fas fa-download me-2"></i>Export CSV
        </a>
    </div>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #6c757d, #495057);">
                <i class="fas fa-users"></i>
            </div>
            <h3><?php echo $totalCount; ?></h3>
            <p>Total Subscribers</p>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                <i class="fas fa-envelope-circle-check"></i>
            </div>
            <h3><?php echo $activeCount; ?></h3>
            <p>Active</p>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <i class="fas fa-envelope-open"></i>
            </div>
            <h3><?php echo $unsubscribedCount; ?></h3>
            <p>Unsubscribed</p>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search Email</label>
                <input type="text" class="form-control" name="search" placeholder="Search by email..."
                    value="<?php echo htmlspecialchars($searchFilter); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All (<?php echo $totalCount; ?>)</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active
                        (<?php echo $activeCount; ?>)</option>
                    <option value="unsubscribed" <?php echo $statusFilter === 'unsubscribed' ? 'selected' : ''; ?>>
                        Unsubscribed (<?php echo $unsubscribedCount; ?>)</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
                <a href="newsletters.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h5>
            <?php echo (!empty($statusFilter) || !empty($searchFilter)) ? 'Filtered Results' : 'All Subscribers'; ?>
            (<?php echo count($subscribers); ?>)
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email Address</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>Subscribed On</th>
                        <th>Unsubscribed On</th>
                        <th width="130">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-envelope fa-2x mb-2 d-block"></i>No subscribers found.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($subscribers as $i => $sub): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($sub['email']); ?>">
                                    <?php echo htmlspecialchars($sub['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unsubscribed</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($sub['ip_address'] ?? '—'); ?></small>
                            </td>
                            <td>
                                <small><?php echo date('d M Y, h:i A', strtotime($sub['subscribed_at'])); ?></small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo $sub['unsubscribed_at'] ? date('d M Y, h:i A', strtotime($sub['unsubscribed_at'])) : '—'; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($sub['status'] === 'active'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="unsubscribe">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Unsubscribe"
                                            onclick="return confirm('Mark this subscriber as unsubscribed?')">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="resubscribe">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Re-activate"
                                            onclick="return confirm('Re-activate this subscriber?')">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                                        onclick="return confirm('Permanently delete this subscriber?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// --- CSV Export ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allSubs = $pdo->query("SELECT email, status, ip_address, subscribed_at, unsubscribed_at FROM newsletters ORDER BY subscribed_at DESC")->fetchAll();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="colourtech-newsletter-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Status', 'IP Address', 'Subscribed On', 'Unsubscribed On']);
    foreach ($allSubs as $row) {
        fputcsv($out, [
            $row['email'],
            $row['status'],
            $row['ip_address'] ?? '',
            $row['subscribed_at'],
            $row['unsubscribed_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

include 'includes/footer.php';
?>