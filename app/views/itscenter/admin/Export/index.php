<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Экспорт</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Экспорт</li>
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
			<form method="post" action="<?=ADMIN;?>/export" role="form" data-toggle="validator">				
				<div class="card">
				  <div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Экспорт</h3>
				  </div><!-- /.card-header -->
				  <div class="card-body">
						<div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="format">Формат файла <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<select class="form-control" name="format">
										<option value="xls_price_roznica">XLS (Прайс розница)</option>
										<option value="xml_price_roznica">YML/XML (Прайс розница)</option>
									</select>
								</div>
							</div>                        
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="article">Артикул товара</label>
								<div class="col-sm-9">
									<input class="form-control" type="text" name="article" placeholder="Укажите список артикулов товара для экспорта" />
								</div>
							</div>						
						</div>				
					</div><!-- /.card-body -->			  
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary">Экспорт</button>
				</div>
			</form>
			<?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
        </div>
    </div>
</section>
<!-- /.content -->