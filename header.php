<?php require_once __DIR__ . '/includes/db.php'; ?>
<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>ColourTech Industries</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="assets/imgs/favicon.svg">
    <!-- CSS here -->
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

    <!-- Backtotop start -->
    <div id="scroll-percentage">
        <span id="scroll-percentage-value"></span>
    </div>
    <!-- Backtotop end -->

    <!-- cursorAnimation start -->
    <div class="cursor-wrapper relative">
        <div class="cursor"></div>
        <div class="cursor-follower"></div>
    </div>
    <!-- cursorAnimation end -->

    <!-- Offcanvas area start -->
    <div class="">
        <div class="fix">
            <div class="offcanvas__area">
                <div class="offcanvas__wrapper">
                    <div class="offcanvas__content">
                        <div class="offcanvas__top d-flex justify-content-between align-items-center">
                            <div class="offcanvas__logo">
                                <a href="index.php">
                                    <img src="assets/imgs/logo-white.png" alt="logo">
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
                                // Fetch social links from site_settings
                                $socialStmt = $pdo->query("
                                SELECT setting_key, setting_value 
                                FROM site_settings 
                                WHERE setting_key IN ('facebook_url','linkedin_url','pinterest_url','instagram_url')
                            ");
                                $socialSettings = $socialStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                                $socialIcons = [
                                    'facebook_url' => 'fab fa-facebook-f',
                                    'linkedin_url' => 'fa-brands fa-linkedin-in',
                                    'pinterest_url' => 'fa-brands fa-pinterest-p',
                                    'instagram_url' => 'fa-brands fa-instagram',
                                ];

                                foreach ($socialIcons as $key => $iconClass):
                                    if (!empty($socialSettings[$key])):
                                        ?>
                                        <li>
                                            <a href="<?php echo htmlspecialchars($socialSettings[$key]); ?>" target="_blank">
                                                <i class="<?php echo $iconClass; ?>"></i>
                                            </a>
                                        </li>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas__overlay"></div>
        <div class="offcanvas__overlay-white"></div>
    </div>
    <!-- Offcanvas area end -->

    <!-- Header area start -->
    <header>
        <div id="header-sticky" class="header__area header-1">
            <div class="container">
                <div class="mega__menu-wrapper p-relative">
                    <div class="header__main">
                        <div class="header__logo">
                            <a href="index.php">
                                <div class="logo">
                                    <img src="assets/imgs/logo.png" alt="logo" class="main-logo">
                                </div>
                            </a>
                        </div>

                        <div class="mean__menu-wrapper d-none d-lg-block">
                            <div class="main-menu">
                                <nav id="mobile-menu">
                                    <ul>
                                        <!-- Home -->
                                        <li class="<?php echo ($currentPage == 'index') ? 'active' : ''; ?>">
                                            <a href="index.php">Home</a>
                                        </li>

                                        <!-- About -->
                                        <li
                                            class="has-dropdown <?php echo ($currentPage == 'about') ? 'active' : ''; ?>">
                                            <a href="#">About ColourTech</a>
                                            <ul class="submenu">
                                                <li><a href="about-us.php">Overview</a></li>
                                                <li><a href="#">Research & Innovation</a></li>
                                                <li><a href="#">Quality & Certification</a></li>
                                                <li><a href="ceo-message.php">CEO Message</a></li>
                                            </ul>
                                        </li>

                                        <!-- Our Products - Dynamic Mega Menu -->
                                        <li
                                            class="has-dropdown has-mega-menu <?php echo ($currentPage == 'product') ? 'active' : ''; ?>">
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
                                                                    $subLink = 'sub-category.php?slug=' . urlencode($sub['slug']);
                                                                    ?>
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
                                        <li class="<?php echo ($currentPage == 'sustain') ? 'active' : ''; ?>">
                                            <a href="sustainability.php">Sustainability</a>
                                        </li>

                                        <!-- News & Update -->
                                        <li class="<?php echo ($currentPage == 'news') ? 'active' : ''; ?>">
                                            <a href="news-event.php">News & Update</a>
                                        </li>

                                        <!-- Career -->
                                        <li>
                                            <a href="#">Career</a>
                                        </li>

                                        <!-- Contact -->
                                        <li class="<?php echo ($currentPage == 'contact') ? 'active' : ''; ?>">
                                            <a href="contact-us.php">Contact</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>

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
    <!-- Header area end -->