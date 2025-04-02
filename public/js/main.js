/* Filters */
$('body').on('change', '.w_sidebar select', function(){
    var selected = $('.w_sidebar option:selected'),
        data = '';
    selected.each(function () {
		if(this.value !=''){
			data += this.value + ',';
		}else{
			data += '';
		}
		
    });
    if(data){
        $.ajax({
            url: location.href,
            data: {filter: data},
            type: 'GET',
            beforeSend: function(){
                $('.preloader').fadeIn(300, function(){
                    $('.product-one').hide();
                });
            },
            success: function(res){
                $('.preloader').delay(500).fadeOut('slow', function(){
                    $('.product-one').html(res).fadeIn();
                    var url = location.search.replace(/filter(.+?)(&|$)/g, ''); //$2
                    var newURL = location.pathname + url + (location.search ? "&" : "?") + "filter=" + data;
                    newURL = newURL.replace('&&', '&');
                    newURL = newURL.replace('?&', '?');
                    history.pushState({}, '', newURL);
					//window.location=newURL;
                });
            },
            error: function () {
                alert('Ошибка!');
            }
        });
    }else{
        window.location = location.pathname;
    }
});

$(function(){
	$(".js-select2").select2({
			closeOnSelect : false,
			placeholder : "Click to select an option",
			allowHtml: true,
			allowClear: true,
			tags: true // создает новые опции на лету
		});

	$('.icons_select2').select2({
		width: "100%",
		templateSelection: iformat,
		templateResult: iformat,
		allowHtml: true,
		placeholder: "Click to select an option",
		dropdownParent: $( '.select-icon' ),//обавили класс
		allowClear: true,
		multiple: false
	});


	function iformat(icon, badge,) {
		var originalOption = icon.element;
		var originalOptionBadge = $(originalOption).data('badge');
	 
		return $('<span><i class="fa ' + $(originalOption).data('icon') + '"></i> ' + icon.text + '<span class="badge">' + originalOptionBadge + '</span></span>');
	}
	
})

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
    source: products,
	templates: {
        empty: 'Товар не найден. Попробуйте ввести запрос по другому.', //optional
        suggestion: function(el){return '<a href="product/'+el.alias+'"><div class="tt-images"><img class="img-fit" src="images/product/mini/'+el.img+'" /></div><div class="tt-info"><div class="tt-name">'+el.name+'</div><div class="tt-price"><span class="fw-600 fs-16 text-primary">'+el.price+' руб</span></div></div></a>';}
    }
});

$('#typeahead').bind('typeahead:select', function(ev, suggestion) {
    // console.log(suggestion);
    window.location = path + '/search/?s=' + encodeURIComponent(suggestion.name);
});

/*Cart*/
$('body').on('click', '.add-to-cart-link', function(e){
     e.preventDefault();
     var id = $(this).data('id'),
	 max = $(this).data('max'),
         qty = $('.quantity input').val() ? $('.quantity input').val() : 1,
         mod = $('.available select').val();
     $.ajax({
         url: '/cart/add',
         data: {id: id, qty: qty, mod: mod, max:max},
         type: 'GET',
         success: function(res){
             showCart(res);
		$('.korzina-'+id+'').attr('style', 'display: none !important');
		$('.vkorzine-'+id+'').css('display', 'block');		
         },
         error: function(){
             alert('Ошибка! Попробуйте позже');
         }
     });
});

/*Cart*/
$('body').on('click', '.add-to-cart-mod', function(e){
     e.preventDefault();
     var id = $(this).data('id'),
	 max = $(this).data('max'),
         qty = $('.korzina-'+id+'').val() ? $('.korzina-'+id+'').val() : 1,
         modification = $('.modification').val();
     $.ajax({
         url: '/cart/add',
         data: {id: id, qty: qty, modification: modification, max:max},
         type: 'GET',
         success: function(res){
             showCart(res);
		$('.korzina-'+id+'').attr('style', 'display: none !important');
		$('.vkorzine-'+id+'').css('display', 'block');		
         },
         error: function(){
             alert('Ошибка! Попробуйте позже');
         }
     });
});

