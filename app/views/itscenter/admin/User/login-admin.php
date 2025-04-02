<div class="login-box">
    <div class="login-logo">
        <a href="/"><b><?=$shop_name?></b></a>
    </div>
    <!-- /.login-logo -->
    <div class="card">
    <div class="card-body login-card-body">
        <p class="login-box-msg">Панель администратора</p>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h4><i class="icon fa fa-ban"></i> Ошибка!</h4>
                <?=$_SESSION['error']; unset($_SESSION['error'])?>
            </div>
        <?php endif; ?>

        <form action="<?=ADMIN;?>/user/login-admin" method="post">
            <div class="form-group has-feedback">
                <input name="email" type="text" class="form-control" placeholder="E-mail">
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input name="password" type="password" class="form-control" placeholder="Пароль">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
			<div class="row">
				<div class="col-8"></div>
				<div class="col-4">
					<button type="submit" class="btn btn-primary btn-block">Вход</button>
				</div>
            </div>		
        </form>
    </div>
	</div>
    <!-- /.login-box-body -->
	<div class="copyright_admin">Copyright © 2010 - <?php $year = date("Y"); echo $year; ?> <a href='http://bypcms.ru'>BYP.CMS</a><br>
		Template AdminLTE by bypshop
	</div>  
</div>
<!-- /.login-box -->