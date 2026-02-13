<?php
/**
 * ColourTech Industries - Admin Dashboard
 */

session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';

$pageTitle = 'Dashboard';
$breadcrumbs = [];

// Get statistics
$stats = [];

// Total Categories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'");
$stats['categories'] = $stmt->fetchColumn();

// Total Sub-Categories
$stmt = $pdo->query("SELECT COUNT(*) FROM sub_categories WHERE status = 'active'");
$stats['sub_categories'] = $stmt->fetchColumn();

// Total Products
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$stats['products'] = $stmt->fetchColumn();

// Total Inquiries
$stmt = $pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'new'");
$stats['inquiries'] = $stmt->fetchColumn();

// Recent Products
$recentProducts = $pdo->query("
    SELECT p.*, sc.name as sub_category_name, c.name as category_name
    FROM products p
    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
    LEFT JOIN categories c ON sc.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

// Recent Inquiries
$recentInquiries = $pdo->query("
    SELECT * FROM contact_inquiries
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// Recent Activity Logs
$recentLogs = $pdo->query("
    SELECT al.*, a.full_name as admin_name
    FROM activity_logs al
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h4>Welcome back, <?php echo htmlspecialchars($currentAdmin['full_name']); ?>! 👋</h4>
    <p>Here's what's happening with your ColourTech Industries today.</p>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-th-large"></i>
            </div>
            <h3><?php echo $stats['categories']; ?></h3>
            <p>Active Categories</p>
            <a href="categories.php" class="btn btn-sm btn-outline-primary mt-2">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3><?php echo $stats['sub_categories']; ?></h3>
            <p>Sub-Categories</p>
            <a href="sub-categories.php" class="btn btn-sm btn-outline-primary mt-2">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-box-open"></i>
            </div>
            <h3><?php echo $stats['products']; ?></h3>
            <p>Total Products</p>
            <a href="products.php" class="btn btn-sm btn-outline-primary mt-2">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-comments"></i>
            </div>
            <h3><?php echo $stats['inquiries']; ?></h3>
            <p>New Inquiries</p>
            <a href="inquiries.php" class="btn btn-sm btn-outline-primary mt-2">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Products -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-box-open me-2"></i>Recent Products</h5>
                <a href="products.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentProducts)): ?>
                    <p class="text-muted text-center py-4">No products added yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($product['color_code']): ?>
                                                    <div style="width: 30px; height: 30px; background: <?php echo $product['color_code']; ?>; border-radius: 5px; margin-right: 10px; border: 1px solid #ddd;"></div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <?php if ($product['product_code']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($product['product_code']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($product['category_name']); ?></small><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($product['sub_category_name']); ?></small>
                                        </td>
                                        <td><?php echo getStatusBadge($product['status']); ?></td>
                                        <td><small><?php echo formatDate($product['created_at']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Inquiries -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-comments me-2"></i>Recent Inquiries</h5>
                <a href="inquiries.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentInquiries)): ?>
                    <p class="text-muted text-center py-4">No inquiries yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInquiries as $inquiry): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong>
                                            <?php if ($inquiry['subject']): ?>
                                                <br><small class="text-muted"><?php echo truncateText($inquiry['subject'], 30); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($inquiry['email']); ?></small></td>
                                        <td><?php echo getStatusBadge($inquiry['status']); ?></td>
                                        <td><small><?php echo timeAgo($inquiry['created_at']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                <a href="activity-logs.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentLogs)): ?>
                    <p class="text-muted text-center py-4">No activity logs yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Description</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $log['action'] === 'created' ? 'success' : 
                                                    ($log['action'] === 'updated' ? 'info' : 
                                                    ($log['action'] === 'deleted' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($log['table_name']); ?></code></td>
                                        <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                        <td><small><?php echo timeAgo($log['created_at']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>