
(function ($) {
    "use strict";
    var windowOn = $(window);

    /*-----------------------------------------------------------------------------------
        Template Name: Artima – Modern Architecture & Interior HTML5 Template
        Author: RRDevs
        Support: https://support.rrdevs.net
        Description: Artima – Modern Architecture & Interior HTML5 Template.
        Version: 1.0
        Developer Github: Rana Ahmmed (https://github.com/mdranaahmed-dev)
    -----------------------------------------------------------------------------------

     */
   /*======================================
   Data Css js
   ========================================*/
    $("[data-background]").each(function() {
        $(this).css(
            "background-image",
            "url( " + $(this).attr("data-background") + "  )"
        );
    });

    $("[data-width]").each(function() {
        $(this).css("width", $(this).attr("data-width"));
    });

    // prelaoder
    let span = $('.letter'),
        tlSmell = new TimelineMax({repeat : -1});
    tlSmell
        .staggerFromTo($('svg .smell'), 3, {y: 50, autoAlpha: 0.5}, {y: -20, autoAlpha: 1}, 1);
    TweenMax.fromTo($('svg #body'), 3, {x: -1, repeat : -1, yoyo : true}, {x: 1, repeat : -1, yoyo : true});

    /*width100ScrollTriggerAnimation*/
    function updateSeparatorWidth(selector, direction = 'right') {
        if(direction === 'left') {
            $(selector).each(function(index, item) {
                const width = parseInt($(item).data('width')) || 0;
                $(item).parent().css('margin-left', (width + 10) + 'px');
            });
        } else {
            $(selector).each(function(index, item) {
                const width = parseInt($(item).data('width')) || 0;
                $(item).parent().css('margin-left', (width + 10) + 'px');
            });
        }
    }
    updateSeparatorWidth('.right-separetor', 'right');
    updateSeparatorWidth('.left-separetor', 'left');

    class GSAPAnimation {
        static Init() {
            /*title-animation*/
            this.upDownScrollTriggerAnimation('.b-t__scroll');
            $('.right-separetor').length && this.width100ScrollTriggerAnimation('.right-separetor');
            $('.left-separetor').length && this.width100ScrollTriggerAnimation('.left-separetor');
            $('.title-animation').length && this.sectionTitleAnimation('.title-animation');
        }
        
        static sectionTitleAnimation(activeClass) {
            let sectionTitleLines = gsap.utils.toArray(activeClass);

            sectionTitleLines.forEach(sectionTextLine => {
                const tl = gsap.timeline({
                    scrollTrigger: {
                        trigger: sectionTextLine,
                        start: 'top 90%',
                        end: 'bottom 60%',
                        scrub: false,
                        markers: false,
                        toggleActions: 'play none none none'
                    }
                });

                const itemSplitted = new SplitText(sectionTextLine, { type: "chars, words" });
                gsap.set(sectionTextLine, { perspective: 100 });
                itemSplitted.split({ type: "words" })
                tl.from(itemSplitted.words, {
                    opacity: 0, 
                    autoAlpha: 0, 
                    transformOrigin: "top center -50",
                    y: "10px",
                    duration: 0.9,
                    stagger: 0.1,
                    ease: "power2.out",
                });
            });
        }

        static width100ScrollTriggerAnimation(activeClass) {
            let Lines = gsap.utils.toArray(activeClass);
            Lines.forEach(Line => {
                const maxWidth = Line.getAttribute('data-width') || '100%';
                gsap.fromTo(Line, {
                    width: "0"
                }, {
                    opacity: 1,
                    width: maxWidth,
                    duration: 0.9,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: Line,
                        start: 'top 100%',
                        end: 'top 10%',
                        scrub: true,
                        markers: false,
                        toggleActions: 'play none none none'
                    }
                });
            });
        }

        static upDownScrollTriggerAnimation(titleClass) {
            let Title = gsap.utils.toArray(titleClass);

            Title.forEach(TitleSingle => {
                const TitleSingleTarget = TitleSingle.getAttribute('data-target');
                const TitleSingleTargetHeight = TitleSingle.getAttribute('data-target-height');

                gsap.to(TitleSingle, {
                    scrollTrigger: {
                        trigger: TitleSingle,
                        start: "top 20%",
                        end: () => {
                            let totalHeight = 0;
                            if(TitleSingleTarget) {
                                totalHeight += $(TitleSingleTarget).innerHeight();
                                totalHeight -=$(TitleSingle).innerHeight();
                            }
                            if(TitleSingleTargetHeight) {
                                totalHeight += parseInt(TitleSingleTargetHeight);
                            }
                            return `+=${totalHeight}`;
                        },
                        pin: true,
                        pinSpacing: false,
                        scrub: true,
                        markers: false,
                    }
                });
            });
        }
    }

    class RRDEVS {
        constructor() {
            this.isLoaded = false;
        }
        static LoadedAfterDoted() {
            if (this.isLoaded) {
                return false;
            }
            this.isLoaded = true;
            $('#preloader').delay(1).fadeOut(0);

            $('.odometer').waypoint(function(direction) {
                if (direction === 'down') {
                    let countNumber = $(this.element).attr("data-count");
                    $(this.element).html(countNumber);
                }
            }, {
                offset: '80%'
            });

            /*Wow Js*/
            if ($('.wow').length) {
                var wow = new WOW({
                    boxClass: 'wow',
                    animateClass: 'animated',
                    offset: 0,
                    mobile: false,
                    live: true
                });
                wow.init();
            }

            /*GSAPAnimation*/
            GSAPAnimation.Init();
        }

        async LoadedAfterProgress(type) {
            if (this.isLoaded) {
                return false;
            }
            this.isLoaded = true;
            if('click' === type) {
                let tl = gsap.timeline();
                tl
                    .to(".preloader-close", {
                        duration: 0.1,
                        scale: 0,
                        onComplete: () => {
                            document.querySelector(".preloader-close").style.display = "none";
                        }
                    })
                    .to(".preloader", {
                        duration: 0.2,
                        height: 0,
                        onComplete: () => {
                            document.querySelector(".preloader").style.display = "none";
                        }
                    });

                await this.delay(0);
            }
            else {
                let randomStop = Math.random() * (0.8 - 0.3) + 0.3;
                let tl = gsap.timeline();
                const preloaderTextElement = document.querySelector(".preloader-text");
                const newText = preloaderTextElement.dataset.text;
                tl
                    .to(".preloader-text", {
                        duration: 1,
                        text: newText
                    })
                    .to(".progress-bar", {
                        duration: 0.3,
                        opacity: 1
                    })
                    .to(".progress", {
                        duration: .1,
                        width: `${randomStop * 100}%`
                    })
                    .to(".progress", {
                        duration: 1 - randomStop,
                        width: "100%"
                    })
                    .to(".preloader-text, .progress-bar", {
                        duration: 0.1,
                        opacity: 0
                    })
                    .to(".preloader-close", {
                        duration: 0.1,
                        scale: 0,
                        onComplete: () => {
                            document.querySelector(".preloader-close").style.display = "none";
                        }
                    })
                    .to(".preloader", {
                        duration: 0.2,
                        height: 0,
                        onComplete: () => {
                            document.querySelector(".preloader").style.display = "none";
                        }
                    });

                await this.delay(2000);
            }

            $('.odometer').waypoint(function(direction) {
                if (direction === 'down') {
                    let countNumber = $(this.element).attr("data-count");
                    $(this.element).html(countNumber);
                }
            }, {
                offset: '80%'
            });

            /*Wow Js*/
            if ($('.wow').length) {
                var wow = new WOW({
                    boxClass: 'wow',
                    animateClass: 'animated',
                    offset: 0,
                    mobile: false,
                    live: true
                });
                wow.init();
            }

            /*GSAPAnimation*/
            GSAPAnimation.Init();
        }

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }

    /*======================================
      Preloader activation with multiple
     ========================================*/
    const preloaders = $("[data-preloader]");
    preloaders.each(function(index, item) {
        if($(item).data('preloader') === 'active') {
            if($(item).data('loaded') === 'progress') {
                const RRDevsInit = new RRDEVS();
                $(".preloader-close").on("click", () => RRDevsInit.LoadedAfterProgress('click'));
                $(window).on('load', () => RRDevsInit.LoadedAfterProgress('load'));
            } else if($(item).data('loaded') === 'doted') {
                $(".preloader-close").on("click", () => RRDEVS.LoadedAfterDoted());
                $(window).on('load', () => RRDEVS.LoadedAfterDoted());
            }
        }
    });

    window.addEventListener('resize', function() {
        gsap.globalTimeline.clear();
    });

    /*======================================
      Mobile Menu Js
      ========================================*/
    $("#mobile-menu").meanmenu({
        meanMenuContainer: ".mobile-menu",
        meanScreenWidth: "1199",
        meanExpand: ['<i class="fa-regular fa-angle-right"></i>'],
    });

    /*======================================
      Sidebar Toggle
      ========================================*/
    $(".offcanvas__close,.offcanvas__overlay").on("click", function () {
        $(".offcanvas__area").removeClass("info-open");
        $(".offcanvas__overlay").removeClass("overlay-open");
    });
    // Scroll to bottom then close navbar
    $(window).scroll(function(){
        if($("body").scrollTop() > 0 || $("html").scrollTop() > 0) {
            $(".offcanvas__area").removeClass("info-open");
            $(".offcanvas__overlay").removeClass("overlay-open");
        }
    });
    $(".sidebar__toggle").on("click", function () {
        $(".offcanvas__area").addClass("info-open");
        $(".offcanvas__overlay").addClass("overlay-open");
    });

    /*======================================
      Body overlay Js
      ========================================*/
    $(".body-overlay").on("click", function () {
        $(".offcanvas__area").removeClass("opened");
        $(".body-overlay").removeClass("opened");
    });

    /*======================================
      Sticky Header Js
      ========================================*/
    $(window).scroll(function () {
        if ($(this).scrollTop() > 10) {
            $("#header-sticky").addClass("rr-sticky");
        } else {
            $("#header-sticky").removeClass("rr-sticky");
        }
    });

    /*======================================
      MagnificPopup image view
      ========================================*/
    $(".popup-image").magnificPopup({
        type: "image",
        gallery: {
            enabled: true,
        },
    });

    /*======================================
      MagnificPopup video view
      ========================================*/
    $(".popup-video").magnificPopup({
        type: "iframe",
    });

    /*======================================
      Page Scroll Percentage
      ========================================*/
    const scrollTopPercentage = ()=> {
        const scrollPercentage = () => {
            const scrollTopPos = document.documentElement.scrollTop;
            const calcHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollValue = Math.round((scrollTopPos / calcHeight) * 100);
            const scrollElementWrap = $("#scroll-percentage");

            scrollElementWrap.css("background", `conic-gradient( var(--rr-theme-primary) ${scrollValue}%, var(--rr-heading-primary) ${scrollValue}%)`);

            if ( scrollTopPos > 100 ) {
                scrollElementWrap.addClass("active");
            } else {
                scrollElementWrap.removeClass("active");
            }

            if( scrollValue < 96 ) {
                $("#scroll-percentage-value").text(`${scrollValue}%`);
            } else {
                $("#scroll-percentage-value").html('<i class="fa-solid fa-angle-up"></i>');
            }
        }
        window.onscroll = scrollPercentage;
        window.onload = scrollPercentage;

        // Back to Top
        function scrollToTop() {
            document.documentElement.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        }

        $("#scroll-percentage").on("click", scrollToTop);
    }
    scrollTopPercentage();

    /*======================================
	One Page Scroll Js
	========================================*/
    var link = $('.onepagenav #mobile-menu ul li a, .onepagenav .mean-nav ul li a');
    link.on('click', function(e) {
        var target = $($(this).attr('href'));
        $('html, body').animate({
            scrollTop: target.offset().top - 76
        }, 600);
        $(this).parent().addClass('active');
        e.preventDefault();
    });
    $(window).on('scroll', function(){
        scrNav();
    });

    function scrNav() {
        var sTop = $(window).scrollTop();
        $('section').each(function() {
            var id = $(this).attr('id'),
                offset = $(this).offset().top-1,
                height = $(this).height();
            if(sTop >= offset && sTop < offset + height) {
                link.parent().removeClass('active');
                $('.main-menu').find('[href="#' + id + '"]').parent().addClass('active');
            }
        });
    }
    scrNav();

    /*======================================
	Smoth animatio Js
	========================================*/
    $(document).on('click', '.smoth-animation', function (event) {
        event.preventDefault();
        $('html, body').animate({
            scrollTop: $($.attr(this, 'href')).offset().top - 50
        }, 300);
    });

        //slider-text
        const scrollers = document.querySelectorAll(".rr-scroller");
        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            addAnimation();
        }
        function addAnimation() {
            scrollers.forEach((scroller) => {
                scroller.setAttribute("data-animated", true);
    
                const scrollerInner = scroller.querySelector(".rr-scroller__inner");
                const scrollerContent = Array.from(scrollerInner.children);
    
                scrollerContent.forEach((item) => {
                    const duplicatedItem = item.cloneNode(true);
                    duplicatedItem.setAttribute("aria-hidden", true);
                    scrollerInner.appendChild(duplicatedItem);
                });
            });
        }

    
         /*testimonial__slider***/
         let clienttestimonial__slider = new Swiper(".testimonial__slider", {
            slidesPerView: 3,
            spaceBetween: 30,
            loop: true,
            centeredSlides: true,
            clickable: true,
            autoplay: {
                delay: 3000,
            },
            pagination: {
                el: ".testimonial__slider-dot",
                clickable: true,
            },
            breakpoints: {
                1200: {
                    slidesPerView: 3,
                },
                992: {
                    slidesPerView: 2,
                },
                0: {
                    slidesPerView: 1,
                },
            }
        });
    
         /*latest-article__slider***/
         let latest_article__slider = new Swiper(".latest-article__slider", {
            slidesPerView: 3,
            spaceBetween: 30,
            loop: true,
            centeredSlides: true,
            clickable: true,
            autoplay: {
                delay: 3000,
            },
            pagination: {
                el: ".latest-article__slider-dot",
                clickable: true,
            },
            breakpoints: {
                1200: {
                    slidesPerView: 3,
                },
                992: {
                    slidesPerView: 2,
                },
                0: {
                    slidesPerView: 1,
                },
            }
        });

        /* image compare js ***/
        var ctrl = jQuery('.filter__container .comparison-ctrl');
        var pic_right = jQuery('.filter__container .pic--right');
        Draggable.create(ctrl,{
            bounds: ctrl.parent(),
            type: "x",
            onDrag: function(){
                pic_right.css('left','calc(50% + '+(this.x - 2)+'px)');
            }
        });
 

    /*testimonial-2__slider***/
    let testimonial2slider = new Swiper(".testimonial-2__slider", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        centeredSlides: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        pagination: {
            el: ".testimonial-2__slider-dot",
            clickable: true,
        },
    });

    if ($(".count-bar").length) {
        $(".count-bar").appear(
            function() {
                var el = $(this);
                var percent = el.data("percent");
                $(el).css("width", percent).addClass("counted");
            }, {
                accY: -50
            }
        );
    }
    


    $('.header__top__lang-select-select select, .contact__form-input-select select').niceSelect();

    $(".search-open-btn").on("click", function () {
        $(".search__popup").addClass("search-opened");
    });
    $(".search-close-btn").on("click", function () {
        $(".search__popup").removeClass("search-opened");
    });


    /*team__slider***/
    let team__slider = new Swiper(".team__slider", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        clickable: true,
        pagination: {
            el: ".team__slider-dot",
            clickable: true,
        },
        autoplay: {
            delay: 3000,
        },
        breakpoints: {
            1200: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });



    function lastNobullet() {
        $(".last_no_bullet ul").each(function() {
            var $listItems = $(this).find("li");

            if ($listItems.length > 1) {
                $listItems.last().addClass("no_bullet");
            }
        });
    }

    lastNobullet();

    $(window).resize(function() {
        lastNobullet();
    });

    $('#contact-us-message__form').submit(function(event) {
        event.preventDefault();
        var form = $(this);
        var valid = true;

        form.find('.error-message').remove();
        form.find('.success-message').remove();

        form.find('input, textarea, select').each(function() {
            if ($(this).val().trim() === '') {
                valid = false;
                $(this).parent().after('<p class="error-message  mt-3 mb-0">This field is required.</p>');
            }
        });

        if (!valid) {
            return;
        }

        $('.loading-form').show();

        setTimeout(function() {
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize()
            }).done(function(data) {
                $('.loading-form').hide();
                form.append('<p class="success-message mt-3 mb-0">Your message has been sent successfully.</p>');
            }).fail(function(data) {
                $('.loading-form').hide();
                form.append('<p class="error-message mt-3 mb-0">Something went wrong. Please try again later.</p>');
            });
        }, 1000);
    });

    $('.carouselTicker-nav').carouselTicker({});

    /*** pricing table2 */
    const pricingMonthlyBtn = $("#monthly-btn"),
        pricingYearlyBtn = $("#yearly-btn"),
        pricingValues = $(".pricing-2__card-price h2, .yearly p");

    if (pricingMonthlyBtn[0] && pricingYearlyBtn[0] && pricingValues.length > 0) {
        pricingMonthlyBtn[0].addEventListener("click", function () {
            updatePricingValuestop("monthly");
            pricingYearlyBtn[0].classList.remove("active");
            pricingMonthlyBtn[0].classList.add("active");
        });

        pricingYearlyBtn[0].addEventListener("click", function () {
            updatePricingValuestop("yearly");
            pricingMonthlyBtn[0].classList.remove("active");
            pricingYearlyBtn[0].classList.add("active");
        });
    }

    function updatePricingValuestop(option) {
        pricingValues.each(function () {
            const pricingValue = $(this);
            const yearlyValue = pricingValue.attr("data-yearly");
            const monthlyValue = pricingValue.attr("data-monthly");

            const newValue = option === "monthly" ? monthlyValue : yearlyValue;
            pricingValue.html(newValue);
        });
    }

    function updatePricingValues(option) {
        pricingValues1.each(function () {
            const pricingValue = $(this);
            const yearlyValue = pricingValue.attr("data-yearly");
            const monthlyValue = pricingValue.attr("data-monthly");

            const newValue = option === "monthly" ? monthlyValue : yearlyValue;
            pricingValue.html(newValue);
        });
    }

    class MagneticButton {
        constructor(options) {
            this.settings = $.extend({
                target: $('[data-magnetic]'),
                class: 'magnetizing',
                attraction: 0.45,
                distance: 100,
                onEnter: function (data) {},
                onExit: function (data) {},
                onUpdate: function (data) {},
            }, options);

            if (!this.settings.target.length) return;

            this.init();
        }

        init() {
            $(window).on('mousemove', (e) => this.magnetize(e));
        }

        distanceFromMouse($target, mouseX, mouseY) {
            let centerX = $target.offset().left + $target.outerWidth() / 2,
                centerY = $target.offset().top + $target.outerHeight() / 2,
                pointX = mouseX - centerX,
                pointY = mouseY - centerY,
                distance = Math.sqrt(Math.pow(pointX, 2) + Math.pow(pointY, 2));

            return Math.floor(distance);
        }

        magnetize(e) {
            let mouseX = e.pageX, mouseY = e.pageY;

            this.settings.target.each((index, element) => {
                let $this = $(element),
                    centerX = $this.offset().left + $this.outerWidth() / 2,
                    centerY = $this.offset().top + $this.outerHeight() / 2,
                    attraction = $this.data('magnetic-attraction') || this.settings.attraction,
                    distance = $this.data('magnetic-distance') || this.settings.distance,
                    deltaX = Math.floor(centerX - mouseX) * -1 * attraction,
                    deltaY = Math.floor(centerY - mouseY) * -1 * attraction,
                    mouseDistance = this.distanceFromMouse($this, mouseX, mouseY),
                    isEnter = $this.data('isEnter') || false,
                    data = {target: $this, y: deltaY, x: deltaX, distance: mouseDistance};

                if (mouseDistance < distance) {
                    gsap.to($this, {y: deltaY, x: deltaX});

                    if (!isEnter) {
                        $this.data('isEnter', true);
                        $this.addClass(this.settings.class);
                        this.settings.onEnter(data);
                    }

                    this.settings.onUpdate(data);
                } else {
                    gsap.to($this, {y: 0, x: 0});

                    if (isEnter) {
                        $this.data('isEnter', false);
                        $this.removeClass(this.settings.class);
                        this.settings.onExit(data);
                    }
                }
            });
        }
    }

    new MagneticButton({
        attraction: (data) => data.target[0].dataset.magneticAttraction,
        distance: (data) => data.target[0].dataset.magneticDistance,
        onEnter: function (data) {
            gsap.to(data.target, {scale: data.target[0].dataset.magneticZoom});
        },
        onExit: function (data) {
            gsap.to(data.target, {scale: 1});
        },
        onUpdate: function (data) {}
    });


    var slider = new Swiper ('.banner__slider', {
        slidesPerView: 1,
        centeredSlides: true,
        loop: true,
        loopedSlides: 6, 
        navigation: {
            prevEl: ".banner__slider__arrow-prev",
            nextEl: ".banner__slider__arrow-next",
        },
    });

    var thumbs = new Swiper ('.gallery-thumbs', {
        slidesPerView: 'auto',
        spaceBetween: 1,
        centeredSlides: true,
        loop: true,
        slideToClickedSlide: true,
    });

    slider.controller.control = thumbs;
    thumbs.controller.control = slider;

    /*client-testimonial__slider***/
    let expert__slider = new Swiper(".expert__slider", {
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        pagination: {
            el: ".expert__slider-dot",
            clickable: true,
        },

        breakpoints: {
            1200: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        }
    });
    
    /*======================================
    recent-project__active
    ========================================*/
    let recent_project__active = new Swiper(".recent-project__active", {
        loop: true,
        slidesPerView: 3,
        spaceBetween: 30,
        centeredSlides: true,
        navigation: {
            prevEl: ".recent-project__slider__arrow-prev",
            nextEl: ".recent-project__slider__arrow-next",
        },
        autoplay: {
            delay: 3000,
        },
        initialSlide: 5,
        breakpoints: {
            1200: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            575: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });

    /*clients-testimonial__slider***/
    let clients_testimonial__slider__slider = new Swiper(".clients-testimonial__slider", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        centeredSlides: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        navigation: {
            prevEl: ".clients-testimonial__slider__arrow-prev",
            nextEl: ".clients-testimonial__slider__arrow-next",
        }
    });

    /*banner-2__slider***/
    let banner_2__slider = new Swiper(".banner-2__slider", {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        centeredSlides: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        navigation: {
            prevEl: ".banner-2__slider__arrow-prev",
            nextEl: ".banner-2__slider__arrow-next",
        }
    });

    /* our-featured-service***/
    let  our_featured_service__slider = new Swiper(".our-featured-service__active", {
        slidesPerView: 4,
        spaceBetween: 30,
        loop: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        navigation: {
            prevEl: ".our-featured-service__slider__arrow-prev",
            nextEl: ".our-featured-service__slider__arrow-next",
        },
        breakpoints: {
            1200: {
                slidesPerView: 4,
            },
            992: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        }
    });

    /* our-team__slider__slider***/
    let  our_team__slider__slider = new Swiper(".our-team__slider", {
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        centeredSlides: true,
        clickable: true,
        autoplay: {
            delay: 3000,
        },
        navigation: {
            prevEl: ".our-team__slider__arrow-prev",
            nextEl: ".our-team__slider__arrow-next",
        },
        breakpoints: {
            1200: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        }
    });


    $('.grid').imagesLoaded(function () {
        var $grid = $('.grid').isotope({
            itemSelector: '.grid-item',
            layoutMode: 'fitRows'
        });

        $('.masonary-menu').on('click', 'button', function () {
            var filterValue = $(this).attr('data-filter');
            $grid.isotope({ filter: filterValue });
        });
        $('.masonary-menu button').on('click', function (event) {
            $(this).siblings('.active').removeClass('active');
            $(this).addClass('active');
            event.preventDefault();
        });
    });

    const instagramwiper = new Swiper('.rr-instagram-active', {
        // Optional parameters
        loop: true,
        slidesPerView: 5,
        autoplay: true,
        spaceBetween: 0,
        breakpoints: {
            '1400': {
                slidesPerView: 5,
            },
            '1200': {
                slidesPerView: 4,
            },
            '992': {
                slidesPerView: 3,
            },
            '768': {
                slidesPerView: 2,
            },
            '576': {
                slidesPerView: 2,
            },
            '0': {
                slidesPerView: 1,
            },

        },
    });


    /*======================================
     circle text 
    ========================================*/
    const text = document.querySelector(".circle-text");
    if(text){
        text.innerHTML = text.innerText
        .split("")
        .map(
            (char, i) => `<span style="transform:rotate(${i * 8.5}deg)">${char}</span>`
        )
        .join("");   
    }
    
    
})(jQuery);