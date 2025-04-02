<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Пользователи</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/user/customers">Список пользователей</a></li>
              <li class="breadcrumb-item active">Редактирование пользователя</li>
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
                <form action="<?=ADMIN;?>/user/edit-customer" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать пользователя <?=h($user->login);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="groups">Группа <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select name="groups" id="groups" class="form-control">
									<option value="">Выберите группу</option>
									<?php foreach($user_groups as $group): ?>
										<option value="<?=$group["id"];?>"<?php if($user->groups == $group["id"]) echo ' selected'; ?>><?=$group["name"];?></option>
									<?php endforeach; ?>
								</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="new_password">Новый пароль</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="new_password" id="new_password" placeholder="Введите пароль, если хотите его изменить">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="confirm_password">Подтвердите пароль</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Введите пароль, если хотите его изменить">
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Имя <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="name" id="name" value="<?=h($user->name);?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="email">Email <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="email" class="form-control" name="email" id="email" value="<?=h($user->email);?>" required>
                            </div>
                        </div>                       
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="telefon">Телефон</label>
							<div class="col-sm-9">
								<input type="text" class="form-control phonez" name="telefon" id="telefon" value="<?=h($user->telefon);?>">
                            </div>
                        </div>
						<input type="hidden" name="role" value="admin">                        
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$user->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->

<!-- Main content -->
<section class="content">
	<div class="row">
          <div class="col-12">
                <form action="<?=ADMIN;?>/user/edit-newsletter" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
			    <div class="card-header card-newsletter p-0">
				<h3 class="card-title p-3">Подписки</h3>
				<div class="card-tools p-3">
					<div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
						<input type="checkbox" data-id="<?=$user->id;?>" class="custom-control-input switch-newsletter" id="customSwitch3" <?php if($user->newsletter == 1) { echo "data-checked=\"1\""; }else{ echo "data-checked=\"0\""; }?> <?php if($user->newsletter == 1) { echo "checked"; }?>>
						<label class="custom-control-label" for="customSwitch3">Вкл/Выкл</label>
					</div>
				</div>
			  </div><!-- /.card-header -->
			  <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="groups">Группа <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select name="unewslet[]" class="form-control unewslet" id="unewslet" multiple <?php if($user->newsletter == 0) { echo "disabled=\"\""; }else{ echo ""; } ?>>
									<?php if(!empty($newsletters)): ?>
										<?php foreach($newsletters as $item): ?>
											<option value="<?=$item['newsletter_id'];?>" selected><?=$item['name'];?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
                        </div>                       
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$user->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->