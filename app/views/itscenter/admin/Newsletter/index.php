<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Рассылка</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Новостная рассылка</li>
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
		    <form action="<?=ADMIN;?>/newsletter" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card nwsl">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Новостная рассылка</h3>					
				</div><!-- /.card-header -->
				<div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="ugroups">Группа пользователей</label>							
							<div class="col-sm-9">								
								<select name="ugroups[]" class="form-control ugroups" id="ugroups"></select>								
							</div>                
						</div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="unewslet">Группа подписок</label>							
							<div class="col-sm-9">								
								<select name="unewslet[]" class="form-control unewslet" id="unewslet"></select>								
							</div>                
						</div>
						<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="subject">Тема рассылки</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="subject" id="subject" value="<?= isset($_SESSION['form_data']['subject']) ? $_SESSION['form_data']['subject'] : '' ?>">
								</div>
							</div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Текст рассылки</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
                            </div>
                        </div>												          
					</div><!-- /.box-body -->				
				</div><!-- /.card-body -->			  
            </div>			
			<div class="box-footer">
                <button type="submit" class="btn btn-primary btn_save">Отправить</button>
            </div>
            <!-- ./card -->
			</form>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->		
</section>