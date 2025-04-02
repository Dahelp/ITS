<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Клиенты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/user">Список клиентов</a></li>
              <li class="breadcrumb-item active">Новый клиент</li>
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
            <form method="post" action="<?=ADMIN;?>/user/add" role="form" data-toggle="validator">				
				<div class="card">
					<div class="card-header d-flex p-0">
						<h3 class="card-title p-3">Добавить клиента</h3>
					</div><!-- /.card-header -->
					<div class="card-body">
						<div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="groups">Группа <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<select class="form-control" name="groups">
										<?php foreach($user_groups as $group): ?>
											<option value="<?=$group["id"];?>"><?=$group["name"];?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="password">Пароль <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<input type="password" class="form-control" name="password" id="password" data-minlength="6" data-error="Пароль должен включать не менее 6 символов" required>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="name">Имя <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="name" id="name" value="<?= isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : '' ?>" required>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="email">Email <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<input type="email" class="form-control" name="email" id="email" value="<?= isset($_SESSION['form_data']['email']) ? $_SESSION['form_data']['email'] : '' ?>" required>
								</div>
							</div>
					
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="telefon">Телефон</label>
								<div class="col-sm-9">
									<input type="text" class="form-control phonez" name="telefon" id="telefon" value="<?= isset($_SESSION['form_data']['telefon']) ? $_SESSION['form_data']['telefon'] : '' ?>">
								</div>
							</div>
							<input type="hidden" name="role" value="user">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="admin_id">Ответственное лицо</label>
								<div class="col-sm-9">
									<select name="admin_id" class="form-control useradmin" id="admin_id" data-placeholder="Выберите ответственное лицо"></select>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="newsletter">Подписка на рассылку</label>
								<div class="col-sm-9">
									<select class="form-control" id="newsletter" name="newsletter" onclick="vidNewsletter(this)">
										<option value="0">Отключено</option>
										<option value="1">Включено</option>
									</select>
								</div>
							</div>
							<div id="vid_newsletter" style="display:none">
								<div class="form-group row">
									<label class="col-sm-3 col-form-label" for="groups">Группа <span class="text-danger">*</span></label>
									<div class="col-sm-9">
										<select name="unewslet[]" class="form-control unewslet" id="unewslet" multiple>
											<?php if(!empty($newsletters)): ?>
												<?php foreach($newsletters as $item): ?>
													<option value="<?=$item['newsletter_id'];?>" selected><?=$item['name'];?></option>
												<?php endforeach; ?>
											<?php endif; ?>
										</select>
									</div>
								</div>
							</div>
						</div>				
					</div><!-- /.card-body -->			  
				</div>
				<div class="box-footer">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
            <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
	function vidNewsletter(el) {
		var u = el.options[el.selectedIndex].value;    
		document.getElementById("vid_newsletter").style.display = (u>0)? "block":"none";																
	}
</script>