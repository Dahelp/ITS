<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Отзывы</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/review">Список отзывов</a></li>
              <li class="breadcrumb-item active">Добавить отзыв</li>
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
			<form action="<?=ADMIN;?>/review/add" method="post" data-toggle="validator">
			<!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Добавить отзыв</h3>
				</div><!-- /.card-header -->
				<div class="card-body">
					<div class="box-body">
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="product_id">Товар</label>
							<div class="col-sm-9">
								<select name="product_id[]" class="form-control select2" id="related" data-placeholder="Выберите товары"></select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="uname">Дата публикации</label>
							<div class="col-sm-9 input-group date" id="reservationdatetime" data-target-input="nearest">
								<input type="text" name="date_post" class="form-control datetimepicker-input" data-target="#reservationdatetime">
								<div class="input-group-append" data-target="#reservationdatetime" data-toggle="datetimepicker">
									<div class="input-group-text"><i class="fa fa-calendar"></i></div>
								</div>
							</div>                                        
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="uname">Имя клиента</label>
								<div class="col-sm-9">
									<input type="text" name="uname" class="form-control" id="uname" placeholder="Имя клиента" required>                                
								</div>                                        
						</div>						
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="content">Отзыв</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
							</div>
						</div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="point">Оценка</label>
							<div class="col-sm-9">
							<select name="point" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Оценка отзыва</option>
								<option value= "5">5</option>
                    			<option value= "4">4</option>
                    			<option value= "3">3</option>
								<option value= "2">2</option>
                    			<option value= "1">1</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
                 			</select>
							</div>
                        </div>						
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img_gallery">Галерея фото отзыва</label>
							<div class="col-sm-9">
								<div id="multi" class="btn btn-success" data-url="review/add-image" data-name="multi" data-razdel="review">Выбрать файл</div>
								
								<div class="multi"></div>								
								<div class="overlay">
									<i class="fa fa-refresh fa-spin"></i>
								</div>
							</div>
						</div>
					</div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-success">Добавить</button>
            </div>
            </form>
            <!-- ./card -->
			
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->