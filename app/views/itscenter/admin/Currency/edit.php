<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Валюты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/currency">Список валют</a></li>
              <li class="breadcrumb-item active">Редактирование валюты</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
	<!-- Main content -->
<section class="content">
	<div class="row">
          <div class="col-12">
                <form action="<?=ADMIN;?>/currency/edit" method="post" data-toggle="validator">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Редактирование валюты <?= $currency->title ?></h3>
						</div><!-- /.card-header -->
						<div class="card-body">
						<div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="title">Наименование валюты</label>
								<div class="col-sm-9">
									<input type="text" name="title" class="form-control" id="title" placeholder="Наименование валюты" required value="<?= h($currency->title) ?>">
								</div>                                        
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="code">Код валюты</label>
								<div class="col-sm-9">
									<input type="text" name="code" class="form-control" id="code" placeholder="Код валюты" required value="<?= h($currency->code) ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="symbol_left">Символ слева</label>
								<div class="col-sm-9">
									<input type="text" name="symbol_left" class="form-control" id="symbol_left" placeholder="Символ слева" value="<?= h($currency->symbol_left) ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="symbol_right">Символ справа</label>
								<div class="col-sm-9">
									<input type="text" name="symbol_right" class="form-control" id="symbol_right" placeholder="Символ справа" value="<?= h($currency->symbol_right) ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="value">Значение</label>
								<div class="col-sm-9">
									<input type="text" name="value" class="form-control" id="value" placeholder="Значение" required data-error="Допускаются цифры и десятичная точка" pattern="^[0-9.]{1,}$" value="<?= $currency->value ?>">								
									<div class="help-block with-errors"></div>
								</div>
							</div>                        
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="base">Базовая валюта</label>
								<div class="col-sm-9">
									<div class="custom-control custom-checkbox">
										<input class="custom-control-input" type="checkbox" id="customCheckbox1" name="base" <?=$currency->base ? ' checked' : null;?>>
										<label style="font-weight:400" for="customCheckbox1" class="custom-control-label"></label>
									</div>
								</div>
								</div>
							</div>
							<div class="box-footer">
								<input type="hidden" name="id" value="<?= $currency->id ?>">
								<button type="submit" class="btn btn-success">Сохранить</button>
							</div>
						</div>
					</div>
				</div>
                </form>
        </div>
    </div>

</section>
<!-- /.content -->
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>