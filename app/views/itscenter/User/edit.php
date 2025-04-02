<?php $user = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]); ?>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">        
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Редактирование профиля</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Профиль</h5>
					</div>
					<div class="card-body">
						<form action="user/edit" method="post" data-toggle="validator">
							<div class="box-body">
								<div class="form-group has-feedback mb-3">
									<label for="name">Группа</label>
									<select class="form-control" name="groups">									
											<option value="3" <?php if($user->groups == 3) { ?>selected<?php } ?>>Физическое лицо</option>
											<option value="4" <?php if($user->groups == 4) { ?>selected<?php } ?>>Юридическое лицо</option>
									</select>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Имя</label>
									<input type="text" class="form-control" name="name" id="name" value="<?=$user->name?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="email">Email</label>
									<input type="email" class="form-control" name="email" id="email" value="<?=$user->email?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="email">Телефон</label>
									<input type="text" class="form-control" name="telefon" id="phone-input2" value="<?=$user->telefon?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group mb-3">
									<label for="password">Пароль</label>
									<input type="password" class="form-control" name="password" id="password" placeholder="Введите пароль, если хотите его изменить">
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary">Сохранить</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->