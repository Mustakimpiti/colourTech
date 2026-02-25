<?php
$currentPage = 'product';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

include 'header.php';

// Fetch product with full hierarchy
$prodStmt = $pdo->prepare("
    SELECT p.*, 
           sc.name AS sub_category_name, sc.slug AS sub_category_slug,
           c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN sub_categories sc ON sc.id = p.sub_category_id
    JOIN categories c ON c.id = sc.category_id
    WHERE p.slug = ? AND p.status = 'active'
");
$prodStmt->execute([$slug]);
$product = $prodStmt->fetch();
if (!$product) {
    header('Location: index.php');
    exit;
}

$productName = html_entity_decode($product['name']);
$subCategoryName = html_entity_decode($product['sub_category_name']);
$categoryName = html_entity_decode($product['category_name']);
$colorCode = !empty($product['color_code']) ? $product['color_code'] : '#cccccc';

// Fetch all product gallery images
$imgStmt = $pdo->prepare("
    SELECT image_path, is_primary 
    FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, sort_order ASC
");
$imgStmt->execute([$product['id']]);
$galleryImages = $imgStmt->fetchAll();

// Increment view count
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product['id']]);

// PDF SVG icon (reusable)
$pdfIcon = '<svg width="20" height="26" viewBox="0 0 20 26" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M19.8372 5.57538L14.2817 0.158691C14.1775 0.0571289 14.0365 0 13.8889 0H2.22219C0.996615 0 0 0.971699 0 2.16668V23.8334C0 25.0283 0.996615 26 2.22224 26H17.7778C19.0034 26 20 25.0283 20 23.8333V5.95832C20 5.81445 19.9414 5.67694 19.8372 5.57538ZM14.4444 1.8493L18.1033 5.41668H15.5555C14.943 5.41668 14.4444 4.93055 14.4444 4.33337V1.8493ZM18.8889 23.8333C18.8889 24.4305 18.3903 24.9166 17.7778 24.9166H2.22224C1.60974 24.9166 1.11115 24.4305 1.11115 23.8333V2.16668C1.11115 1.5695 1.60974 1.08337 2.22224 1.08337H13.3333V4.33337C13.3333 5.5283 14.3299 6.5 15.5556 6.5H18.8889V23.8333Z" fill="#ffffff"/>
    <path d="M12.8325 15.9027C12.3181 15.5081 11.8293 15.1024 11.5038 14.785C11.0806 14.3724 10.7036 13.9725 10.3759 13.5917C10.887 12.0518 11.1111 11.2579 11.1111 10.8347C11.1111 9.03669 10.4448 8.66797 9.4444 8.66797C8.68429 8.66797 7.77773 9.05304 7.77773 10.8865C7.77773 11.6948 8.23185 12.676 9.1319 13.8165C8.91164 14.4719 8.65283 15.2278 8.36205 16.0799C8.22205 16.4888 8.07018 16.8676 7.90955 17.2178C7.77882 17.2744 7.65185 17.332 7.52924 17.3918C7.08763 17.6071 6.66825 17.8007 6.27924 17.9805C4.50513 18.7994 3.33325 19.3411 3.33325 20.4106C3.33325 21.1872 4.19862 21.668 4.99992 21.668C6.03289 21.668 7.59268 20.3228 8.732 18.0567C9.91471 17.6018 11.385 17.2648 12.5455 17.0537C13.4754 17.7509 14.5024 18.418 14.9999 18.418C16.3774 18.418 16.6666 17.6414 16.6666 16.9903C16.6666 15.7096 15.1659 15.7096 14.4443 15.7096C14.2203 15.7097 13.6192 15.7742 12.8325 15.9027Z" fill="#ffffff"/>
</svg>';
?>

