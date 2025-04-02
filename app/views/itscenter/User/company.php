<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">        
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Компания</li>
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
				<div class="card col-xl-7 float-left" style="margin:0 30px 0 0">
					<div class="card-header">
						<h5 class="mb-0 h6">Компания</h5>
					</div>
					<div class="card-body">
						<form action="user/company" method="post" data-toggle="validator">
							<div class="box-body">
								<div class="form-group has-feedback mb-3">
									<label for="comp_name">Название компании</label>
									<input type="text" class="form-control" name="comp_name" id="comp_name" value="<?=$company->comp_name?>" placeholder="Общество с ограниченной ответственностью Компания" required>
									<input type="hidden" class="form-control" name="comp_id" value="<?=$company->id?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="comp_short_name">Краткое название компании</label>
									<input type="text" class="form-control" name="comp_short_name" id="comp_short_name" value="<?=$company->comp_short_name?>" placeholder="ООО Компания">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>								
								<div class="form-group has-feedback mb-3">
									<label for="url_address">Юр. адрес</label>
									<input type="text" class="form-control" name="url_address" id="url_address" value="<?=$company->url_address?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="postal_address">Почтовый адрес</label>
									<input type="text" class="form-control" name="postal_address" id="postal_address" value="<?=$company->postal_address?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="ogrn">ОГРН, ОГРНИП</label>
									<input type="text" class="form-control" name="ogrn" id="ogrn" value="<?=$company->ogrn?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group mb-3">
									<label for="inn">ИНН</label>
									<input type="text" class="form-control" name="inn" id="inn" value="<?=$company->inn?>" required>
								</div>                        
								<div class="form-group has-feedback mb-3">
									<label for="kpp">КПП</label>
									<input type="text" class="form-control" name="kpp" id="kpp" value="<?=$company->kpp?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="bik">БИК</label>
									<input type="text" class="form-control" name="bik" id="bik" value="<?=$company->bik?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="raschet">Расч. счёт</label>
									<input type="text" class="form-control" name="raschet" id="raschet" value="<?=$company->raschet?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="korschet">Кор. счёт</label>
									<input type="text" class="form-control" name="korschet" id="korschet" value="<?=$company->korschet?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="bank">Наименование банка</label>
									<input type="text" class="form-control" name="bank" id="bank" value="<?=$company->bank?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="dir_name">Генеральный директор</label>
									<input type="text" class="form-control" name="dir_name" id="dir_name" value="<?=$company->dir_name?>">
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Система налогообложения</label>
									<select class="form-control" name="nds">									
											<option value="1" <?php if($company->nds == 1) { ?>selected<?php } ?>>с НДС</option>
											<option value="2" <?php if($company->nds == 2) { ?>selected<?php } ?>>без НДС</option>
									</select>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Условия поставки</label>
									<select class="form-control" name="dogovor">									
											<option value="1" <?php if($company->dogovor == 1) { ?>selected<?php } ?>>Договор</option>
											<option value="2" <?php if($company->dogovor == 2) { ?>selected<?php } ?>>Счёт-договор</option>
									</select>
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary">Сохранить</button>
							</div>
						</form>
					</div>
				</div>
				<div class="card col-xl-4 float-left">
					<div class="card-header">
						<h5 class="mb-0 h6">Тип</h5>
					</div>
					<div class="card-body">							
						<div class="box-body">
							<div class="form-group has-feedback mb-3">
								<label for="url_address">Тип взаимодействия</label>
								<select class="form-control" name="nds" disabled="">									
										<option value="1" <?php if($company->tip == 1) { ?>selected<?php } ?>>Розничная торговля</option>
										<option value="2" <?php if($company->tip == 2) { ?>selected<?php } ?>>Оптовая торговля</option>
								</select>
							</div>
							<div class="form-group has-feedback mb-3">
								<label for="url_address">Категории оптовых цены</label>
								<?php foreach($category as $item) { 
									$categoryopt = \R::getRow("SELECT * FROM category, company_typeprice WHERE category.id = company_typeprice.category_id AND company_typeprice.company_id = ? AND company_typeprice.category_id = ?", [$company->id, $item["id"]]);
								?>
									<div class="form-check">
										<input class="form-check-input newsletter_checked" type="checkbox" disabled="" <?php if($categoryopt) { echo "checked=\"\""; } ?>>
										<label class="form-check-label"><?=$item["name"]?></label>
									</div>
								<?php } ?>
							</div>							
						</div>										
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->