<?php require_once __DIR__ . '/includes/db.php'; ?>
<?php
// ─── Dynamic Meta Tag Defaults ────────────────────────────────────────────────
// These can be overridden by any page before including header.php
// e.g. product.php sets $metaTitle, $metaDescription, $metaKeywords
if (!isset($metaTitle))       $metaTitle       = 'ColourTech Industries | Premium Pigment Manufacturer';
if (!isset($metaDescription)) $metaDescription = 'ColourTech Industries manufactures high-performance organic and inorganic pigments for paints, coatings, inks, and plastics. Trusted quality worldwide.';
if (!isset($metaKeywords))    $metaKeywords    = 'pigment manufacturer, organic pigments, inorganic pigments, paint pigments, coating pigments, ColourTech';
if (!isset($metaOgImage))     $metaOgImage     = 'assets/imgs/og-default.jpg'; // fallback OG image
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ─── Primary Meta Tags ──────────────────────────────────────────────── -->
    <title><?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords"    content="<?php echo htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="author"      content="ColourTech Industries">
    <meta name="robots"      content="index, follow">

    <!-- ─── Canonical URL ──────────────────────────────────────────────────── -->
    <?php
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $canonical = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ─── Open Graph (Facebook / LinkedIn / WhatsApp) ────────────────────── -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="ColourTech Industries">
    <meta property="og:title"       content="<?php echo htmlspecialchars($metaTitle,       ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url"         content="<?php echo htmlspecialchars($canonical,        ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($metaOgImage,      ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ─── Twitter Card ───────────────────────────────────────────────────── -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($metaTitle,       ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($metaOgImage,      ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ─── Favicon ────────────────────────────────────────────────────────── -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/imgs/favicon.svg">

    <!-- ─── CSS ────────────────────────────────────────────────────────────── -->
    <link rel="stylesheet" href="assets/css/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/vendor/animate.min.css">
    <link rel="stylesheet" href="assets/css/plugins/swiper.min.css">
    <link rel="stylesheet" href="assets/css/vendor/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/vendor/fontawesome-pro.css">
    <link rel="stylesheet" href="assets/css/vendor/spacing.css">
    <link rel="stylesheet" href="assets/css/plugins/odometer-theme-default.css">
    <link rel="stylesheet" href="assets/css/plugins/carouselTicker.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body class="body-1">

    <!-- Preloader -->
    <div id="preloader" data-preloader="active" data-loaded="doted">
        <div class="preloader-close">x</div>
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div class="preloader" data-preloader="deactive" data-loaded="progress">
        <div class="preloader-close">x</div>
        <div class="wrapper w-100 text-center">
            <div id="progress-bar" class="preloader-text" data-text="RIBUILD"></div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </div>
    </div>

    <div class="loading-form">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <!-- Back to top -->
    <div id="scroll-percentage">
        <span id="scroll-percentage-value"></span>
    </div>

    <!-- Cursor animation -->
    <div class="cursor-wrapper relative">
        <div class="cursor"></div>
        <div class="cursor-follower"></div>
    </div>

    <!-- ─── Offcanvas (mobile menu) ─────────────────────────────────────────── -->
    <div class="">
        <div class="fix">
            <div class="offcanvas__area">
                <div class="offcanvas__wrapper">
                    <div class="offcanvas__content">
                        <div class="offcanvas__top d-flex justify-content-between align-items-center">
                            <div class="offcanvas__logo">
                                <a href="index.php">
                                    <img src="assets/imgs/logo-white.png" alt="ColourTech Industries logo">
                                </a>
                            </div>
                            <div class="offcanvas__close">
                                <button class="offcanvas-close-icon animation--flip">
                                    <span class="offcanvas-m-lines">
                                        <span class="offcanvas-m-line line--1"></span>
                                        <span class="offcanvas-m-line line--2"></span>
                                        <span class="offcanvas-m-line line--3"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="mobile-menu fix"></div>
                        <div class="offcanvas__social">
                            <ul class="header-top-socail-menu d-flex">
                                <?php
                                $socialStmt = $pdo->query("
                                    SELECT setting_key, setting_value
                                    FROM site_settings
                                    WHERE setting_key IN ('facebook_url','linkedin_url','pinterest_url','instagram_url')
                                ");
                                $socialSettings = $socialStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                                $socialIcons = [
                                    'facebook_url'  => 'fab fa-facebook-f',
                                    'linkedin_url'  => 'fa-brands fa-linkedin-in',
                                    'pinterest_url' => 'fa-brands fa-pinterest-p',
                                    'instagram_url' => 'fa-brands fa-instagram',
                                ];

                                foreach ($socialIcons as $key => $iconClass):
                                    if (!empty($socialSettings[$key])): ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars($socialSettings[$key]); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="<?php echo $iconClass; ?>"></i>
                                            </a>
                                        </li>
                                    <?php endif;
                                endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas__overlay"></div>
        <div class="offcanvas__overlay-white"></div>
    </div>

    <!-- ─── Header ──────────────────────────────────────────────────────────── -->
    <header>
        <div id="header-sticky" class="header__area header-1">
            <div class="container">
                <div class="mega__menu-wrapper p-relative">
                    <div class="header__main">

                        <!-- Logo -->
                        <div class="header__logo">
                            <a href="index.php">
                                <div class="logo">
                                    <img src="assets/imgs/logo.png" alt="ColourTech Industries" class="main-logo">
                                </div>
                            </a>
                        </div>

                        <!-- Desktop nav -->
                        <div class="mean__menu-wrapper d-none d-lg-block">
                            <div class="main-menu">
                                <nav id="mobile-menu">
                                    <ul>

                                        <!-- Home -->
                                        <li class="<?php echo ($currentPage === 'index') ? 'active' : ''; ?>">
                                            <a href="index.php">Home</a>
                                        </li>

                                        <!-- About -->
                                        <li class="has-dropdown <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                                            <a href="#">About ColourTech</a>
                                            <ul class="submenu">
                                                <li><a href="about-us.php">Overview</a></li>
                                                <li><a href="#">Research &amp; Innovation</a></li>
                                                <li><a href="#">Quality &amp; Certification</a></li>
                                                <li><a href="ceo-message.php">CEO Message</a></li>
                                            </ul>
                                        </li>

                                        <!-- Our Products – Dynamic Mega Menu -->
                                        <li class="has-dropdown has-mega-menu <?php echo ($currentPage === 'product') ? 'active' : ''; ?>">
                                            <a href="javascript:void(0)">Our Products</a>
                                            <ul class="mega-menu mega-menu-grid-3">
                                                <?php
                                                $catStmt = $pdo->query("
                                                    SELECT id, name, slug
                                                    FROM categories
                                                    WHERE status = 'active'
                                                    ORDER BY sort_order ASC
                                                ");
                                                $menuCategories = $catStmt->fetchAll();

                                                foreach ($menuCategories as $menuCat):
                                                    $catLink = 'category.php?slug=' . urlencode($menuCat['slug']);

                                                    $subStmt = $pdo->prepare("
                                                        SELECT id, name, slug
                                                        FROM sub_categories
                                                        WHERE category_id = ? AND status = 'active'
                                                        ORDER BY sort_order ASC
                                                    ");
                                                    $subStmt->execute([$menuCat['id']]);
                                                    $subCategories = $subStmt->fetchAll();
                                                ?>
                                                    <li>
                                                        <div class="home__menu-item">
                                                            <h4 class="home__menu-title">
                                                                <a href="<?php echo $catLink; ?>">
                                                                    <?php echo htmlspecialchars(html_entity_decode($menuCat['name'])); ?>
                                                                </a>
                                                            </h4>
                                                            <ul>
                                                                <?php foreach ($subCategories as $sub):
                                                                    $subLink = 'sub-category.php?slug=' . urlencode($sub['slug']); ?>
                                                                    <li>
                                                                        <a href="<?php echo $subLink; ?>">
                                                                            <?php echo htmlspecialchars(html_entity_decode($sub['name'])); ?>
                                                                        </a>
                                                                    </li>
                                                                <?php endforeach; ?>

                                                                <?php if (empty($subCategories)): ?>
                                                                    <li><a href="<?php echo $catLink; ?>">View Products</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>

                                        <!-- Sustainability -->
                                        <li class="<?php echo ($currentPage === 'sustain') ? 'active' : ''; ?>">
                                            <a href="sustainability.php">Sustainability</a>
                                        </li>

                                        <!-- News & Update -->
                                        <li class="<?php echo ($currentPage === 'news') ? 'active' : ''; ?>">
                                            <a href="news-event.php">News &amp; Update</a>
                                        </li>

                                        <!-- Career -->
                                        <li>
                                            <a href="#">Career</a>
                                        </li>

                                        <!-- Contact -->
                                        <li class="<?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">
                                            <a href="contact-us.php">Contact</a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        </div>

                        <!-- Mobile hamburger -->
                        <div class="header__right desk-hidden">
                            <div class="header__action d-flex align-items-center">
                                <div class="header__hamburger">
                                    <div class="sidebar__toggle">
                                        <a class="bar-icon" href="javascript:void(0)">
                                            <span></span>
                                            <span></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Header end -->