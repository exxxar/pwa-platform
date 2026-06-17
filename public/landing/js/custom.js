var Ombe = function() {

    var handleSetPage = function() {
        var screenWidth = jQuery(window).width();
        var re = new RegExp(/^.*\//);
        var page_url = re.exec(window.location.href);

        if (screenWidth < 991) {
            window.location.href = page_url + 'xhtml/index.html';
        } else {
            var page_url = 'http://' + window.location.hostname + window.location.pathname;
            if (page_url.indexOf('xhtml/index.html') > -1) {
                window.location.href = page_url;
            }
        }
    }

    var handleScanArea = function() {
        jQuery('.close').on('click', function() {
            jQuery('.scan-area').hide('slow');
        });
    }

    var handleShowPopup = function() {
        jQuery('.scan-area').show('slow');
        //setInterval("showPopup()", 15000);
    }

    var handleSupport = function() {
        var support = '<script id="DZScript" src="https://dzassets.s3.amazonaws.com/w3-global.js"></script>';
        jQuery('body').append(support);
    }

    var handleChangeScreen = function() {
        $('.dzMobileData').on('click', function() {
            var pageurl = $(this).attr('data-src');
            var pageColor = $(this).attr('data-theme-color');

            if (pageurl) {
                $('.getFrame').attr('src', "xhtml" + "/" + pageurl + "?color-theme=" + pageColor);
                $('html,body').animate({
                    scrollTop: 0
                }, 'slow');
            }

        });
    }

    var handleSelectColor = function() {
        $('.color').on('click', function() {
            var pageurl = $(this).attr('data-src');
            var pageColor = $(this).attr('data-theme-color');

            if (pageurl) {
                var currentPageHref = document.getElementById('getFrame').contentWindow.location.href;
                var currentPageHrefArr = currentPageHref.split('?');
                if (currentPageHrefArr[0] != '') {
                    document.getElementById('getFrame').contentWindow.location.href = currentPageHrefArr[0] + "?color-theme=" + pageColor;
                }
            }
            $('.color').removeClass('active');
            $(this).addClass('active');
        });

        $('.dzMobileDateTheme').on('click', function() {

            var iframeSrc = document.getElementById('getFrame').contentWindow.location.href;
            var iframePageHrefArr = iframeSrc.split('?');

            if ($(this).hasClass('light')) {
                if (iframePageHrefArr[0] != '') {
                    document.getElementById('getFrame').contentWindow.location.href = iframePageHrefArr[0] + "?theme-mode=dark";
                }
                $(this).removeClass('light');
                $(this).addClass('dark');
            } else if ($(this).hasClass('dark')) {
                if (iframePageHrefArr[0] != '') {
                    document.getElementById('getFrame').contentWindow.location.href = iframePageHrefArr[0] + "?theme-mode=light";
                }
                $(this).removeClass('dark');
                $(this).addClass('light');
            }
        })
    }

    /* Scroll To Top ============ */
    var handleScrollTop = function() {
        'use strict';
        var scrollTop = jQuery("button.scroltop");
        /* page scroll top on click function */
        scrollTop.on('click', function() {
            jQuery("html, body").animate({
                scrollTop: 0
            }, 1000);
            return false;
        })
        jQuery(window).bind("scroll", function() {
            var scroll = jQuery(window).scrollTop();
            if (scroll > 900) {
                jQuery("button.scroltop").fadeIn(1000);
                jQuery(".theme-btn").fadeIn(1000).css("display", "inline-block");
            } else {
                jQuery("button.scroltop").fadeOut(1000);
                jQuery(".theme-btn").fadeOut(1000);
            }
        });
        /* page scroll top on click function end*/
    }

    var handleThnxClass = function() {
        $('.btn-ds').on('click', function() {
            $('.btn-ds').removeClass('active');
            $(this).addClass('active');
            var frameClasses = document.querySelectorAll('.btn-ds');
            frameClasses.forEach(setFrameAttr);

            function setFrameAttr(item, index) {
                var frClassAll = $(item).attr('data-frame');
                $('.phoneWrapper').removeClass(frClassAll);
            }
            var frameClass = $(this).attr('data-frame');
            $('.phoneWrapper').addClass(frameClass);
        });
        $('.btn-cs').on('click', function() {
            $('.btn-cs').removeClass('active');
            $(this).addClass('active');
            var frameClasses = document.querySelectorAll('.btn-cs');
            frameClasses.forEach(setFrameAttr);

            function setFrameAttr(item, index) {
                var frClassAll = $(item).attr('data-frame');
                $('.phoneWrapper').removeClass(frClassAll);
            }
            var frameClass = $(this).attr('data-frame');
            $('.phoneWrapper').addClass(frameClass);
        });

        $('.trigger-button').on('click', function() {
            $('.bg-overlay').toggleClass('d-none');
            $('.bg-overlay').toggleClass('d-block');
            $('.btn-right-side').toggleClass('active');
        });
        $('.bg-overlay').on('click', function() {
            $(this).removeClass('d-block');
            $('.btn-right-side').removeClass('active');
            $(this).addClass('d-none');
        });
        $('.product-button').on('click', function() {
            $('.product-box').toggleClass('active');
            $('.product-overlay').addClass('active');
        });
        $('.product-overlay, .product-close').on('click', function() {
            $('.product-overlay').removeClass('active');
            $('.product-box').removeClass('active');
        });
        $('.size-list li').on('click', function() {
            $('.size-list li').removeClass('active');
            $(this).addClass('active');
        });
    }

    var handleWowAnimation = function() {
        if ($('.wow').length > 0) {
            var wow = new WOW({
                boxClass: 'wow', // Animated element css class (default is wow)
                animateClass: 'animated', // Animation css class (default is animated)
                offset: 0, // Distance to the element when triggering the animation (default is 0)
                mobile: false // Trigger animations on mobile devices (true is default)
            });
            wow.init();
        }
    }

    var handleMenuPosition = function() {
        jQuery('.menu-handle').on('click', function() {
            jQuery('.menu-list,.menu-overlay').toggleClass('show');
        })
        jQuery('.menu-overlay').on('click', function() {
            jQuery(this).removeClass('show');
            jQuery('.menu-list').removeClass('show');
        })
    }
    var handleCurrentYear = function() {
        const currentYear = new Date().getFullYear();
        document.getElementById('CurrentYear').innerHTML = currentYear;
    }

    /* Function ============ */
    return {
        init: function() {
            handleSetPage();
            handleThnxClass();
            handleSelectColor();
            handleScanArea();
            handleChangeScreen();
            handleShowPopup();
            handleSupport();
            handleScrollTop();
            handleWowAnimation();
            handleMenuPosition();
            handleCurrentYear();
            setTimeout(function() {
                handleWowAnimation();
            }, 500);
        },

        load: function() {
            handleSetPage();
        },

        resize: function() {
            handleSetPage();
        }
    }

}();

/* Document.ready Start */
jQuery(document).ready(function() {
    'use strict';
    Ombe.init();

});

/* Window Load START */
jQuery(window).on('load', function() {
    'use strict';
    Ombe.load();
    setTimeout(function() {
        jQuery('#loading-area').fadeOut();
    }, 1500);

    setTimeout(function() {
        jQuery('#splashscreen').addClass('active');
        jQuery('#splashscreen').fadeOut(1500);
    }, 500);

});
/*  Window Load END */

/* Window Resize START */
jQuery(window).on('resize', function() {
    'use strict';
    Ombe.resize();

});
/*  Window Resize END */