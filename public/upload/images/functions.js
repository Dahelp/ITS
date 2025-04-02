function deleteSubmitFields()
{
	window.document.ts_form.submit_form.value="";
	window.document.ds_form.submit_form.value="";
}

function changeSubmitButton(product)
{
	if(product == 'tyres')
	{
		window.document.getElementById('tyres_submit_button').src="/images/search_click_me.gif";
	}
	else if(product == 'disks')
	{
		window.document.getElementById('disks_submit_button').src="/images/search_click_me.gif";
	}
}

function doSize_car() 
{
	if (document.body.clientWidth<800) 
	{
		return "/images/car_logo-800x600.jpg";
	}
	else if (document.body.clientWidth>800 && document.body.clientWidth<1024) 
	{
		return "/images/car_logo-1024x768.jpg";
	}
	else if (document.body.clientWidth>1024)
	{
		return "/images/car_logo-1280x1024.jpg";
	}
	else
	{
		return "/images/car_logo-1024x768.jpg";
	}
}

function doSize() 
{
	if (document.body.clientWidth<1000) 
	{
		document.getElementById('logo').className =  "logo_800x600";
		document.getElementById('tyres_form_filter').className =  "filter_800x600";
		document.getElementById('disks_form_filter').className =  "filter_800x600";
		document.getElementById('tyres_header').className =  "form_header_800x600";
		document.getElementById('disks_header').className =  "form_header_800x600";
		document.getElementById('top_table').className =  "top_menu_800x600";
		document.getElementById('center').className =  "top_menu_800x600";
		document.getElementById('left').className =  "top_menu_800x600";
		document.getElementById('right').className =  "top_menu_800x600";
		document.getElementById('offers').className =  "offers_800x600";
		document.getElementById('bottom_wheel').className =  "bottom_wheel_800x600";
	}
	else if (document.body.clientWidth>1000 && document.body.clientWidth<1100) 
	{
		document.getElementById('logo').className =  "logo_1024x768";
		document.getElementById('tyres_form_filter').className =  "filter_1024x768";
		document.getElementById('disks_form_filter').className =  "filter_1024x768";
		document.getElementById('tyres_header').className =  "form_header_1024x768";
		document.getElementById('disks_header').className =  "form_header_1024x768";
		document.getElementById('top_table').className =  "top_menu";
		document.getElementById('center').className =  "top_menu";
		document.getElementById('left').className =  "top_menu";
		document.getElementById('right').className =  "top_menu";
		document.getElementById('offers').className =  "offers_1024x768";
		document.getElementById('bottom_wheel').className =  "bottom_wheel_1024x768";
	}
	else if (document.body.clientWidth>1100)
	{
		document.getElementById('logo').className= "logo_1280x1024";
		document.getElementById('tyres_form_filter').className="filter_1280x1024";
		document.getElementById('disks_form_filter').className="filter_1280x1024";
		document.getElementById('tyres_header').className =  "form_header_1280x1024";
		document.getElementById('disks_header').className =  "form_header_1280x1024";
		document.getElementById('top_table').className =  "top_menu";
		document.getElementById('center').className =  "top_menu";
		document.getElementById('left').className =  "top_menu";
		document.getElementById('right').className =  "top_menu";
		document.getElementById('offers').className =  "offers_1280x1024";
		document.getElementById('bottom_wheel').className =  "bottom_wheel_1280x1024";
	}
	else
	{
		document.getElementById('logo').className =  "logo_1024x768";
		document.getElementById('ts_form_td').className =  "form_1024x768";
		document.getElementById('ts_form_td').className =  "form_1024x768";
		document.getElementById('tyres_form_filter').className =  "filter_1024x768";
		document.getElementById('disks_form_filter').className =  "filter_1024x768";
		document.getElementById('tyres_header').className =  "form_header_1024x768";
		document.getElementById('disks_header').className =  "form_header_1024x768";
		document.getElementById('top_table').className =  "top_menu";
		document.getElementById('center').className =  "top_menu";
		document.getElementById('left').className =  "top_menu";
		document.getElementById('right').className =  "top_menu";
		document.getElementById('offers').className =  "offers_1024x768";
		document.getElementById('bottom_wheel').className =  "bottom_wheel_1024x768";
	}
}

