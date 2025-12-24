$(document).ready(() => {
    const currentYear = new Date().getFullYear();
    $('#current-year').text(currentYear);

    $('#hamburger-menu').click(() => {
        const isActive = $('#hamburger-menu').hasClass('active');
        $('#hamburger-menu').toggleClass('active');
        $('#nav-menu').toggleClass('active');
        $('#hamburger-menu').attr('aria-expanded', !isActive);
    });

    $(document).click((e) => {
        if (!$(e.target).closest('.nav').length) {
            $('#hamburger-menu').removeClass('active');
            $('#nav-menu').removeClass('active');
            $('#hamburger-menu').attr('aria-expanded', 'false');
        }
    });

    const searchInput = $('#search-input');
    let searchTimeout;

    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            hideSearchResults();
        }
    });

    $('form[role="search"]').on('submit', function(e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        if (query.length > 0) {
            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    });

    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 60
            }, 600);
        }
    });

    const navText = ["<i class='bx bx-chevron-left' aria-hidden='true'></i>", "<i class='bx bx-chevron-right' aria-hidden='true'></i>"];

    $('#hero-carousel').owlCarousel({
        items: 1,
        dots: false,
        loop: true,
        nav: true,
        navText: navText,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        animateOut: 'fadeOut',
        animateIn: 'fadeIn'
    });

    $('#top-movies-slide').owlCarousel({
        items: 2,
        dots: false,
        loop: true,
        autoplay: true,
        autoplayTimeout: 3000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1
            },
            500: {
                items: 2
            },
            768: {
                items: 3
            },
            1280: {
                items: 4
            },
            1600: {
                items: 6
            }
        }
    });

    $('.movies-slide').owlCarousel({
        items: 2,
        dots: false,
        nav: true,
        navText: navText,
        margin: 15,
        loop: true,
        responsive: {
            0: {
                items: 1
            },
            500: {
                items: 2
            },
            768: {
                items: 3
            },
            1280: {
                items: 4
            },
            1600: {
                items: 6
            }
        }
    });

    $('.movie-item').on('click', function(e) {
        const movieTitle = $(this).find('.movie-item-title').text();
        console.log('Movie clicked:', movieTitle);
    });

    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        return isValid;
    };

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

function performSearch(query) {
    console.log('Searching for:', query);
}

function hideSearchResults() {
}