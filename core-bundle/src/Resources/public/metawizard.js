window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tl_metawizard [data-delete]').forEach(function (el) {
        el.addEventListener('click', function(event) {
            event.preventDefault();
            el.closest('li').querySelectorAll('input, textarea').forEach(function(input) {
                input.value = '';
            });
        });
    });
});
