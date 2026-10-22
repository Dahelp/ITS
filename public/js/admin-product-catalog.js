(function ($) {
    'use strict';
    $(function () {
        var config = window.productCatalogConfig;
        if (!config) return;
        var escape = function (value) { return $('<div>').text(value == null ? '' : value).html(); };
        var plain = function (value) { return $('<div>').html(String(value).replace(/<br\s*\/?\s*>/gi, '; ')).text(); };
        var visible = [3, 5, 6, 10, 12];
        var headers = $('#example thead tr');
        headers.find('th').eq(8).after('<th>Отметки</th>');
        var names = {3:'Товар',5:'Цена и остаток',6:'Качество карточки',10:'Управление',12:'Авито'};
        Object.keys(names).forEach(function (index) { headers.find('th').eq(Number(index)).text(names[index]); });

        function product(data, type, row) {
            if (type === 'export') return 'ID '+row[0]+'; Артикул '+row[2]+'; '+row[4]+'; '+plain(data)+'; '+plain(row[1]);
            if (type !== 'display') return data;
            var media=$('<div>').html(row[1]),image=media.find('img').first();
            var labels=['Основная:','Галерея:','Выгрузка:'],imageInfo='';
            media.find('.small > div').each(function(index){
                var values=$(this).find('.badge').clone();
                values.each(function(){ $(this).text($(this).text().replace(/^(галерея|unload):\s*/i,'')); });
                imageInfo+='<div class="pc-image-line"><span class="pc-image-label">'+labels[index]+'</span><span class="pc-image-values">'
                    +values.map(function(){return this.outerHTML;}).get().join('')+'</span></div>';
            });
            return '<div class="pc-product"><div class="pc-media">'+(image.length?image.prop('outerHTML'):'<span class="pc-photo-empty">Нет фото</span>')+'</div><div class="pc-identity">'
                +'<div class="pc-ids">ID '+escape(row[0])+' <span>Артикул '+escape(row[2])+'</span></div>'
                +'<div class="pc-category">'+escape(row[4])+'</div>'+data+'</div><div class="pc-image-info">'+imageInfo+'</div></div>';
        }
        function quality(data, type, row) {
            if (type === 'export') return plain(data)+'; '+plain(row[7])+'; '+plain(row[8])+'; Входящих: '+row[18];
            if (type !== 'display') return data;
            return '<div class="pc-health-top"><div class="pc-seo">'+data+'</div><div class="pc-attributes">'+row[7]+'</div></div>'
                +'<div class="pc-links">'+row[8]+'</div><div class="pc-incoming">Входящих ссылок: <strong>'+escape(row[18])+'</strong></div>';
        }
        function management(data, type, row) {
            if (type === 'export') return plain(data)+'; '+$('<div>').html(row[9]).find('input:checked').map(function(){return $(this).next('label').text();}).get().join(', ');
            if (type !== 'display') return data;
            return '<div class="pc-status">'+data+'</div><div class="pc-flags">'+row[9]+'</div><div class="pc-actions">'+row[11]+'</div>';
        }
        var exportOptions = {columns:visible, stripHtml:true, orthogonal:'export'};
        var table = $('#example').DataTable({
            processing:true, serverSide:true, stateSave:true, autoWidth:false, responsive:false,
            order:config.order,
            stateSaveParams:function(settings, data){data.analyticsScope=config.scope;data.catalogLayout=2;},
            stateLoadParams:function(settings, data){
                if (!data.columns || data.columns.length !== 20 || data.catalogLayout !== 2) return false;
                data.columns.forEach(function(column,index){column.visible=visible.indexOf(index)!==-1;});
                if (data.analyticsScope !== config.scope) {
                    data.start=0; data.order=config.order;
                    if(data.search)data.search.search='';
                    data.columns.forEach(function(column){if(column.search)column.search.search='';});
                }
            },
            lengthMenu:[[20,50,100,-1],[20,50,100,'Все']],
            columnDefs:[
                {targets:[0,1,2,4,7,8,9,11,13,14,15,16,17,18,19],visible:false},
                {targets:[13,14,15,16,17,18,19],searchable:false},
                {targets:[1,4,6,7,8,9,10,11,12],orderable:false},
                {targets:3,render:product,className:'pc-col-product',width:'34%'},
                {targets:5,className:'pc-col-stock',width:'15%'},
                {targets:6,render:quality,className:'pc-col-quality',width:'19%'},
                {targets:10,render:management,className:'pc-col-management',width:'17%'},
                {targets:12,className:'pc-col-avito',width:'15%'}
            ],
            dom:'<"pc-table-toolbar"l f B>rt<"pc-table-footer"i p>',
            ajax:{url:config.ajax},
            buttons:[{extend:'collection',text:'<i class="fas fa-download" aria-hidden="true"></i> Экспорт',className:'btn-sm',buttons:[
                {extend:'copyHtml5',text:'Копировать',exportOptions:exportOptions},
                {extend:'csvHtml5',bom:true,exportOptions:exportOptions},
                {extend:'excelHtml5',exportOptions:exportOptions},
                {extend:'pdfHtml5',exportOptions:exportOptions},
                {extend:'print',text:'Печать',exportOptions:exportOptions}
            ]}],
            createdRow:function(row,data){
                $(row).toggleClass('pc-inactive',data[10].indexOf('Не активный')!==-1)
                    .toggleClass('pc-locked',data[10].indexOf('Закрыт от индексации')!==-1);
                $(row).children('td').each(function(index){$(this).attr('data-label',names[visible[index]]);});
                $(row).find('.pc-actions a').each(function(){
                    var link=$(this),href=link.attr('href')||'',label='';
                    if(href.indexOf('/edit?')!==-1)label='Редактировать товар';
                    else if(href.indexOf('/delete?')!==-1)label='Удалить товар';
                    else if(href.indexOf('/copy?')!==-1)label='Копировать товар';
                    else if(href.indexOf('/media/convert?')!==-1){label='Конвертировать фото в WebP';link.removeAttr('onclick').on('click',function(){return window.confirm('Конвертировать все изображения товара в WebP?');});}
                    else label='Открыть на сайте';
                    link.attr({'title':label,'aria-label':label});
                });
            }
        });
        var analyticsOrder=document.getElementById('product-analytics-order');
        function reflectOrder(){var order=table.order()[0];if(order)analyticsOrder.value=order[0]+':'+order[1];}
        reflectOrder();table.on('order.dt',reflectOrder);
        analyticsOrder.addEventListener('change',function(){var parts=this.value.split(':');if(parts.length===2)table.order([[Number(parts[0]),parts[1]]]).draw();});

        $('.pa-panel').each(function(){
            var card=$(this),button=card.find('[data-card-widget="collapse"]'),key='product-analytics-collapse:'+this.id;
            function sync(collapsed){button.attr({'aria-expanded':String(!collapsed),'aria-label':collapsed?'Развернуть блок':'Свернуть блок','title':collapsed?'Развернуть блок':'Свернуть блок'});}
            try {if(localStorage.getItem(key)==='1'){card.addClass('collapsed-card');button.find('i').removeClass('fa-minus').addClass('fa-plus');sync(true);}} catch(ignore) {}
            card.on('collapsed.lte.cardwidget expanded.lte.cardwidget',function(event){
                var collapsed=event.type==='collapsed';sync(collapsed);
                try{localStorage.setItem(key,collapsed?'1':'0');}catch(ignore){}
            });
        });
        $('#example').on('change','.product-badge-toggle',function(){
            var input=this;input.disabled=true;
            $.ajax({url:adminpath+'/product/badge-toggle',type:'POST',dataType:'json',data:{id:input.getAttribute('data-id'),field:input.getAttribute('data-field'),value:input.checked?1:0}})
                .done(function(result){if(!result||!result.success){input.checked=!input.checked;alert('Не удалось сохранить отметку товара');}})
                .fail(function(){input.checked=!input.checked;alert('Не удалось сохранить отметку товара');})
                .always(function(){input.disabled=false;});
        });
    });
})(jQuery);
