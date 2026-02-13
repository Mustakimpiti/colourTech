<?php $currentPage = 'contact'; include 'header.php'; ?>

<main>
    <!-- Breadcrumb area start  -->
    <div class="breadcrumb__area header__background-color breadcrumb__header-up breadcrumb-space overly overflow-hidden">
        <div class="breadcrumb__background" data-background="assets/imgs/breadcrumb/contact-us.jpg"></div>
        <div class="container">
            <div class="breadcrumb__bg-left"></div>
            <div class="breadcrumb__bg-right"></div>
            <div class="row align-items-center justify-content-between">
                <div class="col-12">
                    <div class="breadcrumb__content text-center">
                        <h2 class="breadcrumb__title mb-15 mb-sm-10 mb-xs-5 color-white title-animation">Contact Us</h2>

                        <div class="breadcrumb__menu">
                            <nav>
                                <ul>
                                    <li><span><a href="index.php">Home</a></span></li>
                                    <li class="active"><span>Contact Us</span></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb area start  -->

    <!-- "error  area start -->
    <section class="contact-us section-space">
        <div class="container">
            <div class="row mb-minus-30">
                <div class="col-md-6 col-lg-4">
                    <div class="contact-us__info-item">
                        <div class="contact-us__icon">
                            <img src="assets/imgs/contact-us/location.svg" alt="image not found">
                        </div>
                        <div class="contact-us__text">
                            <h6>Visit our office</h6>
                            <a href="#">Plot No. Ex. 12, Opp. CETP, 1st Phase G.I.D.C, Vapi - 396 195, Valsad Dist.,(Gujarat), India.</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="contact-us__info-item">
                        <div class="contact-us__icon">
                            <img src="assets/imgs/contact-us/email.svg" alt="image not found">
                        </div>
                        <div class="contact-us__text">
                            <h6>email address</h6>
                            <a href="mailto:info@colourtechvapi.com">info@colourtechvapi.com</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="contact-us__info-item">
                        <div class="contact-us__icon">
                            <img src="assets/imgs/contact-us/phone.svg" alt="image not found">
                        </div>
                        <div class="contact-us__text">
                            <h6>Phone number</h6>
                            <a href="tel:+912602433771">+91 260 2433771</a>
                            <a href="tel:+912602434771">+91 260 2434771</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- "error  area end -->

    <section class="contact section-space__bottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact__from">
                        <h4 class="title-animation mb-10">Get in touch</h4>
                        <p>Select layout follower boolean editor flows. Scrolling variant move font group variant layout device share.</p>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="contact__form-input">
                                    <input name="name" id="lname" type="text" placeholder="Name">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="contact__form-input">
                                    <input name="email" id="email" type="email" placeholder="Email">
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="contact__form-input">
                                    <input name="number" id="number" type="number" placeholder="Phone">
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="contact__form-input-select d-flex flex-column">
                                    <select name="subject" id="subject" style="display: none;">
                                        <option value="">Subject</option>
                                        <option value="order">Event Order</option>
                                        <option value="objection">Objection</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12">
                                <div class="contact__form-input">
                                    <div class="validation__wrapper-up position-relative">
                                        <textarea name="textarea" id="textarea" placeholder="Type Your Message"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="" class="rr-btn">
                                        <span class="btn-wrap">
                                            <span class="text-one">Make An Order
                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1 6H11" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M6 1L11 6L6 11" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                            
                                        </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1160.5069090954182!2d72.906051!3d20.34577!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be0cfe2981f6cef%3A0xdd32a6561146743e!2sCOLOURTECH%20INDUSTRIES!5e1!3m2!1sen!2sin!4v1767096042572!5m2!1sen!2sin" width="100%" height="555" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

      


</main>
<?php include 'footer.php'; ?>