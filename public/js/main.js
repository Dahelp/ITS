$(function () {
    if ($.fn.select2) {
        $('.js-filter-select').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        $('.js-filter-select').each(function () {
            const placeholder = $(this).data('placeholder') || 'Выберите значение';

            $(this).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                minimumResultsForSearch: 10
            });
        });
    }

    if (/^\/podbor\/[a-z0-9-]+\/?$/i.test(window.location.pathname)) {
        return;
    }

    function parseCurrentState() {
        const path = window.location.pathname.replace(/\/+$/, '');
        const match = path.match(/\/category\/([^\/]+)(?:\/(.+))?$/i);

        let categoryAlias = '';
        let mainAlias = '';

        if (match) {
            categoryAlias = decodeURIComponent(match[1] || '');
            mainAlias = decodeURIComponent(match[2] || '');
        }

        const params = new URLSearchParams(window.location.search);
        const filterParam = params.get('filter') || '';
        const extraIds = filterParam
            .split(',')
            .map(v => parseInt(v, 10))
            .filter(v => !isNaN(v) && v > 0);

        return {
            categoryAlias,
            mainAlias,
            extraIds
        };
    }

    function buildUrl(categoryAlias, mainAlias, extraIds) {
        let url = '/category/' + encodeURIComponent(categoryAlias);

        if (mainAlias) {
            url += '/' + String(mainAlias)
                .split('/')
                .map(part => encodeURIComponent(part))
                .join('/');
        }

        extraIds = [...new Set(extraIds.map(v => parseInt(v, 10)).filter(v => !isNaN(v) && v > 0))];

        if (extraIds.length) {
            url += '?filter=' + extraIds.join(',');
        }

        return url;
    }

    function getSelectedFiltersFromUI() {
        const selected = [];

        $('.js-filter-select').each(function () {
            const $select = $(this);
            const $option = $select.find('option:selected');

            const id = parseInt($option.val(), 10);
            const alias = $option.data('alias') || '';

            if (!isNaN(id) && id > 0) {
                selected.push({
                    id: id,
                    alias: alias
                });
            }
        });

        return selected;
    }

    $(document).off('change.filterSelect').on('change.filterSelect', '.js-filter-select', function () {
      const $changedSelect = $(this);
      const state = parseCurrentState();

      let categoryAlias = $changedSelect.data('category-alias') || state.categoryAlias || '';
      categoryAlias = String(categoryAlias).trim();

      const currentMainAlias = state.mainAlias;

      if (!categoryAlias) {
          return;
      }

      const selected = getSelectedFiltersFromUI();

      if (!selected.length) {
          window.location.href = '/category/' + encodeURIComponent(categoryAlias);
          return;
      }

      if (!currentMainAlias) {
          const first = selected[0];
          const extraIds = selected.slice(1).map(item => item.id);

          window.location.href = buildUrl(categoryAlias, first.alias, extraIds);
          return;
      }

      const mainSelected = selected.find(item => item.alias === currentMainAlias);

      if (mainSelected) {
          const extraIds = selected
              .filter(item => item.alias !== currentMainAlias)
              .map(item => item.id);

          window.location.href = buildUrl(categoryAlias, currentMainAlias, extraIds);
          return;
      }

      const newMain = selected[0];
      const extraIds = selected.slice(1).map(item => item.id);

      window.location.href = buildUrl(categoryAlias, newMain.alias, extraIds);
  });
});

/* Sort product */
$(document).ready(function () {
 $(".sort-inner span").click(function () {
	var id = $(this).attr('id');
	
	$('.sort-inner span').toggleClass('active', false);
	$('.sort-inner span#'+$(this).attr('id')+'').toggleClass('active');
	$.ajax({
	    url: location.href,
            data: 'sort='+id,
            type: 'GET',
            beforeSend: function(){
                $('.preloader').fadeIn(300, function(){
                    $('.product-one').hide();
                });
		
            },
            success: function(res){
                $('.preloader').delay(500).fadeOut('slow', function(){
                    $('.product-one').html(res).fadeIn();
                    var url = location.search.replace(/sort(.+?)(&|$)/g, ''); //$2
                    var newURL = location.pathname + url + (location.search ? "&" : "?") + "sort=" + id;
                    newURL = newURL.replace('&&', '&');
                    newURL = newURL.replace('?&', '?');
                    history.pushState({}, '', newURL);
			
                });
		
            },
            error: function () {
                alert('Ошибка!');
            }
        });
		
 });   
});

//addinn
//$(document).ready(function () {
$('body').on('click', '.btn-inn', function(){



			var inn = $('.inn').val();

            $.ajax({
                type: "GET",
                url: '/cart/inn',
				data: {inn:inn},
                success: function(res) {					
					$('.innok').html(""+res+"");
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Обработка ошибки
                    $(".innok").text("Не удалось подключиться. Статус код: " + jqXHR.status);
                }
            });

});	
  


(function($){
  var lastXhr = null;
  var endpoint = '/search/typeahead';
  var debounceTimer = null;

  var taCache = {
    query: '',
    data: { keywords: [], products: [] }
  };

  function escapeHtml(str){
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildSearchUrl(q){
    return '/search/?s=' + encodeURIComponent(q || '');
  }

  function buildProductUrl(item){
    if (item && item.alias) return '/product/' + item.alias;
    return buildSearchUrl(item && item.name ? item.name : '');
  }

  function buildImg(item){
    if (item && item.img) return '/images/product/mini/' + item.img;
    return '/images/no-photo.png';
  }

  function normalizeResponse(resp){
    if (!resp || typeof resp !== 'object') {
      return { keywords: [], products: [] };
    }
    return {
      keywords: Array.isArray(resp.keywords) ? resp.keywords : [],
      products: Array.isArray(resp.products) ? resp.products : []
    };
  }

  function fetchTypeahead(query, callback){
    query = $.trim(query || '');

    if (!query || query.length < 2) {
      taCache.query = query;
      taCache.data = { keywords: [], products: [] };
      callback(taCache.data);
      return;
    }

    if (taCache.query === query) {
      callback(taCache.data);
      return;
    }

    if (lastXhr && lastXhr.readyState !== 4) {
      try { lastXhr.abort(); } catch(e){}
    }

    lastXhr = $.ajax({
      url: endpoint,
      type: 'GET',
      dataType: 'json',
      cache: false,
      data: { query: query },
      headers: { 'Accept': 'application/json' }
    })
    .done(function(resp){
      taCache.query = query;
      taCache.data = normalizeResponse(resp);
      callback(taCache.data);
    })
    .fail(function(){
      taCache.query = query;
      taCache.data = { keywords: [], products: [] };
      callback(taCache.data);
    });
  }

  function productsSource(query, syncResults, asyncResults){
    syncResults([]);

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function(){
      fetchTypeahead(query, function(resp){
        asyncResults((resp.products || []).slice(0, 8));
      });
    }, 120);
  }

  function buildKeywordsHtml(){
    var keywords = taCache.data && Array.isArray(taCache.data.keywords)
      ? taCache.data.keywords
      : [];

    if (!keywords.length) return '';

    var html = '<div class="tt-keywords">';
    for (var i = 0; i < keywords.length; i++) {
      var text = escapeHtml(keywords[i]);
      html += '<a class="tt-keyword" href="#" data-value="' + text + '">' + text + '</a>';
    }
    html += '</div>';

    return html;
  }

  function refreshTypeaheadWithQuery(newQuery){
    taCache.query = '';
    $input.typeahead('val', newQuery);
    $input.trigger('focus');
    $input.trigger('input');

    setTimeout(function(){
      try { $input.typeahead('open'); } catch(e){}
    }, 0);
  }

  var $input = $('#typeahead');
  var $form = $('.header-search__form');
  var $submit = $form.find('.header-search__submit');

  if (!$input.length || !$form.length) return;

  try { $input.typeahead('destroy'); } catch(e){}

  $input.typeahead(
    {
      minLength: 2,
      highlight: true,
      hint: false
    },
    {
      name: 'products',
      display: 'name',
      limit: 8,
      source: productsSource,
      templates: {
        header: function(){
          return buildKeywordsHtml() +
            '<div class="tt-section-title">Подходящие товары</div>' +
            '<div class="tt-products">';
        },
        empty: function(){
          return buildKeywordsHtml() +
            '<div class="tt-empty">Товар не найден. Попробуйте другой запрос.</div>';
        },
        suggestion: function(item){
          var img = buildImg(item);
          var href = buildProductUrl(item);
          var name = escapeHtml(item && item.name ? item.name : '');
          var price = item && item.price ? escapeHtml(item.price) + ' руб' : '';
          var category = item && item.category ? escapeHtml(item.category) : '';

          return '' +
            '<a class="tt-suggestion" href="' + href + '">' +
              '<div class="tt-images"><img class="img-fit" loading="lazy" src="' + img + '" alt="' + name + '"></div>' +
              '<div class="tt-info">' +
                '<div class="tt-name">' + name + '</div>' +
                (category ? '<div class="tt-meta">' + category + '</div>' : '') +
              '</div>' +
              (price ? '<div class="tt-price">' + price + '</div>' : '') +
            '</a>';
        },
        footer: '</div>'
      }
    }
  );

  $(document)
    .off('click.ttKeyword')
    .on('click.ttKeyword', '.tt-keyword', function(e){
      e.preventDefault();

      var keyword = ($(this).data('value') || $(this).text() || '').trim();
      var current = ($input.typeahead('val') || '').trim();

      if (!keyword) return;

      var currentLc = current.toLowerCase();
      var keywordLc = keyword.toLowerCase();

      var newQuery = current;
      if (currentLc.indexOf(keywordLc) === -1) {
        newQuery = current ? (current + ' ' + keyword) : keyword;
      }

      refreshTypeaheadWithQuery(newQuery);
    });

  $input
    .off('typeahead:select.tt')
    .on('typeahead:select.tt', function(ev, item){
      if (item && item.alias) {
        window.location.href = buildProductUrl(item);
        return;
      }

      var q = $input.typeahead('val') || (item && item.name) || '';
      if (q) {
        window.location.href = buildSearchUrl(q);
      }
    });

  $form
    .off('submit.tt')
    .on('submit.tt', function(e){
      e.preventDefault();
      var q = $.trim($input.typeahead('val') || $input.val() || '');
      if (!q) return false;
      window.location.href = buildSearchUrl(q);
      return false;
    });

  $submit
    .off('mousedown.tt click.tt')
    .on('mousedown.tt', function(e){
      e.preventDefault();
    })
    .on('click.tt', function(e){
      e.preventDefault();
      e.stopPropagation();
      $form.triggerHandler('submit');
      return false;
    });

})(jQuery);


