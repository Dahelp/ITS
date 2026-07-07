$('.sidebar-menu a').each(function(){
    var location = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/\/$/, '');
    var link = this.href;
    if(link == location){
        $(this).parent().addClass('active');
        $(this).closest('.treeview').addClass('active');
    }
});

$('.nav-sidebar a.nav-link').each(function(){
    var location = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/\/$/, '');
    var link = this.href.replace(/\/$/, '');
    if(link === location){
        $(this).addClass('active');
        $(this).parents('.nav-treeview').addClass('menu-open').show();
        $(this).parents('.nav-item').addClass('menu-open');
        $(this).parents('.nav-item').children('a.nav-link').first().addClass('active');
    }
});

// CKEDITOR.replace('editor1');
$( '#editor1' ).ckeditor();
$( '#editor2' ).ckeditor();

$('#reset-filter').click(function(){
    $('#filter input[type=radio]').prop('checked', false);
    return false;
});


	$(".select2").select2({
		language: "ru",
		placeholder: "Начните вводить наименование товара",
		multiple: true,
		cache: true,
		ajax: {			
			url: adminpath + "/order/searchproduct",
			delay: 250,
			dataType: 'json',		
			data: function (params) {
				return {
					q: params.term,
					page: params.page
				};
			},
			processResults: function (data, params) {
				return {
					results: data.items
				};
			}
		}
	});


$(".tiposize").select2({
    placeholder: "Начните вводить наименование товара",
    ajax: {
        url: adminpath + "/plagins/tiposize-technics",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }
    }
});


$(".useradmin").select2({
    placeholder: "Начните вводить контактное лицо",
    minimumInputLength: 1,
    cache: true,
    ajax: {
        url: adminpath + "/user/useradmin",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }
    }
});

$(".usercontact").select2({
    placeholder: "Начните вводить контактное лицо",
    minimumInputLength: 1,
    language: "ru",		
	multiple: true,
	cache: false,
    ajax: {
        url: adminpath + "/user/contacts",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }		
    }
});

$(".companys").select2({
    placeholder: "Начните вводить ИНН или название компании",
    minimumInputLength: 1,
    cache: true,
    ajax: {
        url: adminpath + "/company/inns",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }
    }
});

$(".unewslet").select2({	
    placeholder: "Начните вводить группу подписок",
    tags: true,
    multiple: true,
    minimumResultsForSearch: 10,
    cache: true,
	allowClear: true,
    ajax: {
        url: adminpath + "/newsletter/subscription",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }
    }
});

$(".ugroups").select2({	
    placeholder: "Начните вводить группу пользователей",
    tags: true,
    multiple: true,
    minimumResultsForSearch: 10,
    cache: true,
	allowClear: true,
    ajax: {
        url: adminpath + "/newsletter/groups",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items
            };
        }
    }
});

