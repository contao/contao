/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', function () {
    var edit = document.querySelector('#main .tl_formbody_edit');
    if (!edit) return;

    // Copy from MooTools
    function isHidden (el) {
        var w = el.offsetWidth, h = el.offsetHeight,
            force = /^tr$/i.test(el.tagName);
        return (w===0 && h===0 && !force) ? true : (w!==0 && h!==0 && !force) ? false : el.style.display === 'none';
    }

    var inputs = edit.querySelectorAll('input, textarea'),
        i;

    for (i = 0; i < inputs.length; i++) {
        var input = inputs[i];

        if (
            !input.getAttribute('disabled')
            && !input.getAttribute('readonly')
            && !isHidden(input)
            && !['checkbox', 'radio', 'submit', 'image'].includes(input.getAttribute('type'))
            && !input.closest('.chzn-search')
            && (!input.hasAttribute('autocomplete') || input.getAttribute('autocomplete') === 'off' || !input.value)
        ) {
            input.focus();
            break;
        }
    }
});