function clickclear(thisfield, defaulttext) 
{
	if (thisfield.value == defaulttext) 
	{
		thisfield.value = "";
	}
}

function clickrecall(thisfield, defaulttext) 
{
	if (thisfield.value == "") 
	{
		thisfield.value = defaulttext;
	}
}

function showInfo () 
{
	var allA = document.getElementById('content').getElementsByTagName('a');
	for (var i = 0, l=allA.length; i < l; i++) 
	{
		if (allA[i].toString().indexOf('http') == '-1')
		{
			allA[i].onclick = function (ev)
			{
				ev = ev ? ev : window.event;
				var infoDiv = document.getElementById('info');
				if (typeof (ev.pageY) == 'number') 
				{
					infoDiv.style.top = ev.pageY-50 + 'px';
					infoDiv.style.left = ev.pageX-100 + 'px';
				}
				else
				{
					xcoord = ev.clientX;
					ycoord = ev.clientY;
					xcoord += document.documentElement.scrollLeft;
					ycoord += document.documentElement.scrollTop;
					infoDiv.style.top = ycoord-50 + 'px';
					infoDiv.style.left =  xcoord-100 + 'px';
				}
				infoDiv.style.display = 'block';
			};
		}
	}
}

function showImagesOnOver (id,image_filename,image_title,image_note) 
{
	imageCat = document.getElementById ('catalog-image'),
	imageName = document.getElementById ('image-name'),
	imageCont = document.getElementById ('image-container'),
	imageNote = document.getElementById ('image-note'),

	curIm = document.getElementById (id);
	curIm.onmouseover = function (ev) 
	{
		el = ev ? ev.target : window.event.srcElement;
		ev = ev ? ev : window.event
				imageName.innerHTML = image_title;
		imageCont.innerHTML = '<img src="' + image_filename + '">';
		imageNote.innerHTML = image_note;
		if (typeof (ev.pageY) == 'number') 
		{
			imageCat.style.top = ev.pageY - 270 + 'px';
			imageCat.style.left = ev.pageX+30 + 'px';
		}
		else
		{
			xcoord = ev.clientX;
			ycoord = ev.clientY;
			xcoord += document.documentElement.scrollLeft;
			ycoord += document.documentElement.scrollTop;
			imageCat.style.top = ycoord - 270 + 'px';
			imageCat.style.left =  xcoord+30 + 'px';
		}
		imageCat.style.display = 'block';
	};

	curIm.onmousemove = function (ev)
	{
		ev = ev ? ev : window.event
				if (typeof (ev.pageY) == 'number') 
				{
					imageCat.style.top = ev.pageY - 270 + 'px';
					imageCat.style.left = ev.pageX+30 + 'px';
				}
				else
				{
					xcoord = ev.clientX;
					ycoord = ev.clientY;
					xcoord += document.documentElement.scrollLeft;
					ycoord += document.documentElement.scrollTop;
					imageCat.style.top = ycoord - 270 + 'px';
					imageCat.style.left =  xcoord+30 + 'px';
				}
	};

	curIm.onmouseout = function (ev) 
	{
		imageCat.style.display = 'none';
	};
}

function print_r(theObj)
{
	if(theObj.constructor == Array || theObj.constructor == Object)
	{
		document.write("<ul>")
		for(var p in theObj)
		{
			if(theObj[p].constructor == Array|| theObj[p].constructor == Object)
			{
				document.write("<li>["+p+"] => "+typeof(theObj)+"</li>");
				document.write("<ul>")
				print_r(theObj[p]);
				document.write("</ul>")
			}
			else
			{
				document.write("<li>["+p+"] => "+theObj[p]+"</li>");
			}
		}
		document.write("</ul>")
	}
}