/*Cart complete*/
$('body').on('click', '.add-to-cart-complete', function(e){
     e.preventDefault();
     var id = $(this).data('id'),
	 complete = $(this).data('complete'),
	 set = $(this).data('set'),
         qty = $('.quantity-complete input').val() ? $('.quantity-complete input').val() : 1,
         mod = $('.available select').val();
     $.ajax({
         url: '/cart/addcomplete',
         data: {id: id, qty: qty, mod: mod, complete:complete, set:set},
         type: 'GET',
         success: function(res){
             showCart(res);
		$('.korzina-'+id+'').attr('style', 'display: none !important');
		$('.vkorzine-'+id+'').css('display', 'block');		
         },
         error: function(){
             alert('Ошибка! Попробуйте позже');
         }
     });
});

$('body').on('input', '.detail-quantity', function(){

	var value = this.value.replace(/[^0-9]/g, '');

	if (value < $(this).data('min')) {

		this.value = $(this).data('min');

	} else if (value > $(this).data('max')) {

		this.value = $(this).data('max');

	} else {
		this.value = value;
	}

});
// delete product id modal
$('#exampleModalLive .modal-body').on('click', '.del-item', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/delete',
        data: {id: id},
        type: 'GET',
        success: function(res){
            showCart(res);
		$('.korzina-'+id+'').attr('style', 'display: block !important');
		$('.vkorzine-'+id+'').css('display', 'none');
		recalCart(res);	
        },
        error: function(){
            alert('Error!');
        }
    });
});
// delete product id modal to complete
$('#exampleModalLive .modal-body').on('click', '.del-item-complete', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/deletecomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){
            showCart(res);
		$('.korzina-'+id+'').attr('style', 'display: block !important');
		$('.vkorzine-'+id+'').css('display', 'none');
		recalCart(res);	
        },
        error: function(){
            alert('Error!');
        }
    });
});
// delete product id cart
$('body').on('click', '.del-item-cart', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/deletecart',
        data: {id: id},
        type: 'GET',
        success: function(res){
			recalCart(res);
        },
        error: function(){
            alert('Error!');
        }
    });
});
// delete product id cart to complete
$('body').on('click', '.del-item-complete-cart', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/deletecartcomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){
			recalCart(res);
        },
        error: function(){
            alert('Error!');
        }
    });
});

// increase product id modal
$('body').on('click', '.my-plus', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/plusmodal',
        data: {id: id},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});

// increase product id modal to complete
$('body').on('click', '.my-plus-complete', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/plusmodalcomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});

// increase product id cart
$('body').on('click', '.my-plus-cart', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/pluscart',
        data: {id: id},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});

// increase product id cart to complete
$('body').on('click', '.my-plus-complete-cart', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/pluscartcomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});
// reduce product id modal
$('body').on('click', '.my-minus', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/minusmodal',
        data: {id: id},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});
// reduce product id modal to complete
$('body').on('click', '.my-minus-complete', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/minusmodalcomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});

