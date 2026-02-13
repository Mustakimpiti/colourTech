<?php
/**
 * Admin Panel Header
 */
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

requireLogin();
$currentAdmin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-color: #BD0BBD;
            --secondary-color: #ebb80a;
            --sidebar-bg: #1a1a2e;
            --sidebar-hover: #16213e;
            --topbar-bg: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: var(--sidebar-bg);
            padding-top: 20px;
            transition: all 0.3s;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .sidebar-logo {
            padding: 0 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .sidebar-logo small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 0;
            margin-bottom: 5px;
        }
        
        .menu-item a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item a i {
            width: 25px;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .menu-item a:hover,
        .menu-item a.active {
            background: var(--sidebar-hover);
            color: white;
            padding-left: 30px;
        }
        
        .menu-item a.active {
            border-left: 4px solid var(--primary-color);
            background: linear-gradient(90deg, rgba(189, 11, 189, 0.2) 0%, transparent 100%);
        }
        
        .menu-label {
            padding: 20px 20px 10px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Top Bar */
        .topbar {
            background: var(--topbar-bg);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .topbar-left h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .breadcrumb {
            margin: 0;
            background: transparent;
            padding: 0;
            font-size: 0.9rem;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .admin-details h6 {
            margin: 0;
            font-size: 0.95rem;
            color: #333;
        }
        
        .admin-details small {
            color: #666;
            font-size: 0.8rem;
        }
        
        /* Page Content */
        .page-content {
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h4 {
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: #666;
            margin: 0;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-header h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(189, 11, 189, 0.3);
        }
        
        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 15px;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stats-card p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }
        
        /* Table Styles */
        .table {
            background: white;
        }
        
        .table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
            background: #f8f9fa;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control,
        .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(189, 11, 189, 0.25);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding-top: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .topbar {
                padding: 15px;
            }
            
            .page-content {
                padding: 15px;
            }
        }
    </style>
    
    <?php if (isset($extraCSS)): ?>
        <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <h4><i class="fas fa-palette"></i> ColourTech</h4>
            <small>Admin Panel</small>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-item">
                <a href="index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="menu-label">Product Management</div>
            
            <div class="menu-item">
                <a href="categories.php" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Categories</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="sub-categories.php" class="<?php echo $currentPage === 'sub-categories' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Sub-Categories</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="products.php" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Products</span>
                </a>
            </div>
            
            <!-- <div class="menu-label">Content Management</div>
            
            <div class="menu-item">
                <a href="newsletters.php" class="<?php echo $currentPage === 'newsletters' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Newsletters</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="inquiries.php" class="<?php echo $currentPage === 'inquiries' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>Contact Inquiries</span>
                </a>
            </div>
            
            <div class="menu-label">Settings</div>
            
            <div class="menu-item">
                <a href="site-settings.php" class="<?php echo $currentPage === 'site-settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Site Settings</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="admins.php" class="<?php echo $currentPage === 'admins' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Admin Users</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="activity-logs.php" class="<?php echo $currentPage === 'activity-logs' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
            </div>
            
            <div class="menu-item">
                <a href="profile.php" class="<?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </div> -->
            
            <div class="menu-item">
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h5><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $label => $url): ?>
                                <?php if ($url): ?>
                                    <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo $label; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            
            <div class="topbar-right">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($currentAdmin['full_name'], 0, 2)); ?>
                    </div>
                    <div class="admin-details">
                        <h6><?php echo htmlspecialchars($currentAdmin['full_name']); ?></h6>
                        <small><?php echo ucfirst($currentAdmin['role']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="page-content">
            <?php
            // Display flash messages
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php
                    $icons = [
                        'success' => 'fa-check-circle',
                        'error' => 'fa-exclamation-circle',
                        'warning' => 'fa-exclamation-triangle',
                        'info' => 'fa-info-circle'
                    ];
                    $icon = $icons[$flash['type']] ?? 'fa-info-circle';
                    ?>
                    <i class="fas <?php echo $icon; ?> me-2"></i>
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>