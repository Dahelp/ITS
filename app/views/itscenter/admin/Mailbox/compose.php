<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Написать письмо</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Написать письмо</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-2">
			<?php new \app\widgets\mailbox\Mailbox('mailbox_tpl.php'); ?>
		</div>
		<div class="col-md-10">
			<form action="<?=ADMIN?>/mailbox/compose" method="post" enctype="multipart/form-data">
            <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title">Новое письмо</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="form-group">
					<input class="form-control" name="email" placeholder="Кому:" value="" />					
                </div>
                <div class="form-group">					
					<input class="form-control" name="subject" placeholder="Тема:" value="" />					
                </div>
                <div class="form-group">
                    <textarea class="form-control" name="content" id="editor1" cols="80" rows="10"></textarea>
				</div>
                <div class="form-group">
                  <div class="btn btn-default btn-file">
                    <i class="fas fa-paperclip"></i> Загрузка файла
                    <input type="file" name="attachment_file">
                  </div>
                  <p class="help-block">Максимальный размер 2Мб</p>
                </div>
              </div>
              <!-- /.card-body -->
              <div class="card-footer">
                <div class="float-right">                  
                  <button type="submit" class="btn btn-primary"><i class="far fa-envelope"></i> Отправить</button>
                </div>                
              </div>
              <!-- /.card-footer -->
            </div>                                   
            </form>
        </div>
    </div>
</section>
<!-- /.content -->