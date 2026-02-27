<footer>
    <section class="footer__area-common theme-bg-heading-primary overflow-hidden">
        <div class="footer__top">
            <div class="container">
                <div class="footer__top-shape">
                    <img src="assets/imgs/footer-1/footer-cta-shape.png" alt="image not found">
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-6 text-lg-start text-center rr-mb-40-md">
                        <div class="footer-cta__content-text">
                            <h2 class="footer-cta__content-title color-white">Subscribe to Our Newsletter</h2>
                            <p class="color-white mb-0" style="opacity:0.8;">Get the latest updates on new pigments,
                                industry news and innovations.</p>
                        </div>
                    </div>
                    <div class="col-lg-6 text-lg-end text-center">
                        <div class="footer__widget-subscribe">
                            <input type="email" id="newsletterEmail" placeholder="Enter your email address"
                                autocomplete="email">
                            <button type="button" id="newsletterBtn" class="rr-btn" onclick="subscribeNewsletter()">
                                <span class="btn-wrap">
                                    <span class="text-one">Subscribe
                                        <svg width="12" height="13" viewBox="0 0 12 13" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 6.5H11" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M6 1.5L11 6.5L6 11.5" stroke="white" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="text-two">Subscribe
                                        <svg width="12" height="13" viewBox="0 0 12 13" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 6.5H11" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M6 1.5L11 6.5L6 11.5" stroke="white" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </span>
                            </button>
                        </div>
                        <!-- Feedback message shown below the form -->
                        <div id="newsletterMsg"
                            style="margin-top:10px; display:none; font-size:0.92rem; font-weight:500;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer__main-wrapper footer__bottom-border">
            <div class="container">
                <div class="row mb-minus-50">
                    <div class="col-lg-3 col-6">
                        <div class="footer__widget footer__widget-item-1">
                            <div class="footer__logo mb-15">
                                <a href="index.php">
                                    <img class="img-fluid" src="assets/imgs/logo-white.png" alt="logo not found">
                                </a>
                            </div>

                            <div class="footer__content mb-30 mb-xs-35">
                                <p class="mb-0" style="text-align: justify;">The company, M/S. Colourtech Industries
                                    Pvt. Ltd., situated on plot no EX-12, in the first phase of GIDC VAPI industrial
                                    estate, India.</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="footer__widget footer__widget-item-2">
                            <div class="footer__widget-title">
                                <h6>Useful Links</h6>
                            </div>
                            <div class="footer__link">
                                <ul>
                                    <li><a href="#"> <i class="fa-solid fa-angles-right"></i>Overview</a></li>
                                    <li><a href="#"> <i class="fa-solid fa-angles-right"></i>Research & Innovation</a>
                                    </li>
                                    <li><a href="#"> <i class="fa-solid fa-angles-right"></i>Sustainability</a></li>
                                    <li><a href="#"> <i class="fa-solid fa-angles-right"></i>News & Update</a></li>
                                    <li><a href="#"> <i class="fa-solid fa-angles-right"></i>Career</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="footer__widget footer__widget-item-3">
                            <div class="footer__widget-title">
                                <h6>Product Category</h6>
                            </div>
                            <div class="footer__link">
                                <ul>
                                    <?php
                                    $footerCatStmt = $pdo->query("
                    SELECT name, slug 
                    FROM categories 
                    WHERE status = 'active' 
                    ORDER BY sort_order ASC
                ");
                                    $footerCats = $footerCatStmt->fetchAll();
                                    foreach ($footerCats as $fCat):
                                        $fLink = 'category.php?slug=' . urlencode($fCat['slug']);
                                        $fName = htmlspecialchars(html_entity_decode($fCat['name']));
                                        ?>
                                        <li>
                                            <a href="<?php echo $fLink; ?>">
                                                <i class="fa-solid fa-angles-right"></i><?php echo $fName; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="footer__widget footer__widget-item-4">
                            <div class="footer__widget-title">
                                <h6>Contact Us</h6>
                            </div>

                            <div class="footer__contact">
                                <?php
                                $contactStmt = $pdo->query("
                SELECT setting_key, setting_value 
                FROM site_settings 
                WHERE setting_key IN ('site_phone_1','site_phone_2','site_email','site_address')
            ");
                                $contactInfo = $contactStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                ?>
                                <ul>
                                    <li>
                                        <span class="icon">
                                            <img src="assets/imgs/icon/call.svg" alt="">
                                        </span>
                                        <span class="text">
                                            <span>Phone Number</span>
                                            <?php if (!empty($contactInfo['site_phone_1'])): ?>
                                                <a
                                                    href="tel:<?php echo preg_replace('/\s+/', '', $contactInfo['site_phone_1']); ?>">
                                                    <?php echo htmlspecialchars($contactInfo['site_phone_1']); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($contactInfo['site_phone_2'])): ?>
                                                <a
                                                    href="tel:<?php echo preg_replace('/\s+/', '', $contactInfo['site_phone_2']); ?>">
                                                    <?php echo htmlspecialchars($contactInfo['site_phone_2']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="icon">
                                            <img src="assets/imgs/icon/mail.svg" alt="">
                                        </span>
                                        <span class="text">
                                            <span>Email Address</span>
                                            <?php if (!empty($contactInfo['site_email'])): ?>
                                                <a
                                                    href="mailto:<?php echo htmlspecialchars($contactInfo['site_email']); ?>">
                                                    <?php echo htmlspecialchars($contactInfo['site_email']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                    <li class="address">
                                        <span class="icon">
                                            <img src="assets/imgs/icon/map.svg" alt="">
                                        </span>
                                        <span class="text">
                                            <span>Location</span>
                                            <?php if (!empty($contactInfo['site_address'])): ?>
                                                <a target="_blank" href="https://maps.app.goo.gl/a44JWUKtQt5iXZ5F7">
                                                    <?php echo htmlspecialchars($contactInfo['site_address']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer__bottom">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="footer__copyright text-lg-start text-center">
                            <p class="mb-0">Copyright © 2026 by <a href="index.php">ColorTech Industries</a> All Rights
                                Reserved. | <a href="admin/login.php"
                                    style="color: rgba(255,255,255,0.6); font-size: 0.85em;">Admin</a></p>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="footer__copyright-menu last_no_bullet">
                            <ul>
                                <li><a href="#">Privacy & Policy </a></li>
                                <li><a href="#">Term & Conditions</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</footer>
<!-- Footer area end -->

<!-- Newsletter Subscription Script -->
<script>
    function subscribeNewsletter() {
        const emailInput = document.getElementById('newsletterEmail');
        const msgBox = document.getElementById('newsletterMsg');
        const btn = document.getElementById('newsletterBtn');
        const email = emailInput.value.trim();

        // Client-side validation
        if (!email) {
            showNewsletterMsg('Please enter your email address.', false);
            emailInput.focus();
            return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNewsletterMsg('Please enter a valid email address.', false);
            emailInput.focus();
            return;
        }

        // Disable button while submitting
        btn.disabled = true;
        btn.style.opacity = '0.7';

        const formData = new FormData();
        formData.append('email', email);

        fetch('newsletter-subscribe.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                showNewsletterMsg(data.message, data.success);
                if (data.success) {
                    emailInput.value = '';
                }
            })
            .catch(() => {
                showNewsletterMsg('Something went wrong. Please try again.', false);
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
    }

    function showNewsletterMsg(message, success) {
        const msgBox = document.getElementById('newsletterMsg');
        msgBox.textContent = message;
        msgBox.style.display = 'block';
        msgBox.style.color = success ? '#4ade80' : '#f87171'; // green : red
    }

    // Allow pressing Enter in the email input to submit
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('newsletterEmail');
        if (emailInput) {
            emailInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') subscribeNewsletter();
            });
        }
    });
</script>

<!-- JS here -->
<script src="assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="assets/js/plugins/waypoints.min.js"></script>
<script src="assets/js/vendor/bootstrap.bundle.min.js"></script>
<script src="assets/js/plugins/meanmenu.min.js"></script>
<script src="assets/js/plugins/odometer.min.js"></script>
<script src="assets/js/plugins/swiper.min.js"></script>
<script src="assets/js/plugins/wow.js"></script>
<script src="assets/js/vendor/magnific-popup.min.js"></script>
<script src="assets/js/vendor/type.js"></script>
<script src="assets/js/plugins/nice-select.min.js"></script>
<script src="assets/js/vendor/jquery-ui.min.js"></script>
<script src="assets/js/vendor/jquery.appear.js"></script>
<script src="assets/js/plugins/isotope.pkgd.min.js"></script>
<script src="assets/js/plugins/imagesloaded.pkgd.min.js"></script>
<script src="assets/js/plugins/gsap.min.js"></script>
<script src="assets/js/plugins/ScrollTrigger.min.js"></script>
<script src="assets/js/plugins/SplitText.js"></script>
<script src="assets/js/plugins/tween-max.min.js"></script>
<script src="assets/js/plugins/draggable.min.js"></script>
<script src="assets/js/plugins/jquery.carouselTicker.js"></script>
<script src="assets/js/vendor/ajax-form.js"></script>
<script src="assets/js/plugins/TextPlugin.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/plugins/magiccursor.js"></script>
</body>

</html>