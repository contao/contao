/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-to-clipboard]').forEach(function (link) {
        var data = JSON.parse(link.getAttribute('data-to-clipboard'));

        var clipboardFallback = function () {
            var input = document.createElement('input');
            input.value = data.content;
            document.body.appendChild(input);
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        link.addEventListener('click', function (event) {
            event.preventDefault();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(data.content).catch(clipboardFallback);
            } else {
                clipboardFallback();
            }
        });
    });
});