<main>

    <!-- Product name tag area start -->
    <section class="pt-120 pb-40" style="background-color: #F5F5F5;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-lg-3 mt-10">
                            <div class="why-choose-us-2__item">
                                <div class="why-choose-us-2__text">
                                    <span class="section__subtitle color-black">
                                        <?php echo htmlspecialchars($productName); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Product name tag area end -->

    <!-- Product detail area start -->
    <section class="blog-details section-space">
        <div class="container">
            <div class="row">

                <!-- Color swatch / image -->
                <div class="col-xl-3">
                    <?php if (!empty($galleryImages)): ?>
                        <img src="admin/uploads/products/<?php echo htmlspecialchars($galleryImages[0]['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($productName); ?>"
                            style="width:100%; border-radius:8px; object-fit:cover;">
                    <?php elseif (!empty($product['image'])): ?>
                        <img src="admin/uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($productName); ?>"
                            style="width:100%; border-radius:8px; object-fit:cover;">
                    <?php else: ?>
                        <div class="why-choose-us-3__icon"
                            style="background-color:<?php echo htmlspecialchars($colorCode); ?>;"></div>
                    <?php endif; ?>
                </div>

                <!-- Product info -->
                <div class="col-xl-9">
                    <div class="blog-details__content pt-xs-20">

                        <!-- Product title -->
                        <h4 class="mt-lg-10 mt-sm-10 mt-xs-10 mt-md-10">
                            <?php
                            $catFirstWord = explode(' ', $categoryName)[0];
                            echo htmlspecialchars('COLOUR ' . strtoupper($catFirstWord) . 'TECH ' . $productName);
                            if (!empty($product['product_code'])) {
                                echo ' (' . htmlspecialchars($product['product_code']) . ')';
                            }
                            ?>
                        </h4>

                        <!-- Breadcrumb trail -->
                        <p style="color:#906E50; margin-bottom:10px;">
                            <a href="category.php?slug=<?php echo urlencode($product['category_slug']); ?>"
                                style="color:#906E50;">
                                <?php echo htmlspecialchars($categoryName); ?>
                            </a>
                            &rsaquo;
                            <a href="sub-category.php?slug=<?php echo urlencode($product['sub_category_slug']); ?>"
                                style="color:#906E50;">
                                <?php echo htmlspecialchars($subCategoryName); ?>
                            </a>
                        </p>

                        <!-- Color info -->
                        <?php if (!empty($product['color_name']) || !empty($product['color_index'])): ?>
                            <p style="margin-bottom:10px;">
                                <?php if (!empty($product['color_name'])): ?>
                                    <strong>Color:</strong> <?php echo htmlspecialchars($product['color_name']); ?>
                                    <span
                                        style="display:inline-block; width:16px; height:16px; background-color:<?php echo htmlspecialchars($colorCode); ?>; border-radius:50%; vertical-align:middle; margin-left:6px; border:1px solid #ddd;"></span>
                                <?php endif; ?>
                                <?php if (!empty($product['color_index'])): ?>
                                    &nbsp;&nbsp;<strong>C.I.:</strong> <?php echo htmlspecialchars($product['color_index']); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <!-- Description -->
                        <?php if (!empty($product['short_description'])): ?>
                            <p style="text-align:justify;">
                                <?php echo nl2br(htmlspecialchars(html_entity_decode($product['short_description']))); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($product['full_description'])): ?>
                            <p style="text-align:justify;">
                                <?php echo nl2br(html_entity_decode($product['full_description'])); ?>
                            </p>
                        <?php endif; ?>

                        <!-- PDF Buttons -->
                        <?php if (!empty($product['pdf_file_1'])): ?>
                            <a href="admin/uploads/products/pdfs/<?php echo htmlspecialchars($product['pdf_file_1']); ?>"
                                target="_blank" class="rr-btn border-raduis-10">
                                <span class="btn-wrap">
                                    <span class="text-one"><?php echo htmlspecialchars($product['pdf_file_1_label']); ?>
                                        <?php echo $pdfIcon; ?></span>
                                    <span class="text-two"><?php echo htmlspecialchars($product['pdf_file_1_label']); ?>
                                        <?php echo $pdfIcon; ?></span>
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($product['pdf_file_2'])): ?>
                            <a href="admin/uploads/products/pdfs/<?php echo htmlspecialchars($product['pdf_file_2']); ?>"
                                target="_blank" class="rr-btn border-raduis-10 mt-xs-10" style="margin-left:10px;">
                                <span class="btn-wrap">
                                    <span class="text-one"><?php echo htmlspecialchars($product['pdf_file_2_label']); ?>
                                        <?php echo $pdfIcon; ?></span>
                                    <span class="text-two"><?php echo htmlspecialchars($product['pdf_file_2_label']); ?>
                                        <?php echo $pdfIcon; ?></span>
                                </span>
                            </a>
                        <?php endif; ?>

                        <!-- Sample Request Button -->
                        <a data-bs-toggle="modal" data-bs-target="#sampleModal" class="rr-btn border-raduis-10 mt-xs-10"
                            style="margin-left:10px;">
                            <span class="btn-wrap">
                                <span class="text-one">Sample Request <i class="fa-regular fa-headset"></i></span>
                                <span class="text-two">Sample Request <i class="fa-regular fa-headset"></i></span>
                            </span>
                        </a>

                    </div>
                </div>
            </div>

            <!-- Additional gallery images -->
            <?php if (count($galleryImages) > 1): ?>
                <div class="row" style="margin-top:40px;">
                    <?php foreach (array_slice($galleryImages, 1) as $gImg): ?>
                        <div class="col-md-4" style="margin-bottom:20px;">
                            <img src="admin/uploads/products/<?php echo htmlspecialchars($gImg['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($productName); ?>"
                                style="width:100%; border-radius:8px; object-fit:cover;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
    <!-- Product detail area end -->

    <!-- Sample Request Modal -->
    <div class="modal" id="sampleModal" style="margin-top: 110px;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Sample Request — <?php echo htmlspecialchars($productName); ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <section class="contact section-space__bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12">
                                <form class="contact__from" style="margin-top: 20px;" method="POST"
                                    action="sample-request.php">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="product_name"
                                        value="<?php echo htmlspecialchars($productName); ?>">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="contact__form-input">
                                                <textarea name="message" placeholder="Type Your Message"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="contact__form-input">
                                                <input name="name" type="text" placeholder="Full Name" required>
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="contact__form-input">
                                                <input name="email" type="email" placeholder="Email ID" required>
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="contact__form-input">
                                                <input name="phone" type="text" placeholder="Phone">
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="contact__form-input">
                                                <input name="company" type="text" placeholder="Company Name">
                                            </div>
                                        </div>
                                        <div class="col-12" style="margin-top: 20px;">
                                            <button type="submit" class="rr-btn">
                                                <span class="btn-wrap">
                                                    <span class="text-one">Submit
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1 6H11" stroke="white" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M6 1L11 6L6 11" stroke="white" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </span>
                                                    <span class="text-two">Submit
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1 6H11" stroke="white" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M6 1L11 6L6 11" stroke="white" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal end -->

</main>

<?php include 'footer.php'; ?>