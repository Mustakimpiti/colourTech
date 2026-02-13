<?php $currentPage = 'news'; include 'header.php'; ?>

<main>
    <!-- Breadcrumb area start  -->
    <div class="breadcrumb__area header__background-color breadcrumb__header-up breadcrumb-space overly overflow-hidden">
        <div class="breadcrumb__background" data-background="assets/imgs/breadcrumb/ews-events.jpg"></div>
        <div class="container">
            <div class="breadcrumb__bg-left"></div>
            <div class="breadcrumb__bg-right"></div>
            <div class="row align-items-center justify-content-between">
                <div class="col-12">
                    <div class="breadcrumb__content text-center">
                        <h2 class="breadcrumb__title mb-15 mb-sm-10 mb-xs-5 color-white title-animation"></h2>

                        <div class="breadcrumb__menu">
                            <nav>
                                <ul>
                                    <li><span><a href="index.php">Home</a></span></li>
                                    <li class="active"><span>News & Events</span></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb area start  -->

    <!-- "blog-grid  area start -->
        <section class="blog-grid section-space">
            <div class="container">
                <div class="row mb-minus-30">
                    <div class="col-lg-4">
                        <div class="blog-grid__item">
                            <a href="#" class="blog-grid__media">
                                <img src="assets/imgs/blog-grid/blog-1.jpg" alt="image not found">
                            </a>
                            <span class="blog-grid__date">26 January,2024 </span>
                            <div class="blog-grid__content">
                                
                                <h6 class="mb-10"><a href="#">Five Quick Tips Regarding Architecture.</a></h6>

                                <p>Bibendum est ultricies integer quis auctor elit sed vulputate Vivamus...</p>

                                <a class="read-more" href="#">Read More
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 6H11" stroke="#767676" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M6 1L11 6L6 11" stroke="#767676" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        
                                    </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    <!-- "blog-grid  area end -->

 

</main>

<?php include 'footer.php'; ?>