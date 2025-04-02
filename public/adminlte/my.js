 $(document).on('click', '.delete', function() {
    var res = confirm('Подтвердите удаление');
    if(!res) return false;
});

$('.del-item').on('click', function(){
    var res = confirm('Подтвердите удаление');
    if(!res) return false;
    var $this = $(this),
        id = $this.data('id'),
        src = $this.data('src');
		razdel = $this.data('razdel');
		plagins = $this.data('plagins');
    $.ajax({
        url: adminpath + '/' + $this.data('razdel') + '/delete-gallery',
        data: {id: id, src: src, razdel: razdel, plagins: plagins},
        type: 'POST',
        beforeSend: function(){
            $this.closest('.file-upload').find('.overlay').css({'display':'block'});
        },
        success: function(res){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                if(res == 1){
                    $this.fadeOut();
                }
            }, 1000);
        },
        error: function(){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                alert('Ошибка');
            }, 1000);
        }
    });
});

$('.del-base').on('click', function(){
    var res = confirm('Подтвердите удаление');
    if(!res) return false;
    var $this = $(this),
        id = $this.data('id'),
        src = $this.data('src');
		razdel = $this.data('razdel');
		plagins = $this.data('plagins');
    $.ajax({
        url: adminpath + '/' + $this.data('razdel') + '/delete-baseimg',
        data: {id: id, src: src, razdel: razdel, plagins: plagins},
        type: 'POST',
        beforeSend: function(){
            $this.closest('.file-upload').find('.overlay').css({'display':'block'});
        },
        success: function(res){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                if(res == 1){
                    $this.fadeOut();
                }
            }, 1000);
        },
        error: function(){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                alert('Ошибка');
            }, 1000);
        }
    });
});

$('.del-unload').on('click', function(){
    var res = confirm('Подтвердите удаление');
    if(!res) return false;
    var $this = $(this),
        id = $this.data('id'),
        src = $this.data('src');
		razdel = $this.data('razdel');
		plagins = $this.data('plagins');
    $.ajax({
        url: adminpath + '/' + $this.data('razdel') + '/delete-unload',
        data: {id: id, src: src, razdel: razdel, plagins: plagins},
        type: 'POST',
        beforeSend: function(){
            $this.closest('.file-upload').find('.overlay').css({'display':'block'});
        },
        success: function(res){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                if(res == 1){
                    $this.fadeOut();
                }
            }, 1000);
        },
        error: function(){
            setTimeout(function(){
                $this.closest('.file-upload').find('.overlay').css({'display':'none'});
                alert('Ошибка');
            }, 1000);
        }
    });
});

$('.sidebar-menu a').each(function(){
    var location = window.location.protocol + '//' + window.location.host + window.location.pathname;
    var link = this.href;
    if(link == location){
        $(this).parent().addClass('active');
        $(this).closest('.treeview').addClass('active');
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

if($('div').is('#single')){
    var buttonSingle = $("#single"),
        file;
}
if($('div').is('#multi')){
    var buttonMulti = $("#multi"),
        file;
}
if($('div').is('#unload')){
    var buttonUnload = $("#unload"),
        file;
}

if(buttonSingle){
    new AjaxUpload(buttonSingle, {
        action: adminpath + buttonSingle.data('url') + "?upload=1",
        data: {name: buttonSingle.data('name')},
        name: buttonSingle.data('name'),
	razdel: buttonSingle.data('razdel'),
        onSubmit: function(file, ext){
            if (! (ext && /^(jpg|png|jpeg|gif)$/i.test(ext))){
                alert('Ошибка! Разрешены только картинки');
                return false;
            }
            buttonSingle.closest('.file-upload').find('.overlay').css({'display':'block'});

        },
        onComplete: function(file, response){
            setTimeout(function(){
                buttonSingle.closest('.file-upload').find('.overlay').css({'display':'none'});

                response = JSON.parse(response);
                $('.' + buttonSingle.data('name')).html('<img src="/images/' + buttonSingle.data('razdel') + '/baseimg/' + response.file + '" style="max-height: 160px;">');
            }, 1000);
        }
    });
}

if(buttonMulti){
    new AjaxUpload(buttonMulti, {
        action: adminpath + buttonMulti.data('url') + "?upload=1",
        data: {name: buttonMulti.data('name')},
        name: buttonMulti.data('name'),
	razdel: buttonMulti.data('razdel'),
        onSubmit: function(file, ext){
            if (! (ext && /^(jpg|png|jpeg|gif)$/i.test(ext))){
                alert('Ошибка! Разрешены только картинки');
                return false;
            }
            buttonMulti.closest('.file-upload').find('.overlay').css({'display':'block'});

        },
        onComplete: function(file, response){
            setTimeout(function(){
                buttonMulti.closest('.file-upload').find('.overlay').css({'display':'none'});

                response = JSON.parse(response);
                $('.' + buttonMulti.data('name')).append('<img src="/images/' + buttonMulti.data('razdel') + '/gallery/' + response.file + '" style="max-height: 150px;">');
            }, 1000);
        }
    });
}

if(buttonUnload){
    new AjaxUpload(buttonUnload, {
        action: adminpath + buttonUnload.data('url') + "?upload=1",
        data: {name: buttonUnload.data('name')},
        name: buttonUnload.data('name'),
	razdel: buttonUnload.data('razdel'),
        onSubmit: function(file, ext){
            if (! (ext && /^(jpg|png|jpeg|gif)$/i.test(ext))){
                alert('Ошибка! Разрешены только картинки');
                return false;
            }
            buttonUnload.closest('.file-upload').find('.overlay').css({'display':'block'});

        },
        onComplete: function(file, response){
            setTimeout(function(){
                buttonUnload.closest('.file-upload').find('.overlay').css({'display':'none'});

                response = JSON.parse(response);
                $('.' + buttonUnload.data('name')).append('<img src="/images/' + buttonUnload.data('razdel') + '/unload/' + response.file + '" style="max-height: 150px;">');
            }, 1000);
        }
    });
}

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
        url: path + '/search/typeahead?query=%QUERY'
    }
});

products.initialize();

$("#typeahead").typeahead({
    // hint: false,
    highlight: true
},{
    name: 'products',
    display: 'name',
    limit: 10,
    source: products
});

$('#typeahead').bind('typeahead:select', function(ev, suggestion) {
    // console.log(suggestion);
    window.location = path + '/search/?s=' + encodeURIComponent(suggestion.name);
});

