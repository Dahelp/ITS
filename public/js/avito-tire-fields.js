(function ($) {
    'use strict';
    $(function () {
        $('.avito-tire-dictionary').each(function () {
            $(this).select2({width:'100%',minimumResultsForSearch:10,placeholder:'Выберите значение'});
        });
        var button=document.getElementById('avito-fill-tire-fields');
        var data=document.getElementById('avito-tire-suggestion');
        if(!button || !data)return;
        var suggestion=JSON.parse(data.textContent);
        button.addEventListener('click',function () {
            $('#avito-product-type').val(suggestion.product_type).trigger('change');
            var profile=suggestion.product_type==='Мотошины'?'moto':'tires';
            Object.keys(suggestion.fields).forEach(function(field){
                var value=String(suggestion.fields[field]);
                var input=$('.avito-profile-'+profile+' [name="'+field+'"]');
                if(input.is('select') && !input.find('option').toArray().some(function(option){return option.value===value;}))return;
                input.val(value).trigger('change');
            });
            button.textContent='Параметры заполнены — сохраните объявление';
        });
    });
})(jQuery);
