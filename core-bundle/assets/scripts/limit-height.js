window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('div.limit_height').forEach(function (div) {
        const parent = div.parentNode.closest('.tl_content');

        // Return if the element is a wrapper
        if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

        const hgt = Number(div.className.replace(/[^0-9]*/, ''))

        // Return if there is no height value
        if (!hgt) return;

        const height = div.offsetHeight;

        // Resize the element
        div.style.height = hgt+'px';

        // Do not add the toggle if the preview height is below the max-height
        if (height <= hgt) return;

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');

        button.addEventListener('click', function () {
            if (div.offsetHeight > hgt) {
                div.style.height = hgt+'px';
            } else {
                div.style.height = 'auto';
            }
        });

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');
        toggler.append(button);

        div.append(toggler);
    });
});