function addOption (oListbox, text, value, isDefaultSelected, isSelected)
{
	var oOption = document.createElement("option");
	oOption.appendChild(document.createTextNode(text));
	oOption.setAttribute("value", value);

	if (isDefaultSelected) oOption.defaultSelected = true;
	else if (isSelected) oOption.selected = true;

	oListbox.appendChild(oOption);
}

function array_values( input ) {
	// http://kevin.vanzonneveld.net
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// *     example 1: array_values( {firstname: 'Kevin', surname: 'van Zonneveld'} );
	// *     returns 1: {0: 'Kevin', 1: 'van Zonneveld'}

	var tmp_arr = [], cnt = 0;
	var key = '';

	for ( key in input ){
		tmp_arr[cnt] = input[key];
		cnt++;
	}

	return tmp_arr;
}

function isset(  )
{
	// http://kevin.vanzonneveld.net
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: FremyCompany
	// +   improved by: Onno Marsman
	// *     example 1: isset( undefined, true);
	// *     returns 1: false
	// *     example 2: isset( 'Kevin van Zonneveld' );
	// *     returns 2: true

	var a=arguments; var l=a.length; var i=0;

	if (l==0) { 
		throw new Error('Empty isset'); 
	}

	while (i!=l) {
		if (typeof(a[i])=='undefined' || a[i]===null) { 
			return false; 
		} else { 
			i++; 
		}
	}
	return true;
}

function count( mixed_var, mode )
{
	// http://kevin.vanzonneveld.net
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +      input by: Waldo Malqui Silva
	// *     example 1: count([[0,0],[0,-4]], 'COUNT_RECURSIVE');
	// *     returns 1: 6
	// *     example 2: count({'one' : [1,2,3,4,5]}, 'COUNT_RECURSIVE');
	// *     returns 2: 6

	var key, cnt = 0;

	if( mode == 'COUNT_RECURSIVE' ) mode = 1;
	if( mode != 1 ) mode = 0;

	for (key in mixed_var){
		cnt++;
//		if( mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){
//		cnt += count(mixed_var[key], 1);
//		}
	}

	return cnt;
}

//function getOptionsList(product)
//{
//// Create new JsHttpRequest object.
//var queryOptionsList = new JsHttpRequest();
//// Code automatically called on load finishing
//if(product == 'tyres')
//{
//queryOptionsList.onreadystatechange = function() 
//{
//if (queryOptionsList.readyState == 4) 
//{
//// Write result to page element (_RESULT become responseJS). 
//var radius_select = document.ts_form.radius;
//document.ts_form.radius.length = 0; // clear select
//radiuses = array_values(queryOptionsList.responseJS.radius);

//for (var i = 0, l = radiuses.length; i < l; i++)
//{
//addOption(radius_select, radiuses[i], radiuses[i], true);
//}

//// Write debug information too (output become responseText).
//document.getElementById('debug').innerHTML = queryOptionsList.responseText;
//}
//}
//}
//else if(product == 'disks')
//{
////         queryOptionsList.send( { q: document.ds_form } );
//}

//// Prepare request object (automatically choose GET or POST).
//queryOptionsList.open(null, 'validate.php', true);
//// Send data to backend.
//if(product == 'tyres')
//{
//queryOptionsList.send( { q: document.ts_form } );
//}
//else if(product == 'disks')
//{
//queryOptionsList.send( { q: document.ds_form } );
//}
//}

var d = document;
var offsetfromcursorY=15 // y offset of tooltip
var ie=d.all && !window.opera;
var ns6=d.getElementById && !d.all;
var tipobj,op;

