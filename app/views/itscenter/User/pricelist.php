<script>
function Selected(a) {
        var label = a.value;
        if (label==1) {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        } else if (label==2) {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='block';  
			document.getElementById("Block3").style.display='none';			
        } else if (label==3) {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='block';        
		} else if (label==4) {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='block';
			document.getElementById("Block3").style.display='none';
        }
		else {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        }
         
}
</script>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Прайс-лист</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Прайс-лист</h5>
					</div>
					<div class="card-body">
						<form action="user/pricelist" method="post" data-toggle="validator">
							<div class="box-body">
								<div class="form-group has-feedback mb-3">
									<label for="name">Формат</label>
									<select id="format" class="form-control" name="format">
										<option value= "" selected="selected">Выберите формат</option>
										<option value= "1">PDF</option>
									</select>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Вывод данных</label>
									<select id="actSelect" class="form-control" name="actSelect" aria-required="true" onChange="Selected(this)">
										<option value="" selected="selected">Выберите что выгружать</option>
										<option value="5">Все товары</option>
										<option value="1">Определённую категорию</option>
										<option value="2">По производителю</option>
										<option value="4">Категория и производитель</option>
										<option value="3">Артикул товара</option>									
									</select>
								</div>						
								<div id="Block1" style="display: none;" class="form-group has-feedback mb-3">
									<label for="article">Категория товаров</label>								
									<select class="form-control" name="category_id">
										<option value="" selected="selected">Выберите категорию</option>
										<option value="1">Индустриальные шины</option>
										<option value="2">Шины для квадроциклов</option>
										<option value="25">Камеры, ободные ленты, уплотнительные кольца</option>
										<option value="3">Фильтры</option>
										<option value="4">Диски</option>										
									</select>								                                       
								</div>
								<div id="Block2" style="display: none;" class="form-group has-feedback mb-3">
									<label for="brand_id">Производитель</label>								
									<select id="brand_id" class="form-control" name="brand_id">
										<option value="" selected="selected">Выберите производителя</option>
										<option value="1">EKKA</option>
										<option value="2">CST</option>
										<option value="3">SUPERGUIDER</option>
										<option value="4">Forerunner</option>
										<option value="5">SUN.F</option>										
									</select>							                                       
								</div>							
								<div id="Block3" style="display: none;" class="form-group has-feedback mb-3">
									<label for="article">Артикул товара</label>								
									<input class="form-control" type="text" name="article" placeholder="Артикул товара">							                                       
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary">Создать выгрузку товаров</button>
							</div>
						</form>
						<?php if($product): ?>
							<div class="table-responsive">
								<button class="btn-none" id="btnpdf" type="submit"><i class="fad fa-file-pdf"></i> Прайс-лист PDF от <?php echo \ishop\App::contdate(date("Y-m-d")); ?></button>
							</div>
						<?php else: ?>
							<p class="text-danger">Прайс-лист пока не сформирован.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>				
	</div>
</section>

