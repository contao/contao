window.addEventListener('DOMContentLoaded', function() {
    const pickerScrollTo = document.querySelector('.tl_listing.picker input[name^=picker]:checked');
    if (!pickerScrollTo) return;

    window.sessionStorage.setItem('contao_backend_offset', String(pickerScrollTo.getBoundingClientRect().top + window.scrollY))
    Backend.initScrollOffset()
});