function tooltip(el,txt) {
	tipobj=d.getElementById('info');
	tipobj.innerHTML = txt;
	op = 0.1;	
	tipobj.style.opacity = op; 
	tipobj.style.display="block";
	el.onmousemove=positiontip;
	appear();
}

function hide_info(el) {
	d.getElementById('info').style.display='none';
	el.onmousemove='';
}

function ietruebody(){
	return (d.compatMode && d.compatMode!="BackCompat")? d.documentElement : d.body
}

function positiontip(e) {
	var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
	var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
	var winwidth=ie? ietruebody().clientWidth : window.innerWidth-20
			var winheight=ie? ietruebody().clientHeight : window.innerHeight-20

					var rightedge=ie? winwidth-event.clientX : winwidth-e.clientX;
			var bottomedge=ie? winheight-event.clientY-offsetfromcursorY : winheight-e.clientY-offsetfromcursorY;

			if (rightedge < tipobj.offsetWidth)	tipobj.style.left=curX-tipobj.offsetWidth+"px";
			else tipobj.style.left=curX+"px";

			if (bottomedge < tipobj.offsetHeight) tipobj.style.top=curY-tipobj.offsetHeight-offsetfromcursorY+"px"
			else tipobj.style.top=curY+offsetfromcursorY+"px";
}

function appear() {	
	if(op < 1) {
		op += 0.1;
		tipobj.style.opacity = op;
		tipobj.style.filter = 'alpha(opacity='+op*100+')';
		t = setTimeout('appear()', 30);
	}
}

function removeSnow()
{

	if(window.document.getElementById('sezon')[window.document.getElementById('sezon').selectedIndex].value=="Зима")
	{
		document.getElementById('shipovannost_cell').style.visibility = 'visible';
	}
	else
	{
		document.getElementById('shipovannost_cell').style.visibility = 'hidden';
		document.getElementById('shipovannostШипованность').selected = true;
	}
}

function showMarka()
{
	var proizvoditel = window.document.getElementById('proizvoditel').value;
	proizvoditel = proizvoditel.toLowerCase();

//	if(window.document.getElementById('proizvoditel').value == "Replica")
	if(proizvoditel.match(/replica.*?/))
	{
		document.getElementById('marka_avtomobilja').style.visibility = 'visible';
	}
	else
	{
		document.getElementById('marka_avtomobilja').style.visibility = 'hidden';
	}
}

function select_cotragent_individual()
{
	if( $('input[name=delivery]:checked').val() == 'russian-post' ){
		$('#payment_nocache_count,#payment_card-sber').show();
		$('#payment_cache,#payment_nocache,#payment_bank-card').hide();
		$('#details').show();
		$('#payment').val('payment_nocache_count');
		
		$('#delivery_message_other_town,#address_other_town_message').show();
		$('#delivery_message_spb,#self_delivery_message,#address_spb_message').hide();
	}
	else{
		$('#payment_cache,#payment_bank-card').show();
		$('#payment_nocache,#payment_nocache_count,#payment_card-sber').hide();
		$('#details').hide();
		$('#payment').val('cache');
	}
	$('#delivery_courier').show();
}

function select_cotragent_legalentity(){
	
		$('#payment').val('nocache');
		$('#payment_nocache').show();
		$('#payment_cache,#payment_nocache_count,#payment_card-sber,#payment_bank-card').hide();
		$('#details').show();
		$('#delivery_courier').hide();
				//		$("#delivery_courier_input").removeAttr("selected");
				//		$("#delivery_courier_input").prop("selected", false);
}

function select_delivery_by_payment(){
	switch($('input[name=contragent]:checked').val())
	{
  case 'individual':
		select_cotragent_individual();
		break;
  case 'legal-entity':
	  select_cotragent_legalentity();
	}
}

function select_delivery_courier()
{
	$('#delivery_message_spb,#delivery_message_price,#address_spb_message').show();
	$('#delivery_message_other_town,#self_delivery_message,#address_other_town_message').hide();
	select_delivery_by_payment();
}