// reduce product id cart
$('body').on('click', '.my-minus-cart', function(){
    var id = $(this).data('id');
    $.ajax({
        url: '/cart/minuscart',
        data: {id: id},
        type: 'GET',
        success: function(res){
		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});
// reduce product id cart to complete
$('body').on('click', '.my-minus-complete-cart', function(){
    var id = $(this).data('id');
	var min = $(this).data('min');
	var set = $(this).data('set');
    $.ajax({
        url: '/cart/minuscartcomplete',
        data: {id: id, min: min, set:set},
        type: 'GET',
        success: function(res){		
            recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });
});



// promo id cart
$('.vpromo').attr('value', '');
$('body').on('click', '.btn-promo', function(){
	var val = $('.vpromo').val();//Получаем данные из input
	$.ajax({
        url: '/cart/promocart',
		data: {val: val},
        type: 'GET',
        success: function(res){		
			recalCart(res);
        },
        error: function(){
            alert('Ошибка при пересчёте!');
        }
    });    
});

function clearPromo() {
    $.ajax({
        url: '/cart/clearpromo',
        type: 'GET',
        success: function(res){
			recalCart(res);
        },
        error: function(){
            alert('Ошибка! Попробуйте позже');
        }
    });
}

function recalCart(cart){
	if($.trim(cart) == '<h3>Корзина пуста</h3>'){
        $('#exampleModalLive .modal-footer a, #exampleModalLive .modal-footer .btn-primary').css('display', 'none');	
   	}else{
        $('#exampleModalLive .modal-footer a, #exampleModalLive .modal-footer .btn-primary').css('display', 'inline-block');
	}
    $('#exampleModalLive .modal-body').html(cart);
	$('.product-cart').html(cart);
	var qty = $('#prodcart .cart-qty').text();
	if(qty>0){
		$('.simpleCart_qty').html($('#exampleModalLive .cart-qty').text());	
    }else{
      	$('.simpleCart_qty').text('0');
		$('.cart-block').css('display', 'none');
		$('.cart-no-product').css('display', 'block');
   	}
  	if($('.cart-sum').text()){
   	     $('.simpleCart_total').html($('#exampleModalLive .cart-sum').text());	
  	}else{
   	     $('.simpleCart_total').text('0');
   	}

}

function showCart(cart){
    if($.trim(cart) == '<h3>Корзина пуста</h3>'){
        $('#exampleModalLive .modal-footer a, #exampleModalLive .modal-footer .btn-primary').css('display', 'none');	
    }else{
        $('#exampleModalLive .modal-footer a, #exampleModalLive .modal-footer .btn-primary').css('display', 'inline-block');
    }
    $('#exampleModalLive .modal-body').html(cart);
    $('#exampleModalLive').modal();
	if($('.cart-qty').text()){
       	 $('.simpleCart_qty').html($('#exampleModalLive .cart-qty').text());	
    	}else{
        $('.simpleCart_qty').text('0');
    }
    if($('.cart-sum').text()){
        $('.simpleCart_total').html($('#exampleModalLive .cart-sum').text());	
    }else{
        $('.simpleCart_total').text('0');
    }
}

function showCarts(cart){

    $('#exampleModalLive .modal-body').html(cart);
    $('#exampleModalLive').modal();
	if($('.cart-qty').text()){
       	 $('.simpleCart_qty').html($('#exampleModalLive .cart-qty').text());	
    	}else{
        $('.simpleCart_qty').text('0');
    }
    if($('.cart-sum').text()){
        $('.simpleCart_total').html($('#exampleModalLive .cart-sum').text());	
    }else{
        $('.simpleCart_total').text('0');
    }
}

function getCart() {
    $.ajax({
        url: '/cart/show',
        type: 'GET',
        success: function(res){
            showCart(res);		
        },
        error: function(){
            alert('Ошибка! Попробуйте позже');
        }
    });
}

function clearCart() {
    $.ajax({
        url: '/cart/clear',
        type: 'GET',
        success: function(res){
            showCart(res);
		$('.clear-korzina').attr('style', 'inline-display: block !important');
		$('.clear-vkorzine').css('display', 'none');
		recalCart(res);
        },
        error: function(){
            alert('Ошибка! Попробуйте позже');
        }
    });
}
/*Cart*/

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

$('body').on('click', '.btn-wishlist', function(){
    var product_id = $(this).data('id');
	var user_id = $(this).data('userid');
    $.ajax({
        url: '/user/bookmarks',
        data: {product_id: product_id, user_id: user_id},
        type: 'GET',
        success: function(res){
			$('#wishlist-'+product_id+'').removeClass('btn-wishlist');
			$('#wishlist-'+product_id+'').addClass("btn-wishlist2");
        },
        error: function(){
            alert('Error!');
        }
    });
});

$('body').on('click', '.btn-comparison', function(){
    var product_id = $(this).data('id');
	var category_id = $(this).data('categoryid');
	var kolcompar = $('#comparison_kol').text();
	
    $.ajax({
        url: '/comparison/addcomparison',
        data: {product_id: product_id, category_id: category_id, kolcompar: kolcompar},
        type: 'GET',
		dataType: 'json',
        success: function(res){
			$('#comparison-'+product_id+'').removeClass('btn-comparison');
			$('#comparison-'+product_id+'').addClass("btn-comparison2");
			if(res.result>1) {
				$('.comparison_kol').html("<a href=\"comparison\"> Сравнение (<span id=\"comparison_kol\">"+res.result+"</span>)</a>");
			}else{
				$('.comparison_kol').html(" Сравнение (<span id=\"comparison_kol\">"+res.result+"</span>)");
			}

        },
        error: function(){
            alert('Error!');
        }
    });
});

$('body').on('click', '#comparison-close', function(){
    var product_id = $(this).data('id');
	var category_id = $(this).data('categoryid');
	var kolcompar = $('#comparison_kol').text();
	
    $.ajax({
        url: '/comparison/deletecomparison',
        data: {product_id: product_id, category_id: category_id, kolcompar: kolcompar},
        type: 'GET',
		dataType: 'json',
        success: function(res){
			$('#comparison-'+product_id+'').removeClass('btn-comparison');
			$('#comparison-'+product_id+'').addClass("btn-comparison2");
			$('.comparison_kol').html(" Сравнение (<span id=\"comparison_kol\">"+res.result+"</span>)");			
			$('.close-compartd-'+product_id+'').css('display', 'none');
			$('.comparison-catvse').html(" Все ("+res.result+")");
			$('.comparcat').html(""+res.result2+"");
			if(res.result==0) {
				$('.no-comparison').css('display', 'none');
				$('.no-compar-sess-block').css('display', 'block');
			}
        },
        error: function(){
            alert('Error!');
        }
    });
});

$('#dostavka_id').change(function(){
	var dostavka = $(this).val();
	if(dostavka==1) {
		$('#another_sklad').css('display', 'block');
		$('#another_sklad').html('<select class="form-control" name="branch_id"><option value="1">г. Климовск</option></select>');
		$('#another_city').css('display', 'none');
		$('#another_city').html('');
		$('#another_transport').css('display', 'none');		
		$('#another_transport').html('');
		$('#another_adress').css('display', 'none');		
		$('#another_adress').html('');
	}
	if(dostavka==2) {
		$('#another_city').css('display', 'block');
		$('#another_city').html('<input type="text" class="form-control" name="city_name" placeholder="Укажите город для доставки" required="">');
		$('#another_transport').css('display', 'block');
		$('#another_transport').html('<select class="form-control" name="transport_id"><option value="">Выберите транспортную компанию</option><option value="">-------------------------</option><option value="1">ПЭК</option><option value="2">Деловые Линии</option><option value="3">Байкал-Сервис</option><option value="5">Кит</option><option value="6">СДЭК</option><option value="8">Энергия</option></select>');		
		$('#another_sklad').css('display', 'none');
		$('#another_sklad').html('');
		$('#another_adress').css('display', 'none');		
		$('#another_adress').html('');
	}
	if(dostavka==3) {
		$('#another_city').css('display', 'block');
		$('#another_city').html('<select class="form-control" name="city_name"><option value="Москва">Москва</option></select>');
		$('#another_adress').css('display', 'block');
		$('#another_adress').html('<input type="text" name="address" class="form-control" id="address" placeholder="Адрес доставки товаров">');
		$('#another_sklad').css('display', 'none');
		$('#another_transport').css('display', 'none');
		$('#another_transport').html('');
	}
});

$('#vidurlface').change(function(){
	var vid = $(this).val();
	if(vid==3) {
		$('#vid_urlface').css('display', 'none');
		$('#vid_urlface').html('');
	}
	if(vid==4) {
		$('#vid_urlface').css('display', 'block');
		//$('#vid_urlface').html('<div class="col-sm-12 rekvizity row"><div class="col-sm-5"><label class="form-label" for="rekvizity">Укажите ИНН компании <span class="text-danger">*</span></label><div class="row"><div class="col-sm-6"><input class="form-control inn" type="text" name="inn"></div><div class="col-sm-6"><div class="btn btn-primary btn-inn">Подтвердить</div></div></div></div><div class="col-sm-2 align-middle">ИЛИ</div><div class="col-sm-5"><label class="form-label" for="rekvizity">Прикрепить реквизиты <span class="text-danger">*</span></label><input class="btn btn-default" type="file" name="rekvizity"></div></div><p></p><div class="col-sm-12 innok"></div><p></p><div class="col-sm-6"><label class="form-label" for="nds">Система налогообложения <span class="text-danger">*</span></label><select name="nds" class="form-control" required><option value = "" selected="selected">Выберите систему налогообложения</option><option value = "1">с НДС</option><option value = "2">без НДС</option></select></div><p></p><div class="col-sm-6"><label class="form-label" for="dogovor">Условия поставки <span class="text-danger">*</span></label><select name="dogovor" class="form-control" required><option value = "" selected="selected">Выберите условия поставки</option><option value = "1">Договор</option><option value = "2">Счёт-договор</option></select></div>');
		$('#vid_urlface').html('<div class="col-sm-12 rekvizity row"><div class="col-sm-5"><label class="form-label" for="rekvizity">Прикрепить реквизиты <span class="text-danger">*</span></label><input class="btn btn-default" type="file" name="rekvizity" required /></div></div><div class="col-sm-6"><label class="form-label" for="nds">Система налогообложения <span class="text-danger">*</span></label><select name="nds" class="form-control" required><option value = "" selected="selected">Выберите систему налогообложения</option><option value = "1">с НДС</option><option value = "2">без НДС</option></select></div><p></p><div class="col-sm-6"><label class="form-label" for="dogovor">Условия поставки <span class="text-danger">*</span></label><select name="dogovor" class="form-control" required><option value = "" selected="selected">Выберите условия поставки</option><option value = "1">Договор</option><option value = "2">Счёт-договор</option></select></div>');
	
	}    
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

/*newYear2025
(function (t) {
  function e(t) {
    var e = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
    var a = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
    var n = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
    return function (e) {
      var o,
        i = [],
        r = [1, 3, 5],
        d = "rgba(138, 216, 235, " + e + ")",
        e = document.createElement("canvas"),
        c = e.getContext("2d"),
        s = t.width() + n,
        l = t.height() + a;
      e.width = s, e.height = l, e.style.position = "absolute", e.style.top = "0", e.style.left = "0", t.append(e);
      for (var h = 0; h < 150; h++) o = {
        x: Math.random() * s,
        y: Math.random() * l,
        speed: .3 * Math.random(),
        radius: Math.random() * (r[1] - r[0]) + r[0],
        direction: .5 < Math.random() ? 1 : -1,
        speedX: .1 * Math.random()
      }, i.push(o);
      !function t() {
        c.clearRect(0, 0, s, l);
        for (var e = 0; e < i.length; e++) c.beginPath(), c.fillStyle = d, c.arc(i[e].x, i[e].y, i[e].radius, 0, 2 * Math.PI), c.fill(), i[e].x += i[e].speedX * i[e].direction, i[e].y += i[e].speed, i[e].y > l && (i[e].y = 0), (i[e].x < 0 || i[e].x > s) && (i[e].direction = -i[e].direction);
        requestAnimationFrame(t);
      }();
    }(e);
  }
  t(document).ready(function () {
    t("body").addClass("newYear2025"), e(t(".site-header"), 0.7, 60, 80), e(t(".fbg"), .3, 60), e(t(".site-footer"), .3),
	t(".category-box__block .menu__title").each(function () {
      e(t(this), .2, 20, 80);
    });
  });
})(window.jQuery = window.$ = jQuery);
*/