<!--product-end-->
<?php 
function urlimagesbase64($path) {
	$type = pathinfo($path, PATHINFO_EXTENSION);
	$data = file_get_contents($path);
	$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
	return $base64;
}
$logo = urlimagesbase64('images/Logo_round.jpg');
$logos = urlimagesbase64('images/logo-2.png');
$images = urlimagesbase64('images/product/baseimg/'.$product->img.'');
?>
<script>
document.getElementById('btnpdf').onclick = function() {
var docDefinition = {
	info: {
		title:'<?=$product->name;?>',
		author:'ИТС-Центр',
		subject:'Товары',
		keywords:'Шины, диски, фильтры, на спецтехнику'
	},
	
	pageSize:'A4',
	pageOrientation:'portrait',
	pageMargins:[30,30,30,30],
	content:[		
        {
			columns: [
				{
						image: "<?=$logo?>",
						width: 80
				},
				[
					{
						text:'Общество с ограниченной ответственностью «ИТС-Центр»',
						fontSize:14,
						alignment: 'center',
						margin:[0, 0, 0, 10]
					},
					{
						text:'142117, Московская область, г. Подольск, деревня Коледино, ул. Троицкая, д.1Г, стр.1, помещение В-348/49,\nтел./факс +7 (495) 424-98-90, e-mail: info@its50.ru, ИНН/КПП 5036103305/503601001, р/с 40702810901080002314\n в филиале «Центральный Банк ВТБ (ПАО), корр/с 30101810145250000411, БИК 044525411',
						alignment: 'center',
						fontSize:8,
						margin:[0, 0, 0, 15]
					}
				]			
				 
			]
			
		},
		{
			table: {
				widths:['*'],				
				body: [
					[
						{
							border: [false,'#00ffff', false, '#00ffff'],
							text:'',							
						}
					]
				]
			}
		},
		{
			columns: [
				{
					text:'Прайс-лист от <?php echo \ishop\App::contdate(date("Y-m-d")); ?>',
					fontSize:16,
					alignment: 'center',
					margin:[0, 20, 0, 20],
					bold: true
				}
			]
		},		
		{					
			layout: 'lightHorizontalLines', // optional
			<?php if($company["tip"] == '2') { ?>
			table: {
				
				widths: [ 40, 70, 30, 200, 40, 30, 40 ],
				margin: [0,50,0,30],
				body: [
					[ 
						{ text: 'Артикул', fontSize: 8, style: 'tableHeader' },
						{ text: 'Производитель', fontSize: 8, style: 'tableHeader' },
						{ text: 'Модель', fontSize: 8, style: 'tableHeader' },
						{ text: 'Наименование', fontSize: 8, style: 'tableHeader' },
						{ text: 'Наличие', fontSize: 8, style: 'tableHeader' },
						{ text: 'Опт', fontSize: 8, style: 'tableHeader' },
						{ text: 'Розница', fontSize: 8, style: 'tableHeader' }						
					],
					<?php foreach($product as $prod) { 
						$ucompany = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $prod["category_id"]]);
					?>
					[ 
						{ text: '<?=$prod["article"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["vendor"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["model"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["name"]?>', link: '<?=PATH?>/product/<?=$prod["alias"]?>', fontSize: 8, style: 'tableHeader', decoration: 'underline' },
						{ text: '<?=$prod["quantity"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?php if($ucompany["tip"] !="2" ) { }else{ ?>
							<?php if($ucompany["znachenie"] =="" ) { ?>
								<?=$prod["opt_price"]?>
							<?php }else{ ?>
								<?php $price_nds = round($prod["price"] - ($prod["price"]/1.2), 0) * 6; $price_opt = $price_nds - (($price_nds/100) * $ucompany["znachenie"]); echo $opt = round($price_opt / 6) * 6; ?>
							<?php } ?><?php } ?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["price"]?>', fontSize: 8, style: 'tableHeader' }	
					],
					<?php } ?>
				]
			}
			<?php }
				if($company["tip"] == '1' or $company["tip"] == '') {
			?>
			table: {
				
				widths: [ 40, 70, 30, 230, 40, 40 ],
				margin: [0,50,0,30],
				body: [
					[ 
						{ text: 'Артикул', fontSize: 8, style: 'tableHeader' },
						{ text: 'Производитель', fontSize: 8, style: 'tableHeader' },
						{ text: 'Модель', fontSize: 8, style: 'tableHeader' },
						{ text: 'Наименование', fontSize: 8, style: 'tableHeader' },
						{ text: 'Наличие', fontSize: 8, style: 'tableHeader' },
						{ text: 'Розница', fontSize: 8, style: 'tableHeader' }						
					],
					<?php foreach($product as $prod) { ?>
					[ 
						{ text: '<?=$prod["article"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["vendor"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["model"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["name"]?>', link: '<?=PATH?>/<?=$prod["alias"]?>', fontSize: 8, style: 'tableHeader', decoration: 'underline' },
						{ text: '<?=$prod["quantity"]?>', fontSize: 8, style: 'tableHeader' },
						{ text: '<?=$prod["price"]?>', fontSize: 8, style: 'tableHeader' }	
					],
					<?php } ?>
				]
			}
			<?php } ?>
		},
		{
			columns: [
				{					
					text: '',
					margin: [0,50,0,30],
					fontSize:8
				}
			]				
		}
		
		
	],
	footer:[
		{				
			columns: [
				{
						image: "<?=$logos?>",
						width: 110,
						margin: [30,0]					
				},
				[
					{
						text:'Телефон: +7 (495) 424-98-90\nWhatsApp: +7 (916) 562-52-79',
						fontSize:10,
						alignment: 'left',
						margin:[90, 0, 0, 10],
						width: 280						
					}
					
				],
				[
					{
						text:'Email: info@its-center.ru\nСайт: its-center.ru',
						fontSize:10,
						alignment: 'left',
						margin:[100, 0, 0, 10],
						width: 250
					}
				]				 
			]
		}
    
	],
	styles: {
		footer: {			
			margin:[30, 0, 30, 0],
			background: '#cccccc'
		}
	}
};

var win = window.open('', '_blank')
pdfMake.createPdf(docDefinition).open({}, win);
}
</script>