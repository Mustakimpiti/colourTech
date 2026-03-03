<?php
/**
 * ColourTech Industries - Inquiries Overview (Dashboard)
 */
session_start();
define('ADMIN_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/inquiry_helpers.php';

$pageTitle   = 'Contact Inquiries';
$breadcrumbs = ['Contact Inquiries' => ''];

// Handle POST (overview page can also process actions, redirects back here)
handleInquiryPost($pdo, 'inquiries.php');

// Counts
$statusCounts = fetchStatusCounts($pdo);
$typeCounts   = fetchTypeCounts($pdo);
$totalCount   = $typeCounts['total'];
$newCount     = $statusCounts['new']      ?? 0;
$readCount    = $statusCounts['read']     ?? 0;
$repliedCount = $statusCounts['replied']  ?? 0;
$archivedCount= $statusCounts['archived'] ?? 0;

// Recent 10 inquiries for the overview table
$recentInquiries = $pdo->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC LIMIT 10")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4>Contact Inquiries</h4>
            <p class="mb-0">Overview of all colour sample requests, datasheet downloads, and general enquiries</p>
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
            <p>New / Unread</p>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #e6a817, #c88a00);">
                <i class="fas fa-paint-brush"></i>
            </div>
            <h3><?php echo $typeCounts['sample']; ?></h3>
            <p>Sample Requests</p>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                <i class="fas fa-file-arrow-down"></i>
            </div>
            <h3><?php echo $typeCounts['pdf']; ?></h3>
            <p>PDF Downloads</p>
        </div>
    </div>
</div>

<!-- Quick Navigation Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <a href="inquiries-contact.php" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="transition: transform .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon mb-0" style="background: linear-gradient(135deg, #6c757d, #495057); width:50px; height:50px; flex-shrink:0;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">General Contact</h5>
                        <p class="mb-0 text-muted"><?php echo $typeCounts['contact']; ?> inquir<?php echo $typeCounts['contact'] === 1 ? 'y' : 'ies'; ?></p>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="inquiries-samples.php" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="transition: transform .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon mb-0" style="background: linear-gradient(135deg, #e6a817, #c88a00); width:50px; height:50px; flex-shrink:0;">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Sample Requests</h5>
                        <p class="mb-0 text-muted"><?php echo $typeCounts['sample']; ?> request<?php echo $typeCounts['sample'] === 1 ? '' : 's'; ?></p>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 mb-3">
        <a href="inquiries-pdf.php" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="transition: transform .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stats-icon mb-0" style="background: linear-gradient(135deg, #0d6efd, #0a58ca); width:50px; height:50px; flex-shrink:0;">
                        <i class="fas fa-file-arrow-down"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">PDF Downloads</h5>
                        <p class="mb-0 text-muted"><?php echo $typeCounts['pdf']; ?> download<?php echo $typeCounts['pdf'] === 1 ? '' : 's'; ?></p>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Recent 10 Inquiries -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Inquiries</h5>
        <div class="d-flex gap-2">
            <a href="inquiries-contact.php" class="btn btn-sm btn-outline-secondary">Contact</a>
            <a href="inquiries-samples.php" class="btn btn-sm btn-outline-warning">Samples</a>
            <a href="inquiries-pdf.php"     class="btn btn-sm btn-outline-primary">PDF</a>
        </div>
    </div>
    <div class="card-body">
        <?php renderInquiryTable($recentInquiries, generateCSRFToken(), true); ?>
    </div>
</div>

<?php renderInquiryModalsAndJS(generateCSRFToken()); ?>
<?php include 'includes/footer.php'; ?>