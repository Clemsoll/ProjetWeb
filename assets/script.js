$(document).ready(function() {
    $('#menu-toggle').click(function() {
        $('#mobile-menu').slideToggle();
    });

    $(document).click(function(e) {
        if(!$(e.target).closest('header').length) {
            if($(window).width() < 768) {
                $('#mobile-menu').slideUp();
            }
        }
    });

    $(window).resize(function() {
        if($(window).width() >= 768) {
            $('#mobile-menu').hide();
            $('#navbar').show();
        }
    });

    $('a[href^="/"]:not([href*="pages"])').each(function() {
        $(this).click(function(e) {
            if($(window).width() < 768) {
                $('#mobile-menu').slideUp();
            }
        });
    });
});