$('body').on('click', '.switch-newsletter', function(){
    var id = $(this).data('id');
	var checked = $(this).data('checked');
    $.ajax({
        url: adminpath + '/user/addblock',
        data: {id: id, checked: checked},
        type: 'GET',
        success: function(res){
			$('.card-tools').html(res);
			if(checked==1){ $(".unewslet").prop("disabled", true); }
			if(checked==0){ $(".unewslet").prop("disabled", false); }
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});

$(document).on('change', '.ordermanager', function(){
    var id = $(this).val();
	var orderid = $(this).data('orderid');
    $.ajax({
        url: adminpath + '/order/ordermanager',
        data: {id: id, orderid: orderid},
        type: 'GET',
        success: function(res){			
			console.log(res);
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});

$('.rrs-click').click(function(){	
    var price = $( ".price" ).val();
	if ($(this).is(':checked')){
		$('.price_rrs').attr('value', ''+price+'');
	} else {
		$(".price_rrs").attr("value", '');
	}
});

$('#add').on('submit', function(){
     if(!isNumeric( $('#category_id').val() )){
         alert('Выберите категорию');
         return false;
     }
});

function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

$(document).on('change', '.category_id', function() {                   
	var id = $(this).val();
		$.ajax({
			type: "POST",			
			url: adminpath + "/product/filters",
			data: {id:id},
			success: function(id){
				$('.filters').html(id);
				$('.filters').style.display = "block";
			}			

        });
});

/* Search */
var products = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
        wildcard: '%QUERY',
        url: adminpath + '/search/typeahead?query=%QUERY'
    }
});

products.initialize();

function adminSearchEscape(text) {
    return String(text || '').replace(/[&<>"']/g, function(ch) {
        return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[ch];
    });
}

$("#typeahead").typeahead({
    // hint: false,
    highlight: true
},{
    name: 'products',
    display: 'name',
    limit: 10,
    source: products,
    templates: {
        suggestion: function(item) {
            var type = item.type ? '<span class="admin-search-suggest__type">' + adminSearchEscape(item.type) + '</span>' : '';
            var subtitle = item.subtitle ? '<span class="admin-search-suggest__subtitle">' + adminSearchEscape(item.subtitle) + '</span>' : '';
            return '<div class="admin-search-suggest">' +
                '<span class="admin-search-suggest__title">' + adminSearchEscape(item.name) + '</span>' +
                type +
                subtitle +
            '</div>';
        },
        notFound: '<div class="admin-search-suggest admin-search-suggest--empty">Ничего не найдено</div>'
    }
});

$('#typeahead').bind('typeahead:select', function(ev, suggestion) {
    // console.log(suggestion);
    window.location = suggestion.url || (adminpath + '/search?q=' + encodeURIComponent(suggestion.name));
});

$('#typeahead').closest('form').on('submit', function() {
    var q = $.trim($('#typeahead').typeahead('val') || $('#typeahead').val() || '');
    if (!q) {
        return false;
    }
});


/* UPLOAD / DELETE */

/* global AjaxUpload, $ */
(function(){
  // === БАЗА ================================================================
  var adminpath = window.adminpath || '/admin/';

    function parseJsonSafe(raw){
        if (raw == null) return null;
        raw = String(raw).trim().replace(/^<pre[^>]*>/i,'').replace(/<\/pre>$/i,'');
        // быстрые успешные «не JSON» ответы
        if (raw === '' || raw === '1' || /^ok$/i.test(raw)) {
            return { ok: true, result: 1, _raw: raw };
        }
        try { return JSON.parse(raw); }
        catch(e){ console.error('Bad JSON:', raw); return null; }
    }


  function toggleOverlay($btn, on){
    var $ov = $btn.closest('.file-upload').find('.overlay');
    if ($ov.length) $ov.css('display', on ? 'block' : 'none');
  }

  function buildUrl(section, mode, file){
    // приоритет previewUrl с бэка
    if (file.previewUrl) return file.previewUrl;
    // иначе соберём сами
    var base = '/images/' + section + '/';
    if (mode === 'single') return base + 'baseimg/' + file.file;
    if (mode === 'multi')  return base + 'gallery/' + file.file;
    if (mode === 'unload') return base + 'unload/'  + file.file;
    return base + file.file;
  }

  function successOk(data){
    if (!data) return false;
    if (data.ok === true) return true;
    if (data.result === 1) return true;
    if (data.status && String(data.status).toLowerCase() === 'ok') return true;
    // если сервер вернул «1»/«OK», мы из parseJsonSafe уже сделали ok:true
    return false;
    }

  // === ЗАГРУЗКА ============================================================
  var Uploader = {
    // Инициализируем кнопки загрузки (single / multi / unload)
    init: function(){
      if (typeof AjaxUpload === 'undefined') {
        console.error('AjaxUpload не найден. Подключи скрипт AjaxUpload.');
        return;
      }
      this.wireBtn('#single',  'single',  '.single');  // базовое
      this.wireBtn('#multi',   'multi',   '.multi');   // галерея
      this.wireBtn('#unload',  'unload',  '.unload');  // для выгрузки
    },

    wireBtn: function(selector, mode, previewContainerSel){
      var $btn = $(selector);
      if (!$btn.length) return;

      // Раздел берём из data-section (новый) или data-razdel (старый)
      var section = ($btn.data('section') || $btn.data('razdel') || 'product');

      // Доп. настраиваемые размеры можно прокинуть через data-*, если нужно
      var w      = parseInt($btn.data('w')      || 0, 10);
      var h      = parseInt($btn.data('h')      || 0, 10);
      var wmini  = parseInt($btn.data('wmini')  || 0, 10);
      var hmini  = parseInt($btn.data('hmini')  || 0, 10);

      // Для наглядности, куда рисовать превью
      var $preview = $(previewContainerSel);

      new AjaxUpload($btn, {
        action: adminpath + 'media/upload?upload=1',
        name: 'file', // поле файла
        data: {
          section: section,
          mode: mode,
          w: w, h: h, wmini: wmini, hmini: hmini
        },
        onSubmit: function(file, ext){
          if (!(ext && /^(jpg|jpeg|png|gif|webp|avif)$/i.test(ext))) {
            alert('Ошибка! Разрешены: jpg, jpeg, png, gif, webp, avif');
            return false;
          }
          toggleOverlay($btn, true);
        },
        onComplete: function(file, response){
          toggleOverlay($btn, false);
          var data = parseJsonSafe(response);
          if (!data || !data.ok) {
            alert((data && data.error) || 'Ошибка загрузки');
            console.debug('Ответ загрузки:', response);
            return;
          }

          // URL превью
          var url = buildUrl(section, mode, data);

          // HTML превью и отдельная кнопка удаления с нужными data-*
          var html = '';
          if (mode === 'single') {
            // Базовое — одно изображение, перезаписываем контейнер
            html = '<img src="'+url+'" ' +
                   'style="max-height:150px" alt="">' +
                   '<div><button type="button" class="btn btn-danger btn-sm mt-2 del-base" ' +
                   'data-id="0" data-src="'+(data.file || '')+'" data-section="'+section+'">' +
                   'Удалить изображение</button></div>';
            $preview.html(html);
          } else if (mode === 'unload') {
            html = '<img src="'+url+'" ' +
                   'style="max-height:150px" alt="">' +
                   '<div><button type="button" class="btn btn-danger btn-sm mt-2 del-unload" ' +
                   'data-id="0" data-src="'+(data.file || '')+'" data-section="'+section+'">' +
                   'Удалить изображение</button></div>';
            $preview.html(html);
          } else {
            // Галерея — добавляем
            html = '<img src="'+url+'" ' +
                   'class="del-item" style="max-height:150px;cursor:pointer;margin:4px" ' +
                   'data-id="0" data-src="'+(data.file || '')+'" data-section="'+section+'">';
            $preview.append(html);
          }
        }
      });
    }
  };

    // =======================
    // УДАЛЕНИЕ ИЗОБРАЖЕНИЙ
    // =======================

    (function() {
    // базовый путь админки
    var adminpath = window.adminpath || '/admin/';

    // удобный helper для overlay
    function withOverlay(el, on) {
        var wrap = el.closest('.file-upload');
        if (wrap) {
        var ov = wrap.querySelector('.overlay');
        if (ov) ov.style.display = on ? 'block' : 'none';
        }
    }

    // отправка x-www-form-urlencoded
    function postForm(url, dataObj) {
        return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams(dataObj)
        }).then(function(r){ return r.json(); });
    }

    // удаление превью из DOM
    function removePreviewNode(imgEl) {
        var previewWrap = imgEl.closest ? imgEl.closest('.single, .unload') : null;
        if (previewWrap) {
            previewWrap.innerHTML = '';
            return;
        }

        // если это базовая/выгрузочная — обычно она одна: очищаем контейнер
        var parent = imgEl.parentElement;
        if (parent) {
        if (parent.classList.contains('single') || parent.classList.contains('unload')) {
            parent.innerHTML = '';
        } else {
            imgEl.remove();
        }
        }
    }

    // Определяем режим по классам (чтобы не ломать твёрдо прошитую верстку)
    function resolveModeByClass(imgEl) {
        if (imgEl.classList.contains('del-unload')) return 'unload'; // product unload
        if (imgEl.classList.contains('del-item'))   return 'multi';  // галерея
        return 'single';                                           // базовая
    }

    // Один общий обработчик для всех *.del-*
    document.addEventListener('click', function(e) {
        var img = e.target;

        // Поддерживаем обе схемы: либо единый класс .js-del-img, либо старые классы
        if (!(img.classList && (img.classList.contains('js-del-img') || img.classList.contains('del-item') || img.classList.contains('del-base') || img.classList.contains('del-unload')))) {
        return;
        }

        e.preventDefault();

        // подтверждение
        if (!confirm('Подтвердите удаление')) return;

        // собираем данные
        var section = img.dataset.razdel || img.dataset.section || 'product';  // product|complete|review|...
        var id      = img.dataset.id ? parseInt(img.dataset.id, 10) : 0;
        var src     = img.dataset.src || img.getAttribute('data-src') || '';
        var mode    = img.dataset.mode || resolveModeByClass(img);

        if (!src) {
        alert('Не указан файл (src) для удаления');
        return;
        }

        // overlay on
        withOverlay(img, true);

        postForm(adminpath + 'media/delete', {
        section: section,
        id:      String(id),
        src:     src,
        mode:    mode
        })
        .then(function(json) {
        // overlay off
        withOverlay(img, false);

        if (!json || json.ok !== true) {
            var msg = (json && json.error) ? json.error : 'Неизвестная ошибка';
            alert('Ошибка удаления: ' + msg);
            return;
        }

        // успех — сразу убираем превью с страницы
        removePreviewNode(img);
        // по желанию можно показать mini-toast:
        // console.log('Удалено:', section, mode, src);
        })
        .catch(function(err) {
        withOverlay(img, false);
        alert('Ошибка сети: ' + err);
        });
    });
    })();


  // === СТАРТ ===============================================================
  $(function(){
    Uploader.init();
  });
})();





