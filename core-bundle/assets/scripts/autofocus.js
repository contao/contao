window.addEventListener('DOMContentLoaded', function () {
    const edit = document.querySelector('#main .tl_formbody_edit');
    if (!edit) return;

    const inputs = edit.querySelectorAll('input, textarea');

    for (let i = 0; i < inputs.length; i++) {
        const input = inputs[i];

        if (
            !input.disabled
            && !input.readonly
            && input.offsetWidth
            && input.offsetHeight
            && !['checkbox', 'radio', 'submit', 'image'].includes(input.type)
            && !input.closest('.chzn-search')
            && (!input.autocomplete || input.autocomplete === 'off' || !input.value)
        ) {
            input.focus();
            break;
        }
    }
});
