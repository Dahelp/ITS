<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Импорт</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Импорт товаров</li>
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
		    <form action="<?=ADMIN;?>/import" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Импорт товаров</h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="format">Формат файла</label>
							<div class="col-sm-9">
							<select name="format" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите формат</option>
								<option value= "xml">XML</option>               			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="category_id">Родительская категория</label>
                            <div class="col-sm-9">
							<?php new \app\widgets\menu\Menu([
                                'tpl' => WWW . '/menu/select.php',
                                'container' => 'select',
                                'cache' => 0,
                                'cacheKey' => 'admin_select',
                                'class' => 'form-control',
                                'attrs' => [
                                    'name' => 'category_id',
                                    'id' => 'category_id',
                                ],
                                'prepend' => '<option>Выберите категорию</option>',
                            ]) ?>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_file">URL-файл</label>
								<div class="col-sm-9">
									<input type="text" name="url_file" class="form-control" id="url_file" placeholder="Укажите URL путь к файлу">											
								</div>                                        
                         </div>					          
					</div> 				
              </div><!-- /.card-body -->			  
            </div>
			<div class="box-footer">
                <button type="submit" class="btn btn-primary btn_save">Импортировать</button>
            </div>
            <!-- ./card -->
			</form>
          
          <!-- /.col -->
        </div>
        <!-- /.row -->

	</div>	
</section>
<!-- /.content -->
