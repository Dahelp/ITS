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
              <li class="breadcrumb-item active">Добавить валюту</li>
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
                <form action="<?=ADMIN;?>/currency/add" method="post" data-toggle="validator">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Добавить валюту</h3>
						</div><!-- /.card-header -->
						<div class="card-body">
						<div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="title">Наименование валюты</label>
								<div class="col-sm-9">
									<input type="text" name="title" class="form-control" id="title" placeholder="Наименование валюты" required>
								</div>                                        
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="code">Код валюты</label>
								<div class="col-sm-9">
									<input type="text" name="code" class="form-control" id="code" placeholder="Код валюты" required>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="symbol_left">Символ слева</label>
								<div class="col-sm-9">
									<input type="text" name="symbol_left" class="form-control" id="symbol_left" placeholder="Символ слева">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="symbol_right">Символ справа</label>
								<div class="col-sm-9">
									<input type="text" name="symbol_right" class="form-control" id="symbol_right" placeholder="Символ справа">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="value">Значение</label>
								<div class="col-sm-9">
									<input type="text" name="value" class="form-control" id="value" placeholder="Значение" required data-error="Допускаются цифры и десятичная точка" pattern="^[0-9.]{1,}$">								
									<div class="help-block with-errors"></div>
								</div>
							</div>                        
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="base">Базовая валюта</label>
								<div class="col-sm-9">
									<input type="checkbox" name="base">                                
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-success">Добавить</button>
							</div>
						</div>
					</div>
				</div>
                </form>
        </div>
    </div>
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>
</section>
<!-- /.content -->