function select_delivery_self_delivery(){
	
	$('#self_delivery_message,#address_spb_message').show();
	$('#delivery_message_other_town,#delivery_message_spb,#delivery_message_other_town,#address_other_town_message').hide();
	if($('input[name^=tyre]').val() != 1){
		$('#delivery_message_price').hide();
	}
	select_delivery_by_payment();
//	$( '#checkout_form' ).submit();
}

function select_delivery_russianpost(){
	$('#delivery_message_other_town,#address_other_town_message').show();
	$('#delivery_message_spb,#self_delivery_message,#address_spb_message').hide();
	
	select_delivery_by_payment();

//	$( '#checkout_form' ).submit();
}

function addSubmit() //addOption(radius_select, radiuses[i], radiuses[i], true);
{
	document.getElementById('submitPart1').value='Обработать дубли часть 1';
	var partsCount = document.getElementById('partsCount').value ;
	partsCount++;
	document.getElementById('partsCount').value = partsCount;

	form_table = document.getElementById('doubles_form_table');
//	Create tr
	var newTr = document.createElement("tr");
	newTr.setAttribute("id",'submitTr'+partsCount);
	form_table.appendChild(newTr);
//	.Create td
	var newTd = document.createElement("td");
	newTd.setAttribute("id",'submitTd'+partsCount);
	newTd.setAttribute("colspan",3);
	newTd.setAttribute("align",'center');
	newTr.appendChild(newTd);
//	.Create state td
	var newTdState = document.createElement("td");
	newTdState.setAttribute("id",'statePart'+partsCount);
	newTdState.setAttribute("align",'center');
	newTr.appendChild(newTdState);

	var newSubmit = document.createElement("input");
	newSubmit.setAttribute("type",'button');
	newSubmit.setAttribute("name",'submitPart'+partsCount);
	newSubmit.setAttribute("value",'Обработать дубли часть '+partsCount);
	newSubmit.setAttribute("id",'submitPart'+partsCount);
	var product = get_url_param('product');
	newSubmit.setAttribute("onclick",'processDoubles('+"'"+ product +"'"+')');
	newSubmit.setAttribute("disabled",true);
	newTd.appendChild(newSubmit);
}

function removeSubmit()
{
	var partsCount = document.getElementById('partsCount').value;
	removingTr = document.getElementById('submitTr'+partsCount);

	if(partsCount != 1)
	{
		partsCount--;
		document.getElementById('partsCount').value = partsCount;

		removingSubmitFormTable = document.getElementById('doubles_form_table');
		removingSubmitFormTable.removeChild(removingTr);
	}
	if(partsCount == 1)
	{
		document.getElementById('submitPart1').value='Обработать дубли';
	}
}

function processDoubles(product)
{
	var currentPart = document.getElementById('currentPart').value;
	var partsCount = document.getElementById('partsCount').value;

	if(currentPart == 1)
	{
		removingPlusTd = document.getElementById('plusSubmitTd');
		removingPlus = document.getElementById('plusSubmit');
		removingPlusTd.removeChild(removingPlus);

		removingMinusTd = document.getElementById('minusSubmitTd');
		removingMinus = document.getElementById('minusSubmit');
		removingMinusTd.removeChild(removingMinus);
	}

	var query = new JsHttpRequest();
	// Code automatically called on load finishing
	query.onreadystatechange = function() 
	{
		window.document.getElementById('statePart'+currentPart).innerHTML = '<img src=/images/loading.gif alt=Обработка width=25 height=20>';
		window.document.getElementById('statePart'+currentPart).title='Идёт обработка';

		if (query.readyState == 4) 
		{
//			window.document.getElementById('statePart'+currentPart).innerHTML = query.responseJS ;
			if(query.responseJS.state == 'done')
			{
				window.document.getElementById('statePart'+currentPart).innerHTML = '<img src=/images/success.png alt=Удачно>';
				window.document.getElementById('statePart'+currentPart).title='Удачно';
				document.getElementById('submitPart'+currentPart).disabled = true;
				if(currentPart != partsCount)
				{
					document.getElementById('currentPart').value = ++currentPart;
					document.getElementById('submitPart'+currentPart).disabled = false;
				}
				else
				{
					window.document.getElementById('debug').innerHTML = 'Сделано';
				}
			}
			else
			{
				window.document.getElementById('statePart'+currentPart).innerHTML = '<img src=/images/ship.gif alt=Неудача>';
				window.document.getElementById('statePart'+currentPart).title='Неудача';
			}
		}
//		window.document.getElementById('debug').innerHTML = query.responseText;

	} // if there is answer on request

	// Prepare request object (automatically choose GET or POST).
	query.open(null, '/control/import/doubles.php?product='+product, true);
	// Send data to backend.
	query.send( { q: document.getElementById('doubles_form')} );
}

