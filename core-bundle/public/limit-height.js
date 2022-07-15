/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('div.limit_height').forEach(function (div) {
        const parent = div.parentNode.closest('.tl_content');

        // Return if the element is a wrapper
        if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

        const hgt = Number(div.className.replace(/[^0-9]*/, ''))

        // Return if there is no height value
        if (!hgt) return;

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');
        toggler.append(button);

        // Disable the function if the preview height is below the max-height
        if (div.offsetHeight <= hgt) {
            return;
        }

        div.style.height = hgt+'px';

        button.addEventListener('click', function () {
            if (div.offsetHeight > hgt) {
                div.style.height = hgt+'px';
            } else {
                div.style.height = 'auto';
            }
        });

        div.append(toggler);
    });
});
