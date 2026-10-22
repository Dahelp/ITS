(function () {
    'use strict';
    var field = document.getElementById('avito-title');
    var counter = document.getElementById('avito-title-count');
    var error = document.getElementById('avito-title-error');
    if (!field || !counter || !error) return;
    var limit = Number(field.getAttribute('data-title-limit'));
    function update() {
        var length = Array.from(field.value).length;
        var tooLong = length > limit;
        var message = tooLong ? 'Превышение на ' + (length - limit) + ' симв. Сократите название перед сохранением.' : '';
        counter.textContent = length + '/' + limit;
        counter.classList.toggle('text-danger', tooLong);
        field.classList.toggle('is-invalid', tooLong);
        field.setAttribute('aria-invalid', String(tooLong));
        field.setCustomValidity(message);
        error.textContent = message;
        error.hidden = !tooLong;
    }
    field.addEventListener('input', update);
    field.addEventListener('change', update);
    update();
})();
