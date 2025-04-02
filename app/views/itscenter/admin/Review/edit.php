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
              <li class="breadcrumb-item active">Редактировать отзыв ID:<?=$review->id?></li>
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
			<div class="menu_btn">
				<?php foreach($product as $item): ?>
					<a target="_blank" href="/product/<?=$item["alias"]?>" class="btn btn-success"><i class="fad fa-eye"></i> Просмотр на сайте: <?=$item['name'];?></a>
				<?php endforeach; ?>
            </div>
			<form action="<?=ADMIN;?>/review/edit" method="post" data-toggle="validator">
			<!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Редактировать отзыв</h3>
				</div><!-- /.card-header -->
				<div class="card-body">
					<div class="box-body">
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="product_id">Товар</label>
							<div class="col-sm-9">
								<select name="product_id[]" class="form-control select2" id="related" multiple>
									<?php if(!empty($product)): ?>
                                    <?php foreach($product as $item): ?>
                                        <option value="<?=$item['product_id'];?>" selected><?=$item['name'];?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="uname">Имя клиента</label>
								<div class="col-sm-9">
									<input type="text" name="uname" class="form-control" id="uname" placeholder="Имя клиента" value="<?=$review["uname"]?>" required>                                
								</div>                                        
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="uname">Дата публикации</label>
							<div class="col-sm-9 input-group date" id="reservationdatetime" data-target-input="nearest">
								<input type="text" name="date_post" class="form-control datetimepicker-input" data-target="#reservationdatetime" value="<?=$review->date_post?>">
								<div class="input-group-append" data-target="#reservationdatetime" data-toggle="datetimepicker">
									<div class="input-group-text"><i class="fa fa-calendar"></i></div>
								</div>
							</div>                                        
						</div>						
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="content">Отзыв</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=$review->content?></textarea>
							</div>
						</div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="point">Оценка</label>
							<div class="col-sm-9">
							<select name="point" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Оценка отзыва</option>
								<option value= "5" <?php if($review->point == "5") { echo "selected=\"selected\""; } ?>>5</option>
                    			<option value= "4" <?php if($review->point == "4") { echo "selected=\"selected\""; } ?>>4</option>
                    			<option value= "3" <?php if($review->point == "3") { echo "selected=\"selected\""; } ?>>3</option>
								<option value= "2" <?php if($review->point == "2") { echo "selected=\"selected\""; } ?>>2</option>
                    			<option value= "1" <?php if($review->point == "1") { echo "selected=\"selected\""; } ?>>1</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show" <?php if($review->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value= "hide" <?php if($review->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>                    			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img_gallery">Галерея фото отзыва</label>
							<div class="col-sm-9">
                                        <div id="multi" class="btn btn-success" data-url="review/add-image" data-name="multi" data-razdel="review">Выбрать файл</div>                                        
                                        <div class="multi">
                                            <?php if(!empty($gallery)): ?>
                                                <?php foreach($gallery as $item): ?>
                                                    <img src="/images/review/gallery/<?=$item;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$review->id;?>" data-src="<?=$item;?>" data-razdel="review" class="del-item">
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    
                                    <div class="overlay">
                                        <i class="fa fa-refresh fa-spin"></i>
                                    </div>
                                </div>
                        </div>
					</div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$review->id;?>">
                <button type="submit" class="btn btn-success">Сохранить</button>
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