function get_url_param(name)
{ 
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]"); 
	var regexS = "[\\?&]"+name+"=([^&#]*)"; 
	var regex = new RegExp( regexS ); 
	var results = regex.exec( window.location.href ); 
	if( results == null )
	{
		return "";
	}
	else
	{
		return results[1];
	}
}
/**
 * Uploads file and starts import function on success.
 * @param file object from POST form.
 * @return nothing
 * @author Mikhail V. Koval <k.mihail.v@gmail.com>
 */
function doFileUploadLoad(value)
{
	// Create new JsHttpRequest object.
	var req = new JsHttpRequest();
	// Code automatically called on load finishing.
	req.onreadystatechange = function() 
	{
		if (req.readyState == 4) 
		{
			// Write result to page element (_RESULT becomes responseJS).
			if(req.responseJS.state == 'true')
			{
				document.getElementById('debug').innerHTML = req.responseText;
				importCsv();
			}
			if(req.responseJS.state == 'false')
			{
				document.getElementById('debug').innerHTML = req.responseText;
				req.abort();
				return false;
			}
		}
		document.getElementById('debug').innerHTML = req.responseText;
	}
	var product = get_url_param('product');
	// Prepare request object (automatically choose GET or POST).
	req.open(null, '/control/import/import.php?product=' + product, true);
	// Send data to backend.
	req.send( { file: value } );
}
/**
 * Send the signal to import csv file and recursive call of itself until the arrival the signal from the server that importing is completed.
 * @param nothing
 * @return nothing
 * @author Mikhail V. Koval <k.mihail.v@gmail.com>
 */
function importCsv()
{
	// Create new JsHttpRequest object.
	var req = new JsHttpRequest();
	// Code automatically called on load finishing.
	req.onreadystatechange = function() 
	{
		if (req.readyState == 4) 
		{
			// Write result to page element (_RESULT becomes responseJS).
			if(req.responseJS.state == 'true')
			{
				document.getElementById('debug').innerHTML = req.responseText;
				importCsv();
			}
			if(req.responseJS.state == 'false')
			{
				document.getElementById('debug').innerHTML = req.responseText;
				req.abort();
				return false;
			}
			if(req.responseJS.state == 'completed')
			{
				document.getElementById('debug').innerHTML = req.responseText;
				req.abort();
				return true;
			}
		}
		document.getElementById('debug').innerHTML = req.responseText;
	}
	var product = get_url_param('product');
	// Prepare request object (automatically choose GET or POST).
	req.open(null, '/control/import/import.php?product=' + product, true);
	// Send data to backend.
	req.send( { partsCount: document.getElementById('partsCount').value } );
}

// main js
$(document).ready(function () {
	$('.btn-burger').click(function () {
		$('.sidebar-menu').fadeToggle();
	});

	$('.btn-close__menu').click(function () {
		$('.sidebar-menu').fadeOut();
	});
});
