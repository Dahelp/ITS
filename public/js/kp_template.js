// public/js/kp_template.js
(function () {
  function buildDocDefinition(d) {
    // d = { title, subtitle, priceStr, sku, contentText, attrsBody, images:{logo,logos,product} }
    return {
      info: {
        title: d.subtitle || 'Коммерческое предложение',
        author: 'ИТС-Центр',
        subject: 'Товары',
        keywords: 'Шины, диски, фильтры, на спецтехнику'
      },
      pageSize: 'A4',
      pageOrientation: 'portrait',
      pageMargins: [30, 30, 30, 30],
      content: [
        {
          columns: [
            d.images.logo ? { image: d.images.logo, width: 80 } : { text: '' },
            [
              { text: 'Общество с ограниченной ответственностью «ИТС-Центр»', fontSize: 14, alignment: 'center', margin: [0, 0, 0, 10] },
              { text: '142117, Московская область, г. Подольск, деревня Коледино, ул. Троицкая, д.1Г, стр.1, помещение В-348/49,\nтел./факс +7 (495) 424-98-90, e-mail: info@its50.ru, ИНН/КПП 5036103305/503601001, р/с 40702810901080002314\n в филиале «Центральный Банк ВТБ (ПАО), корр/с 30101810145250000411, БИК 044525411', alignment: 'center', fontSize: 8, margin: [0, 0, 0, 15] }
            ]
          ]
        },
        { table: { widths: ['*'], body: [[{ border: [false, '#00ffff', false, '#00ffff'], text: '' }]] } },
        { columns: [{ text: d.title || 'Коммерческое предложение', fontSize: 14, alignment: 'center', margin: [0, 20, 0, 0], bold: true }] },
        { columns: [{ text: d.subtitle || '', fontSize: 14, alignment: 'center', margin: [0, 20, 0, 20], bold: true }] },
        // ...внутри buildDocDefinition(d)
        {
        columns: [
            {
            stack: [
                d.images.product ? { image: d.images.product, width: 200, margin: [20, 20, 0, 0] } : { text: '' },
                d.sku ? { text: 'Артикул: ' + d.sku, fontSize: 8, alignment: 'center', margin: [0, 20, 0, 20] } : { text: '' }
            ]
            },
            [
            // Блок цены с учётом скидки
            (function () {
                var rows = [];
                if (d.oldPriceStr) {
                rows.push([{ text: 'Старая цена: ' + d.oldPriceStr, fontSize: 10, decoration: 'lineThrough', color: '#888' }]);
                rows.push([{ text: 'Цена со скидкой: ' + (d.priceStr || ''), fontSize: 14, bold: true }]);
                if (d.discountBadge) {
                    rows.push([{ text: 'Скидка: ' + d.discountBadge, fontSize: 10, color: '#c0392b' }]);
                }
                } else {
                rows.push([{ text: 'Цена: ' + (d.priceStr || ''), fontSize: 14, bold: true }]);
                }
                return {
                table: { widths: ['*'], body: rows, headerRows: 1 },
                layout: 'lightHorizontalLines',
                margin: [40, 0, 20, 20]
                };
            })(),
            d.attrsBody && d.attrsBody.length
                ? { style: 'tableExample', table: { widths: ['*', 'auto'], body: d.attrsBody, headerRows: 1 }, layout: 'lightHorizontalLines', margin: [40, 0, 20, 0] }
                : { text: '' }
            ]
        ]
        },
   
      ],
      footer: [
        // тонкая линия (без заливки)
        { canvas: [{ type: 'line', x1: 30, y1: 0, x2: 565, y2: 0, lineWidth: 1, color: '#eaeaea' }], margin: [0, 0, 0, 6] },
        {
          columns: [
            d.images.logos ? { image: d.images.logos, width: 110, margin: [30, 0, 0, 0] } : { text: '' },
            { text: 'Телефон: +7 (495) 424-98-90\n', fontSize: 10, alignment: 'left', margin: [90, 0, 0, 10], width: 230 },
            { text: 'Email: info@its-center.ru\nСайт: its-center.ru', fontSize: 10, alignment: 'left', margin: [100, 0, 0, 10], width: 250 }
          ]
        }
      ]
    };
  }

  var pdfMakePromise = null;

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = function () { reject(new Error('Не удалось загрузить ' + src)); };
      document.head.appendChild(script);
    });
  }

  function loadPdfMake() {
    if (window.pdfMake && window.pdfMake.vfs) return Promise.resolve();
    if (pdfMakePromise) return pdfMakePromise;

    pdfMakePromise = loadScript('/js/pdfmake.js')
      .then(function () { return loadScript('/js/vfs_fonts.js'); })
      .then(function () {
        if (!window.pdfMake || !window.pdfMake.vfs) {
          throw new Error('pdfMake загрузился без шрифтов');
        }
      })
      .catch(function (error) {
        pdfMakePromise = null;
        throw error;
      });

    return pdfMakePromise;
  }

  function openPdfFromDoc(docDefinition, fileName, pdfWindow) {
    var win = pdfWindow || window.open('', '_blank');
    if (!win) { alert('Разрешите всплывающие окна для сайта'); return; }
    pdfMake.createPdf(docDefinition).getBlob(function (blob) {
      if (!blob) { win.close(); alert('Не удалось сформировать PDF'); return; }
      var url = URL.createObjectURL(blob);
      win.location = url;
      setTimeout(function () { try { win.document.title = fileName || 'document.pdf'; } catch (_) {} }, 300);
    });
  }
  // Публичный API
  window.KPTemplate = {
    buildDoc: buildDocDefinition,
    openPdf: openPdfFromDoc,
    attach: function (btnSelector, data, fileName) {
      var btn = document.querySelector(btnSelector);
      if (!btn) return;

      btn.addEventListener('click', function () {
        if (btn.disabled) return;

        // Открываем вкладку в рамках пользовательского клика, чтобы браузер не заблокировал её после загрузки скриптов.
        var pdfWindow = window.open('', '_blank');
        if (!pdfWindow) { alert('Разрешите всплывающие окна для сайта'); return; }

        var initialHtml = btn.innerHTML;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.textContent = 'Формируем…';

        loadPdfMake()
          .then(function () {
            openPdfFromDoc(buildDocDefinition(data), fileName, pdfWindow);
          })
          .catch(function (error) {
            pdfWindow.close();
            console.error(error);
            alert('Не удалось загрузить модуль формирования PDF. Попробуйте ещё раз.');
          })
          .finally(function () {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.innerHTML = initialHtml;
          });
      });
    }  };
})();
