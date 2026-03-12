(function ($) {
    "use strict";
    
    // Dropdown on mouse hover
    $(document).ready(function () {
        function toggleNavbarMethod() {
            if ($(window).width() > 992) {
                $('.navbar .dropdown').on('mouseover', function () {
                    $('.dropdown-toggle', this).trigger('click');
                }).on('mouseout', function () {
                    $('.dropdown-toggle', this).trigger('click').blur();
                });
            } else {
                $('.navbar .dropdown').off('mouseover').off('mouseout');
            }
        }
        toggleNavbarMethod();
        $(window).resize(toggleNavbarMethod);
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    function initCarousel(selector, options) {
        if (!$.fn || typeof $.fn.owlCarousel !== 'function') {
            return;
        }

        var $el = $(selector);
        if (!$el.length) {
            return;
        }

        try {
            $el.owlCarousel(options);
        } catch (err) {
            console.warn('Owl init failed for', selector, err);
        }
    }

    // Vendor carousel
    initCarousel('.vendor-carousel', {
        loop: true,
        margin: 29,
        nav: false,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:2
            },
            576:{
                items:3
            },
            768:{
                items:4
            },
            992:{
                items:5
            },
            1200:{
                items:6
            }
        }
    });


    // Related carousel
    initCarousel('.related-carousel', {
        loop: true,
        margin: 29,
        nav: false,
        autoplay: true,
        smartSpeed: 1000,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:2
            },
            768:{
                items:3
            },
            992:{
                items:4
            }
        }
    });


    // Product Quantity
    $('.quantity button').on('click', function () {
        var button = $(this);
        var $input = button.parent().parent().find('input');
        var oldValue = parseInt($input.val(), 10);

        if (isNaN(oldValue) || oldValue < 1) {
            oldValue = 1;
        }

        var newVal = oldValue;
        if (button.hasClass('btn-plus')) {
            newVal = oldValue + 1;
        } else {
            newVal = Math.max(1, oldValue - 1);
        }

        $input.val(newVal);
    });

    $('.quantity input').on('input blur', function () {
        var val = parseInt($(this).val(), 10);
        if (isNaN(val) || val < 1) {
            $(this).val(1);
        }
    });
    
})(jQuery);