/*Button cart product*/
document.addEventListener('click', function(e){
  const btn = e.target.closest('.js-open-cart');
  if(!btn) return; 
});

document.addEventListener('click', function(e){
  const btn = e.target.closest('.pc-iconbtn');
  if(!btn) return;
  e.preventDefault();
  e.stopPropagation();
});

/* =========================
   CART / CHECKOUT — STABLE (FINAL FULL)
   Includes:
   - Safe routing: cart_table -> #cartTableWrap, cart_modal -> modal only
   - Modal +/- / delete / clear
   - Cart page +/- / delete
   - Header qty/sum sync after ANY operation
   - Step1/Step2/Step3 right summary sync
   - Checkout steps router + restore
   - Catalog/Product buttons sync: <a.korzina-ID> ↔ <button.vkorzine-ID>
   - window.getCart() / window.clearCart()
   - PATH-safe URLs
   - ✅ Step2 readonly items refresh (orderItemsWrap) without page reload
   ========================= */

(function($){
  'use strict';

  var NS = '.cartStable';
  var inFlight = false;

  // BASE PATH (если сайт не в корне)
  var BASE = (typeof window.PATH !== 'undefined' && window.PATH !== null) ? String(window.PATH) : '';
  if (BASE === '/') BASE = '';
  function url(p){ return BASE + p; }

  // -------------------------
  // Utils
  // -------------------------
  function parseFloatSafe(txt){
    return parseFloat(String(txt || '0').replace(',', '.')) || 0;
  }
  function parseIntSafe(txt){
    return parseInt(String(txt || '0'), 10) || 0;
  }

  function currencyPartsFromText(text){
    text = String(text || '').trim();
    var left  = (text.match(/^[^\d]+/) || [''])[0];
    var right = (text.match(/[^\d]+$/) || [''])[0];
    return { left: left, right: right };
  }

  function formatMoneyWithCurrency(v, curText){
    v = Math.max(0, Number(v) || 0);
    var c = currencyPartsFromText(curText);
    var s = v.toFixed(2).replace(/\.?0+$/,'');
    return c.left + s + c.right;
  }
  
  // -------------------------
  // Detect response type
  // -------------------------
  function isCartTableHtml(html){
    try{
      var $t = $('<div>').html(html);
      return $t.find('.cart-qty').length > 0 &&
             $t.find('.cart-sum').length > 0;
    }catch(e){
      return false;
    }
  }

  function isCartModalHtml(html){
    try{
      var $t = $('<div>').html(html);
      return $t.find('.cart-qty-modal').length > 0;
    }catch(e){
      return false;
    }
  }

  // -------------------------
  // Read state from cart_table (single source of truth)
  // -------------------------
  function readCartTableState(){
    var $w = $('#cartTableWrap');
    var sumText = $.trim($w.find('.cart-sum').first().text());

    return {
      qty:      parseIntSafe($w.find('.cart-qty').first().text()),
      sumText:  sumText,
      weight:   $.trim($w.find('.cart-weight').first().text()),
      volume:   $.trim($w.find('.cart-volume').first().text()),

      subtotal: parseFloatSafe($w.find('.cart-subtotal-val').last().text()),
      discount: parseFloatSafe($w.find('.cart-discount-val').last().text()),

      promoCode:    $.trim($w.find('.promo-code-val').first().text()),
      promoApplied: parseIntSafe($w.find('.promo-applied').first().text()) === 1,

      promoOk:  parseIntSafe($w.find('.promo-state').attr('data-ok')),
      promoMsg: String($w.find('.promo-state').attr('data-msg') || '')
    };
  }

  // -------------------------
  // Header sync
  // -------------------------
  function setHeader(qty, sumText){
    qty = parseIntSafe(qty);

    $('#cart-total').text(String(qty));

    if ($('.cart-block').length && $('.cart-no-product').length){
      if (qty > 0){
        $('.cart-block').show();
        $('.cart-no-product').hide();
      } else {
        $('.cart-block').hide();
        $('.cart-no-product').show();
      }
    }
  }

  function updateHeaderFromCartTable(){
    if (!$('#cartTableWrap').length) return;
    var st = readCartTableState();
    setHeader(st.qty, st.sumText);
  }

  function updateHeaderFromModalHtml(html){
    var $tmp = $('<div>').html(html);
    var qty = $.trim($tmp.find('.cart-qty-modal').last().text());
    var sum = $.trim($tmp.find('.cart-sum-modal').last().text());
    if (qty !== '' || sum !== ''){
      setHeader(qty, sum);
    }
  }

  // -------------------------
  // Summary sync (right block Step1/Step2/Step3)
  // -------------------------
  function updateSummaryFromCartTable(){
    if (!$('#cartTableWrap').length) return;

    var st = readCartTableState();
    var $scope = $('#checkoutForm').length ? $('#checkoutForm') : $(document);

    // qty
    $scope.find('.js-qty').text(String(st.qty));

    // subtotal ("Товары")
    $scope.find('.js-subtotal').text(formatMoneyWithCurrency(st.subtotal, st.sumText));

    // discount row (Bootstrap-safe: d-none <-> d-flex)
    var d = st.discount;
    if (!isFinite(d)) d = 0;
    if (Math.abs(d) < 0.01) d = 0;

    var $row = $scope.find('.js-discount-row');

    if (d > 0){
      var discountText = formatMoneyWithCurrency(d, st.sumText);
      var c = currencyPartsFromText(st.sumText);
      var pure = discountText.replace(c.left, '').replace(c.right, '');

      $scope.find('.js-discount').text('-' + pure);

      $row.removeClass('d-none').addClass('d-flex').attr('aria-hidden', 'false');
    } else {
      $scope.find('.js-discount').text('');
      $row.addClass('d-none').removeClass('d-flex').attr('aria-hidden', 'true');
    }

    // weight/volume
    if (st.weight !== '') $scope.find('.js-weight').text(st.weight);
    if (st.volume !== '') $scope.find('.js-volume').text(st.volume);

    // total ("Итого")
    $scope.find('.js-total').text(st.sumText);

    // empty -> reset step storage
    if (st.qty <= 0){
      try { localStorage.removeItem('checkout_step'); } catch(e){}
      try { sessionStorage.removeItem('checkout_step'); } catch(e){}
    }
  }

  // -------------------------
  // Promo UI sync (Step2)
  // -------------------------
  function updatePromoUiFromCartTable(){
    var $input = $('#promoCodeInput');
    var $apply = $('#btnApplyPromo');
    var $clear = $('#btnClearPromo');
    var $msg   = $('#promoMsg');

    if (!$input.length || !$apply.length) return;
    if (!$('#cartTableWrap').length) return;

    var st = readCartTableState();

    if (st.promoApplied && st.promoCode){
      $input.val(st.promoCode).prop('readonly', true);
      $apply.prop('disabled', true);
      if ($clear.length) $clear.show().prop('disabled', false);

      if ($msg.length){
        var m = st.promoMsg || '✅ Промокод применён';
        $msg.show().css('color', st.promoOk ? '#16a34a' : '#ef4444').text(m);
      }
    } else {
      $input.prop('readonly', false);
      $apply.prop('disabled', false);
      if ($clear.length) $clear.hide().prop('disabled', true);
      if ($msg.length) $msg.hide().text('');
    }
  }

  function syncAllFromCartTable(){
    updateHeaderFromCartTable();
    updateSummaryFromCartTable();
    updatePromoUiFromCartTable();
  }

  // -------------------------
  // ✅ Step2 readonly items refresh
  // Требует:
  // - контейнер: #orderItemsWrap
  // - endpoint: /cart/items (возвращает HTML списка)
  // -------------------------
  function refreshOrderItemsReadonly(){
    if (!$('#orderItemsWrap').length) return;

    $.ajax({
      url: url('/cart/items'),
      type: 'GET',
      cache: false,
      success: function(html){
        $('#orderItemsWrap').html(html);
      }
    });
  }

  // -------------------------
  // Catalog/Product buttons sync
  // -------------------------
  function showBuyButton(pid){
    $('a.korzina-' + pid).removeAttr('style').show();
    $('button.vkorzine-' + pid).hide();
  }
  function showInCartButton(pid){
    $('a.korzina-' + pid).hide();
    $('button.vkorzine-' + pid).removeAttr('style').show();
  }

  function getProductIdsFromModalHtml(html){
    var ids = {};
    try{
      var $t = $('<div>').html(html);
      $t.find('tr[data-product-id]').each(function(){
        var pid = parseInt($(this).attr('data-product-id') || '0', 10);
        if (pid > 0) ids[pid] = 1;
      });
    }catch(e){}
    return ids;
  }

  function syncCatalogButtonsFromIds(ids){
    $('[class*="vkorzine-"]').hide();
    $('a[class*="korzina-"]').each(function(){ $(this).removeAttr('style').show(); });

    Object.keys(ids).forEach(function(pid){
      showInCartButton(pid);
    });
  }

  function getProductIdsFromCartTable(){
    var ids = {};
    var $w = $('#cartTableWrap');
    if(!$w.length) return ids;

    $w.find('[id^="wishlist-"]').each(function(){
      var m = String(this.id).match(/^wishlist-(\d+)$/);
      if (m) ids[parseInt(m[1], 10)] = 1;
    });

    $w.find('.cart-action--fav[data-id]').each(function(){
      var pid = parseInt($(this).attr('data-id') || '0', 10);
      if (pid > 0) ids[pid] = 1;
    });

    return ids;
  }

  function syncCatalogButtons(){
    var ids = getProductIdsFromCartTable();

    $('button[class*="vkorzine-"]').hide();
    $('a[class*="korzina-"]').each(function(){ $(this).removeAttr('style').show(); });

    Object.keys(ids).forEach(function(pid){
      showInCartButton(pid);
    });
  }

  // -------------------------
  // Modal
  // -------------------------
  function showCartModal(html){
    var modalEl = document.getElementById('exampleModalLive');
    var $modal = $('#exampleModalLive');

    $modal.find('.modal-body').html(html);

    var isEmpty = $.trim($modal.find('.modal-body').text()).toLowerCase().indexOf('корзина пуста') !== -1;

    if (isEmpty){
      $modal.find('.modal-footer a, .modal-footer .btn-primary').hide();
    } else {
      $modal.find('.modal-footer a, .modal-footer .btn-primary').css('display','inline-block');
    }

    updateHeaderFromModalHtml(html);

    var ids = getProductIdsFromModalHtml(html);
    syncCatalogButtonsFromIds(ids);

    if ($('#cartTableWrap').length) {
      syncAllFromCartTable();
      syncCatalogButtons();
      refreshOrderItemsReadonly();
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  $(document).on('click', '.js-cart-modal-close', function (e) {
    e.preventDefault();

    var modalEl = document.getElementById('exampleModalLive');

    if (modalEl && window.bootstrap && bootstrap.Modal) {
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.hide();
    }

    setTimeout(function () {
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open').css({
        paddingRight: '',
        overflow: ''
      });
    }, 300);
  });

  function requestCart(endpoint, data){
  if (inFlight) return;
  inFlight = true;

  $.ajax({
    url: url(endpoint),
    data: data || {},
    type: 'GET',
    success: function(res){

      var endpointStr = String(endpoint || '');
      var ctx = (data && data.ctx) ? String(data.ctx) : '';

      // /cart/show -> ВСЕГДА модалка
      if (endpointStr.indexOf('/cart/show') !== -1){
        showCartModal(res);
        $(document).trigger('cart:updated');
        return;
      }

      // cart_table -> только wrap
      if (isCartTableHtml(res)){
        if ($('#cartTableWrap').length){
          $('#cartTableWrap').html(res);
        }

        syncAllFromCartTable();
        syncCatalogButtons();
        refreshOrderItemsReadonly();

        // если после обновления корзина пуста — переключаем пустое состояние страницы
        if (!$.trim(res)) {
          $('.cart-block').hide();
          $('.cart-no-product').show();
        } else {
          $('.cart-block').show();
          $('.cart-no-product').hide();
        }

        $(document).trigger('cart:updated');
        return;
      }

      // cart_modal -> модалка
      if (isCartModalHtml(res)){
        showCartModal(res);

        // если очистка была из модалки, а под ней открыта страница корзины —
        // синхронизируем и страницу тоже
        if (endpointStr.indexOf('/cart/clear') !== -1 && ctx === 'modal' && $('#cartTableWrap').length) {
          $('.cart-block').hide();
          $('.cart-no-product').show();
          $('#cartTableWrap').html('');
          $('#cart-total').text('0');
        }

        $(document).trigger('cart:updated');
        return;
      }

      // fallback -> модалка
      showCartModal(res);
      $(document).trigger('cart:updated');
    },
    error: function(){
      alert('Ошибка! Попробуйте позже');
    },
    complete: function(){
      inFlight = false;
    }
  });
}

  // -------------------------
  // Checkout steps
  // -------------------------
  function initCheckoutSteps(){
  if (!$('#checkoutForm').length) return;

  var restoringDelivery = false;
  var DELIVERY_KEY = 'checkout_delivery_state_v1';

  function setBadges(step){
    $('.checkout-step').removeClass('is-active');
    $('.checkout-step[data-step-badge="'+step+'"]').addClass('is-active');
  }

  function readDeliveryState(){
    try{
      var raw = sessionStorage.getItem(DELIVERY_KEY);
      if (!raw) return {};
      var obj = JSON.parse(raw);
      return (obj && typeof obj === 'object') ? obj : {};
    }catch(e){
      return {};
    }
  }

  function saveDeliveryState(){
    if (restoringDelivery) return;

    var st = {
      dostavka_id: String($('#dostavka_id').val() || ''),
      transport_id: String($('#transport_id').val() || ''),
      branch_id: String($('#branch_id').val() || ''),
      city_name: String($('#city_name').val() || ''),
      address: String($('#address').val() || '')
    };

    try{
      sessionStorage.setItem(DELIVERY_KEY, JSON.stringify(st));
    }catch(e){}
  }

  function optionTextLower($sel){
    try{
      return String($sel.find('option:selected').text() || '').toLowerCase();
    }catch(e){
      return '';
    }
  }

  // Определяем режим доставки НЕ по id 1/2/3, а по смыслу option
  function detectDeliveryMode(){
    var $sel = $('#dostavka_id');
    var v = String($sel.val() || '').trim();
    if (!v) return '';

    // 1) если ты когда-то добавишь data-mode в option — подхватим автоматически
    var $opt = $sel.find('option:selected');
    var mode = String($opt.data('mode') || $opt.data('kind') || '').trim(); // tk|pickup|address
    if (mode) return mode;

    // 2) иначе — угадываем по тексту
    var t = optionTextLower($sel);

    // самовывоз
    if (t.indexOf('самовы') !== -1 || t.indexOf('склад') !== -1 || t.indexOf('пункт') !== -1) {
      return 'pickup';
    }

    // транспортная компания
    if (t.indexOf('транспорт') !== -1 || t.indexOf('тк') !== -1) {
      return 'tk';
    }

    // доставка по адресу
    if (t.indexOf('адрес') !== -1 || t.indexOf('курьер') !== -1 || t.indexOf('достав') !== -1) {
      return 'address';
    }

    // 3) fallback на старую схему (если вдруг у тебя реально 1/2/3)
    if (v === '1') return 'tk';
    if (v === '2') return 'pickup';
    if (v === '3') return 'address';

    // неизвестно
    return '';
  }

    window.detectDeliveryMode = detectDeliveryMode; // для консоли/отладки

  function toggleDeliveryFields(){
    var mode = detectDeliveryMode();

    var $transport = $('#another_transport');
    var $sklad     = $('#another_sklad');
    var $city      = $('#another_city');
    var $addr      = $('#another_adress');

    // сначала всё скрываем
    $transport.hide();
    $sklad.hide();
    $city.hide();
    $addr.hide();

    // снимаем required со всех зависимых полей
    $('#transport_id, #branch_id, #city_name').prop('required', false);

    if (mode === 'tk'){ // ТК
      $transport.show();
      $city.show();
      $('#transport_id, #city_name').prop('required', true);
    } else if (mode === 'pickup'){ // самовывоз
      $sklad.show();
      $('#branch_id').prop('required', true);
    } else if (mode === 'address'){ // доставка по адресу
      $city.show();
      $addr.show();
      $('#city_name').prop('required', true);
    }

    // фиксируем состояние
    saveDeliveryState();
  }

  function restoreDeliveryState(){
    var st = readDeliveryState();
    if (!st || typeof st !== 'object') return;

    restoringDelivery = true;

    // 1) dostavka
    if (st.dostavka_id && $('#dostavka_id').length){
      if ($('#dostavka_id option[value="'+String(st.dostavka_id).replace(/"/g,'\\"')+'"]').length){
        $('#dostavka_id').val(st.dostavka_id);
      }
    }

    // 2) сначала показать нужные блоки
    toggleDeliveryFields();

    // 3) восстановить поля ТОЛЬКО после того, как блоки в DOM показаны
    if (st.transport_id && $('#transport_id').length){
      if ($('#transport_id option[value="'+String(st.transport_id).replace(/"/g,'\\"')+'"]').length){
        $('#transport_id').val(st.transport_id);
      }
    }
    if (st.branch_id && $('#branch_id').length){
      if ($('#branch_id option[value="'+String(st.branch_id).replace(/"/g,'\\"')+'"]').length){
        $('#branch_id').val(st.branch_id);
      }
    }

    if (typeof st.city_name === 'string' && $('#city_name').length){
      $('#city_name').val(st.city_name);
    }
    if (typeof st.address === 'string' && $('#address').length){
      $('#address').val(st.address);
    }

    restoringDelivery = false;

    // 4) после восстановления — сохранить заново (фиксируем состояние)
    saveDeliveryState();
  }

  function validateStep(step){
    var $scope = $('[data-step="'+step+'"]');
    var ok = true;
    $scope.find(':input[required]').each(function(){
      if(!this.checkValidity()){
        this.reportValidity();
        ok = false;
        return false;
      }
    });
    return ok;
  }

    function escapeHtml(s){
    return String(s || '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function refreshDeliveryReadonly(){
    var $block = $('#deliveryReadonlyBlock');
    if (!$block.length) return;

    var v = String($('#dostavka_id').val() || '').trim();
    if (!v){
      $block.hide();
      return;
    }

    // название способа
    var methodText = $('#dostavka_id option:selected').text() || '—';
    $block.find('.js-delivery-method').text(methodText);

    // режим как на Step2
    var mode = detectDeliveryMode(); // tk|pickup|address|''

    var parts = [];

    if (mode === 'tk'){
      var trVal = String($('#transport_id').val() || '').trim();
      var trTxt = $('#transport_id option:selected').text() || '';
      var city  = String($('#city_name').val() || '').trim();

      if (trVal && trTxt) parts.push('Транспортная: <b>' + escapeHtml(trTxt) + '</b>');
      if (city) parts.push('Город: <b>' + escapeHtml(city) + '</b>');

    } else if (mode === 'pickup'){
      var brVal = String($('#branch_id').val() || '').trim();
      var brTxt = $('#branch_id option:selected').text() || '';
      if (brVal && brTxt) parts.push('Пункт: <b>' + escapeHtml(brTxt) + '</b>');

    } else if (mode === 'address'){
      var city2 = String($('#city_name').val() || '').trim();
      var addr  = String($('#address').val() || '').trim();

      if (city2) parts.push('Город: <b>' + escapeHtml(city2) + '</b>');
      if (addr)  parts.push('Адрес: <b>' + escapeHtml(addr) + '</b>');
    }

    $block.find('.js-delivery-details').html(parts.length ? parts.join('<br>') : '');
    $block.show();
  }

  function setStep(step){
    step = parseInt(step, 10) || 1;

    // перед сменой шага — сохраняем доставку (иначе можно потерять city/address)
    saveDeliveryState();

    // если корзина пустая — всегда шаг 1
    var qtyNow = 0;
    if ($('#cartTableWrap').length){
      qtyNow = parseIntSafe($('#cartTableWrap .cart-qty').first().text());
    }
    if (qtyNow <= 0) step = 1;

    $('[data-step]').hide();
    $('[data-step="'+step+'"]').show();
    setBadges(step);

    $('[data-step-field]').prop('required', false);

    if(step === 2){
      $('#dostavka_id').prop('required', true);

      // ✅ ВАЖНО: сначала восстановить, потом показать/required
      restoreDeliveryState();

      // если ты используешь readonly-список товаров на шаге 2
      if (typeof refreshOrderItemsReadonly === 'function') {
        refreshOrderItemsReadonly();
      }
    }

    if(step === 3){
      $('[name="telefon"][data-step-field="3"]').prop('required', true);
      $('[name="email"][data-step-field="3"]').prop('required', true);
      $('[name="name"][data-step-field="3"]').prop('required', true);

      // ✅ для Step3 — тоже восстановим доставку и покажем readonly-блок
      restoreDeliveryState();
      refreshDeliveryReadonly();
    }

    $('#checkoutForm').attr('data-current-step', step);
    try { sessionStorage.setItem('checkout_step', String(step)); } catch(e){}

    syncAllFromCartTable();
  }

  // кнопки шагов
  $('#btnToStep2').off('click.checkout').on('click.checkout', function(){ setStep(2); });
  $('#btnBackToStep1').off('click.checkout').on('click.checkout', function(){ setStep(1); });

  $('#btnToStep3').off('click.checkout').on('click.checkout', function(){
    if (validateStep(2)) setStep(3);
  });
  $('#btnBackToStep2').off('click.checkout').on('click.checkout', function(){ setStep(2); });

  // события формы доставки
  $('#dostavka_id').off('change.checkout').on('change.checkout', function(){
    toggleDeliveryFields();
  });

  $('#transport_id, #branch_id').off('change.checkout').on('change.checkout', saveDeliveryState);
  $('#city_name, #address')
  .off('.checkout')
  .on('input.checkout change.checkout keyup.checkout blur.checkout paste.checkout', function(){
    // небольшая задержка, чтобы автокомплит/скрипты успели проставить value
    setTimeout(saveDeliveryState, 0);
  });

  $('#dostavka_id, #transport_id, #branch_id, #city_name, #address')
  .off('change.deliveryreadonly input.deliveryreadonly')
  .on('change.deliveryreadonly input.deliveryreadonly', function(){
    refreshDeliveryReadonly();
  });

  // submit
  $('#checkoutForm').off('submit.checkout').on('submit.checkout', function(e){
    var currentStep = parseInt($(this).attr('data-current-step') || '1', 10);
    if (currentStep !== 3){
      e.preventDefault();
      return false;
    }
    if (!validateStep(3)){
      e.preventDefault();
      return false;
    }
  });

  // restore step
  var saved = 1;
  try { saved = parseInt(sessionStorage.getItem('checkout_step') || '1', 10) || 1; } catch(e){}
  if (window.cartHasItems === false) saved = 1;
  if (saved < 1 || saved > 3) saved = 1;

  setStep(saved);
}

  // -------------------------
  // PUBLIC for layout onclick="getCart()" / clearCart()
  // -------------------------
  window.getCart = function(){
    requestCart('/cart/show', {});
  };

  window.clearCart = function(){
    requestCart('/cart/clear', { ctx: 'modal' });
  };

  // -------------------------
  // Bind handlers ONCE
  // -------------------------
  function bindHandlersOnce(){
    $('body').off(NS);

    // OPEN CART MODAL
    $('body').on('click' + NS, '.js-open-cart, #getCart', function(e){
      e.preventDefault();
      requestCart('/cart/show', {});
    });

	// CLEAR CART
	$('body').off('click' + NS, '.clear-cart, #clearCart, .js-clear-cart');
	$('body').on('click' + NS, '.clear-cart, #clearCart, .js-clear-cart', function(e){
	  e.preventDefault();
	  e.stopPropagation();

	  var ctx = $(this).closest('#exampleModalLive').length ? 'modal' : 'page';
	  requestCart('/cart/clear', { ctx: ctx });
	});

    // ADD TO CART
    $('body').on('click' + NS, '.add-to-cart-link', function(e){
      e.preventDefault();
      var $btn = $(this);
      var id = $btn.data('id');

      requestCart('/cart/add', {
        id:  id,
        qty: ($('.quantity input').val() || 1),
        mod: $('.available select').val(),
        max: $btn.data('max')
      });

      showInCartButton(id);
    });

    // ADD TO CART (mod)
    $('body').on('click' + NS, '.add-to-cart-mod', function(e){
      e.preventDefault();
      var $btn = $(this);
      var id = $btn.data('id');

      var qty = 1;
      var $qtyInput = $('.korzina-' + id).filter('input').first();
      if ($qtyInput.length) qty = ($qtyInput.val() || 1);

      requestCart('/cart/add', {
        id: id,
        qty: qty,
        modification: $('.modification').val(),
        max: $btn.data('max')
      });

      showInCartButton(id);
    });

    // ADD COMPLETE
    $('body').on('click' + NS, '.add-to-cart-complete', function(e){
      e.preventDefault();

      var $btn = $(this);
      var $wrap = $btn.closest('.complete-summary__actions');
      var qty = parseInt($wrap.find('input[name="quantity"]').val(), 10) || 1;
      var mod = $('.available select').val() || '';

      requestCart('/cart/addcomplete', {
        id: $btn.data('id'),
        qty: qty,
        mod: mod,
        complete: parseInt($btn.data('complete'), 10) || 0,
        complete_id: parseInt($btn.data('completeId'), 10) || 0
      });
    });

    // -------------------------
    // +/- CART PAGE
    // -------------------------
    $('body').on('click' + NS, '.my-plus-cart', function(e){
      e.preventDefault();
      requestCart('/cart/pluscart', { id: $(this).data('id') });
    });

    $('body').on('click' + NS, '.my-minus-cart', function(e){
      e.preventDefault();
      var $btn = $(this);
      var $row = $btn.closest('.cart-item');
      var qty = parseIntSafe($.trim($row.find('.qty-item').first().text()));
      var endpoint = (qty <= 1) ? '/cart/deletecart' : '/cart/minuscart';
      requestCart(endpoint, { id: $btn.data('id') });
    });

    $('body').on('click' + NS, '.my-plus-complete-cart', function(e){
      e.preventDefault();
      var $btn = $(this);
      requestCart('/cart/pluscartcomplete', {
        id: $btn.data('id'),
        complete_id: $btn.data('completeId')
      });
    });

    $('body').on('click' + NS, '.my-minus-complete-cart', function(e){
      e.preventDefault();
      var $btn = $(this);
      var $row = $btn.closest('.cart-item');
      var qty = parseIntSafe($.trim($row.find('.qty-item').first().text()));
      var endpoint = (qty <= 1) ? '/cart/deletecartcomplete' : '/cart/minuscartcomplete';
      requestCart(endpoint, {
        id: $btn.data('id'),
        complete_id: $btn.data('completeId')
      });
    });

    // delete CART PAGE
    $('body').on('click' + NS, '.del-item-cart, .del-item-complete-cart', function(e){
      e.preventDefault();
      var $btn = $(this);
      var isComplete = $btn.hasClass('del-item-complete-cart');

      var payload = { id: $btn.data('id') };
      if (isComplete){
        payload.complete_id = $btn.data('completeId');
      }
      requestCart(isComplete ? '/cart/deletecartcomplete' : '/cart/deletecart', payload);
    });

    // -------------------------
    // +/- MODAL
    // -------------------------
    $('body').on('click' + NS, '.my-plus', function(e){
      e.preventDefault();
      requestCart('/cart/plusmodal', { id: $(this).data('id') });
    });

    $('body').on('click' + NS, '.my-minus', function(e){
      e.preventDefault();
      var $btn = $(this);
      var $row = $btn.closest('tr');
      var qty = parseIntSafe($.trim($row.find('.qty-item').first().text()));
      var endpoint = (qty <= 1) ? '/cart/delete' : '/cart/minusmodal';
      requestCart(endpoint, { id: $btn.data('id') });
    });

    $('body').on('click' + NS, '.my-plus-complete', function(e){
      e.preventDefault();
      var $btn = $(this);
      requestCart('/cart/plusmodalcomplete', {
        id: $btn.data('id'),
        complete_id: $btn.data('completeId')
      });
    });

    $('body').on('click' + NS, '.my-minus-complete', function(e){
      e.preventDefault();
      var $btn = $(this);
      var $row = $btn.closest('tr');
      var qty = parseIntSafe($.trim($row.find('.qty-item').first().text()));
      var endpoint = (qty <= 1) ? '/cart/deletecomplete' : '/cart/minusmodalcomplete';
      requestCart(endpoint, {
        id: $btn.data('id'),
        complete_id: $btn.data('completeId')
      });
    });

    // delete MODAL (Х)
    $('body').on('click' + NS, '.del-item, .del-item-complete', function(e){
      e.preventDefault();
      var $btn = $(this);
      var isComplete = $btn.hasClass('del-item-complete');

      var payload = { id: $btn.data('id') };
      if (isComplete){
        payload.complete_id = $btn.data('completeId');
      }
      requestCart(isComplete ? '/cart/deletecomplete' : '/cart/delete', payload);
    });

    // PROMO
    $('body').on('click' + NS, '#btnApplyPromo', function(){
      var code = $.trim($('#promoCodeInput').val() || '');
      requestCart('/cart/promocart', { val: code });
    });

    $('body').on('click' + NS, '#btnClearPromo', function(){
      requestCart('/cart/clearpromo', {});
    });
  }

  // -------------------------
  // INIT
  // -------------------------
  $(function(){
    bindHandlersOnce();
    initCheckoutSteps();

    // первичная синхронизация
    if ($('#cartTableWrap').length){
      syncAllFromCartTable();
      syncCatalogButtons();
    }

    // если есть Step2 readonly блок — подгрузим один раз
    if ($('#orderItemsWrap').length){
      refreshOrderItemsReadonly();
    }
  });

})(jQuery);

/* CART END */


$('#currency').change(function(){
    window.location = 'currency/change?curr=' + $(this).val();
});

$('.available select').on('change', function(){
    var modId = $(this).val(),
        color = $(this).find('option').filter(':selected').data('title'),
        price = $(this).find('option').filter(':selected').data('price'),
		quantity = $(this).find('option').filter(':selected').data('quantity'),
        basePrice = $('#base-price').data('base');
		baseQuantity = $('#base-quantity').data('basequant');
    if(price){
        $('#base-price').text(symboleLeft + price + symboleRight);
    }else{
        $('#base-price').text(symboleLeft + basePrice + symboleRight);
    }
	if(quantity){
		$('.detail-quantity').attr('data-max', quantity);
		$('.add-to-cart-link').attr('data-max', quantity);
		$('.detail-quantity').attr('max', quantity);
	}else{
		$('.detail-quantity').attr('data-max', baseQuantity);
		$('.add-to-cart-link').attr('data-max', baseQuantity);
		$('.detail-quantity').attr('max', baseQuantity);
	}
});

function updateComparisonCount(count) {
    $('#comparison_kol').text(parseInt(count || 0, 10));
}

function updateWishlistCount(count) {
    $('#wishlist_kol').text(parseInt(count || 0, 10));
}

function setCompareButtonState(productId, active) {
    var $btn = $('#comparison-' + productId);
    if (!$btn.length) return;

    $btn.toggleClass('is-active', !!active)
        .attr('title', active ? 'В сравнении' : 'Добавить в сравнение');

    var $icon = $btn.find('i');
    if (!$icon.length) return;

    $icon.removeClass('far fas fa-chart-bar fa-tasks')
         .addClass(active ? 'fas fa-chart-bar' : 'far fa-chart-bar');
}

function setWishlistButtonState(productId, active) {
    var $btn = $('#wishlist-' + productId);
    if (!$btn.length) return;

    $btn.toggleClass('is-active', !!active)
        .attr('title', active ? 'В избранном' : 'Добавить в избранное');

    var $icon = $btn.find('i');
    if (!$icon.length) return;

    $icon.removeClass('far fas fa-heart')
         .addClass(active ? 'fas fa-heart' : 'far fa-heart');
}

$('body').on('click', '.js-compare, .btn-comparison, .btn-comparison2', function (e) {
    e.preventDefault();

    var $btn = $(this);
    var product_id = parseInt($btn.data('id') || 0, 10);
    var category_id = parseInt($btn.data('categoryid') || 0, 10);

    if (!product_id) return;

    var isActive = $btn.hasClass('is-active') || $btn.hasClass('btn-comparison2');
    var url = isActive ? '/comparison/deletecomparison' : '/comparison/addcomparison';

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        data: {
            product_id: product_id,
            category_id: category_id
        },
        success: function (res) {
            if (!res || res.ok === false) {
                alert('Ошибка обработки сравнения');
                return;
            }

            var count = parseInt(res.result || 0, 10);
            var nowActive = !isActive;

            setCompareButtonState(product_id, nowActive);
            updateComparisonCount(count);

            $('#comparison-' + product_id)
                .removeClass('btn-comparison btn-comparison2 btn-icon')
                .addClass('js-compare pc-iconbtn')
                .toggleClass('is-active', nowActive);

            if ($('.comparison-catvse').length) {
                $('.comparison-catvse').html(' Все (' + count + ')');
            }

            if ($('.comparcat').length && typeof res.result2 !== 'undefined') {
                $('.comparcat').html(res.result2);
            }

            if ($('.close-compartd-' + product_id).length && !nowActive) {
                $('.close-compartd-' + product_id).hide();
            }

            if (count === 0) {
                $('.no-comparison').hide();
                $('.no-compar-sess-block').show();
            }
        },
        error: function (xhr) {
            console.log('compare ajax error:', xhr.responseText);
            alert('Error!');
        }
    });
});

$('body').on('click', '.js-wishlist, .btn-wishlist, .btn-wishlist2', function (e) {
    e.preventDefault();

    var $btn = $(this);
    var product_id = parseInt($btn.data('id') || 0, 10);

    if (!product_id) return;

    $.ajax({
        url: (window.APP_PATH || '') + '/user/wishlists-toggle',
        type: 'GET',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: { product_id: product_id },
        success: function (res) {
            if (!res || !res.ok) return;

            var added = (res.state === 'added');

            setWishlistButtonState(product_id, added);

            $('#wishlist-' + product_id)
                .removeClass('btn-wishlist btn-wishlist2 btn-icon')
                .addClass('js-wishlist pc-iconbtn')
                .toggleClass('is-active', added);

            if (typeof res.count !== 'undefined') {
                updateWishlistCount(res.count);
            }
        },
        error: function (xhr) {
            console.log('wishlist ajax error:', xhr.responseText);
            alert('Error!');
        }
    });
});

$('body').on('click', '.comparison-remove-btn', function () {
    var product_id = $(this).data('id');
    var category_id = $(this).data('categoryid');

    $.ajax({
        url: '/comparison/deletecomparison',
        data: {
            product_id: product_id,
            category_id: category_id
        },
        type: 'GET',
        dataType: 'json',
        success: function(res){
            if (!res || res.ok === false) {
                alert('Ошибка удаления из сравнения');
                return;
            }

            $('.close-compartd-' + product_id).remove();
            updateComparisonCount(res.result);

            if (parseInt(res.result, 10) === 0) {
                $('.comparison-toolbar').hide();
                $('.comparison-content').hide();
                $('.comparison-empty-main').hide();
                $('.no-compar-sess-block').show();
            }
        },
        error: function(xhr){
            console.log(xhr.responseText);
            alert('Error!');
        }
    });
});

$(document).off('change.delivery', '#dostavka_id').on('change.delivery', '#dostavka_id', function(){
  var dostavka = $(this).val();

  if(dostavka==1) {
    $('#another_sklad').show().html(
      '<select class="form-control" name="branch_id" id="branch_id">' +
        '<option value="1">г. Подольск (мкр. Климовск)</option>' +
      '</select>'
    );

    $('#another_city').hide().html('');
    $('#another_transport').hide().html('');
    $('#another_adress').hide().html('');
  }

  if(dostavka==2) {
    $('#another_city').show().html(
      '<input type="text" class="form-control" name="city_name" id="city_name" placeholder="Укажите город для доставки">'
    );

    $('#another_transport').show().html(
      '<select class="form-control" name="transport_id" id="transport_id">' +
        '<option value="">Выберите транспортную компанию</option>' +
        '<option value="">-------------------------</option>' +
        '<option value="1">ПЭК</option>' +
        '<option value="2">Деловые Линии</option>' +
        '<option value="3">Байкал-Сервис</option>' +
        '<option value="5">Кит</option>' +
        '<option value="6">СДЭК</option>' +
        '<option value="8">Энергия</option>' +
      '</select>'
    );

    $('#another_sklad').hide().html('');
    $('#another_adress').hide().html('');
  }

  if(dostavka==3) {
    $('#another_city').show().html(
      '<select class="form-control" name="city_name" id="city_name">' +
        '<option value="Москва">Москва</option>' +
      '</select>'
    );

    $('#another_adress').show().html(
      '<input type="text" name="address" class="form-control" id="address" placeholder="Адрес доставки товаров">'
    );

    $('#another_sklad').hide().html('');
    $('#another_transport').hide().html('');
  }

  // после перестройки полей — попросим шаги пересчитать required/кнопку
  if (typeof initCheckoutSteps === 'function') initCheckoutSteps();
});


$(document).off('change.clientType', '#vidurlface').on('change.clientType', '#vidurlface', function(){
  var vid = $(this).val();

  if(vid==3) {
    $('#vid_urlface').hide().html('');
  }

  if(vid==4) {
    $('#vid_urlface').show().html(
      '<div class="col-sm-12 rekvizity row">' +
        '<div class="col-sm-5">' +
          '<label class="form-label" for="rekvizity">Прикрепить реквизиты <span class="text-danger">*</span></label>' +
          '<input class="btn btn-default" type="file" name="rekvizity" id="rekvizity" required />' +
        '</div>' +
      '</div>' +
      '<div class="col-sm-6">' +
        '<label class="form-label" for="nds">Система налогообложения <span class="text-danger">*</span></label>' +
        '<select name="nds" class="form-control" id="nds" required>' +
          '<option value="" selected="selected">Выберите систему налогообложения</option>' +
          '<option value="1">с НДС</option>' +
          '<option value="2">без НДС</option>' +
        '</select>' +
      '</div>' +
      '<p></p>' +
      '<div class="col-sm-6">' +
        '<label class="form-label" for="dogovor">Условия поставки <span class="text-danger">*</span></label>' +
        '<select name="dogovor" class="form-control" id="dogovor" required>' +
          '<option value="" selected="selected">Выберите условия поставки</option>' +
          '<option value="1">Договор</option>' +
          '<option value="2">Счёт-договор</option>' +
        '</select>' +
      '</div>'
    );
  }

  if (typeof initCheckoutSteps === 'function') initCheckoutSteps();
});


$(document).on('click','[data-toggle="class-toggle"]',function () {
	var $this = $(this);
	var target = $this.data("target");
	var sameTriggers = $this.data("same");
	var backdrop = $(this).data("backdrop");

	if ($(target).hasClass("active")) {
		$(target).removeClass("active");
		$(sameTriggers).removeClass("active");
		$this.removeClass("active");
		$('body').removeClass("overflow-hidden");
	} else {
		$(target).addClass("active");
		$this.addClass("active");
		if(backdrop == 'static'){
			$('body').addClass("overflow-hidden");
		}
	}
});

$('[data-toggle="aiz-side-menu"] a').each(function () {
	var pageUrl = window.location.href.split(/[?#]/)[0];
	if (this.href == pageUrl || $(this).hasClass("active")) {
		$(this).addClass("active");
		$(this).closest(".aiz-side-nav-item").addClass("mm-active");
		$(this)
			.closest(".level-2")
			.siblings("a")
			.addClass("level-2-active");
		$(this)
			.closest(".level-3")
			.siblings("a")
			.addClass("level-3-active");
	}
});

$('body').on('click', '.newsletter_checked', function(){
	var newsletter_id = $(this).data('newsletter_id');
	var checked = $(this).data('checked');
    $.ajax({
        url: '/user/addnewsletter',
        data: {newsletter_id: newsletter_id, checked: checked},
        type: 'GET',
        success: function(res){
			$('.form-newsletter').html(res);
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});

$('body').on('click', '.switch-newsletter', function(){
	var checked = $(this).data('checked');
    $.ajax({
        url: '/user/deletenewsletter',
        data: {checked: checked},
        type: 'GET',
        success: function(res){
			$('.form-newsletter').html(res);
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});

/*Скролл для header */
document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header--modern');
  if (!header) return;

  var lastScrollTop = 0;

  function onScroll() {
    var st = window.pageYOffset || document.documentElement.scrollTop;

    if (st > 10) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }

    if (st > 80) {
      header.classList.add('is-compact');
    } else {
      header.classList.remove('is-compact');
    }

    lastScrollTop = st <= 0 ? 0 : st;
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});

/*
(function ($) {
  function ensureBodyClass() {
    $("body").addClass("newYear2025");
  }

  function createOrGetCanvas() {
    let canvas = document.querySelector("canvas.ny-snow");
    if (!canvas) {
      canvas = document.createElement("canvas");
      canvas.className = "ny-snow";
      document.body.appendChild(canvas);
    }
    return canvas;
  }

  function setupSnow(canvas, options) {
    const ctx = canvas.getContext("2d");
    let vw = 0, vh = 0, dpr = 1;

    const flakes = [];
    const maxFlakes = options.maxFlakes ?? 160;
    const baseColor = options.color ?? "138, 216, 235";
    const alpha = options.alpha ?? 0.6;

    function resize() {
      dpr = Math.max(1, window.devicePixelRatio || 1);
      vw = Math.floor(window.innerWidth);
      vh = Math.floor(window.innerHeight);

      // Визуальный размер
      canvas.style.width = vw + "px";
      canvas.style.height = vh + "px";

      // Реальный буфер
      canvas.width = Math.floor(vw * dpr);
      canvas.height = Math.floor(vh * dpr);

      // Координаты в CSS-пикселях
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      // Если flakes ещё нет — создаём; если есть — не трогаем позиции (чтобы не "прыгали")
      if (flakes.length === 0) {
        for (let i = 0; i < maxFlakes; i++) {
          flakes.push(makeFlake(true));
        }
      }
    }

    function makeFlake(randomY) {
      const r = 1 + Math.random() * 2.8;          // радиус
      return {
        x: Math.random() * vw,
        y: randomY ? Math.random() * vh : -10,
        r,
        vy: 0.4 + Math.random() * 1.1,           // скорость вниз
        vx: (Math.random() - 0.5) * 0.35,        // дрейф
      };
    }

    function step() {
      ctx.clearRect(0, 0, vw, vh);
      ctx.fillStyle = `rgba(${baseColor}, ${alpha})`;

      for (let i = 0; i < flakes.length; i++) {
        const f = flakes[i];

        f.x += f.vx;
        f.y += f.vy;

        // лёгкое "качание"
        f.x += Math.sin((f.y + i) * 0.01) * 0.2;

        // перезапуск снизу
        if (f.y > vh + 10) {
          flakes[i] = makeFlake(false);
          continue;
        }

        // отражение по краям (без ухода вправо)
        if (f.x < -10) f.x = vw + 10;
        if (f.x > vw + 10) f.x = -10;

        ctx.beginPath();
        ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
        ctx.fill();
      }

      requestAnimationFrame(step);
    }

    // Важно: на iOS иногда первый layout "пустой" до клика.
    // Делаем resize после полной загрузки и ещё раз с небольшим таймаутом.
    resize();
    window.addEventListener("resize", resize, { passive: true });
    window.addEventListener("orientationchange", () => setTimeout(resize, 150), { passive: true });
    window.addEventListener("load", () => setTimeout(resize, 50), { once: true });

    step();
  }

  $(function () {
    ensureBodyClass();
    const canvas = createOrGetCanvas();

    setupSnow(canvas, {
      alpha: 0.55,
      maxFlakes: 150
    });
  });
})(jQuery);
*/

/* =========================
   GLOBAL INIT
========================= */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initCookieBar();
    initBackToTop();
    initDropdownMenuLinks();
    initMenuDrop();
    initB2BBlock();
    initPhoneMasks();
    initModalFormsValidation();
    initCatalogMenu();
    initYandexMetrika();
  });

  window.addEventListener('load', function () {
    initSwipers();
    initFlexSlider();
  });

  /* =========================
     1. Lazy modal frames
  ========================= */
  

  /* =========================
     2. Cookie bar
  ========================= */
  function initCookieBar() {
    var consentKey = 'cookie_accepted';
    var cookieBar = document.getElementById('cookie-bar');
    var acceptBtn = document.getElementById('accept-cookies');

    if (!cookieBar || !acceptBtn) return;

    function getCookie(name) {
      var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      return match ? match[2] : null;
    }

    if (!getCookie(consentKey)) {
      cookieBar.style.display = 'flex';
    } else {
      cookieBar.remove();
    }

    acceptBtn.addEventListener('click', function () {
      var date = new Date();
      date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
      document.cookie = consentKey + '=true; expires=' + date.toUTCString() + '; path=/';
      cookieBar.remove();

      fetch('/user/accept-cookie', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ accept: true })
      }).then(function (res) {
        return res.ok && console.log('Согласие зафиксировано');
      });
    });
  }

  /* =========================
     3. Back to top
  ========================= */
  function initBackToTop() {
    if (typeof jQuery === 'undefined') return;
    if (!jQuery('#back-to-top').length) return;

    jQuery(window).on('scroll', function () {
      if (jQuery(this).scrollTop() > 50) {
        jQuery('#back-to-top').fadeIn();
      } else {
        jQuery('#back-to-top').fadeOut();
      }
    });

    jQuery('#back-to-top').on('click', function () {
      jQuery('body,html').animate({ scrollTop: 0 }, 500);
    });
  }

  /* =========================
     4. Dropdown menu links
  ========================= */
  function initDropdownMenuLinks() {
    if (typeof jQuery === 'undefined') return;

    jQuery('.dropdown .menu_links-item').on('click', function () {
      jQuery('.dropdown-menu').toggleClass('show');
      jQuery('.mmenu-close').toggleClass('show');
    });

    jQuery('.mmenu-close').on('click', function () {
      jQuery('.mmenu-close').removeClass('show');
      jQuery('.dropdown-menu').removeClass('show');
    });
  }

  /* =========================
     5. menu_drop
  ========================= */
  function initMenuDrop() {
    if (typeof jQuery === 'undefined') return;
    if (!jQuery('.menu_drop').length) return;

    var menu_ul = jQuery('.menu_drop > li > ul');
    var menu_a = jQuery('.menu_drop > li > a');

    menu_ul.hide();

    menu_a.on('click', function (e) {
      e.preventDefault();

      if (!jQuery(this).hasClass('active')) {
        menu_a.removeClass('active');
        menu_ul.filter(':visible').slideUp('normal');
        jQuery(this).addClass('active').next().stop(true, true).slideDown('normal');
      } else {
        jQuery(this).removeClass('active');
        jQuery(this).next().stop(true, true).slideUp('normal');
      }
    });
  }

  /* =========================
     6. Swipers
  ========================= */
  function initSwipers() {
    if (typeof Swiper === 'undefined') return;

    function initSimpleSwiper(selector, nextBtn, prevBtn) {
      if (!document.querySelector(selector)) return;

      new Swiper(selector, {
        slidesPerGroup: 1,
        speed: 500,
        watchOverflow: true,
        navigation: {
          nextEl: nextBtn,
          prevEl: prevBtn
        },
        breakpoints: {
          0: {
            slidesPerView: 1,
            spaceBetween: 12,
            slidesOffsetBefore: 12,
            slidesOffsetAfter: 12
          },
          481: {
            slidesPerView: 2,
            spaceBetween: 14,
            slidesOffsetBefore: 12,
            slidesOffsetAfter: 12
          },
          640: {
            slidesPerView: 3,
            spaceBetween: 16,
            slidesOffsetBefore: 12,
            slidesOffsetAfter: 12
          },
          1024: {
            slidesPerView: 4,
            spaceBetween: 14,
            slidesOffsetBefore: 12,
            slidesOffsetAfter: 12
          },
          1281: {
            slidesPerView: 5.083,
            spaceBetween: 12,
            slidesOffsetBefore: 12,
            slidesOffsetAfter: 12
          }
        }
      });
    }

    initSimpleSwiper('.swiper1', '.swiper-button-next-1', '.swiper-button-prev-1');
    initSimpleSwiper('.swiper2', '.swiper-button-next-2', '.swiper-button-prev-2');
    initSimpleSwiper('.swiper3', '.swiper-button-next-3', '.swiper-button-prev-3');
  }

  /* =========================
     7. B2B block
  ========================= */
  function initB2BBlock() {
    var btn = document.querySelector('.category-b2b__btn');
    var el = document.getElementById('b2bCategoryBlock');

    if (!btn || !el) return;

    btn.addEventListener('click', function (e) {
      e.preventDefault();

      var isOpen = el.classList.contains('show');

      if (isOpen) {
        el.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
      } else {
        el.classList.add('show');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  }

  /* =========================
     8. Flexslider
  ========================= */
  function initFlexSlider() {
    if (typeof jQuery === 'undefined') return;
    if (typeof jQuery.fn.flexslider !== 'function') return;
    if (!jQuery('#slider').length && !jQuery('#carousel').length) return;

    if (jQuery('#carousel').length) {
      jQuery('#carousel').flexslider({
        animation: 'slide',
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        itemWidth: 210,
        itemMargin: 5,
        asNavFor: '#slider'
      });
    }

    if (jQuery('#slider').length) {
      jQuery('#slider').flexslider({
        prevArrow: "<img src='https://svgshare.com/i/6Ei.svg' class='prev' alt='1'>",
        nextArrow: "<img src='https://svgshare.com/i/6Gf.svg' class='next' alt='2'>",
        animation: 'slide',
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        sync: '#carousel',
        start: function () {
          jQuery('body').removeClass('loading');
        }
      });
    }
  }

  /* =========================
     9. Phone masks
  ========================= */
  function initPhoneMasks() {
	  var selectors = [
		'#phone-input',
		'#phone-input1',
		'#phone-input2',
		'#phone-input3',
		'#phonenumber',
		'#catalog-phone',
		'#callback-phone'
	  ];

    function anyExists(root) {
      for (var i = 0; i < selectors.length; i++) {
        if ((root || document).querySelector(selectors[i])) return true;
      }
      return false;
    }

    function initMasks(root) {
      if (!window.IMask) return;
      root = root || document;

      var maskOptions = {
        mask: '{+7} (#00) 000-00-00',
        definitions: { '#': /[012345679]/ },
        lazy: false,
        placeholderChar: ' '
      };

      selectors.forEach(function (sel) {
        var nodes = root.querySelectorAll(sel);
        if (!nodes || !nodes.length) return;

        for (var i = 0; i < nodes.length; i++) {
          var el = nodes[i];
          if (!el || el.dataset.imaskAttached) continue;

          try {
            el._imask = IMask(el, maskOptions);
            el.dataset.imaskAttached = '1';
          } catch (e) {}

          if (el.id === 'phone-input1') {
            var btn = document.querySelector('.btn-decide');
            var btnDecide = document.getElementById('btn-decide');

            function updateBtn() {
              var complete = el._imask && el._imask.masked && el._imask.masked.isComplete;

              if (btn) btn.classList.toggle('active', !!complete);

              if (btnDecide) {
                if (complete) {
                  btnDecide.removeAttribute('disabled');
                } else {
                  btnDecide.setAttribute('disabled', '');
                }
              }
            }

            el.addEventListener('input', updateBtn);
            updateBtn();
          }
        }
      });
    }

    function ensureIMaskAndInit(root) {
      if (!anyExists(root || document)) return;

      function go() {
        try { initMasks(root); } catch (e) {}
      }

      if (window.IMask) {
        go();
        return;
      }

      var s = document.createElement('script');
      s.src = '/js/imask.min.js';
      s.async = true;
      s.onload = go;
      s.onerror = function () {};
      document.head.appendChild(s);
    }

    ensureIMaskAndInit();

    document.addEventListener('shown.bs.modal', function (e) {
      ensureIMaskAndInit(e.target);
    });

    var mo = new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        var m = muts[i];
        for (var j = 0; j < m.addedNodes.length; j++) {
          var node = m.addedNodes[j];
          if (node.nodeType === 1 && anyExists(node)) {
            ensureIMaskAndInit(node);
          }
        }
      }
    });

    try {
      mo.observe(document.body, { childList: true, subtree: true });
    } catch (e) {}
  }

  function initModalFormsValidation() {
    var forms = document.querySelectorAll('.js-modal-validate');
    if (!forms.length) return;

    forms.forEach(function(form) {
      var submitBtn = form.querySelector('.callback-submit');
      var requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');

      function isMaskedPhoneComplete(input) {
        if (!input) return true;
        if (!input.name || input.name !== 'phone') return true;

        if (input._imask && input._imask.masked) {
          return !!input._imask.masked.isComplete;
        }

        var value = String(input.value || '').replace(/\D/g, '');
        return value.length >= 11;
      }

      function updateFieldState(field) {
        var valid = field.checkValidity();

        if (field.name === 'phone') {
          valid = valid && isMaskedPhoneComplete(field);
        }

        if (field.type === 'checkbox') {
          field.classList.toggle('is-invalid', !field.checked);
          field.classList.toggle('is-valid', field.checked);
          return field.checked;
        }

        field.classList.toggle('is-invalid', !valid);
        field.classList.toggle('is-valid', valid && field.value.trim() !== '');

        return valid;
      }

      function updateFormState() {
        var ok = true;

        requiredFields.forEach(function(field) {
          if (!updateFieldState(field)) {
            ok = false;
          }
        });

        if (submitBtn) {
          submitBtn.disabled = !ok;
        }

        return ok;
      }

      requiredFields.forEach(function(field) {
        ['input', 'change', 'blur', 'keyup'].forEach(function(evt) {
          field.addEventListener(evt, updateFormState);
        });
      });

      form.addEventListener('submit', function(e) {
        if (!updateFormState()) {
          e.preventDefault();
          e.stopPropagation();

          var firstInvalid = form.querySelector('.is-invalid');
          if (firstInvalid) {
            firstInvalid.focus();
          }
          return false;
        }
      });

      form.addEventListener('reset', function() {
        setTimeout(function() {
          form.querySelectorAll('.is-valid, .is-invalid').forEach(function(el) {
            el.classList.remove('is-valid', 'is-invalid');
          });
          if (submitBtn) submitBtn.disabled = true;
        }, 0);
      });

      updateFormState();
    });
  }

  /* =========================
     10. Catalog menu
  ========================= */
  function initCatalogMenu() {
    (function () {
      const toggleBtn   = document.getElementById('catalogToggle');
      const menu        = document.getElementById('catalogMenu');
      const nav         = document.getElementById('catalogNav');
      const contentWrap = document.getElementById('catalogContentWrap');
      const source      = document.getElementById('catalogSource');

      if (!toggleBtn || !menu || !nav || !contentWrap || !source) return;

      const mqDesktop = window.matchMedia('(min-width: 993px)');
      const isDesktop = () => mqDesktop.matches;

      const barTitle = menu.querySelector('.catalog-menu__bar-title');
      const backBtn  = menu.querySelector('[data-back="1"]');

      // =========================
      // Read items from source
      // =========================
      const topLinks = Array.from(source.querySelectorAll('a.dropdown-item.submenu'));
      const items = [];

      topLinks.forEach((a, idx) => {
        const panel = a.nextElementSibling;
        if (!panel || !panel.classList.contains('dropdown-menu-byp')) return;

        items.push({
          idx,
          title: (a.textContent || '').trim(),
          href:  (a.getAttribute('href') || '#').trim(),
          panel
        });
      });

      if (!items.length) return;

      // =========================
      // Render nav + content
      // =========================
      nav.innerHTML = '';
      contentWrap.innerHTML = '';

      items.forEach((it) => {
        const id = `cat-${it.idx}`;

        // LEFT
        const navEl = document.createElement('a');
        navEl.className = 'catalog-menu__nav-el has-arrow';
        navEl.href = it.href;
        navEl.dataset.target = id;
        navEl.textContent = it.title;
        nav.appendChild(navEl);

        // RIGHT
        const contentEl = document.createElement('section');
        contentEl.className = 'catalog-menu__content';
        contentEl.id = id;

        const srcPanelClone = it.panel.cloneNode(true);

        // IMPORTANT: ручной шаблон должен быть .catalog-manual
        const manual = srcPanelClone.querySelector('.catalog-manual');
        if (manual) {
          // вырезаем manual из клона и вставляем внутрь нашей секции
          contentEl.appendChild(manual);
        } else {
          // fallback: старый блок как есть
          srcPanelClone.style.position = 'static';
          srcPanelClone.style.left = 'auto';
          srcPanelClone.style.top = 'auto';
          srcPanelClone.style.width = 'auto';
          contentEl.appendChild(srcPanelClone);
        }

        contentWrap.appendChild(contentEl);
      });

      // =========================
      // Active switching
      // =========================
      let lastActiveId = null;

      function setActive(id) {
        if (!id || id === lastActiveId) return;
        lastActiveId = id;

        nav.querySelectorAll('.catalog-menu__nav-el').forEach(el => {
          el.classList.toggle('is-active', el.dataset.target === id);
        });

        contentWrap.querySelectorAll('.catalog-menu__content').forEach(el => {
          el.classList.toggle('is-active', el.id === id);
        });
      }

      setActive(`cat-${items[0].idx}`);

      // =========================
      // Mobile screens
      // =========================
      function showNavScreen() {
        menu.classList.remove('mobile-show-content');
        if (barTitle) barTitle.textContent = 'Каталог';
        if (backBtn) backBtn.style.display = 'none';
      }

      function showContentScreen(titleText) {
        menu.classList.add('mobile-show-content');
        if (barTitle) barTitle.textContent = titleText || '';
        if (backBtn) backBtn.style.display = '';
      }

      // =========================
      // Open / Close
      // =========================
      function syncTopOffset() {
        const panel = menu.querySelector('.catalog-menu__panel');
        if (!panel) return;

        if (!isDesktop()) {
          panel.style.top = '0px';
          return;
        }

        const header = document.querySelector('.header__cont.v1, .header, header, .navbar');
        const h = header ? header.getBoundingClientRect().height : 90;
        panel.style.top = Math.max(0, Math.round(h)) + 'px';
      }

      function openMenu() {
        showNavScreen();
        menu.classList.add('is-open');
        document.body.classList.add('catalog-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        menu.setAttribute('aria-hidden', 'false');
        syncTopOffset();
      }

      function closeMenu() {
        menu.classList.remove('is-open');
        menu.classList.remove('mobile-show-content');
        document.body.classList.remove('catalog-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
        showNavScreen();
      }

      toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        menu.classList.contains('is-open') ? closeMenu() : openMenu();
      });

      // =========================
      // Close on search interaction (ROBUST for typeahead)
      // =========================
      function isSearchTarget(t) {
        if (!t || t.nodeType !== 1) return false;
        return (
          t.matches('#typeahead') ||
          t.matches('.tt-input') ||
          (t.closest('.search') && t.closest('form'))
        );
      }

      function maybeCloseFromSearch(e) {
        if (!menu.classList.contains('is-open')) return;
        if (isSearchTarget(e.target)) closeMenu();
      }

      // focus + typing + first touch
      document.addEventListener('focusin', maybeCloseFromSearch, true);
      document.addEventListener('input',   maybeCloseFromSearch, true);
      document.addEventListener('pointerdown', maybeCloseFromSearch, true);
      document.addEventListener('touchstart',  maybeCloseFromSearch, { capture: true, passive: true });

      // =========================
      // Protect from external document click "closers"
      // =========================
      menu.addEventListener('pointerdown', (e) => {
        if (!menu.classList.contains('is-open')) return;

        const isBackdrop = e.target.classList && e.target.classList.contains('catalog-menu__backdrop');
        if (!isBackdrop) e.stopPropagation();
      }, true);

      // =========================
      // Click logic (close/back/backdrop + mobile open content)
      // =========================
      menu.addEventListener('click', (e) => {
        if (!menu.classList.contains('is-open')) return;

        const isBackdrop = e.target.classList && e.target.classList.contains('catalog-menu__backdrop');

        if (e.target.closest && e.target.closest('[data-close="1"]')) {
          e.preventDefault();
          closeMenu();
          return;
        }

        if (e.target.closest && e.target.closest('[data-back="1"]')) {
          e.preventDefault();
          showNavScreen();
          return;
        }

        const navItem = e.target.closest && e.target.closest('.catalog-menu__nav-el');
        if (navItem && !isDesktop()) {
          e.preventDefault();
          setActive(navItem.dataset.target);
          showContentScreen(navItem.textContent.trim());
          return;
        }

        if (isBackdrop) {
          e.preventDefault();
          closeMenu();
        }
        // ВАЖНО: клики по ссылкам внутри контента не трогаем — переход должен работать.
      }, true);

      // Desktop hover switching (без дерганья)
      nav.addEventListener('mousemove', (e) => {
        if (!isDesktop()) return;
        const el = e.target.closest('.catalog-menu__nav-el');
        if (!el) return;
        setActive(el.dataset.target);
      });

      // Esc close
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu();
      });

      window.addEventListener('resize', () => {
        if (menu.classList.contains('is-open') && isDesktop()) syncTopOffset();
      });

    })();
  }

  /* =========================
     11. Yandex metrika
  ========================= */
  function initYandexMetrika() {
    if (!window.APP_CONFIG || !window.APP_CONFIG.ymId) return;
    if (window.__ymLoaded) return;

    window.__ymLoaded = true;

    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () {
        (m[i].a = m[i].a || []).push(arguments);
      };
      m[i].l = 1 * new Date();
      k = e.createElement(t);
      a = e.getElementsByTagName(t)[0];
      k.async = 1;
      k.src = r;
      a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');

    ym(window.APP_CONFIG.ymId, 'init', {
      clickmap: true,
      trackLinks: true,
      accurateTrackBounce: true,
      webvisor: false,
      ecommerce: 'dataLayer'
    });
  }

})();

$(function () {
    function isPodborPage() {
        return /^\/podbor\/[a-z0-9-]+\/?$/i.test(window.location.pathname);
    }

    function getPodborAlias() {
        const m = window.location.pathname.match(/^\/podbor\/([a-z0-9-]+)\/?$/i);
        return m ? m[1] : '';
    }

    function getSelectedPodborIds() {
        const ids = [];

        $('.js-filter-select').each(function () {
            const val = parseInt($(this).val(), 10);
            if (!isNaN(val) && val > 0) {
                ids.push(val);
            }
        });

        return [...new Set(ids)];
    }

    function hydratePodborSelects() {
        if (!isPodborPage()) return;

        const params = new URLSearchParams(window.location.search);
        const filter = (params.get('filter') || '').trim();
        if (!filter) return;

        const ids = filter
            .split(',')
            .map(v => parseInt(v, 10))
            .filter(v => !isNaN(v) && v > 0);

        $('.js-filter-select').each(function () {
            const $select = $(this);
            let selectedValue = '';

            $select.find('option').each(function () {
                const v = parseInt($(this).val(), 10);
                if (ids.includes(v)) {
                    selectedValue = String(v);
                    return false;
                }
            });

            $select.val(selectedValue);

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }
        });
    }

    if (!isPodborPage()) {
        return;
    }

    hydratePodborSelects();

    $('.js-filter-select').off('.podborDirect');

    $('.js-filter-select').on('change.podborDirect select2:select.podborDirect select2:clear.podborDirect', function () {
        const alias = getPodborAlias();
        if (!alias) return;

        setTimeout(function () {
            const ids = getSelectedPodborIds();

            let url = '/podbor/' + alias;
            if (ids.length) {
                url += '?filter=' + ids.join(',');
            }

            window.location.href = url;
        }, 0);
    });
});
