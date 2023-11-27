window.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header');
    if (!header) return;

    let lastScroll = 0;

    window.addEventListener('scroll', function() {
        // Make sure the scroll value is between 0 and maxScroll
        const currentScroll = Math.max(0, Math.min(document.documentElement.scrollHeight - document.documentElement.clientHeight, window.scrollY));

        if (lastScroll < currentScroll) {
            header.classList.add('header--hidden');
        } else if (lastScroll > currentScroll) {
            header.classList.remove('header--hidden');
        }

        lastScroll = currentScroll;
    }, {
        passive: true
    });
});
