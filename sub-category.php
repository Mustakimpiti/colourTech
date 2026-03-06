<?php
// ─── Page identity ────────────────────────────────────────────────────────────
$currentPage = 'product';

// ─── Slug validation ──────────────────────────────────────────────────────────
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/db.php'; // adjust if needed

// ─── Fetch sub-category with parent category info ─────────────────────────────
$subStmt = $pdo->prepare("
    SELECT sc.*, c.name AS category_name, c.slug AS category_slug
    FROM sub_categories sc
    JOIN categories c ON c.id = sc.category_id
    WHERE sc.slug = ? AND sc.status = 'active'
");
$subStmt->execute([$slug]);
$subCategory = $subStmt->fetch();
if (!$subCategory) {
    header('Location: index.php');
    exit;
}

$subCategoryName = html_entity_decode($subCategory['name']);
$subCategoryId   = $subCategory['id'];
$categoryName    = html_entity_decode($subCategory['category_name']);
$categorySlug    = $subCategory['category_slug'];

// ─── Features (JSON) ──────────────────────────────────────────────────────────
$features = [];
if (!empty($subCategory['features'])) {
    $decoded = json_decode($subCategory['features'], true);
    if (is_array($decoded)) $features = $decoded;
}

// ─── Fetch sub-category images ────────────────────────────────────────────────
$imgStmt = $pdo->prepare("
    SELECT image_path, is_primary
    FROM sub_category_images
    WHERE sub_category_id = ?
    ORDER BY is_primary DESC, sort_order ASC
");
$imgStmt->execute([$subCategoryId]);
$subCatImages = $imgStmt->fetchAll();

// Primary image used for breadcrumb bg and OG image
$breadcrumbBg  = !empty($subCatImages[0]['image_path'])
    ? 'admin/uploads/sub-categories/' . $subCatImages[0]['image_path']
    : 'assets/imgs/breadcrumb/paint-coating.jpg';

// Skip first (used in header), show next 2 inline
$contentImages = array_slice($subCatImages, 1, 2);

// ─── Meta tags — DB values with smart fallbacks ───────────────────────────────
$metaTitle = !empty($subCategory['meta_title'])
    ? $subCategory['meta_title']
    : $subCategoryName . ' | ' . $categoryName . ' | ColourTech Industries';

$metaDescription = !empty($subCategory['meta_description'])
    ? $subCategory['meta_description']
    : (!empty($subCategory['short_description'])
        ? substr(strip_tags(html_entity_decode($subCategory['short_description'])), 0, 160)
        : 'ColourTech Industries offers premium ' . $subCategoryName . ' for ' . $categoryName . ' applications. Request a sample today.');

$metaKeywords = !empty($subCategory['meta_keywords'])
    ? $subCategory['meta_keywords']
    : implode(', ', array_filter([
        $subCategoryName,
        $categoryName,
        $subCategoryName . ' ' . $categoryName,
        'ColourTech Industries',
        'pigment manufacturer',
        'industrial pigments',
    ]));

// ─── OG image ─────────────────────────────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'];

$metaOgImage = !empty($subCatImages[0]['image_path'])
    ? $baseUrl . '/admin/uploads/sub-categories/' . $subCatImages[0]['image_path']
    : $baseUrl . '/assets/imgs/og-default.jpg';

// ─── Include header (meta vars must be set before this) ───────────────────────
include 'header.php';

// ─── Fetch active products for this sub-category ──────────────────────────────
$prodStmt = $pdo->prepare("
    SELECT p.*, pi.image_path AS gallery_image
    FROM products p
    LEFT JOIN product_images pi
        ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE p.sub_category_id = ? AND p.status = 'active'
    ORDER BY p.sort_order ASC
");
$prodStmt->execute([$subCategoryId]);
$products = $prodStmt->fetchAll();

// ─── Border / padding class cycling for product grid ─────────────────────────
$borderClasses  = ['has--border-1', 'has--border-2', 'has--border-3'];
$paddingClasses = [];
$total          = count($products);

for ($i = 0; $i < $total; $i++) {
    $row       = floor($i / 3);
    $totalRows = ceil($total / 3);

    if ($row === 0 && $totalRows === 1) {
        $paddingClasses[$i] = 'has--padding';
    } elseif ($row === 0) {
        $paddingClasses[$i] = 'has--padding-pb';
    } elseif ($row === $totalRows - 1) {
        $paddingClasses[$i] = 'has--padding';
    } else {
        $paddingClasses[$i] = 'has--padding-ptb';
    }
}
?>

<main>

    <!-- ─── Breadcrumb ───────────────────────────────────────────────────────── -->
    <div class="breadcrumb__area header__background-color breadcrumb__header-up breadcrumb-space overly overflow-hidden">
        <div class="breadcrumb__background" data-background="<?php echo htmlspecialchars($breadcrumbBg); ?>"></div>
        <div class="container">
            <div class="breadcrumb__bg-left"></div>
            <div class="breadcrumb__bg-right"></div>
            <div class="row align-items-center justify-content-between">
                <div class="col-12">
                    <div class="breadcrumb__content text-center">
                        <!-- intentionally left minimal — matches original -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb end -->

    <!-- ─── Sub-category detail ──────────────────────────────────────────────── -->
    <section class="blog-details section-space">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="blog-details__content">

                        <h4><?php echo htmlspecialchars($subCategoryName); ?></h4>

                        <?php if (!empty($subCategory['short_description'])): ?>
                            <p style="text-align:justify;">
                                <?php echo nl2br(htmlspecialchars(html_entity_decode($subCategory['short_description']))); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($subCategory['full_description'])): ?>
                            <p style="text-align:justify;">
                                <?php echo nl2br(htmlspecialchars(html_entity_decode($subCategory['full_description']))); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($contentImages)): ?>
                            <div class="row">
                                <?php foreach ($contentImages as $img): ?>
                                    <div class="col-xl-6 blog-details__media">
                                        <img src="admin/uploads/sub-categories/<?php echo htmlspecialchars($img['image_path']); ?>"
                                             alt="<?php echo htmlspecialchars($subCategoryName); ?>"
                                             style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- ─── Features list ────────────────────────────────────────────── -->
            <?php if (!empty($features)): ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="protfolio-details">
                            <h4>Features</h4>
                            <div class="about-company__wrapper">
                                <ul>
                                    <?php foreach ($features as $feature): ?>
                                        <li>
                                            <span>
                                                <svg width="14" height="10" viewBox="0 0 14 10" fill="none"
                                                     xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13 1L4.75 9L1 5.36364" stroke="#906E50" stroke-width="1.5"
                                                          stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                            <?php echo htmlspecialchars($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- ─── Products grid ────────────────────────────────────────────────────── -->
    <?php if (!empty($products)): ?>
        <section class="what-we-do section-space__bottom">
            <div class="container">
                <div class="what-we-do__wrapper">
                    <div class="what-we-do__bg">
                        <img src="assets/imgs/what-we-do/shape.png" alt="">
                    </div>
                    <div class="row">
                        <?php foreach ($products as $i => $product):
                            $productName  = html_entity_decode($product['name']);
                            $productLink  = 'product.php?slug=' . urlencode($product['slug']);
                            $colorCode    = !empty($product['color_code']) ? $product['color_code'] : '#cccccc';
                            $borderClass  = $borderClasses[$i % 3];
                            $paddingClass = $paddingClasses[$i];
                        ?>
                            <div class="col-xl-4 has--border <?php echo $borderClass; ?>">
                                <div class="what-we-do__item <?php echo $paddingClass; ?>">

                                    <!-- Colour swatch or product image -->
                                    <div class="what-we-do__item-icon">
                                        <a href="<?php echo $productLink; ?>">
                                            <?php if (!empty($product['gallery_image'])): ?>
                                                <img src="admin/uploads/products/<?php echo htmlspecialchars($product['gallery_image']); ?>"
                                                     alt="<?php echo htmlspecialchars($productName); ?>"
                                                     style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                                            <?php else: ?>
                                                <div class="why-choose-us-2__icon"
                                                     style="background-color:<?php echo htmlspecialchars($colorCode); ?>;">
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                    </div>

                                    <div class="text">
                                        <a href="<?php echo $productLink; ?>">
                                            <span class="section__subtitle color-black">
                                                <?php echo htmlspecialchars($productName); ?>
                                            </span>
                                        </a>
                                        <?php if (!empty($product['color_name'])): ?>
                                            <p class="what-we-do__item-desc">
                                                <?php echo htmlspecialchars($product['color_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>