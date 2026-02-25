<?php
$currentPage = 'product';

// Get slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

include 'header.php';

// Fetch category
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active'");
$catStmt->execute([$slug]);
$category = $catStmt->fetch();
if (!$category) {
    header('Location: index.php');
    exit;
}

$categoryName = html_entity_decode($category['name']);
$categoryId = $category['id'];

// Fetch sub-categories with their primary image
$subStmt = $pdo->prepare("
    SELECT 
        sc.*,
        sci.image_path AS primary_image
    FROM sub_categories sc
    LEFT JOIN sub_category_images sci 
        ON sci.sub_category_id = sc.id 
        AND sci.is_primary = 1
    WHERE sc.category_id = ? AND sc.status = 'active'
    ORDER BY sc.sort_order ASC
");
$subStmt->execute([$categoryId]);
$subCategories = $subStmt->fetchAll();

// Breadcrumb background
$breadcrumbBg = !empty($category['image'])
    ? 'admin/uploads/categories/' . $category['image']
    : 'assets/imgs/breadcrumb/paint-coating.jpg';
?>

<main>
    <!-- Breadcrumb area start -->
    <div
        class="breadcrumb__area header__background-color breadcrumb__header-up breadcrumb-space overly overflow-hidden">
        <div class="breadcrumb__background" data-background="<?php echo htmlspecialchars($breadcrumbBg); ?>"></div>
        <div class="container">
            <div class="breadcrumb__bg-left"></div>
            <div class="breadcrumb__bg-right"></div>
            <div class="row align-items-center justify-content-between">
                <div class="col-12">
                    <div class="breadcrumb__content text-center">
                        <h2 class="breadcrumb__title mb-15 mb-sm-10 mb-xs-5 color-white title-animation">
                            <?php echo htmlspecialchars($categoryName); ?>
                        </h2>
                        <div class="breadcrumb__menu">
                            <nav>
                                <ul>
                                    <li><span><a href="index.php">Home</a></span></li>
                                    <li class="active"><span><?php echo htmlspecialchars($categoryName); ?></span></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb area end -->

    <!-- Sub-categories area start -->
    <section class="about-us section-space">
        <div class="container">
            <div class="row">
                <div class="section__title-wrapper text-center mb-55 mb-xs-40">
                    <h2 class="section__title title-animation">
                        <?php echo htmlspecialchars($categoryName); ?>
                    </h2>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div>
                        <div>
                            <?php if (!empty($subCategories)): ?>
                                <div class="row">
                                    <?php foreach ($subCategories as $index => $sub):
                                        $subName = html_entity_decode($sub['name']);
                                        $subNumber = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                                        $subLink = 'sub-category.php?slug=' . urlencode($sub['slug']);

                                        if (!empty($sub['primary_image'])) {
                                            $imgSrc = 'admin/uploads/sub-categories/' . $sub['primary_image'];
                                        } else {
                                            $imgSrc = 'assets/imgs/products/placeholder.jpg';
                                        }
                                        ?>
                                        <div class="col-md-6 col-lg-4 col-xl-4" style="padding-top: 30px;">
                                            <div class="projects__item">
                                                <div class="has--overlay"></div>
                                                <div class="has--overlay-2"></div>
                                                <div class="projects__media">
                                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                                        alt="<?php echo htmlspecialchars($subName); ?>"
                                                        style="width:100%; height:100%; object-fit:cover;">
                                                </div>

                                                <div class="projects__text-top">
                                                    <h2><?php echo $subNumber; ?></h2>
                                                    <h6><a
                                                            href="<?php echo $subLink; ?>"><?php echo htmlspecialchars($subName); ?></a>
                                                    </h6>
                                                </div>
                                                <div class="projects__text-bottom">
                                                    <h2><?php echo $subNumber; ?></h2>
                                                    <h6><a
                                                            href="<?php echo $subLink; ?>"><?php echo htmlspecialchars($subName); ?></a>
                                                    </h6>
                                                </div>

                                                <a class="projects__arrow" href="<?php echo $subLink; ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1 8.00018H15" stroke="#906E50" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M8 1.00018L15 8.00018L8 15.0002" stroke="#906E50"
                                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php else: ?>
                                <div class="row">
                                    <div class="col-12 text-center py-5">
                                        <p style="font-size: 1.2rem; color: #666;">No sub-categories found.</p>
                                        <a href="index.php" class="rr-btn mt-30">
                                            <span class="btn-wrap">
                                                <span class="text-one">Back to Home</span>
                                                <span class="text-two">Back to Home</span>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Sub-categories area end -->
</main>

<?php include 'footer.php'; ?>