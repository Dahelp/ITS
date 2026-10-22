(function () {
    'use strict';
    var status = document.getElementById('avito-id-sync-status');
    if (!status || !window.jQuery) return;
    var busy = false, lastRevision = null;
    function check() {
        if (busy || document.hidden) return;
        busy = true;
        window.jQuery.ajax({url: status.dataset.url, dataType: 'json', timeout: 20000})
            .done(function (data) {
                if (data.busy) return;
                if ((Number(data.assigned) > 0 || Number(data.deleted) > 0) && lastRevision !== data.checked_at) {
                    if (window.jQuery.fn.dataTable.isDataTable('#avito-table')) {
                        window.jQuery('#avito-table').DataTable().ajax.reload(null, false);
                    }
                }
                lastRevision = data.checked_at;
                if (data.errors && data.errors.length) {
                    status.textContent = data.errors.join(' ') + ' Проверка повторится автоматически.';
                    return;
                }
                status.textContent = Number(data.pending) > 0
                    ? 'Ожидают номер от Авито: ' + Number(data.pending) + '. Проверяем раз в минуту, пока открыт список.'
                    : 'Все номера объявлений получены из Авито.';
                if (Number(data.deletions_pending) > 0) status.textContent += ' Ожидают подтверждения снятия: ' + Number(data.deletions_pending) + '.';
            })
            .fail(function () {
                status.textContent = 'Не удалось проверить номера Авито. Повторим проверку автоматически.';
            })
            .always(function () { busy = false; });
    }
    window.jQuery(function () { check(); });
    window.setInterval(check, 60000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
})();
