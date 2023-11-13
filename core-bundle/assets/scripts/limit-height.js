window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('div.limit_height').forEach(function(div) {
        window.console && console.warn('Using "limit_height" class on child_record_callback is deprecated. Set a list.sorting.limitHeight in your DCA instead.');

        const parent = div.parentNode.closest('.tl_content');

        // Return if the element is a wrapper
        if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

        const hgt = Number(div.className.replace(/[^0-9]*/, ''))

        // Return if there is no height value
        if (!hgt) return;

        const style = window.getComputedStyle(div, null);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        const height = div.clientHeight - padding;

        // Do not add the toggle if the preview height is below the max-height
        if (height <= hgt) return;

        // Resize the element if it is higher than the maximum height
        div.style.height = hgt+'px';

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.title = Contao.lang.expand;
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');

        button.addEventListener('click', function() {
            if (div.style.height == 'auto') {
                div.style.height = hgt+'px';
                button.title = Contao.lang.expand;
            } else {
                div.style.height = 'auto';
                button.title = Contao.lang.collapse;
            }
        });

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');
        toggler.append(button);

        div.append(toggler);
    });
});
